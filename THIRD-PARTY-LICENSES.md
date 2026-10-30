# Third-Party Licenses

## Streamable SHA-3 for PHP (`src/Libraries/Crypto/SHA3/Sponge.php`)

- **Upstream**: `desktopd/php-sha3-streamable`, vendored from
  https://github.com/evias/PHP-SHA3-Streamable at commit
  `b099bb81050c71eef7f42d46d287086a4949b861`.
- **Copyright**: © 2018 Desktopd Developers
- **License**: GNU Lesser General Public License v3.0 or later (LGPL-3.0+).
  The full license text is available at https://www.gnu.org/licenses/lgpl-3.0.html.
- **Modifications**: re-namespaced to
  `WishKnish\KnishIO\Client\Libraries\Crypto\SHA3`, declared the `$blockSize`
  property for PHP 8.2+ compatibility (previously applied post-install by
  `AfterInstallation::sha3Fix()`). No algorithmic changes; SHAKE-256 output is
  byte-identical to upstream (verified against the cross-SDK canonical vectors).

This file remains under LGPL-3.0+; the rest of this SDK is licensed as
described in `LICENSE`. To satisfy LGPL §4, you may replace
`src/Libraries/Crypto/SHA3/Sponge.php` with a modified version — it is a
self-contained class consumed only through its public API.

## KnishIO-Crypto-Core 0.2.0 (`kcore/<target>/libkcore.so`, `libkcore.dylib`, `kcore.dll`)

- **Upstream**: https://github.com/WishKnish/KnishIO-Crypto-Core, the release packages
  `knishio-crypto-core-0.2.0-<target>.tar.gz`; each bundled library is the package's
  `lib/libkcore.so.0.2.0`, `lib/libkcore.0.2.0.dylib` or `bin/kcore.dll`, byte for byte,
  pinned by sha256 in `scripts/kcore-pins.json` (checked by `scripts/fetch-kcore.sh --check`).
- **License**: GNU General Public License v3.0. The package's `LICENSE` is byte-identical to
  this SDK's `LICENSE`, which therefore covers it.
- **Use**: loaded through PHP FFI by `src/Libraries/Kcore.php` when `ext-ffi` is available;
  the SDK runs without it.

## mlkem-native (compiled into libkcore)

- **Upstream**: https://github.com/pq-code-package/mlkem-native at commit
  `0ba906cb14b1c241476134d7403a811b382ca498`, the copy KnishIO-Crypto-Core 0.2.0 builds from
  (`external/mlkem-native`).
- **License**: at your choice Apache-2.0, ISC or MIT; a fork of the public-domain Kyber reference
  implementation. The full text, including all three licenses, ships as
  `kcore/LICENSE-mlkem-native` (pinned by sha256 in `scripts/kcore-pins.json`).
