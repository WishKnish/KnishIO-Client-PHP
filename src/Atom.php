<?php
namespace WishKnish\KnishIO\Client;

use ArrayObject;
use desktopd\SHA3\Sponge as SHA3;
use WishKnish\KnishIO\Client\libraries\Str;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Atom
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $walletAddress
 * @property string $isotope
 * @property string|null $token
 * @property string|null $value
 * @property string|null $metaType
 * @property string|null $metaId
 * @property array $meta
 * @property string|null $otsFragment
 * @property integer $createdAt
 *
 */
class Atom
{
    use Json;

    public $position;
    public $walletAddress;
    public $isotope;
    public $token;
    public $value;
    public $metaType;
    public $metaId;
    public $meta;
    public $otsFragment;
    public $createdAt;

    /**
     * Atom constructor.
     *
     * @param string $position
     * @param string $walletAddress
     * @param string $isotope
     * @param null|string $token
     * @param null|string $value
     * @param null|string $metaType
     * @param null|string $metaId
     * @param array $meta
     * @param null|string $otsFragment
     */
    public function __construct ( $position, $walletAddress, $isotope, $token = null, $value = null, $metaType = null, $metaId = null, array $meta = null, $otsFragment = null )
    {
        $this->position = $position;
        $this->walletAddress = $walletAddress;
        $this->isotope = $isotope;
        $this->token = $token;
        $this->value = null !== $value ? ( string ) $value : null;

        $this->metaType = $metaType;
        $this->metaId = $metaId;
        $this->meta = $meta ? static::normalizeMeta( $meta ) : [];

        $this->otsFragment = $otsFragment;
        $this->createdAt = Str::currentTimeMillis();
    }

    /**
     * @param array $atoms
     * @param string $output
     * @return array[]|string|string[]|null
     * @throws \ReflectionException|\Exception
     */
    public static function hashAtoms( array $atoms, $output = 'base17' )
    {
        $atomList = ( new ArrayObject( $atoms ) )->getArrayCopy();

        usort($atomList, static function ( self $first, self $second ) {

            if ( $first->position === $second->position ) {
                return 0;
            }

            return $first->position < $second->position ? -1 : 1;
        });

        $molecularSponge = SHA3::init( SHA3::SHAKE256 );
        $numberOfAtoms = count( $atomList );

        foreach ( $atomList as $atom ) {
            $molecularSponge->absorb( $numberOfAtoms );

            foreach ( ( new \ReflectionClass( $atom ) )->getProperties() as $property ) {

                if ( $property->class === self::class && $property->isPublic() && !$property->isStatic() ) {
                    $value = $property->getValue( $atom );
                    $name = $property->getName();

                    if ( 'otsFragment' === $name ) {
                        continue;
                    }

                    if ( 'meta' === $name ) {
                        $list = static::normalizeMeta( $value );

                        foreach ( $list as $meta ) {
                            $molecularSponge->absorb( ( string ) $meta['key']);
                            $molecularSponge->absorb( ( string ) ( $meta['value'] ?: 'null' ) );
                        }

                        $property->setValue( $atom, $list );

                        continue;
                    }

                    if ( in_array( $name, [ 'position', 'walletAddress', 'isotope', ], true ) ) {

                        $molecularSponge->absorb( ( string ) $value );
                        continue;
                    }

                    if ( null !== $value ) {

                        $molecularSponge->absorb( ( string ) $value );
                    }
                }
            }
        }

        switch ( $output ) {
            case 'hex': {
                $target = bin2hex( $molecularSponge->squeeze(32 ) );
                break;
            }
            case 'array': {
                $target = str_split( bin2hex( $molecularSponge->squeeze(32 ) ) );
                break;
            }
            case 'base17': {
                $target = str_pad( Str::charsetBaseConvert( bin2hex( $molecularSponge->squeeze(32 ) ), 16, 17, '0123456789abcdef', '0123456789abcdefg' ), 64, '0', STR_PAD_LEFT );
                break;
            }
            default: {
                $target = null;
            }
        }

        return $target;
    }

    /**
     * @param array $meta
     * @return array
     */
    protected static function normalizeMeta ( array $meta )
    {
        $deep = array_filter ( $meta, static function ( $val ) { return is_array( $val ); } );
        $plane = array_filter ( $meta, static function ( $val ) { return !is_array( $val ); } );
        return array_replace( $deep,  array_map( static function ( $key, $val ) { return ['key' => $key, 'value' => $val]; }, array_keys( $plane ), array_values( $plane ) ) );
    }
}
