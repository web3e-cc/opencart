<?php
namespace Opencart\Catalog\Controller\Extension\Web3e\Payment;

use Web3e\Gateway\Client;
use Web3e\Gateway\GatewayException;
use Web3e\Gateway\WebhookVerifier;

/**
 * Web3e Crypto Payments — storefront controller (OpenCart 4.x).
 *
 * index()   — renders the "Confirm" button on checkout.
 * confirm() — creates a hosted-checkout invoice and returns the redirect URL.
 * callback()— signed IPN endpoint; settles the order on credit.
 *
 * @package Web3e\OpenCart
 */
class Web3e extends \Opencart\System\Engine\Controller
{
    private function sdk(): void
    {
        require_once DIR_SYSTEM . 'library/web3e/lib/Web3e/Gateway/GatewayException.php';
        require_once DIR_SYSTEM . 'library/web3e/lib/Web3e/Gateway/Signer.php';
        require_once DIR_SYSTEM . 'library/web3e/lib/Web3e/Gateway/Client.php';
        require_once DIR_SYSTEM . 'library/web3e/lib/Web3e/Gateway/WebhookVerifier.php';
    }

    public function index(): string
    {
        $this->load->language('extension/web3e/payment/web3e');

        $data['confirm'] = $this->url->link('extension/web3e/payment/web3e.confirm', '', true);

        return $this->load->view('extension/web3e/payment/web3e', $data);
    }

    public function confirm(): void
    {
        $this->load->language('extension/web3e/payment/web3e');

        $json = [];

        if (!isset($this->session->data['order_id'])) {
            $json['error'] = $this->language->get('error_order');
        }

        if (!$json) {
            $this->sdk();
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if (!$order_info) {
                $json['error'] = $this->language->get('error_order');
            } else {
                $callback_url = $this->url->link('extension/web3e/payment/web3e.callback', '', true);

                try {
                    $client = new Client(
                        (string) $this->config->get('payment_web3e_public_id'),
                        (string) $this->config->get('payment_web3e_api_secret'),
                        (string) ($this->config->get('payment_web3e_api_base') ?: 'https://api.web3e.cc')
                    );
                    $invoice = $client->createInvoice(
                        [
                            'order_id' => (string) $order_info['order_id'],
                            'order_amount' => $this->formatAmount($order_info['total'], $order_info['currency_code'], $order_info['currency_value']),
                            'order_currency' => $order_info['currency_code'],
                            'order_description' => sprintf($this->language->get('text_order'), $order_info['order_id']),
                            'success_url' => $this->url->link('checkout/success', '', true),
                            'cancel_url' => $this->url->link('checkout/checkout', '', true),
                            'callback_url' => $callback_url,
                            'buyer_email' => $order_info['email'],
                        ],
                        'oc-' . $order_info['order_id']
                    );
                } catch (GatewayException $e) {
                    $this->log->write('Web3e: createInvoice failed — ' . $e->getMessage());
                    $json['error'] = $this->language->get('error_gateway');
                    $invoice = [];
                }

                if (!isset($json['error'])) {
                    if (empty($invoice['checkout_url'])) {
                        $json['error'] = $this->language->get('error_gateway');
                    } else {
                        // Move the order to the store's default (pending) status; IPN promotes it to paid.
                        $this->model_checkout_order->addHistory($order_info['order_id'], (int) $this->config->get('config_order_status_id'), '', false);
                        $json['redirect'] = $invoice['checkout_url'];
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function callback(): void
    {
        $this->sdk();

        $raw = file_get_contents('php://input');
        $webhook_id = $_SERVER['HTTP_WEBHOOK_ID'] ?? '';
        $signature = $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '';

        $verifier = new WebhookVerifier((string) $this->config->get('payment_web3e_webhook_secret'));
        if (!$verifier->verify((string) $raw, $webhook_id, $signature)) {
            $this->response->addHeader($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
            $this->response->setOutput('invalid signature');
            return;
        }

        $event = json_decode((string) $raw, true);
        $payment = (isset($event['payment']) && is_array($event['payment'])) ? $event['payment'] : [];
        $order_id = $payment['merchant_order_id'] ?? null;
        $status = $payment['status'] ?? '';

        if ($order_id !== null && in_array($status, ['credited', 'overpaid'], true)) {
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder((int) $order_id);
            // Idempotent: only promote an order that has not already reached the paid status.
            $paid_status = (int) $this->config->get('payment_web3e_order_status_id');
            if ($order_info && $paid_status && (int) $order_info['order_status_id'] !== $paid_status) {
                $this->model_checkout_order->addHistory((int) $order_id, $paid_status, 'Web3e: crypto payment credited (' . ($payment['id'] ?? '') . ')', true);
            }
        }

        $this->response->setOutput('ok');
    }

    /** Present the order total in the order currency, as an 8dp decimal string. */
    private function formatAmount(float $total, string $currency, float $value): string
    {
        $this->load->model('checkout/order');
        $amount = $this->currency->format($total, $currency, $value, false);
        return number_format((float) $amount, 8, '.', '');
    }
}
