#!/usr/bin/env bash
# Build the installable .ocmod.zip for OpenCart 4.x.
#
# Two rules come straight from OpenCart's own installer (admin/controller/marketplace/installer.php,
# verified on 4.1.0.3) and neither is negotiable:
#
#   1. `$code = basename($filename, '.ocmod.zip')` — the FILE NAME becomes the extension code, and the
#      files land in `extension/<code>/`. So the archive must be named exactly web3e.ocmod.zip; putting
#      a version in the name would install to extension/web3e-4.0.0/ and 404 every route.
#   2. Entries are extracted to `extension/<code>/<path inside the zip>`. So the zip root holds
#      admin/ catalog/ system/ — NOT extension/web3e/… (that would nest the tree twice).
#
# The version the merchant sees comes from install.json, not from the file name.
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
code="web3e"
src="$root/extension/$code"
out="$root/dist"
zip_file="$out/$code.ocmod.zip"

[ -d "$src" ] || { echo "missing $src" >&2; exit 1; }
command -v zip >/dev/null || { echo "the 'zip' utility is required" >&2; exit 1; }

version="$(grep -o '"version"[[:space:]]*:[[:space:]]*"[^"]*"' "$root/install.json" | head -1 | sed 's/.*"\([^"]*\)"$/\1/')"

rm -rf "$out"
mkdir -p "$out/build"
cp -R "$src"/. "$out/build/"
cp "$root/install.json" "$out/build/install.json"

(cd "$out/build" && zip -qr "$zip_file" . -x '.*' -x '*/.*')
rm -rf "$out/build"

echo "built $zip_file (version $version)"
echo "contents:"
unzip -l "$zip_file" | sed -n '4,12p'
