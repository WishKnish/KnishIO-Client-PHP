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

namespace WishKnish\KnishIO\Client\Storage;

/**
 * Class FileStorageBackend
 * @package WishKnish\KnishIO\Client\Storage
 */
class FileStorageBackend implements StorageBackend {

  /**
   * @var string
   */
  protected string $path;

  /**
   * @var array<string, string>
   */
  protected array $store = [];

  /**
   * FileStorageBackend constructor.
   *
   * @param string $path
   */
  public function __construct ( string $path ) {
    $this->path = $path;
    $this->load();
  }

  /**
   * Load store from disk if file exists
   *
   * @return void
   */
  protected function load (): void {
    if ( file_exists( $this->path ) ) {
      $content = file_get_contents( $this->path );
      if ( $content !== false && $content !== '' ) {
        $data = json_decode( $content, true );
        if ( is_array( $data ) ) {
          $this->store = $data;
        }
      }
    }
  }

  /**
   * Atomically persist the current store to disk via tempfile + rename with chmod 0600
   *
   * @return void
   */
  protected function persist (): void {
    $dir = dirname( $this->path );
    if ( !is_dir( $dir ) ) {
      if ( !mkdir( $dir, 0700, true ) && !is_dir( $dir ) ) {
        throw new SecretStorageException( "Failed to create directory: {$dir}" );
      }
    }

    $tmpPath = $this->path . '.tmp.' . bin2hex( random_bytes( 8 ) );
    $json = json_encode( $this->store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    if ( $json === false ) {
      throw new SecretStorageException( 'Failed to serialize storage to JSON' );
    }

    if ( file_put_contents( $tmpPath, $json ) === false ) {
      throw new SecretStorageException( "Failed to write temp file: {$tmpPath}" );
    }

    @chmod( $tmpPath, 0600 );

    if ( !rename( $tmpPath, $this->path ) ) {
      @unlink( $tmpPath );
      throw new SecretStorageException( "Failed to rename temp file to: {$this->path}" );
    }

    @chmod( $this->path, 0600 );
  }

  /**
   * @param string $key
   * @return string|null
   */
  public function getItem ( string $key ): ?string {
    return $this->store[ $key ] ?? null;
  }

  /**
   * @param string $key
   * @param string $value
   * @return void
   */
  public function setItem ( string $key, string $value ): void {
    $this->store[ $key ] = $value;
    $this->persist();
  }

  /**
   * @param string $key
   * @return bool
   */
  public function removeItem ( string $key ): bool {
    if ( !array_key_exists( $key, $this->store ) ) {
      return false;
    }
    unset( $this->store[ $key ] );
    $this->persist();
    return true;
  }

  /**
   * @return array<int, string>
   */
  public function keys (): array {
    return array_keys( $this->store );
  }

  /**
   * @return string
   */
  public function getPath (): string {
    return $this->path;
  }
}
