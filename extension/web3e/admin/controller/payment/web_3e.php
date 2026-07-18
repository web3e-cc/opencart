<?php
namespace Opencart\Admin\Controller\Extension\Web3e\Payment;

/**
 * Web3e Crypto Payments — admin settings controller (OpenCart 4.x).
 *
 * @package Web3e\OpenCart
 */
class Web3e extends \Opencart\System\Engine\Controller
{
    private string $route = 'extension/web3e/payment/web3e';

    public function index(): void
    {
        $this->load->language($this->route);

        $this->document->setTitle($this->language->get('heading_title'));

        $data['save'] = $this->url->link($this->route . '.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('payment_web3e');

        foreach (['api_base', 'public_id', 'api_secret', 'webhook_secret', 'status', 'order_status_id'] as $field) {
            $key = 'payment_web3e_' . $field;
            $data[$key] = $settings[$key] ?? '';
        }
        if ($data['payment_web3e_api_base'] === '') {
            $data['payment_web3e_api_base'] = 'https://api.web3e.cc';
        }

        // IPN callback URL to paste into the Web3e dashboard.
        $data['callback_url'] = $this->url->link('extension/web3e/payment/web3e.callback', '', true);

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->route, $data));
    }

    public function save(): void
    {
        $this->load->language($this->route);

        $json = [];

        if (!$this->user->hasPermission('modify', $this->route)) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('payment_web3e', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
