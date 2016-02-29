<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WCPBC_Admin_Translation_Management
 *
 * Translation Management for WooCommerce Price Based Country
 *
 * @class 		WCPBC_Admin_Translation_Management
 * @version		1.4.1
 * @author 		oscargare
 * @category	Class
 */
class WCPBC_Admin_Translation_Management {

	/**
	 * Get Custom plugin fields
	 */
	public static function custom_fields(){

		$custom_fields = array();

		$region_keys = array_keys( WCPBZIP()->get_regions() );

		foreach ( $region_keys as $key ) {

			foreach ( array( '_price', '_regular_price','_sale_price', '_price_method' ) as $field ) {

				$custom_fields[] = '_' . $key . $field;
				$custom_fields[] = '_' . $key . '_variable' . $field;

				if ( $field !== '_price_method' ) {
					foreach ( array('min', 'max') as $min_or_max ) {
						$custom_fields[] = '_' . $key . '_' . $min_or_max . $field . '_variation_id';	
					}					
				}
			}
		}

		return $custom_fields;
	}

	/**
	 *  Add customs fields to WPML Translation management 
	 */
	public static function add_custom_fields() {		
		//wpml_copy_from_original_custom_fields
		global $iclTranslationManagement;		

		$change = false;

		foreach ( self::custom_fields() as $field) {
			
			if ( ! isset( $iclTranslationManagement->settings['custom_fields_translation'][$field] ) ) {
							
				$iclTranslationManagement->settings['custom_fields_translation'][$field] = 1;	//copy

				$change = true;
			}
		}

		if ( $change ) {
			$iclTranslationManagement->save_settings();
		}
	}

	/**
	 * Enqueue scripts
	 */
	public static function wpml_scripts() {

		global $woocommerce_wpml, $pagenow;

		if ( isset($woocommerce_wpml) &&  is_object( $woocommerce_wpml ) && get_class($woocommerce_wpml) == 'woocommerce_wpml' ) {

			if( ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product' && !$woocommerce_wpml->products->is_original_product($_GET['post']) ) ||
            	($pagenow == 'post-new.php' && isset($_GET['source_lang']) && isset($_GET['post_type']) && $_GET['post_type'] == 'product') && 
            	! $woocommerce_wpml->settings['trnsl_interface'] ) {

				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	            wp_register_script('wcpbc-lock-fields', WCPBZIP()->plugin_url() . 'assets/js/wcpbc-admin-lock-fields' . $suffix . '.js', array('jquery'), WCPBZIP()->version, true );

	        	wp_localize_script( 'wcpbc-lock-fields', 'wcpbc_regions_keys', array_keys( WCPBZIP()->get_regions() ) );

        		wp_enqueue_script( 'wcpbc-lock-fields' );

        	}

		}
	}

	/**
	 *  Check WCML Multi currency	 
	 */	
	public static function check_wcml_multicurrency(){
		
		global $woocommerce_wpml;

		if ( isset($woocommerce_wpml) &&  is_object( $woocommerce_wpml ) && get_class($woocommerce_wpml) == 'woocommerce_wpml' && $woocommerce_wpml->settings['enable_multi_currency'] > 0 ) {
			add_action( 'admin_notices', array( __CLASS__, 'disable_wcml_multicurrency_notice' ) );			
		}
	}

	/**
	 * Diable WCML Multicurrency notice
	 */
	public static function disable_wcml_multicurrency_notice(){
		?>
		<div class="error">
			<p><?php _e( '<strong>WooCommerce Price Based Country incompatiblity found!</strong><br />WooCommerce Multilingual Multiple currencies is incompatible with WooCommerce Price Based on Country. While WooCommerce Multilingual Multiple currencies option is active can cause unexpected results. Go to <a href="' . admin_url( 'admin.php?page=wpml-wcml' ) . '">WooCommerce Multilingual settings page</a> and disables WooCommerce Multilingual Multi Currency option.', 'wc-price-based-country' ); ?></p>						
		</div>		
		<?php
	}

}

add_action( 'init', 'WCPBC_Admin_Translation_Management::add_custom_fields', 1510 );
add_action( 'admin_init', 'WCPBC_Admin_Translation_Management::check_wcml_multicurrency' );
add_action( 'admin_enqueue_scripts', 'WCPBC_Admin_Translation_Management::wpml_scripts' );