<?php
/**
 * WooCommerce Price Based Country Regions Table List
 *
 * @author   oscargare
 * @version  1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WCPBC_Admin_Regions_Table_List extends WP_List_Table {

	/**
	 * @var string
	 */
	private $base_currency = NULL;
	
	/**
	 * @var string
	 */
	private $default_region_key = NULL;

	/**
	 * Initialize the regions table list
	 */
	public function __construct() {
		
		$this->base_currency = get_option( 'woocommerce_currency' );
	
		$this->default_region_key = uniqid('wc_price_based_country_default_region_key_');
		
		parent::__construct( array(
			'singular' => __( 'Region', 'wc-price-based-country' ),
			'plural'   => __( 'Regions', 'wc-price-based-country' ),
			'ajax'     => false
		) );
	}

	/**
	 * Get list columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'		=> '<input type="checkbox" />',
			'name'		=> __( 'Region name', 'wc-price-based-country' ),
			'countries'	=> __( 'Countries', 'wc-price-based-country' ),
			'currency'	=> __( 'Currency', 'wc-price-based-country' )
		);
	}

	/**
	 * Column cb
	 *
	 * @param  array $region
	 * @return string
	 */
	public function column_cb( $region ) {
		if ( $region['key'] !== $this->default_region_key ) {
			return sprintf( '<input type="checkbox" name="region_key[]" value="%1$s" />', $region['key'] );
		} else{
			return '';
		}
	}

	/**
	 * Return name column
	 *
	 * @param  array $region
	 * @return string
	 */
	public function column_name( $region ) {		

		if ( $region['key'] !== $this->default_region_key ) {

			$url = admin_url( 'admin.php?page=wc-settings&tab=price-based-country&section=regions&edit_region=' . $region['key'] );		

			$output = '<strong>';
			$output .= '<a href="' . esc_url( $url ) . '" class="row-title">';
			if ( empty( $region['name'] ) ) {
				$output .= esc_html__( 'Region name', 'wc-price-based-country' );
			} else {
				$output .= esc_html( $region['name'] );
			}
			$output .= '</a>';
			$output .= '</strong>';			
			
			// Get actions
			$actions = array(
				'id'    => sprintf( 'Slug: %s', $region['key'] ),
				'edit'  => '<a href="' . esc_url( $url ) . '">' . __( 'View/Edit', 'woocommerce' ) . '</a>',
				'trash' => '<a class="submitdelete" title="' . esc_attr__( 'Remove region', 'wc-price-based-country' ) . '" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'remove_region' => $region['key'] ), admin_url( 'admin.php?page=wc-settings&tab=price-based-country&section=regions' ) ), 'wc-price-based-country-remove-region' ) ) . '">' . __( 'Remove region', 'wc-price-based-country' ) . '</a>'
			);

			$row_actions = array();

			foreach ( $actions as $action => $link ) {
				$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
			}

			$output .= '<div class="row-actions">' . implode(  ' | ', $row_actions ) . '</div>';

		} else {
			$output = '<strong>' . $region['name'] . '</strong>';
		}
		
		return $output;
	}

	/**
	 * Return countries column
	 *
	 * @param  array $row
	 * @return string
	 */
	public function column_countries( $region ) {

		$display = '';

		if ( is_array( $region['countries'] ) ) {

			$countries = array();
									
			foreach( $region['countries'] as $iso_code ) {
				$countries[] = WC()->countries->countries[$iso_code];										
			}
			$display = implode($countries, ', ');

		} else {

			$display = $region['countries'];
		}	
		
		return $display;	
	}

	/**
	 * Return currency column
	 *
	 * @param  array $row
	 * @return string
	 */
	public function column_currency( $region ) {
		$currencies = get_woocommerce_currencies();	

		$output = $currencies[$region['currency']] . ' (' . get_woocommerce_currency_symbol($region['currency']) . ') <br />';
		
		if ( $region['key'] == $this->default_region_key ) {
			$output .= '<span class="description">' . __( 'Default', 'wc-price-based-country' ) . '</span>';
		} else {
			$output .= '<span class="description">1 ' . $this->base_currency .' = ' . wc_format_localized_decimal( $region['exchange_rate'] ) . ' ' . $region['currency'] . '</span>';
		}
		return $output;
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'remove' => __( 'Remove region', 'wc-price-based-country' )
		);
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {

		$data = array(
			array(
				'key' 		=> $this->default_region_key,
				'name' 		=> 'Default Zone',
				'countries'	=> 'All countries not are included in other zones',
				'currency'	=> $this->base_currency
			)
		);		

		foreach ( get_option( 'wc_price_based_country_regions', array() ) as $key => $region) {
			$data[] = array_merge( array('key'=> $key), $region );
		}
		
		$columns = $this->get_columns();
  		$hidden = array();
  		$sortable = array();
  		

  		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->items = $data;		
	}
}

