<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Country Selector Widget
 *
 * @author   OscarGare
 * @category Widgets 
 * @version  1.5.5
 * @extends  WC_Widget
 */
class WCPBC_Widget_Country_Selector extends WC_Widget {

	/**
	 * @var string
	 */
	private static $_other_countries_text = '';

	/**
	 * Constructor
	 */
	public function __construct() {		
		$this->widget_description = __( 'A country switcher for your store.', 'wc-price-based-country' );
		$this->widget_id          = 'wcpbc_country_selector';
		$this->widget_name        = __( 'WooCommerce Country Switcher', 'wc-price-based-country' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Country', 'wc-price-based-country' ),
				'label' => __( 'Title', 'wc-price-based-country' )
			),
			'other_countries_text'  => array(
				'type'  => 'text',
				'std'   => __( 'Other countries', 'wc-price-based-country' ) ,
				'label' => __( 'Other countries text', 'wc-price-based-country' )
			)
		);

		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 */
	function widget( $args, $instance ) {				

		self::$_other_countries_text = isset( $instance['other_countries_text']) ? $instance['other_countries_text'] : $this->settings['other_countries_text']['std'] ;

		add_filter('wcpbc_other_countries_text', array( __CLASS__, 'get_other_countries_text') );

		$this->widget_start( $args, $instance );						

		do_action('wcpbc_manual_country_selector');

		$this->widget_end( $args );
	}

	/**
	 * Get other countries text
	 * @return string
	 */
	public static function get_other_countries_text( $value ) {
		return self::$_other_countries_text;
	}
	
}

?>