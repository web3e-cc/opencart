# Web3e Crypto Payments for OpenCart

Accept USDT and other crypto in **OpenCart 4.x** via the Web3e gateway — hosted checkout + signed IPN.
Built on [`web3e/crypto-gateway-php`](https://github.com/web3e-cc/crypto-gateway-php).

## How it works

1. On checkout the storefront controller calls `POST /invoices` and returns the `checkout_url`; the
   buyer is redirected there (a stable idempotency key keeps a retry from minting a second invoice).
2. When the payment is credited, Web3e POSTs a **signed** IPN to the `callback` route.
3. The callback verifies the `Webhook-Signature`, then promotes the order to the configured paid status
   (idempotent — skips an order already in that status).

## Layout (OpenCart 4.x)

```
extension/web3e/
  admin/controller|language|view/…/payment/web3e.php     # settings
  catalog/controller|language|view/…/payment/web3e.php   # confirm() + callback()
  system/library/web3e/lib/…                             # bundled SDK
install.json
```

## Install

1. Run `bin/sync-sdk.sh` to vendor the SDK.
2. Zip the `extension/` + `install.json` and upload via **Extensions → Installer**, or copy `extension/`
   into your OpenCart root.
3. **Extensions → Payments → Web3e Crypto Payments → Edit** — fill in the settings.

## Configure

| Field | Value |
|---|---|
| API Base URL | `https://api.web3e.cc` |
| API Key (public id) | `gwk_…` from your Web3e dashboard |
| API Secret | the matching secret |
| Webhook Secret | the IPN signing secret from your dashboard |
| Paid Order Status | e.g. *Processing* / *Complete* |

Copy the shown **IPN Callback URL** into your Web3e dashboard.

> Targets OpenCart **4.x** (namespaced extensions). 3.x uses a different `upload/` layout and would need
> a port. Verify on a live OpenCart install before production (see the repo's integration checklist).

## License

MIT — see [LICENSE](LICENSE).
