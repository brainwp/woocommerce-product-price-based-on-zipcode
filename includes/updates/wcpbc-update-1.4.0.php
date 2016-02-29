<?php
/**
 * Update WCPBC to 1.4.0
 *
 * @author 		OscarGare
 * @category 	Admin
 * @version     1.4.0
 */

if ( ! defined( 'ABSPATH' ) )  exit; // Exit if accessed directly

global $wpdb;

if ( $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}icl_translations'")  ) {

	$regions = get_option( 'wc_price_based_country_regions' );

	if ( ! $regions ) {
		$regions = array();
	}

	$custom_fields = array('_price', '_regular_price', '_sale_price', '_price_method');	
	$meta_keys = array();

	foreach ( array_keys( $regions ) as $key ) {
		foreach ( $custom_fields as $custom_field ) {
			
			$meta_keys[] = '_' . $key . $custom_field;
			$meta_keys[] = '_' . $key . '_variable' . $custom_field;
		}
	}

	if ( count( $meta_keys ) ) {

		$sql = "SELECT trid, meta_key, meta_value FROM {$wpdb->postmeta} INNER JOIN {$wpdb->prefix}icl_translations ON post_id = trid AND element_type = 'post_product' AND NOT source_language_code IS NULL";
		$sql .= " WHERE meta_value <>'' AND meta_key in ( '" . implode("', '" , $meta_keys ) . "')";

		$rows = $wpdb->get_results( $sql );

		foreach ($rows as $row) {			
			update_post_meta( $row->trid, $row->meta_key, $row->meta_value);
		}
	}	
}

?>