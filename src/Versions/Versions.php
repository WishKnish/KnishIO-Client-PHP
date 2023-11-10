<?php

namespace WishKnish\KnishIO\Client\Versions;

use WishKnish\KnishIO\Client\Versions\Traits\Reflections;

class Versions
{
  use Reflections;

  public function __get(string $name): ?HashAtom {
    if (array_key_exists($name, static::reflections())) {
      return static::reflections()[$name];
    }

    $trace = debug_backtrace();
    trigger_error(
      'Undefined property ' . $name .
      ' in ' . $trace[0]['file'] .
      ' line ' . $trace[0]['line'],
      E_USER_WARNING);

    return null;
  }

  public function __set(string $name, mixed $value): void {
    throw new \LogicException();
  }

  public function __isset(string $name): bool {
    return array_key_exists($name, static::reflections());
  }

  public function __unset(string $name): void {
    throw new \LogicException();
  }
}
