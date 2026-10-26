#!/usr/bin/env bash
# This repo's CycloneDX SBOM — one definition shared by ci, audit, publish and local runs.
# Usage: bash .github/scripts/sbom.sh [OUT_DIR]   (default sbom-out)
# Needs the cyclonedx/cyclonedx-php-composer 6.2.0 global plugin and npm.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
OUT="${1:-sbom-out}"
case "$OUT" in ''|/) echo "SBOM FAIL refusing OUT_DIR '$OUT'" >&2; exit 1 ;; esac
VERSION="$(bash .github/scripts/check-version.sh | sed -n 's/^OK: version is \([^ ]*\).*/\1/p')"
[ -n "$VERSION" ] || { echo "SBOM FAIL could not read the version" >&2; exit 1; }
rm -rf "$OUT"; mkdir -p "$OUT/gen"
# composer.lock is gitignored: CI resolves it script-free first. package-lock.json (the Node
# ML-KEM bridge) is committed.
[ -f composer.lock ] || { echo "SBOM FAIL composer.lock missing — resolve first" >&2; exit 1; }
composer CycloneDX:make-sbom --spec-version=1.5 --output-format=JSON --omit=dev --output-reproducible --output-file="$OUT/gen/composer.cdx.json"
npm sbom --sbom-format cyclonedx --sbom-type library --omit dev --package-lock-only > "$OUT/gen/npm-bridge.cdx.json"
python3 .github/scripts/sbom.py --name knishio-client-php --version "$VERSION" --type library \
  --base "$OUT/gen/composer.cdx.json" --base "$OUT/gen/npm-bridge.cdx.json" --out "$OUT"
