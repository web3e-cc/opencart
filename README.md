# Web3e Crypto Payments for OpenCart

OpenCart payment extension for the **Web3e** crypto gateway (hosted checkout + signed IPN).

> **Scaffold stub.** Follows the same 4-step shape as the WooCommerce reference; built on the
> `web3e/crypto-gateway-php` SDK.

## Target layout (OpenCart 4.x; 3.x mirrors under `upload/`)

```
extension/web3e/
  admin/
    controller/payment/web3e.php      # settings form (API key/secret/webhook secret, status)
    language/en-gb/payment/web3e.php
    view/template/payment/web3e.twig
  catalog/
    controller/payment/web3e.php      # confirm() → Client::createInvoice() → redirect to checkout_url
    controller/payment/web3e.callback.php  # IPN: verify signature → addOrderHistory(paid)
    language/en-gb/payment/web3e.php
  system/library/web3e/               # vendored web3e/crypto-gateway-php (bin/sync-sdk.sh)
install.json
```

## Integration points

- Admin controller — standard OpenCart settings (`setting_model->editSetting`), enable + order-status mapping.
- Catalog `confirm()` — create the invoice with `order_id = $this->session->data['order_id']`, return the
  `checkout_url` to redirect to.
- Callback — read raw body + `Webhook-Id`/`Webhook-Signature`, verify with `WebhookVerifier`, then on
  `credited`/`overpaid` call `model_checkout_order->addHistory()` with the "paid" status (idempotent — skip
  if the order is already in a paid state).

## Notes

- Bundle the SDK into `system/library/web3e/` (OpenCart hosts have no Composer).
- License: MIT.
