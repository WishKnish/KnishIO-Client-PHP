<?php

use Random\RandomException;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;

class MLKEM768 {
  const PARAMS = [
    'N' => 256,
    'K' => 3,
    'Q' => 3329,
    'ETA1' => 2,
    'ETA2' => 2,
    'DU' => 10,
    'DV' => 4,
    'SEED_LEN' => 32,
  ];

  private Shake256 $shake256;

  public function __construct() {
    $this->shake256 = new Shake256();
  }

  /**
   * @throws RandomException
   */
  public function keygen(): array {
    $d = random_bytes(self::PARAMS['SEED_LEN']);
    return $this->keygenWithSeed($d);
  }

  private function keygenWithSeed($d): array {
    $publicSeed = $this->shake256::hash(substr($d, 0, 32), 32);
    $noiseSeed = $this->shake256::hash(substr($d, 32, 32), 32);

    $nonce = 0;
    $s = array_fill(0, self::PARAMS['K'], null);
    for ($i = 0; $i < self::PARAMS['K']; $i++) {
      $s[$i] = $this->sampleCBD($noiseSeed, $nonce++, self::PARAMS['ETA1']);
    }

    $e = array_fill(0, self::PARAMS['K'], null);
    for ($i = 0; $i < self::PARAMS['K']; $i++) {
      $e[$i] = $this->sampleCBD($noiseSeed, $nonce++, self::PARAMS['ETA1']);
    }

    $a = $this->genMatrix($publicSeed);

    $t = array_fill(0, self::PARAMS['K'], null);
    for ($i = 0; $i < self::PARAMS['K']; $i++) {
      $t[$i] = $this->polyVectorMul($a[$i], $s);
      $t[$i] = $this->polyAdd($t[$i], $e[$i]);
    }

    $pk = $this->packPublicKey($t, $publicSeed);
    $sk = $this->packPrivateKey($s, $pk);

    return ['publicKey' => $pk, 'secretKey' => $sk];
  }

  public function encaps($pk) {
    $m = random_bytes(32);
    return $this->encapsWithSeed($pk, $m);
  }

  private function encapsWithSeed($pk, $m) {
    [$publicSeed, $t] = $this->unpackPublicKey($pk);

    $kr = $this->shake256->hash($m . $pk, 64);
    $k = substr($kr, 0, 32);
    $r = substr($kr, 32, 32);

    $nonce = 0;
    $r_vec = array_fill(0, self::PARAMS['K'], null);
    for ($i = 0; $i < self::PARAMS['K']; $i++) {
      $r_vec[$i] = $this->sampleCBD($r, $nonce++, self::PARAMS['ETA1']);
    }

    $e1 = array_fill(0, self::PARAMS['K'], null);
    for ($i = 0; $i < self::PARAMS['K']; $i++) {
      $e1[$i] = $this->sampleCBD($r, $nonce++, self::PARAMS['ETA2']);
    }

    $e2 = $this->sampleCBD($r, $nonce, self::PARAMS['ETA2']);

    $a = $this->genMatrix($publicSeed);
    $u = array_fill(0, self::PARAMS['K'], null);
    for ($i = 0; $i < self::PARAMS['K']; $i++) {
      $u[$i] = $this->polyVectorMul($a[$i], $r_vec);
      $u[$i] = $this->polyAdd($u[$i], $e1[$i]);
    }

    $v = $this->polyVectorMul($t, $r_vec);
    $v = $this->polyAdd($v, $e2);
    $v = $this->polyAdd($v, $this->encodeMessage($m));

    $c = $this->packCiphertext($u, $v);
    $ss = $this->shake256->hash($kr . $c, 32);

    return ['ciphertext' => $c, 'sharedSecret' => $ss];
  }

  public function decaps($sk, $c) {
    [$s, $pk] = $this->unpackPrivateKey($sk);
    [$publicSeed, $t] = $this->unpackPublicKey($pk);
    [$u, $v] = $this->unpackCiphertext($c);

    $m_prime = $this->polySubtract($v, $this->polyVectorMul($u, $s));
    $m_prime = $this->decodeMessage($m_prime);

    $kr_prime = $this->shake256->hash($m_prime . $pk, 64);
    $k_prime = substr($kr_prime, 0, 32);
    $r_prime = substr($kr_prime, 32, 32);

    $cmp = $this->encapsWithSeed($pk, $m_prime)['ciphertext'];

    if ($c === $cmp) {
      return $this->shake256->hash($kr_prime . $c, 32);
    } else {
      return $this->shake256->hash($sk . $c, 32);
    }
  }

  // Implement other necessary methods:
  // - sampleCBD
  // - genMatrix
  // - polyVectorMul
  // - polyAdd
  // - polySubtract
  // - encodeMessage
  // - decodeMessage
  // - packPublicKey
  // - packPrivateKey
  // - packCiphertext
  // - unpackPublicKey
  // - unpackPrivateKey
  // - unpackCiphertext
  // ... and any other utility functions

}
