<?php
/**
 * WC Product Price Based Country Functions
 *
 * General functions available on both the front-end and admin.
 *
 * @author 		oscargare
 * @version     1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get WooCommerce customer country	 
 *
 * @return string
 */
function wcpbc_get_woocommerce_zipcode() {
	if ( is_admin() ) {
		return;
	}
	$code = WC()->customer->get_postcode();	

	if ( $code !== WC()->customer->get_shipping_postcode() && WC()->customer->get_shipping_postcode() ) {
		$code = WC()->customer->get_shipping_postcode();	
	}		

	if ( $code ) {
		return $code;
	}
}

/**
 * Set WooCommerce customer country
 *
 * @param string $country	 		 
 */
function wcpbc_set_woocommerce_country( $country ) {
	
	$ship_to_different_address = get_option( 'woocommerce_ship_to_destination' ) === 'shipping' ? 1 : 0;

	if ( 
		WC()->customer->get_country() !== WC()->customer->get_shipping_country() && 
		'shipping' === get_option('wc_price_based_country_based_on', 'shipping') && 
		'1' == apply_filters( 'woocommerce_ship_to_different_address_checked', $ship_to_different_address ) 
		) 
	{
		WC()->customer->set_shipping_country( $country );
	} else {
		WC()->customer->set_country( $country );
		WC()->customer->set_shipping_country( $country );
	}

}
	
/**
 * Get custom plugin product fields for a region
 *
 * @param  string $region_key
 * @return array
 */
function wcpbc_get_product_meta_keys( $region_key ){

	$custom_fields = array();
	
	foreach ( array( '_price', '_regular_price','_sale_price', '_price_method' ) as $field ) {

		$custom_fields[ $field ] = '_' . $region_key . $field;
		$custom_fields[ '_variable' . $field ] = '_' . $region_key . '_variable' . $field;

		if ( $field !== '_price_method' ) {
			foreach ( array('min', 'max') as $min_or_max ) {
				$custom_fields[ '_' . $min_or_max . $field . '_variation_id' ] = '_' . $region_key . '_' . $min_or_max . $field . '_variation_id';	
			}					
		}
	}	

	return $custom_fields;
}

/**
 * Function which handles the start and end of scheduled sales via cron. 
 */
function wcpbc_scheduled_sales() {	
	global $wpdb;

	$region_keys = array_keys( WCPBZIP()->get_regions() );	
	
	$sql =	"
		SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
		LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
		LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
		WHERE postmeta.meta_key = %s
		AND postmeta_2.meta_key in (%s, %s)
		AND postmeta_3.meta_key in (%s, %s)
		AND postmeta.meta_value > 0
		AND postmeta.meta_value < %s
		AND postmeta_2.meta_value != postmeta_3.meta_value
	";
	
	foreach ( $region_keys as $region_key ) {
		
		extract( wcpbc_get_product_meta_keys( $region_key ) );

		$current_time = current_time( 'timestamp' );
		
		// Sales which are due to start
		$product_ids = $wpdb->get_col( $wpdb->prepare( $sql, 
			'_sale_price_dates_from', 
			$_price, $_variable_price,
			$_sale_price, $_variable_sale_price,
			$current_time  
		));
		
		if ( $product_ids ) {			
			foreach ( $product_ids as $product_id ) {							
				if ( $sale_price = get_post_meta( $product_id, $meta_key_sale_price, true ) ) {
					update_post_meta( $product_id, $meta_key_price, $sale_price );
				}
			}
			
			delete_transient( 'wcpbc_products_onsale_' . $region_key );
		}
		
		// Sales which are due to end
		$product_ids = $wpdb->get_col( $wpdb->prepare( $sql, 
			'_sale_price_dates_to', 
			$_price, $_variable_price,
			$_sale_price, $_variable_sale_price,
			$current_time  
		));
		
		if ( $product_ids ) {			
			foreach ( $product_ids as $product_id ) {							
				$regular_price = get_post_meta( $product_id, $meta_key_regular_price, true );
				update_post_meta( $product_id, $meta_key_price, $regular_price );
				update_post_meta( $product_id, $meta_key_sale_price, '' );
			}
			
			delete_transient( 'wcpbc_products_onsale_' . $region_key );
		}
	}	
}
//add_action( 'woocommerce_scheduled_sales', 'wcpbc_scheduled_sales' );

/**
 * Clear all WCPBC transients cache for product data.
 *
 * @param int $post_id (default: 0)
 */
function wcpbc_delete_product_transients( $post_id = 0 ) {
	
	$transients_to_clear = array(
		'wcpbc_products_onsale_'
	);
	
	foreach ( array_keys( WCPBZIP()->get_regions() ) as $region_key ) {
		foreach ( $transients_to_clear as $transient ) {
			delete_transient( $transient . $region_key );
		}
	}
}
add_action( 'woocommerce_delete_product_transients', 'wcpbc_delete_product_transients' );

/**
 * Return base currency
 *
 * @retrun string
 */
function wcpbc_get_base_currency() {
	return get_option( 'woocommerce_currency');
}

/**
 * Return installed currencies
 *
 * @return array
 */
function wcpbc_get_installed_currencies() {
	
	$base_currency = wcpbc_get_base_currency();
	$installed_currencies = array();

	foreach (WCPBZIP()->get_regions() as $region) {
		
		if ( $base_currency !== $region['currency'] && ! in_array( $region['currency'], $installed_currencies ) ) {
			$installed_currencies[] = $region['currency'];
		}
	}

	return array_unique( apply_filters( 'wcpbc_installed_currencies', $installed_currencies ) );
}

/**
 * Return a a array with all currencies avaiables in WooCommerce with associate countries 
 *
 * @return array
 */
function wcpbc_get_currencies() {

	return array_unique( 
		apply_filters( 'wcpbc_currencies', 
			array(
				'AED' => array('AE'),
				'ARS' => array('AR'),
				'AUD' => array('AU', 'CC', 'CX', 'HM', 'KI', 'NF', 'NR', 'TV'),
				'BDT' => array('BD'),
				'BRL' => array('BR'),
				'BGN' => array('BG'),
				'CAD' => array('CA'),
				'CLP' => array('CL'),
				'CNY' => array('CN'),
				'COP' => array('CO'),
				'CZK' => array('CZ'),
				'DKK' => array('DK', 'FO', 'GL'),
				'DOP' => array('DO'),
				'EUR' => array('AD', 'AT', 'AX', 'BE', 'BL', 'CY', 'DE', 'EE', 'ES', 'FI', 'FR', 'GF', 'GP', 'GR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MC', 'ME', 'MF', 'MQ', 'MT', 'NL', 'PM', 'PT', 'RE', 'SI', 'SK', 'SM', 'TF', 'VA', 'YT'),
				'HKD' => array('HK'),
				'HRK' => array('HR'),
				'HUF' => array('HU'),
				'ISK' => array('IS'),
				'IDR' => array('ID'),
				'INR' => array('IN'),
				'NPR' => array('NP'),
				'ILS' => array('IL'),
				'JPY' => array('JP'),
				'KIP' => array('LA'),
				'KRW' => array('KR'),
				'MYR' => array('MY'),
				'MXN' => array('MX'),
				'NGN' => array('NG'),
				'NOK' => array('BV', 'NO', 'SJ'),
				'NZD' => array('CK', 'NU', 'NZ', 'PN', 'TK'),
				'PYG' => array('PY'),
				'PHP' => array('PH'),
				'PLN' => array('PL'),
				'GBP' => array('GB', 'GG', 'GS', 'IM', 'JE'),
				'RON' => array('RO'),
				'RUB' => array('RU'),
				'SGD' => array('SG'),
				'ZAR' => array('ZA'),
				'SEK' => array('SE'),
				'CHF' => array('LI'),
				'TWD' => array('TW'),
				'THB' => array('TH'),
				'TRY' => array('TR'),
				'UAH' => array('UA'),
				'USD' => array('BQ', 'EC', 'FM', 'IO', 'MH', 'PW', 'TC', 'TL', 'US', 'VG'),
				'VND' => array('VN'),
				'EGP' => array('EG')
			)
		)
	);
}
