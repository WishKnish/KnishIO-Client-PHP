<div style="text-align:center">
  <img src="https://raw.githubusercontent.com/WishKnish/KnishIO-Technical-Whitepaper/master/KnishIO-Logo.png" alt="Knish.IO: Post-Blockchain Platform" />
</div>
<div style="text-align:center">info@wishknish.com | https://wishknish.com</div>

# Knish.IO PHP Client SDK

This is the official PHP implementation of the Knish.IO client SDK. Its purpose is to expose class libraries for building and signing Knish.IO Molecules, composing Atoms, generating Wallets, and much more.

## Installation

The SDK can be installed via Composer:

```bash
composer require wishknish/knishio-client-php
```

**Requirements:**
- PHP 8.2 or higher
- Required extensions: `ext-json`, `ext-sodium`, `ext-mbstring`
- Composer for dependency management

After installation, include the autoloader in your project:

```php
<?php
require_once 'vendor/autoload.php';

use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Wallet;
```

## Basic Usage

The purpose of the Knish.IO SDK is to expose various ledger functions to new or existing applications.

There are two ways to take advantage of these functions:

1. The easy way: use the `KnishIOClient` wrapper class

2. The granular way: build `Atom` and `Molecule` instances and broadcast GraphQL messages yourself

This document will explain both ways.

## The Easy Way: KnishIOClient Wrapper

1. Include the wrapper class in your application code:
   ```php
   <?php
   use WishKnish\KnishIO\Client\KnishIOClient;
   ```

2. Instantiate the class with your node URI:
   ```php
   $client = new KnishIOClient('http://localhost:8000/graphql');
   $client->setCellSlug('my-cell-slug');
   ```

3. Request authorization token from the node:
   ```php
   $response = $client->requestAuthToken($secret);
   
   if ($response->success()) {
       // Authentication successful
       $authToken = $client->getAuthToken();
       echo "Authenticated successfully!\n";
   } else {
       throw new Exception('Authentication failed: ' . $response->reason());
   }
   ```

   (**Note:** The `$secret` parameter can be a salted combination of username + password, a biometric hash, an existing user identifier from an external authentication process, for example)

4. Begin using `$client` to trigger commands described below...

### KnishIOClient Methods

- Query metadata for a **Wallet Bundle**. Omit the `$bundleHash` parameter to query your own Wallet Bundle:
  ```php
  $response = $client->queryBundle('c47e20f99df190e418f0cc5ddfa2791e9ccc4eb297cfa21bd317dc0f98313b1d');
  
  if ($response->success()) {
      $bundleData = $response->data();
      print_r($bundleData); // Raw Metadata
  }
  ```

- Query metadata for a **Meta Asset**:

  ```php
  $result = $client->queryMeta(
      metaType: 'Vehicle',
      metaId: null, // Meta ID
      key: 'LicensePlate',
      value: '1H17P',
      latest: true, // Limit meta values to latest per key
      throughAtom: true // Optional, query through Atom (default: true)
  );

  print_r($result); // Raw Metadata
  ```

- Writing new metadata for a **Meta Asset**:

  ```php
  $response = $client->createMeta(
      metaType: 'Pokemon',
      metaId: 'Charizard',
      metadata: [
          'type' => 'fire',
          'weaknesses' => [
              'rock',
              'water',
              'electric'
          ],
          'immunities' => [
              'ground',
          ],
          'hp' => 78,
          'attack' => 84,
      ]
  );

  if ($response->success()) {
      // Do things!
      echo "Metadata created successfully!\n";
  }

  print_r($response->data()); // Raw response
  ```

- Query Wallets associated with a Wallet Bundle:

  ```php
  $wallets = $client->queryWallets(
      bundleHash: 'c47e20f99df190e418f0cc5ddfa2791e9ccc4eb297cfa21bd317dc0f98313b1d',
      tokenSlug: 'FOO' // Optional, filter by token
  );

  print_r($wallets); // Raw response
  ```

- Declaring new **Wallets**:

  (**Note:** If Tokens are sent to undeclared Wallets, **Shadow Wallets** will be used (placeholder
  Wallets that can receive, but cannot send) to store tokens until they are claimed.)

  ```php
  $response = $client->createWallet('FOO'); // Token Slug for the wallet we are declaring

  if ($response->success()) {
      // Do things!
      echo "Wallet created successfully!\n";
  }

  print_r($response->data()); // Raw response
  ```

- Issuing new **Tokens**:

  ```php
  $response = $client->createToken(
      tokenSlug: 'CRZY', // Token slug (ticker symbol)
      amount: 100000000, // Initial amount to issue
      meta: [
          'name' => 'CrazyCoin', // Public name for the token
          'fungibility' => 'fungible', // Fungibility style (fungible / nonfungible / stackable)
          'supply' => 'limited', // Supply style (limited / replenishable)
          'decimals' => 2 // Decimal places
      ],
      units: [], // Optional, for stackable tokens
      batchId: null // Optional, for stackable tokens
  );

  if ($response->success()) {
      // Do things!
      echo "Token created successfully!\n";
  }

  print_r($response->data()); // Raw response
  ```

- Transferring **Tokens** to other users:

  ```php
  $response = $client->transferToken(
      bundleHash: '7bf38257401eb3b0f20cabf5e6cf3f14c76760386473b220d95fa1c38642b61d', // Recipient's bundle hash
      tokenSlug: 'CRZY', // Token slug
      amount: 100,
      units: [], // Optional, for stackable tokens
      batchId: null // Optional, for stackable tokens
  );

  if ($response->success()) {
      // Do things!
      echo "Token transferred successfully!\n";
  }

  print_r($response->data()); // Raw response
  ```

- Creating a new **Rule**:

  ```php
  $response = $client->createRule(
      metaType: 'MyMetaType',
      metaId: 'MyMetaId',
      rule: [
          // Rule definition
      ],
      policy: [] // Optional policy object
  );

  if ($response->success()) {
      // Do things!
      echo "Rule created successfully!\n";
  }

  print_r($response->data()); // Raw response
  ```

- Querying **Atoms**:

  ```php
  $response = $client->queryAtom([
      'molecularHash' => 'hash',
      'bundleHash' => 'bundle',
      'isotope' => 'V',
      'tokenSlug' => 'CRZY',
      'latest' => true,
      'limit' => 15,
      'offset' => 1
  ]);

  print_r($response->data()); // Raw response
  ```

- Working with **Buffer Tokens**:

  ```php
  // Deposit to buffer
  $depositResponse = $client->depositBufferToken(
      tokenSlug: 'CRZY',
      amount: 100,
      tradeRates: [
          'OTHER_TOKEN' => 0.5
      ]
  );

  // Withdraw from buffer
  $withdrawResponse = $client->withdrawBufferToken(
      tokenSlug: 'CRZY',
      amount: 50
  );

  print_r([$depositResponse->data(), $withdrawResponse->data()]); // Raw responses
  ```

- Getting client fingerprint:

  ```php
  $fingerprint = $client->getFingerprint();
  echo $fingerprint . "\n";

  $fingerprintData = $client->getFingerprintData();
  print_r($fingerprintData);
  ```

## Advanced Usage: Working with Molecules

For more granular control, you can work directly with Molecules:

- Create a new Molecule:
  ```php
  use WishKnish\KnishIO\Client\Molecule;
  
  $molecule = new Molecule($secret, $sourceWallet, $remainderWallet, $cellSlug);
  ```

- Create a custom Mutation:
  ```php
  use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
  
  $mutation = new MutationProposeMolecule($client, $molecule);
  ```

- Sign and check a Molecule:
  ```php
  $molecule->sign();
  try {
      $molecule->check();
      echo "Molecule validation passed!\n";
  } catch (\Exception $e) {
      echo "Molecule validation failed: " . $e->getMessage() . "\n";
  }
  ```

- Execute a custom Query or Mutation:
  ```php
  $response = $client->executeQuery($mutation);
  
  if ($response->success()) {
      echo "Molecule executed successfully!\n";
  }
  ```

## The Hard Way: DIY Everything

This method involves individually building Atoms and Molecules, triggering the signature and validation processes, and communicating the resulting signed Molecule mutation or Query to a Knish.IO node via GraphQL.

1. Include the relevant classes in your application code:
    ```php
    <?php
    use WishKnish\KnishIO\Client\{Molecule, Wallet, Atom};
    use WishKnish\KnishIO\Client\Libraries\Crypto;
    ```

2. Generate a 2048-symbol hexadecimal secret, either randomly, or via hashing login + password + salt, OAuth secret ID, biometric ID, or any other static value.

3. (optional) Initialize a signing wallet with:
   ```php
   $wallet = new Wallet(
       secret: $secret,
       token: $tokenSlug,
       position: $customPosition, // (optional) instantiate specific wallet instance vs. random
       characters: $characterSet // (optional) override the character set used by the wallet
   );
   ```

   **WARNING 1:** If ContinuID is enabled on the node, you will need to use a specific wallet, and therefore will first need to query the node to retrieve the `position` for that wallet.

   **WARNING 2:** The Knish.IO protocol mandates that all C and M transactions be signed with a `USER` token wallet.

4. Build your molecule with:
   ```php
   $molecule = new Molecule(
       secret: $secret,
       sourceWallet: $sourceWallet, // (optional) wallet for signing
       remainderWallet: $remainderWallet, // (optional) wallet to receive remainder tokens
       cellSlug: $cellSlug // (optional) used to point a transaction to a specific branch of the ledger
   );
   ```

5. Either use one of the shortcut methods provided by the `Molecule` class (which will build `Atom` instances for you), or create `Atom` instances yourself.

   DIY example:
    ```php
    // This example records a new Wallet on the ledger

    // Define metadata for our new wallet
    $newWalletMeta = [
        'address' => $newWallet->address,
        'token' => $newWallet->token,
        'bundle' => $newWallet->bundle,
        'position' => $newWallet->position,
        'batchId' => $newWallet->batchId,
    ];

    // Build the C isotope atom
    $walletCreationAtom = new Atom(
        position: $sourceWallet->position,
        walletAddress: $sourceWallet->address,
        isotope: 'C',
        token: $sourceWallet->token,
        metaType: 'wallet',
        metaId: $newWallet->address,
        meta: $newWalletMeta,
        index: $molecule->generateIndex()
    );

    // Add the atom to our molecule
    $molecule->addAtom($walletCreationAtom);

    // Adding a ContinuID / remainder atom
    $molecule->addContinuIdAtom();
    ```

   Molecule shortcut method example:
    ```php
    // This example commits metadata to some Meta Asset

    // Defining our metadata
    $metadata = [
        'foo' => 'Foo',
        'bar' => 'Bar'
    ];

    $molecule->initMeta(
        meta: $metadata,
        metaType: 'MyMetaType',
        metaId: 'MetaId123'
    );
    ```

6. Sign the molecule with the stored user secret:
    ```php
    $molecule->sign();
    ```

7. Make sure everything checks out by verifying the molecule:
    ```php
    try {
        $molecule->check();
        // If we're validating a V isotope transaction,
        // add the source wallet as a parameter
        $molecule->check($sourceWallet);
        echo "Molecule validation passed!\n";
    } catch (\Exception $e) {
        echo "Molecule check failed: " . $e->getMessage() . "\n";
        // Handle the error
    }
    ```

8. Broadcast the molecule to a Knish.IO node:
    ```php
    use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
    
    // Build our mutation object using the KnishIOClient wrapper
    $mutation = new MutationProposeMolecule($client, $molecule);

    // Send the mutation to the node and get a response
    $response = $client->executeQuery($mutation);
    ```

9. Inspect the response...
    ```php
    // For basic queries, we look at the data property:
    print_r($response->data());

    // For mutations, check if the molecule was accepted by the ledger:
    echo $response->success() ? "Success" : "Failed";

    // We can also check the reason for rejection
    echo $response->reason();

    // Some queries may also produce a payload, with additional data:
    print_r($response->payload());
    ```

   Payloads are provided by responses to the following queries:
    1. `QueryBalance` and `QueryContinuId` -> returns a `Wallet` instance
    2. `QueryWalletList` -> returns a list of `Wallet` instances
    3. `MutationProposeMolecule`, `MutationRequestAuthorization`, `MutationCreateIdentifier`, `MutationLinkIdentifier`, `MutationClaimShadowWallet`, `MutationCreateToken`, `MutationRequestTokens`, and `MutationTransferTokens` -> returns molecule metadata

## Demo System

This SDK includes a comprehensive demo system with interactive examples and CLI tools. Explore the demo folder to see practical implementations:

```bash
# Test the demo functionality
php demo/test-demo-functionality.php

# Run interactive examples
php demo/examples/01-authentication.php
php demo/examples/02-wallets.php
php demo/examples/03-tokens.php
php demo/examples/04-metadata.php
php demo/examples/05-encryption.php

# Launch the interactive CLI demo
php demo/cli/interactive-demo.php
```

The demo system provides:
- **Authentication examples** - Client initialization and authorization
- **Wallet operations** - Creation, management, and ContinuID handling
- **Token lifecycle** - Creation, transfers, and supply management
- **Metadata storage** - Asset metadata and versioning
- **Encryption capabilities** - Quantum-resistant message encryption
- **Interactive CLI** - Full-featured command-line interface

See `demo/README.md` for complete setup instructions and configuration options.

## Security

This SDK implements quantum-resistant cryptography for future-proof security:

- All signatures use XMSS (post-quantum secure)
- Encryption uses ML-KEM768 (NIST approved)
- One-time keys prevent signature reuse
- Secure random generation for all cryptographic operations

For security issues, please email security@wishknish.com instead of using the issue tracker.

## Features

- 🚀 **Post-Blockchain Architecture**: DAG-based distributed ledger with organism-inspired transaction model
- 🔐 **Quantum-Resistant Security**: XMSS signatures and ML-KEM768 (NIST FIPS-203) encryption
- ⚡ **Network-Bound Scalability**: Performance improves as the network grows
- 🔄 **Cross-Platform Compatibility**: Full compatibility with JavaScript, Kotlin, Python, and C clients
- 📦 **Comprehensive SDK**: Complete API for wallets, tokens, metadata, and transactions
- 🧬 **Molecular Composition**: Atomic operations grouped into molecular transactions
- 🏢 **Cellular Architecture**: Application-specific sub-ledgers with isolation

## Getting Help

Knish.IO is under active development, and our team is ready to assist with integration questions. The best way to seek help is to stop by our [Telegram Support Channel](https://t.me/wishknish). You can also [send us a contact request](https://knish.io/contact) via our website.

### Support Resources

- 📧 Email: info@wishknish.com
- 💬 Telegram: [WishKnish Support](https://t.me/wishknish)
- 🐛 Issues: [GitHub Issues](https://github.com/WishKnish/KnishIO-Client-PHP/issues)
- 🌐 Website: [https://knish.io](https://knish.io)
- 📚 [Technical Whitepaper](https://github.com/WishKnish/KnishIO-Technical-Whitepaper)
- 🔗 Related SDKs:
  - [JavaScript Client](https://github.com/WishKnish/KnishIO-Client-JS)
  - [Kotlin Client](https://github.com/WishKnish/KnishIO-Client-Kotlin)
  - [Python Client](https://github.com/WishKnish/KnishIO-Client-Python)

## Development Notes

### Cross-Platform Testing

The PHP SDK maintains 100% compatibility with the JavaScript SDK through comprehensive test vectors. These manually-curated test vectors ensure consistent behavior across platforms.

### Prerequisites

- PHP 8.2 or higher
- Composer for dependency management
- Required extensions: `ext-json`, `ext-sodium`, `ext-mbstring`

### Building from Source

```bash
# Clone the repository
git clone https://github.com/WishKnish/KnishIO-Client-PHP.git
cd KnishIO-Client-PHP

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Update dependencies
composer update

# Generate documentation (if configured)
composer run-script docs
```

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit --filter MoleculeTest

# Generate coverage report
vendor/bin/phpunit --coverage-html coverage/
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Authors

- **WishKnish Corp.** - *Initial work* - [WishKnish](https://wishknish.com)
- **Eugene Teplitsky** - *Lead Developer*

See also the list of [contributors](https://github.com/WishKnish/KnishIO-Client-PHP/contributors) who participated in this project.

---

<div style="text-align:center">
  <strong>Built with ❤️ for the post-blockchain future</strong>
</div>