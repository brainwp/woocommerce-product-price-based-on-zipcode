<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WCPBC_Frontend
 *
 * WooCommerce Price Based Country Front-End
 *
 * @class 		WCPBC_Frontend
 * @version		1.5.0
 * @author 		oscargare
 */
class WCPBC_Frontend {
	
	/**
	 * Hook actions and filters
	 */
	public static function init(){						
		
		add_action( 'woocommerce_init', array( __CLASS__ , 'check_test_mode'), 10 );
		
		add_action( 'woocommerce_init', array( __CLASS__ , 'check_manual_country_widget'), 20 );		
		
		add_action( 'wp_enqueue_scripts', array( __CLASS__ , 'load_checkout_script' ) );

		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__ , 'checkout_country_update' ) );	
	}	
		
	/**
	 * Check test mode
	 */	
	public static function check_test_mode(){
		
		if ( get_option('wc_price_based_country_test_mode', 'no') === 'yes' && $test_country = get_option('wc_price_based_country_test_country') ) {
			
			wcpbc_set_woocommerce_country( $test_country );
			
			/* add test store message */
			add_action( 'wp_footer', array( __CLASS__, 'test_store_message' ) );
		}		
	}
	
	/**
	 * Check manual country widget
	 */	
	public static function check_manual_country_widget(){
				
		if ( isset( $_POST['wcpbc-manual-country'] ) && $_POST['wcpbc-manual-country'] ) {			
			
			wcpbc_set_woocommerce_country( wc_clean( $_POST['wcpbc-manual-country'] ) );			
		}
	}
		
	/**
	 * Return test store message 
	 */
	public static function test_store_message() {
		echo '<p class="demo_store">' . __( 'This is a demo store for testing purposes', 'wc-price-based-country') . '</p>';
	}
	
	/**
	 * Add script to checkout page	 
	 */
	public static function load_checkout_script( ) {

		if ( is_checkout() ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( version_compare( WC()->version, '2.4', '<' ) ) {
				$version = '-2.3';
			} else {
				$version = '';
			}

			wp_enqueue_script( 'wc-price-based-country-checkout', WCPBZIP()->plugin_url() . 'assets/js/wcpbc-checkout' . $version . $suffix . '.js', array( 'wc-checkout', 'wc-cart-fragments' ), WC_VERSION, true );
		}

	}

	/**
	 * Update WCPBC Customer country when order review is update
	 */
	public static function checkout_country_update( $post_data ) {			
		$country = isset( $_POST['country'] ) ? $_POST['country'] : '';
		
		if ( isset( $_POST['s_country'] ) && ! wc_ship_to_billing_address_only() && get_option('wc_price_based_country_based_on', 'billing') == 'shipping' ) {			
			$country = $_POST['s_country'];
		}

		if ( $country && ! in_array( $country , WCPBZIP()->customer->countries ) ) {			
			WCPBZIP()->customer->set_country( $country );
		}
	}
}

WCPBC_Frontend::init();