#!/usr/bin/env bash
# Writes or checks the bundled libkcore in kcore/<target>/, and the third-party license texts it
# carries in kcore/, against scripts/kcore-pins.json.
#
#   bash scripts/fetch-kcore.sh --write   download each pinned KnishIO-Crypto-Core release package,
#                                         check its sha256, extract the library to kcore/<target>/<name>;
#                                         download each pinned license text to kcore/<name>
#   bash scripts/fetch-kcore.sh --check   the same downloads and sha256 checks, then require the
#                                         committed files to be byte-identical
#
# Prints "FETCH_KCORE ok <target|license>" per file, or "FETCH_KCORE FAIL <target|license> <reason>"
# and exits 1. Needs curl, tar, php and sha256sum or shasum.
set -euo pipefail

mode="${1:-}"
case "$mode" in --write|--check) ;; *) echo "usage: bash scripts/fetch-kcore.sh --write|--check" >&2; exit 2 ;; esac

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
pins="$root/scripts/kcore-pins.json"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

sha256() {
  if command -v sha256sum > /dev/null 2>&1; then sha256sum "$1" | awk '{print $1}'
  else shasum -a 256 "$1" | awk '{print $1}'; fi
}

# One line per target: <target> <url> <sha256> <member> <name>
rows="$(php -r '
  $p = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
  foreach ($p["targets"] as $t => $v) {
    $url = str_replace(["{version}", "{target}"], [$p["version"], $t], $p["url"]);
    echo "$t $url {$v["sha256"]} {$v["member"]} {$v["name"]}\n";
  }' "$pins")"

status=0
while read -r target url want member name; do
  fail() { echo "FETCH_KCORE FAIL $target $1"; status=1; }
  tgz="$tmp/$target.tar.gz"
  if ! curl -fsSL --retry 3 -o "$tgz" "$url"; then fail "download failed: $url"; continue; fi
  got="$(sha256 "$tgz")"
  if [ "$got" != "$want" ]; then fail "sha256 $got, pinned $want"; continue; fi
  top="$(basename "$url" .tar.gz)"
  if ! tar -xzf "$tgz" -C "$tmp" "$top/$member"; then fail "no member $member"; continue; fi
  dest="$root/kcore/$target/$name"
  if [ "$mode" = --write ]; then
    mkdir -p "$(dirname "$dest")"
    cp "$tmp/$top/$member" "$dest"
    chmod 0644 "$dest"
  elif ! cmp -s "$tmp/$top/$member" "$dest"; then
    fail "kcore/$target/$name is not $member of the pinned package"; continue
  fi
  echo "FETCH_KCORE ok $target"
done <<< "$rows"

# One line per license text: <name> <url> <sha256>
licenses="$(php -r '
  $p = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
  foreach ($p["licenses"] as $n => $v) {
    echo "$n {$v["url"]} {$v["sha256"]}\n";
  }' "$pins")"

while read -r name url want; do
  fail() { echo "FETCH_KCORE FAIL $name $1"; status=1; }
  if ! curl -fsSL --retry 3 -o "$tmp/$name" "$url"; then fail "download failed: $url"; continue; fi
  got="$(sha256 "$tmp/$name")"
  if [ "$got" != "$want" ]; then fail "sha256 $got, pinned $want"; continue; fi
  if [ "$mode" = --write ]; then
    mkdir -p "$root/kcore"
    cp "$tmp/$name" "$root/kcore/$name"
    chmod 0644 "$root/kcore/$name"
  elif ! cmp -s "$tmp/$name" "$root/kcore/$name"; then
    fail "kcore/$name is not the pinned file"; continue
  fi
  echo "FETCH_KCORE ok $name"
done <<< "$licenses"
exit "$status"
