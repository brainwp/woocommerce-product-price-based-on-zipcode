<?php
/**
 * Update WCPBC to 1.5.0
 *
 * @author 		OscarGare
 * @category 	Admin
 * @version     1.5.0
 */

if ( ! defined( 'ABSPATH' ) )  exit; // Exit if accessed directly

/**
 * Increments the transient version to invalidate cache. Fix incorrect chache key for product prices in variable products
 */
WC_Cache_Helper::get_transient_version( 'product', true );

/**
 * Add a new options
 */
update_option('wc_price_based_country_based_on', 'billing');

update_option('wc_price_based_shipping_conversion', 'no');

/**
 * Show ads
 */ 
update_option('wc_price_based_country_hide_ads', 'no' );

?>