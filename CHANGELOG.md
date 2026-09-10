# Changelog

All notable changes to the KnishIO Client PHP SDK are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Releases are published to Packagist (`wishknish/knishio-client-php`) via git tags.
Conventions for tags, commits, and these entries: `docs/SDK-RELEASE-CONVENTIONS.md`
in the KnishIOClientSDK monorepo.

Entries above `0.8.0` were backfilled on 2026-07-27 from the repository's own tag
and commit history rather than written at release time; where the history does
not substantiate a detail, the entry says so instead of guessing.

## [1.0.0] — 2026-09-10

### Added

- A wallet now decrypts records addressed to its own ML-KEM-768 identity even when it is
  configured at ML-KEM-1024, by deriving that identity on demand from the same wallet key.
  The 64-byte ML-KEM seed is parameter-set-independent, so `Wallet::decryptMessageML()` and
  `Wallet::decryptBinaryML()` now dispatch on the decoded ciphertext's length and decapsulate
  with the matching identity; the derived private key lives only for that call and is never
  cached on the wallet. `Wallet::decryptMyMessageML()` correspondingly tries both identities'
  hash shares when looking up the `CipherHash` map, so an envelope a pre-bump peer addressed to
  `hashShare(our_768_pubkey)` is found. Reading pre-bump ML-KEM-768 records therefore needs no
  configuration change.
- `Wallet::mlKemParameterSetFromPubkey()` recovers a parameter set from a serialized public
  key's raw length (1568 bytes → ML-KEM-1024, 1184 → ML-KEM-768; FIPS 203 makes them disjoint).

### Changed

- **Breaking:** ML-KEM-1024 is the default post-quantum parameter set. `Wallet::__construct()`
  and `Wallet::create()` take an `$mlKemParameterSet` argument (`1024` default, `768`
  step-back), and `KnishIOClient` threads it through; an unsupported value throws
  `CryptoException`.
- Encapsulation is strict and stays strict: encrypting to a recipient key whose length does not
  match this wallet's configured parameter set throws `CryptoException` rather than silently
  downgrading. The advertised public key remains single-set — the configured set's key, in
  `Wallet::$pubkey`, in the signed U-atom `walletPubkey` meta, and at auth. Inbound is
  permissive, outbound is strict, deliberately: reading a 768 record you own downgrades
  nothing, but encapsulating at 768 would.

### Removed

- `Libraries\OpenSSLMLKEM` — dead, uncalled code, deleted rather than ported to two parameter
  sets.
- The `*ML768` method family. `encryptMessageML768`/`decryptMessageML768`,
  `encryptMessageML768Multi`/`decryptMessageML768Multi`, `encryptStringML768`/
  `decryptMyMessageML768` and `decryptBinaryML768` are now `encryptMessageML`/
  `decryptMessageML`, `encryptMessageMLMulti`/`decryptMessageMLMulti`, `encryptStringML`/
  `decryptMyMessageML` and `decryptBinaryML`. No aliases are retained: the methods are
  parameter-set-agnostic, so a `768` in their names would have been wrong from 1.0.0 onward.

### Fixed

- The auth-token session snapshot now records the wallet's ML-KEM parameter set
  (`wallet.mlKemParameterSet`) and `AuthToken::restore()` honours it, resolving in three tiers:
  an explicit snapshot field, else the stored validator key's decoded length, else ML-KEM-768.
  A session persisted by an 0.9.x build restores as ML-KEM-768 instead of silently becoming
  ML-KEM-1024 with a public key the validator never recorded for that token — which also made
  outbound encryption throw, because the stored validator key is 1184 bytes.
- The strict encapsulation length guard now applies at **every** encapsulation entry point.
  `Wallet::encryptBinaryML()` called `PostQuantumCrypto::encapsulate()` directly with no length
  check, so a wrong-length recipient key reached the Noble bridge and surfaced as a wrapped
  bridge error instead of the actionable parameter-set message. Both entry points now share one
  guard, and its message is unchanged.
- Doc comments and `{@see}` links that named methods removed in the rename
  (`encryptMessageML768()`, `decryptMessageML768()`, `encryptMessageML768Multi()`,
  `encryptStringML768`, `decryptMyMessageML768`) now point at the surviving names.

### Notes

- `0.9.4`–`0.9.9` were never published. The ML-KEM-1024 cutover is a breaking API change and
  takes the 1.0.0 line, which also states that this SDK's client surface is stable.
- Nothing on the wire changed and no hashed bytes changed. The parameter set is recoverable
  from FIPS 203's disjoint key/ciphertext lengths, so no migration is required — proven by a
  frozen pre-bump ML-KEM-768 auth molecule (`vectors.legacyMlkem768AuthMolecule`) that this
  release validates from a default ML-KEM-1024 build.

## [0.9.3] — 2026-08-05

### Changed

- Test suite modernization: `@dataProvider` annotations migrated to
  `#[DataProvider]` attributes; no-op `curl_close()` and
  `ReflectionMethod::setAccessible()` calls removed.

### Added

- Classical NaCl cross-platform parity vectors asserted in the test suite.

### Changed — cross-SDK gauntlet reporting integrity

- The self-test now publishes cross-validation **coverage**, not just a verdict:
  `crossValidation.{ran,targetsExpected,targetsValidated}` and `runId` sit alongside
  `crossSdkCompatible` in the results file. The boolean alone could not distinguish
  "validated every peer, all passed" from "validated nothing and so found no failures".
- `crossSdkCompatible` now defaults to **false** and must be earned. It was `true`, so every early return out of cross-validation published a pass.
- Cross-validation **fails** instead of reporting "compatible" when the shared results
  directory is missing or holds no peer results. Absence of evidence is not evidence of
  compatibility.
- Round 1 no longer asserts a cross-SDK verdict it cannot have; it records that no
  cross-validation ran.
- A coverage floor is required before a pass: every expected peer must have been validated,
  in addition to no individual check having failed.
- Each peer is now checked for all 7 required molecule types. The validation loop iterates
  the molecule keys that are **present**, so an omitted molecule was indistinguishable from
  a validated one.
- Peer results are matched with `*-results.json`. `str_ends_with($f, '.json')` also
  matched the canonical vector **masters** living in that directory and fed them into the
  peer loop as though they were SDK results.

Contract for these fields: `sdks/canonical-test-keys.json` in the KnishIOClientSDK
monorepo. Audit: `docs/audits/REPORTING-INTEGRITY-2026-08-05.md`.

## [0.9.2] — 2026-07-12

Coordinated dependency-security release across all 8 SDKs. Release record:
`docs/sdk-release-0.9.2-execution-2026-07-12.md` (monorepo).

### Security

- The SHA3 sponge is now **vendored** at `src/Libraries/Crypto/SHA3/`, removing
  the unpinned `desktopd/SHA3` dev-master VCS dependency. Installation is plain
  Packagist resolution with no post-install hook, and the vendored sponge
  reproduces the canonical cross-SDK vector.
- 9 dependency advisories cleared, including floor raises for `guzzle`, `psr7`,
  and `webonyx/graphql-php`.

### Added

- `composer audit` gate in CI.

### Changed

- PHPUnit upgraded to 11.

### Notes

- `0.9.1` was staged in `composer.json` on 2026-06-30 (a clear error when a node
  advertises a non-ML-KEM recipient key) but was never tagged and never published
  to Packagist. That fix ships in `0.9.2`.

## [0.9.0] — 2026-06-29

Coordinated `0.9.0` across all 8 SDKs, marking the post-quantum ML-KEM transport
milestone. Runbook: `docs/sdk-release-audit-2026-06-29.md` (monorepo).

### Added

- **ML-KEM768 CipherHash encrypted transport** (PQ Phase E), migrating the
  transport off classical NaCl.
- Multi-recipient stackable (NFT) transfer builder, plus claim and read fixes.
- `hasBundle()` (JS parity).
- `mlkem768` keygen + decrypt vector and a "decrypt their message" ML-KEM768
  cross-validation; `buffer_withdraw_conservation` regression lock;
  `tokenCreation`, `walletCreation`, and `shadowWalletClaim` cross-SDK parity
  vectors in the self-test.
- The repo's first CI workflow, with a PHPStan static-analysis gate and the
  self-test parity gate.

### Fixed

- The wire payload now emits validator-compatible `MoleculeInput` / `AtomInput`.
- `burnToken` rebuilt as the JS-canonical 3-atom zero-sum molecule.
- PHP 8.4+ implicit-nullable deprecations resolved; PHPStan tightened to 8.5.
- Two drifted test properties corrected, and the live-server tests gated so they
  no longer fail an offline run.

### Removed

- Dead `QueryUserActivity` query, dead `QueryLinkIdentifierMutation` and its
  co-orphaned `ResponseIdentifier`, and the dead `Standard*` framework.

### Changed

- The canonical ML-KEM768 envelope is designated in the docblocks, distinguishing
  it from the classical NaCl path.

### Notes

- Local version `0.8.2` was staged on 2026-06-22 (the multi-recipient stackable
  transfer builder) but was never tagged or published; it reaches consumers here.

## [0.8.1] — 2026-06-15

### Fixed

- The ContinuID I-atom metadata is now populated, completing 6-SDK
  molecular-hash parity.

### Added

- `buffer_deposit_conservation` vector assertion (the PHP SDK is a reference
  anchor for it) and a synced fixture.

### Changed

- README notes that network reads are fresh by construction (no response cache).

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

[Unreleased]: https://github.com/WishKnish/KnishIO-Client-PHP/compare/1.0.0...HEAD
[1.0.0]: https://github.com/WishKnish/KnishIO-Client-PHP/releases/tag/1.0.0
[0.9.2]: https://github.com/WishKnish/KnishIO-Client-PHP/releases/tag/0.9.2
[0.9.0]: https://github.com/WishKnish/KnishIO-Client-PHP/releases/tag/0.9.0
[0.8.1]: https://github.com/WishKnish/KnishIO-Client-PHP/releases/tag/0.8.1
[0.8.0]: https://github.com/WishKnish/KnishIO-Client-PHP/releases/tag/0.8.0
