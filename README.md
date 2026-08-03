# Web3e Crypto Payments for OpenCart 4.x

Accept USDT and other crypto in **OpenCart 4.x** via the Web3e gateway — hosted checkout + signed IPN.
Built on [`web3e/crypto-gateway-php`](https://github.com/web3e-cc/crypto-gateway-php).

## Which package do I need?

| Your store | Package | Versions |
|---|---|---|
| OpenCart **4.x** | **this one** | `4.x.y` |
| OpenCart **3.0.x** | [`web3e-cc/opencart3`](https://github.com/web3e-cc/opencart3) | `3.x.y` |

**The first number of the plugin version is the OpenCart version it is for.** The two generations use
incompatible extension conventions (namespaces, directory layout, routing, model signatures), so they
ship as separate packages that can never be mixed up by version alone. Inside a line, the second and
third numbers are the usual feature/fix increments — a breaking change bumps the *minor*, because the
major is reserved for the engine.

If the wrong package does get installed, the settings screen refuses to open and tells you which one to
download instead.

## How it works

1. On checkout the storefront controller calls `POST /invoices` and returns the `checkoutUrl`; the
   buyer is redirected there (a stable idempotency key keeps a retry from minting a second invoice).
2. When the payment is credited, Web3e POSTs a **signed** IPN to the `callback` route.
3. The callback verifies the `SM-Webhook-Signature`, then promotes the order to the configured paid status
   (idempotent — skips an order already in that status).

## Layout (OpenCart 4.x)

```
extension/web3e/
  admin/controller|language|view/…/payment/web3e.php     # settings
  catalog/controller|language|view/…/payment/web3e.php   # confirm() + callback()
  system/library/web3e/lib/…                             # bundled SDK
install.json
```

## Build the installable package

```bash
bin/sync-sdk.sh     # vendor the SDK (already committed)
bin/package.sh      # → dist/web3e.ocmod.zip
```

> **The file name is not cosmetic.** OpenCart 4's installer takes the extension code straight from it —
> `$code = basename($filename, '.ocmod.zip')` — and extracts the archive into `extension/<code>/`. Renaming
> the file to `web3e-4.0.0.ocmod.zip` would install the plugin into `extension/web3e-4.0.0/`, and every
> route would 404. Keep it `web3e.ocmod.zip`; the version lives in `install.json`, which is what the
> installer displays. For the same reason the archive holds `admin/ catalog/ system/` at its root, not
> `extension/web3e/…` — the installer already prefixes that path.

## Install

1. Upload `dist/web3e.ocmod.zip` via **Extensions → Installer** (or copy `extension/web3e/` into
   `extension/` in your OpenCart root by hand).
2. **Extensions → Payments → Web3e Crypto Payments → Edit** — fill in the settings.

## Configure

| Field | Value |
|---|---|
| API Base URL | `https://api.web3e.cc` |
| API Key (public id) | `gwk_…` from your Web3e dashboard |
| API Secret | the matching secret |
| Webhook Secret | the IPN signing secret from your dashboard |
| Paid Order Status | e.g. *Processing* / *Complete* |

Copy the shown **IPN Callback URL** into your Web3e dashboard.

> Targets OpenCart **4.x** (namespaced extensions); verified on 4.1.0.3. OpenCart 3.0.x is a separate
> package — see [Which package do I need?](#which-package-do-i-need) above.

## License

MIT — see [LICENSE](LICENSE).
