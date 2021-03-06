<?php
namespace WishKnish\KnishIO\Client\Libraries;

use Tuupola\Base58 as Base;
use Tuupola\Base58\BcmathEncoder;
use Tuupola\Base58\GmpEncoder;
use Tuupola\Base58\PhpEncoder;
use Tuupola\Base58\BaseEncoder;

/**
 * Class Base58
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Base58 extends Base
{
    /**
     * @var BaseEncoder
     */
    private $encoder;

    /**
     * @var array
     */
    private $options = [
        'characters' => Base::GMP,
        'check' => false,
        'version' => 0x00,
    ];

    /**
     * Base58 constructor.
     *
     * @param array $options
     */
    public function __construct( array $options = [] )
    {

        parent::__construct( $options );

        $this->options = array_merge( $this->options, $options );

        if ( extension_loaded( 'gmp' ) ) {
            $this->encoder = new GmpEncoder( $this->options );
        }
        else if ( extension_loaded( 'bcmath' ) ) {
            $this->encoder = new BcmathEncoder( $this->options );
        }
        else {
            $this->encoder = new PhpEncoder( $this->options );
        }

    }

}
