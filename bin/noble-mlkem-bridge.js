#!/usr/bin/env node

/**
 * Noble ML-KEM helper for PHP
 *
 * One long-lived Node.js process per PHP process (src/Libraries/NobleMLKEMBridge.php starts it
 * with `serve`) running the JavaScript @noble/post-quantum library, so its keys match the
 * JavaScript SDK's. Used when the bundled libkcore cannot be loaded through ext-ffi.
 *
 * Protocol: one JSON request per stdin line,
 *   {"id": <int>, "cmd": "keygen"|"encaps"|"decaps", "args": [...]}
 * answered by exactly one stdout line,
 *   {"id": <int>, "result": {...}}  or  {"id": <int|null>, "error": "<message>"}
 *   keygen [seedHex, paramSet?]          -> {publicKey, secretKey} (base64)
 *   encaps [publicKeyBase64]             -> {ciphertext, sharedSecret} (base64)
 *   decaps [ciphertextBase64, secretKeyBase64] -> {sharedSecret} (base64)
 * It exits 0 when stdin closes.
 *
 * Usage: node noble-mlkem-bridge.js serve
 *
 * @package WishKnish\KnishIO\Client
 */

import { createInterface } from 'node:readline';
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
 * Generate an ML-KEM key pair from seed (deterministic) at the requested parameter set
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

const COMMANDS = {
  keygen: (args) => {
    if (args.length < 1 || args.length > 2) {
      throw new Error('keygen requires seedHex and optional paramSet argument');
    }
    return keygen(args[0], args[1] || '1024');
  },
  encaps: (args) => {
    if (args.length !== 1) {
      throw new Error('encaps requires publicKeyBase64 argument');
    }
    return encaps(args[0]);
  },
  decaps: (args) => {
    if (args.length !== 2) {
      throw new Error('decaps requires ciphertextBase64 and secretKeyBase64 arguments');
    }
    return decaps(args[0], args[1]);
  }
};

/**
 * Answers one request line; never throws.
 */
function answer(line) {
  let id = null;
  try {
    const request = JSON.parse(line);
    id = Number.isInteger(request.id) ? request.id : null;
    const run = COMMANDS[request.cmd];
    if (!run) {
      throw new Error(`Unknown command: ${request.cmd}`);
    }
    return { id, result: run(Array.isArray(request.args) ? request.args.map(String) : []) };
  } catch (error) {
    return { id, error: error.message };
  }
}

function main() {
  if (process.argv[2] !== 'serve' || process.argv.length !== 3) {
    console.error('Usage: noble-mlkem-bridge.js serve   (newline-delimited JSON on stdin; see the file header)');
    process.exit(2);
  }
  const lines = createInterface({ input: process.stdin, crlfDelay: Infinity });
  lines.on('line', (line) => {
    if (line.trim() !== '') {
      process.stdout.write(JSON.stringify(answer(line)) + '\n');
    }
  });
  lines.on('close', () => process.exit(0));
}

main();