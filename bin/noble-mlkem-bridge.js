#!/usr/bin/env node

/**
 * Noble ML-KEM Bridge for PHP
 *
 * This Node.js script provides a bridge between PHP and the JavaScript
 * @noble/post-quantum library, ensuring 100% compatibility with the
 * JavaScript SDK's ML-KEM-768 implementation.
 *
 * Usage:
 *   node noble-mlkem-bridge.js keygen <seedHex>
 *   node noble-mlkem-bridge.js encaps <publicKeyBase64>
 *   node noble-mlkem-bridge.js decaps <ciphertextBase64> <secretKeyBase64>
 *
 * @package WishKnish\KnishIO\Client
 */

import { ml_kem768, ml_kem1024 } from '@noble/post-quantum/ml-kem.js';

/**
 * Convert hex string to Uint8Array
 */
function hexToBytes(hex) {
  if (hex.length % 2 !== 0) {
    throw new Error('Hex string must have even length');
  }
  const bytes = new Uint8Array(hex.length / 2);
  for (let i = 0; i < bytes.length; i++) {
    bytes[i] = parseInt(hex.substr(i * 2, 2), 16);
  }
  return bytes;
}

/**
 * Convert Uint8Array to hex string
 */
function bytesToHex(bytes) {
  return Array.from(bytes)
    .map(b => b.toString(16).padStart(2, '0'))
    .join('');
}

/**
 * Convert Uint8Array to base64
 */
function bytesToBase64(bytes) {
  return Buffer.from(bytes).toString('base64');
}

/**
 * Convert base64 to Uint8Array
 */
function base64ToBytes(base64) {
  return new Uint8Array(Buffer.from(base64, 'base64'));
}

/**
 * Generate ML-KEM-768 key pair from seed (deterministic)
 */
function keygen(seedHex, paramSet = '1024') {
  if (seedHex.length !== 128) {
    throw new Error('Seed must be exactly 128 hex characters (64 bytes)');
  }

  const seed = hexToBytes(seedHex);
  const engine = String(paramSet) === '768' ? ml_kem768 : ml_kem1024;
  const { publicKey, secretKey } = engine.keygen(seed);

  return {
    publicKey: bytesToBase64(publicKey),
    secretKey: bytesToBase64(secretKey)
  };
}

/**
 * Encapsulate - generate shared secret and ciphertext from public key
 */
function encaps(publicKeyBase64) {
  const publicKey = base64ToBytes(publicKeyBase64);
  let engine;
  if (publicKey.length === 1568) {
    engine = ml_kem1024;
  } else if (publicKey.length === 1184) {
    engine = ml_kem768;
  } else {
    throw new Error(`Unexpected public key length: ${publicKey.length}`);
  }
  const { cipherText, sharedSecret } = engine.encapsulate(publicKey);

  return {
    ciphertext: bytesToBase64(cipherText),
    sharedSecret: bytesToBase64(sharedSecret)
  };
}

/**
 * Decapsulate - recover shared secret from ciphertext using secret key
 */
function decaps(ciphertextBase64, secretKeyBase64) {
  const cipherText = base64ToBytes(ciphertextBase64);
  const secretKey = base64ToBytes(secretKeyBase64);
  let engine;
  if (cipherText.length === 1568 && secretKey.length === 3168) {
    engine = ml_kem1024;
  } else if (cipherText.length === 1088 && secretKey.length === 2400) {
    engine = ml_kem768;
  } else {
    throw new Error(`Unexpected ciphertext (${cipherText.length}) or secretKey (${secretKey.length}) length`);
  }

  const sharedSecret = engine.decapsulate(cipherText, secretKey);

  return {
    sharedSecret: bytesToBase64(sharedSecret)
  };
}

/**
 * Main entry point
 */
function main() {
  const args = process.argv.slice(2);

  if (args.length < 1) {
    console.error('Usage: noble-mlkem-bridge.js <command> [args...]');
    console.error('Commands:');
    console.error('  keygen <seedHex>                          - Generate key pair from seed');
    console.error('  encaps <publicKeyBase64>                  - Encapsulate shared secret');
    console.error('  decaps <ciphertextBase64> <secretKeyBase64> - Decapsulate shared secret');
    process.exit(1);
  }

  const command = args[0];

  try {
    let result;

    switch (command) {
      case 'keygen':
        if (args.length < 2 || args.length > 3) {
          throw new Error('keygen requires seedHex and optional paramSet argument');
        }
        result = keygen(args[1], args[2] || '1024');
        break;

      case 'encaps':
        if (args.length !== 2) {
          throw new Error('encaps requires publicKeyBase64 argument');
        }
        result = encaps(args[1]);
        break;

      case 'decaps':
        if (args.length !== 3) {
          throw new Error('decaps requires ciphertextBase64 and secretKeyBase64 arguments');
        }
        result = decaps(args[1], args[2]);
        break;

      default:
        throw new Error(`Unknown command: ${command}`);
    }

    // Output result as JSON
    console.log(JSON.stringify(result));
    process.exit(0);

  } catch (error) {
    console.error(JSON.stringify({
      error: error.message,
      stack: error.stack
    }));
    process.exit(1);
  }
}

// Run main function
main();