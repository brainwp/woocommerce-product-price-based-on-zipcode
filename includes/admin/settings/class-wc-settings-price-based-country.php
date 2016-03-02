<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Settings_Price_Based_Country' ) ) :

/**
 * WC_Settings_Price_Based_Country
 *
 * WooCommerce Price Based Country settings page
 * 
 * @version		1.5.2
 * @author 		oscargare
 */
class WC_Settings_Price_Based_Country extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'price-based-country';
		$this->label = __( 'Price Based on Country', 'wc-price-based-zipcode' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );			
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );		

		//table list row actions
		self::regions_list_row_actions();
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Settings', 'woocommerce' ),
			'regions'     => __( 'Regions', 'wc-price-based-zipcode' )		
		);

		return apply_filters( 'wc_price_based_country_get_sections', $sections );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters( 'wc_price_based_country_settings', array(
			array(
				'title' => __( 'General Options', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'general_options'
			),

			array(
				'title'    => __( 'Price Based On', 'wc-price-based-zipcode' ),
				'desc'     => __( 'This controls which address is used to refresh products prices on checkout.' ),
				'id'       => 'wc_price_based_country_based_on',
				'default'  => 'billing',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',				
				'desc_tip' =>  true,
				'options'  => array(
					'billing'      => __( 'Customer billing country', 'wc-price-based-zipcode' ),
					'shipping' => __( 'Customer shipping country', 'wc-price-based-zipcode' )
				)
			),
			
			array(
				'title'    => __( 'Shipping', 'wc-price-based-zipcode' ),
				'desc' 		=> __( 'Enabled currency conversion to "Flat Rate" And "International Flat Rate"', 'wc-price-based-zipcode' ),
				'id' 		=> 'wc_price_based_shipping_conversion',
				'default'	=> 'no',
				'type' 		=> 'checkbox'				
			),

			array(
				'type' => 'sectionend',
				'id' => 'general_options'
			),

			array( 
				'title' => __( 'Test Mode', 'wc-price-based-zipcode' ), 
				'type' => 'title', 
				'desc' => 'If you want to check that prices are shown successfully, enable test mode and enter the Country which you want to do the test.', 
				'id' => 'price_based_country_test'
			),

			array(
				'title' => __( 'Enabled/Disabled', 'wc-price-based-zipcode' ),
				'desc' 		=> __( 'Enabled Test Mode', 'wc-price-based-zipcode' ),
				'id' 		=> 'wc_price_based_country_test_mode',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __('If test mode is enabled, a demo store notice will be displayed .')
			),

			array(
				'title' => __( 'Test country', 'wc-price-based-zipcode' ),					
				'id' 		=> 'wc_price_based_country_test_country',				
				'default'	=> wc_get_base_location(),
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	WC()->countries->countries
			),
			
			array( 'type' => 'sectionend', 'id' => 'price_based_country_test' )
		));

		return $settings;
	}
	
	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;
		
		self::display_donate_notice();	//display de donate notice
		
		if ( 'regions' == $current_section ) {										
			self::regions_output();
		} else {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;
		
		if( $current_section == 'regions' && ( isset( $_GET['edit_region'] ) || isset( $_GET['add_region'] ) ) ) {			
			self::regions_save();
			
		} elseif( $current_section == 'regions' && isset( $_POST['action'] ) && $_POST['action'] == 'remove' && isset( $_POST['region_key'] ) ) {
			self::regions_delete_bulk();
			
		} elseif( $current_section !== 'regions' ) {			
			//save settings				
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );										

			update_option( 'wc_price_based_country_timestamp', time() );	
		}		
	}
	
	/**
	 * Display donate notices
	 */
	private static function display_donate_notice() {

		if ( get_option('wc_price_based_country_hide_ads', 'no') == 'no' ) {

			global $pagenow;		
		
			if ( isset( $_GET['wc_price_based_country_donate_hide'] ) && $_GET['wc_price_based_country_donate_hide'] == 'true' ) {
				update_option('wc_price_based_country_hide_ads', 'yes');
			} else {
				?>
				<div class="updated">
					<p><strong>Donate to Price Based Country</strong></p>
					<p><?php _e('It is difficult to provide, support, and maintain free software. Every little bit helps is greatly appreciated!','wc-price-based-zipcode') ; ?></p>
					<p class="submit">
						<a class="button-primary" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NG75SHRLAX28L"><?php _e( 'Donate now', 'woocommerce' ); ?></a>
						<a class="skip button-secondary" href="<?php echo esc_url( add_query_arg( 'wc_price_based_country_donate_hide', 'true', admin_url( 'admin.php?page=wc-settings&tab=price-based-country' ) ) ); ?>">Don't show me again</a>
					</p>
		   		</div>
				<?php							
			}
		}		
	}
	
	/**
	 * Regions Page output
	 */
	private static function regions_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['add_region'] ) || isset( $_GET['edit_region'] ) ) {
			$region_key   = isset( $_GET['edit_region'] ) ? $_GET['edit_region'] : NULL;
			$region = self::get_regions_data( $region_key);
			include( 'views/html-regions-edit.php' );
		} else {
			self::regions_table_list_output();		
		}		
	}

	/**
	 * Regions table list output
	 */
	private static function regions_table_list_output() {
		
		include_once( WCPBZIP()->plugin_path() . 'includes/admin/class-wcpbc-admin-regions-table-list.php' );

		echo '<h3>' .  __( 'Regions', 'wc-price-based-zipcode' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=price-based-country&section=regions&add_region=1' ) ) . '" class="add-new-h2">' . __( 'Add Region', 'wc-price-based-zipcode' ) . '</a></h3>';

		 $keys_table_list = new WCPBC_Admin_Regions_Table_List();
		 $keys_table_list->prepare_items();
		 $keys_table_list->views();		
		 $keys_table_list->display();
	}

	/**
	 * Get region data
	 *
	 * @param  string $key
	 * @return array
	 */
	private static function get_regions_data( $key, $values = FALSE ) {	

		$region = apply_filters( 'wc_price_based_country_default_region_data', array(
			'name'        			=> '',
			'zipcodes'       		=> '',
			'currency'   			=> get_option('woocommerce_currency'),
			'empty_price_method'   	=> '',
			'exchange_rate' 		=> '1'
		));

		$regions = get_option( 'wc_price_based_country_regions', array() );	

		if ( array_key_exists($key, $regions) ) {
			$region = $regions[$key];
		}

		if ( is_array($values) ) {
			 $region = array_intersect_key( $values, $region);			
			 $region['exchange_rate'] = isset( $region['exchange_rate'] ) ? wc_format_decimal($region['exchange_rate']) : 0;
		}

		return $region;
	}		
	
	/**
	 * Get a unique slug that indentify a region
	 *
	 * @param  string $new_slug
	 * @param  array $slugs
	 * @return array
	 */
	private static function get_unique_slug( $new_slug, $slugs ){				
		
		$seqs = array();

		foreach ( $slugs as $slug ) {
			$slug_parts = explode( '-', $slug, 2 );
			if ( $slug_parts[0] == $new_slug && ( count( $slug_parts ) == 1 || is_numeric( $slug_parts[1] ) ) ) {
				$seqs[] = isset( $slug_parts[1] ) ? $slug_parts[1] : 0;
			}			
		}		
		
		if ($seqs ) {
			rsort($seqs);					
			$new_slug = $new_slug .'-' . ( $seqs[0]+1 );		
		}

		return $new_slug;
	}
	
	/**
	 * Validate region data
	 * @param array $fields
	 * @return boolean
	 */
	private static function validate_region_fields( $fields ) {
		
		$valid = false;
		
		if ( empty( $fields['name'] ) ) {
			WC_Admin_Settings::add_error( __( 'Group name is required.', 'wc-price-based-zipcode' ) );

		} elseif ( ! isset( $fields['zipcodes'] ) || empty( $fields['zipcodes'] ) ) {
			WC_Admin_Settings::add_error( __( 'Add at least one ZipCode to the list.', 'wc-price-based-zipcode' ) );

		} else {
			$valid = true;
		}
		
		return apply_filters( 'wc_price_based_country_admin_region_fields_validate', $valid, $fields );
	}
	
	/**
	 * Save region
	 */
	private static function regions_save() {											

		$region_key   = isset( $_GET['edit_region'] ) ? wc_clean( $_GET['edit_region'] ) : NULL;

		$region = self::get_regions_data($region_key, $_POST );
		
		if ( self::validate_region_fields( $region ) ) {

			$regions = get_option( 'wc_price_based_country_regions', array() );			

		 	if (is_null($region_key)) {
		 		$region_key = self::get_unique_slug( sanitize_title( $region['name']), array_keys( $regions ) );
		 	}
		 	$regions[$region_key] = $region;

		 	update_option( 'wc_price_based_country_regions', $regions );			

		 	update_option( 'wc_price_based_country_timestamp', time() );

		 	$_GET['edit_region'] = $region_key;
		}		
				
	}

	/**
	 * Regions table list row actions
	 */
	private static function regions_list_row_actions(){
		if ( isset( $_GET['remove_region'] ) && 
			 isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && 
			 isset( $_GET['tab'] ) && 'price-based-country' == $_GET['tab'] && 
			 isset( $_GET['section'] ) && 'regions' == isset( $_GET['section'] ) 
			) {

			self::regions_delete();				
		}
	}

	/**
	 * Delete region
	 */
	private static function regions_delete() {
		
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc-price-based-country-remove-region' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$region_key = wc_clean( $_GET['remove_region'] );		
		$regions = get_option( 'wc_price_based_country_regions', array() );		

		if ( isset($regions[$region_key]) ) {	

			unset($regions[$region_key]);			
			self::regions_delete_post_meta($region_key);
			
			update_option( 'wc_price_based_country_regions', $regions );			
			update_option( 'wc_price_based_country_timestamp', time() );

			WC_Admin_Settings::add_message( __( 'Region have been deleted.', 'wc-price-based-zipcode' ) );
		}					
	}

	/**
	 * Bulk delete regions
	 */
	private static function regions_delete_bulk() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
		}

		$region_keys = wc_clean( $_POST['region_key'] );
		$regions = get_option( 'wc_price_based_country_regions', array() );		

		foreach ($region_keys as $region_key) {			
			if ( isset( $regions[$region_key] ) ) {			
				unset($regions[$region_key]);
				self::regions_delete_post_meta($region_key);
			}			
		}		

		update_option( 'wc_price_based_country_regions', $regions );
		update_option( 'wc_price_based_country_timestamp', time() );			
	}
	
	/**
	 * Delete postmeta data 
	 */
	private static function regions_delete_post_meta( $region_key ) {
		global $wpdb;
		foreach ( wcpbc_get_product_meta_keys( $region_key ) as $meta_key ) {
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ) );	
		}		
	}
}

endif;

return new WC_Settings_Price_Based_Country();
