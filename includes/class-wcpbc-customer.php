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
		if ( ! $wc_customer_zipcode || empty( $wc_customer_zipcode ) ) {
			return false;
		}
		$codes = explode( "\n", $zipcodes[ 'zipcodes' ] );
		$wc_customer_zipcode = str_replace( array(' ', '-' ), '', $wc_customer_zipcode );
		$wc_customer_zipcode = intval( $wc_customer_zipcode );
		if ( in_array( $wcpbc_customer, $zipcodes ) ) {
			return true;
		}
		foreach( $codes as $zipcode ) {
			if ( intval( $zipcode ) == $wc_customer_zipcode ) {
				return true;
			}
			$zipcode = explode( '- ', $zipcode );
			$zipcode = $zipcode[1];
			$zipcode = explode( '|', $zipcode );
			if ( $wc_customer_zipcode >= intval( $zipcode[0] ) && $wc_customer_zipcode <= intval( $zipcode[1] ) ) {
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
		if ( ! $wc_customer_zipcode || empty( $wc_customer_zipcode ) ) {
			return false;
		}
		
		$has_region = false;

		$this->_data = array();
		$wc_customer_zipcode = str_replace( array(' ', '-' ), '', $wc_customer_zipcode );
		$wc_customer_zipcode = intval( $wc_customer_zipcode );

		foreach ( WCPBZIP()->get_regions() as $key => $group_data ) {
			$codes = explode( "\n", $group_data[ 'zipcodes' ] );
			if ( in_array( $wc_customer_zipcode, $codes ) ) {
				$this->_data = array_merge( $group_data, array( 'group_key' => $key, 'timestamp' => time() ) );
				$has_region = true;
				break;
			}
			foreach( $codes as $zipcode ) {
				if ( intval( $zipcode ) == $wc_customer_zipcode ) {
					$this->_data = array_merge( $group_data, array( 'group_key' => $key, 'timestamp' => time() ) );
					$has_region = true;
					break;
				}
				$zipcode = explode( '- ', $zipcode );
				$zipcode = $zipcode[1];
				$zipcode = explode( '|', $zipcode );
				if ( $wc_customer_zipcode >= intval( $zipcode[0] ) && $wc_customer_zipcode <= intval( $zipcode[1] ) ) {
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