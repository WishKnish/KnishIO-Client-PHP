<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */
namespace WishKnish\KnishIO\Client\Instance\Rules;

use JsonException;
use JsonSerializable;
use WishKnish\KnishIO\Client\Instance\Rules\Exception\RuleArgumentException;
use WishKnish\KnishIO\Client\Instance\Rules\Exception\RulePropertyException;
use WishKnish\KnishIO\Client\Traits\Json;



class Meta implements JsonSerializable {
  use Json;

  private array $properties = [];

  public function __construct ( ...$metas ) {

    foreach ( $metas as $property => $value ) {
      if ( is_numeric( $property ) ) {
        throw new RuleArgumentException( 'Parameters should only be passed as named parameters' );
      }

      $this->properties[ $property ] = $value;
    }
  }

  /**
   * @param string $property
   *
   * @return mixed
   */
  public function __get( string $property ): mixed {
    if ( !array_key_exists( $property, $this->properties )) {
      throw new RulePropertyException( "Property {static::class}::$$property does not exist." );
    }

    return $this->properties[ $property ];
  }

  /**
   * @param string $property
   * @param mixed $value
   *
   * @return void
   */
  public function __set( string $property, mixed $value ): void {
    if ( is_numeric( $property ) ) {
      throw new RuleArgumentException( 'A property cannot be a number.' );
    }

    $this->properties[ $property ] = $value;
  }

  /**
   * @param string $property
   *
   * @return bool
   */
  public function  __isset( string $property ): bool {
    return isset( $this->properties[ $property ] );
  }

  /**
   * @param string $property
   *
   * @return void
   */
  public function __unset ( string $property ): void {
    unset( $this->properties[ $property ] );
  }

  /**
   * @return string
   * @throws JsonException
   */
  public function toJson (): string {
    return json_encode( static::toArray( $this ), JSON_THROW_ON_ERROR );
  }

  /**
   * @return array
   */
  public function jsonSerialize (): array {
    return static::toArray( $this );
  }

  /**
   *
   * @param string $string
   *
   * @return static
   *
   * @throws JsonException
   */
  public static function jsonToObject ( string $string ): static {
    $meta = json_decode( $string, true, 512, JSON_THROW_ON_ERROR );

    return static::arrayToObject( $meta );
  }

  /**
   * @param array $data
   * @param Meta|null $object
   *
   * @return static
   */
  public static function arrayToObject ( array $data,  ?Meta $object = null ): static {
    $meta = $object ?? new static();

    foreach ( $data as $key => $value ) {
      $meta->{ $key } = $value;
    }

    return $meta;
  }

  public static function toArray( Meta $object ): array {
    $meta = [];

    foreach ( $object->properties() as $property ) {
      $meta[ $property ] = $object->{ $property };
    }

    return $meta;
  }

  public function properties(): array {
    return array_keys( $this->properties );
  }
}
