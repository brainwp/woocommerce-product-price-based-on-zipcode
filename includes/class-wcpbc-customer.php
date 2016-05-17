<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCPBC_Customer' ) ) :

/**
 * WCPBC_Customer
 *
 * Store WCPBC frontend data Handler
 *
 * @class 		WCPBC_Customer
 * @version		1.5.0
 * @category	Class
 * @author 		oscargare
 */
class WCPBC_Customer {

	/** Stores customer price based on country data as an array */
	protected $_data;

	/** Stores bool when data is changed */
	private $_changed = false;

	/**
	 * Constructor for the wcpbc_customer class loads the data.
	 *
	 * @access public
	 */

	public function __construct() {		
		
		$this->_data = WC()->session->get( 'wcpbc_customer' );	
		
		$wc_customer_zipcode = wcpbc_get_woocommerce_zipcode();					

		if ( empty( $this->_data ) || ! $this->zipcode_exists( $wc_customer_zipcode, $this->_data ) || ( $this->timestamp < get_option( 'wc_price_based_country_timestamp' ) ) ) {

			$this->set_zipcode( $wc_customer_zipcode );
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie(true);
		}

		// When leaving or ending page load, store data
		add_action( 'shutdown', array( $this, 'save_data' ), 10 );	
	}	

	/**
	 * save_data function.
	 *
	 * @access public
	 */
	public function save_data() {
		
		if ( $this->_changed ) {
			WC()->session->set( 'wcpbc_customer', $this->_data );				
		}	

	}
	/**
	 * check if zipcode exists in array
	 * @access public
	 */
	public function zipcode_exists( $wc_customer_zipcode, $zipcodes ) {
		$codes = explode( ',', $zipcodes[ 'zipcodes' ] );
		if ( in_array( $wcpbc_customer, $zipcodes ) ) {
			return true;
		}
		$multiple = explode( ' ', $wc_customer_zipcode );
		$multiple = $multiple[0] . ' *';
		if( in_array( $multiple, $codes ) ) {
			return true;
		}
		$postcode = $wc_customer_zipcode;
		$postcode_size = strlen( $postcode );
		for ($i = 0; $i != $postcode_size; $i++) {
			$postcode = substr_replace( $postcode, '', -1 );
			$multiple = $postcode . '*';
			if( in_array( $multiple, $codes ) ) {
				return true;
			}
		}

		return false;

	}
	/**
	 * __get function.
	 *
	 * @access public
	 * @param string $property
	 * @return string
	 */
	public function __get( $property ) {
		$value = isset( $this->_data[ $property ] ) ? $this->_data[ $property ] : '';

		if ( $property === 'zipcodes' && ! $value) {
			$value = array();			
		}

		return $value;
	}

	/**
	 * Sets wcpbc data form country.
	 *
	 * @access public
	 * @param mixed $country
	 * @return boolean
	 */
	public function set_zipcode( $wc_customer_zipcode ) {
		
		$has_region = false;

		$this->_data = array();

		foreach ( WCPBZIP()->get_regions() as $key => $group_data ) {
			$isset = false;

			$codes = explode( ',' , $group_data[ 'zipcodes' ] );
			if ( in_array( $wc_customer_zipcode, $codes ) ) {
				$this->_data = array_merge( $group_data, array( 'group_key' => $key, 'timestamp' => time() ) );
				$has_region = true;
				break;
			}
			$multiple = explode( ' ', $wc_customer_zipcode );
			$multiple = $multiple[0] . ' *';
			if( in_array( $multiple, $codes ) ) {
				$this->_data = array_merge( $group_data, array( 'group_key' => $key, 'timestamp' => time() ) );
				$has_region = true;
				break;
			}
			$postcode = $wc_customer_zipcode;
			$postcode_size = strlen( $postcode );
			for ($i = 0; $i != $postcode_size; $i++) {
				$postcode = substr_replace( $postcode, '', -1 );
				$multiple = $postcode . '*';
				if( in_array( $multiple, $codes ) ) {
					$this->_data = array_merge( $group_data, array( 'group_key' => $key, 'timestamp' => time() ) );
					$has_region = true;
					break;
				}
			}
			if ( $has_region ) {
				break;
			}
		}

		$this->_changed = true;
		return $has_region;
	}		
}

endif;

?>