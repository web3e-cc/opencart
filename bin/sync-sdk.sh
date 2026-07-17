#!/usr/bin/env bash
# Vendor the web3e/crypto-gateway-php SDK into the extension so it ships self-contained
# (OpenCart hosts have no Composer). Prefers a Composer install; falls back to a sibling checkout.
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
dest="$root/extension/web3e/system/library/web3e/lib/Web3e/Gateway"

if [ -d "$root/vendor/web3e/crypto-gateway-php/src" ]; then
  src="$root/vendor/web3e/crypto-gateway-php/src"
elif [ -d "$root/../crypto-gateway-php/src" ]; then
  src="$root/../crypto-gateway-php/src"
else
  echo "SDK source not found. Run 'composer install' or clone crypto-gateway-php as a sibling of this repo." >&2
  exit 1
fi

mkdir -p "$dest"
cp "$src"/*.php "$dest"/
echo "Vendored SDK from: $src -> $dest"
ls -1 "$dest"
