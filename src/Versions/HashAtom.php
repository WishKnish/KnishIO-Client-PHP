<?php

namespace WishKnish\KnishIO\Client\Versions;

use ReflectionClass;
use ReflectionException;
use WishKnish\KnishIO\Client\Atom;

abstract class HashAtom
{

  /**
   * @throws ReflectionException
   */
  public function view(): array {
    return static::structure($this);
  }

  public static function create(Atom $atom): static {
    $hashAtom = new static();

    foreach ($atom as $property => $value) {
      if (property_exists($hashAtom, $property)) {
        $hashAtom->{$property} = $value;
      }
    }

    return $hashAtom;
  }

  /**
   * @throws ReflectionException
   */
  public static function structure(HashAtom|array $object): array {
    $result = [];
    if ($object instanceof self ) {
      $keys = array_map(static fn($property) => $property->name, (new ReflectionClass($object::class))->getProperties());
      usort($keys, function ($first, $second) {
        if ($first === $second) { return 0; }
        return ($first < $second) ? -1 : 1;
      });

      foreach ($keys as $key) {
        if (is_array($object->{$key}) || $object->{$key} instanceof self) {
          $result[] = [$key => static::structure($object->{$key})];
          continue;
        }
        $result[] = [$key => $object->{$key}];
      }

      return $result;
    }

    foreach ($object as $key => $value) {
      if (is_array($value) || $value instanceof self) {

        $result[] = array_is_list($object) ? static::structure($value) : [$key => static::structure($value)];
        continue;
      }
      $result[] = array_is_list($object) ? static::structure($value) : [$key => $value];
    }

    return $result;
  }
}
