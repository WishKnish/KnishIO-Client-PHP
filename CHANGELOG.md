# Changelog

All notable changes to the KnishIO Client PHP SDK are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Releases are published to Packagist (`wishknish/knishio-client-php`) via git tags.

## [0.8.0] - 2026-06-06

Cross-SDK alignment release — brings the PHP SDK to cryptographic + structural
parity with the JavaScript reference and the rest of the 0.8.0 SDK line
(JS/TS/Python/Kotlin). Version bumped `0.6.4` → `0.8.0` to match the ecosystem.

### Added
- **ML-KEM768 (post-quantum) support** on `Wallet` — key generation, encryption,
  and decryption — via a `noble-postquantum` bridge (`bin/noble-mlkem-bridge.js`),
  producing byte-identical keys to the JS SDK.
- Missing GraphQL query and mutation types, for full API coverage.
- Cross-SDK test-vector validation: `PatentVectorValidationTest` (generate_secret,
  continuid_chain, base17, multi-isotope, bigint-carry, WOTS+ two-pass,
  atom_value_format) and `CrossPlatformVectorsTest` (SHAKE256, bundle_hash,
  wallet_generation) against the shared canonical masters.

### Fixed
- **Policy ContinuID signing (F-3):** `addPolicyAtom` now signs the R-atom from the
  established source wallet (not a freshly-created wallet), so policy molecules pass
  ContinuID validation — matching `createRule`.
- UTXO balance debit for V/B isotopes; T-isotope amount handling.
- Value-transfer self-check: transfers now balance to a zero sum.
- R-atom construction aligned with the other SDKs.

### Changed
- `generateKey` normalized for cross-SDK byte parity.
- Atoms no longer auto-inject `pubkey`/`characters`; metadata must be published
  explicitly (matches the JS reference).
- Upgraded `webonyx/graphql-php` to v15; updated dependency requirements.
- Cryptography aligned with the JavaScript reference implementation throughout.

## Earlier releases

See the git tag history (`0.6.4`, `0.4.0`, `0.2.0`, `0.1.x`) on GitHub/Packagist.
