<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WCPBC_Admin
 *
 * WooCommerce Price Based Country Admin 
 *
 * @class 		WCPBC_Admin
 * @version		1.5.0
 * @author 		oscargare
 * @category	Class
 */
class WCPBC_Admin {

	/**
	 * Hook actions and filters
	 */
	public static function init(){
		
		add_action( 'init', array( __CLASS__, 'includes' ) );
		
		add_action( 'init', array( __CLASS__, 'about_hooks' ) );
		
		add_action( 'current_screen', array( __CLASS__, 'dashboard_includes' ) );
		
		add_action( 'admin_init', array( __CLASS__, 'admin_redirects' ) );
		
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_script' ) );	

		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'settings_price_based_country' ) );					

		add_filter( 'woocommerce_currency',  array( __CLASS__, 'order_currency' ) );			
				
	}

	/**
	 * Include any classes we need within admin.
	 */
	public static function includes() {				

		include_once('class-wcpbc-admin-product-data.php');					

		if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {		
			include_once('class-wcpbc-admin-translation-management.php');					
		}
	}
	
	/**
	 * Add hooks to manage about page
	 */
	public static function about_hooks() {
		if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'wcpbc-about' ) {
			add_action( 'admin_menu', array( __CLASS__, 'create_about_page' ) );			
		}
	}
	
	/**
	 * Include admin files conditionally
	 */
	public static function dashboard_includes() {
		$screen = get_current_screen();

		if ( $screen->id == 'dashboard' ) {			
			include( 'class-wcpbc-admin-dashboard.php' );
		}
	}
	
	/**
	 * Handle redirects to welcome page after install and updates.
	 *
	 * Transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public static function admin_redirects() {
		if ( ! get_transient( '_wcpbc_activation_redirect' ) ) {
			return;
		}

		delete_transient( '_wcpbc_activation_redirect' );

		if ( ( ! empty( $_GET['page'] ) && $_GET['page'] === 'wcpbc-about' ) || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		
		wp_safe_redirect( admin_url( 'index.php?page=wcpbc-about' ) );
		exit;

	}
	
	/**
	 * Add Price Based Country settings tab to woocommerce settings
	 */
	public static function settings_price_based_country( $settings ) {

		$settings[] = include( 'settings/class-wc-settings-price-based-country.php' );

		return $settings;
	}	

	
	/**
	 * default currency in order
	 */
	public static function order_currency( $currency )	{

		global $post;

		if ($post && $post->post_type == 'shop_order' ) {
			
			global $theorder;
			if ( $theorder ) 
				return $theorder->order_currency;

		}
			
		return $currency;
	}	

	public static function load_admin_script( ) {	

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'wc-price-based-country-admin', WCPBZIP()->plugin_url() . 'assets/js/wcpbc-admin' . $suffix . '.js', array('jquery'), WCPBZIP()->version, true );		

	}
	
	/**
	 * Display the welcome/about page after successfully upgrading to the latest version.
	 *
	 * @since 1.5
	 */
	public static function create_about_page() {
		$about_page = add_dashboard_page( __( 'Welcome to WooCommerce Price Based On Countries', 'wc-price-based-zipcode' ), __( 'About WooCommerce Price Based On Countries', 'wc-price-based-zipcode' ), 'manage_options', 'wcpbc-about', array( __CLASS__, 'about_screen' ) );		
		add_action( 'admin_head', array( __CLASS__, 'remove_about_page_link' ) );
	}
	
	/**
	 * Output the about screen.
	 */
	public static function about_screen() {		
		include_once( 'views/html-about.php' );
	}
	
	/**
	 * Remove dashboard about page link.
	 */
	public static function remove_about_page_link() {
		remove_submenu_page( 'index.php', 'wcpbc-about' );
	}			

}

WCPBC_Admin::init();
