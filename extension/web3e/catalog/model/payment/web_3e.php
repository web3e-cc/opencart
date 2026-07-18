<?php
namespace Opencart\Catalog\Model\Extension\Web3e\Payment;

use Opencart\System\Engine\Model;

/**
 * Web3e Crypto Payments — storefront payment-method model (OpenCart 4.x).
 *
 * OpenCart builds the checkout payment list by calling getMethods() on every enabled payment
 * extension's model (see catalog/model/checkout/payment_method.php). Without this file the gateway
 * is installed and configured but never offered at checkout.
 *
 * @package Web3e\OpenCart
 */
class Web3e extends Model
{
    /**
     * @param array<string, mixed> $address
     * @return array<string, mixed>
     */
    public function getMethods(array $address = []): array
    {
        $this->load->language('extension/web3e/payment/web3e');

        // Crypto settlement is independent of the buyer's address, so the method is offered for every
        // cart — physical or fully digital. (Geo-zone gating is intentionally omitted; add it here if
        // the admin form ever grows a payment_web3e_geo_zone_id setting.)
        $option_data['web3e'] = [
            'code' => 'web3e.web3e',
            'name' => $this->language->get('heading_title'),
        ];

        return [
            'code'       => 'web3e',
            'name'       => $this->language->get('heading_title'),
            'option'     => $option_data,
            'sort_order' => $this->config->get('payment_web3e_sort_order'),
        ];
    }
}
