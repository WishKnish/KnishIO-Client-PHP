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
