#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use WishKnish\KnishIO\Client\Storage\SecretEnvelope;
use WishKnish\KnishIO\Client\Storage\SecretStorageMetadata;
use WishKnish\KnishIO\Client\Storage\EncryptedSecretPayload;

if ($argc < 2) {
  fwrite(STDERR, "Usage: secret-storage-cli.php <seal|open> [args...]\n");
  exit(1);
}

$cmd = $argv[1];

if ($cmd === 'seal' || $cmd === 'seal-recovery') {
  if ($argc < 5) {
    fwrite(STDERR, "Usage: secret-storage-cli.php seal <passphrase> <secret> <bundleHash> [label]\n");
    exit(1);
  }
  $passphrase = $argv[2];
  $secret = $argv[3];
  $bundleHash = $argv[4];
  $label = ($argc >= 6 && strlen($argv[5]) > 0) ? $argv[5] : null;

  $meta = new SecretStorageMetadata(
    bundleHash: $bundleHash,
    label: $label,
    createdAt: 1700000000000,
    hardwareBacked: false,
    providerType: 'aes-gcm'
  );

  $payload = SecretEnvelope::seal($secret, $passphrase, $meta);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
  exit(0);
} elseif ($cmd === 'open') {
  if ($argc < 4) {
    fwrite(STDERR, "Usage: secret-storage-cli.php open <passphrase> <payloadJson>\n");
    exit(1);
  }
  $passphrase = $argv[2];
  $payloadJson = $argv[3];
  $data = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
  $payload = EncryptedSecretPayload::fromArray($data);
  $plain = SecretEnvelope::open($payload, $passphrase);
  echo $plain . "\n";
  exit(0);
}

fwrite(STDERR, "Unknown command: $cmd\n");
exit(1);
