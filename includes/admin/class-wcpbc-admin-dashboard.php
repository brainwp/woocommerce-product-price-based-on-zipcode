<?php
/**
 * Price Based Country Admin Dashboard
 *
 * @author      OscarGare
 * @category    Admin 
 * @version     1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WCPBC_Admin_Dashboard' ) ) :

/**
 * WCPBC_Admin_Dashboard Class
 */
class WCPBC_Admin_Dashboard {

	/**
	 * @var array
	 */
	protected $query = NULL;

	/**
	 * @var array
	 */
	protected $active_currency = NULL;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_dashboard_status_widget_sales_query', array( $this, 'default_currency_sales_query' ) );				
		add_filter( 'wc_price_args', array( $this, 'add_currency_to_wc_price_args') );
		add_filter( 'woocommerce_reports_get_order_report_query', array( $this, 'order_report_query' ) );
		add_action( 'woocommerce_after_dashboard_status_widget', array( $this, 'dashboard_status_widget' ) );
	}
	
	/**
	 * Return sales month by currency
	 * @param string $currency
	 * @return number
	 */
	protected function get_sales_month( $currency ) {
		global $wpdb;

		$sales = 0;

		if ( ! is_null($this->query) ) {
			$query = $this->query;
			$query['where'] .= "AND meta__order_currency.meta_value='{$currency}' ";

			$sales = $wpdb->get_var( implode( ' ', $query ) );
		}

		return $sales;
	}

	/**
	 * If exists a active_currency add this currency to wc_price args
	 * @return array
	 */
	public function add_currency_to_wc_price_args( $wc_price_args ) {	
		if ( ! is_null( $this->active_currency ) ) {
			$wc_price_args['currency'] = $this->active_currency;
		}

		return $wc_price_args;
	}

	/**
	 * Add to query default currency filter
	 * @param array $query
	 * @return array
	 */ 
	public function default_currency_sales_query( $query ){
		global $wpdb;		
		//Add post_meta order currency		
		$query['join']	.= " INNER JOIN {$wpdb->postmeta} AS meta__order_currency ON posts.ID = meta__order_currency.post_id AND meta__order_currency.meta_key='_order_currency'";

		//save the currency to use in action
		$this->query = $query;

		//Add default currency filter
		$default_currency = wcpbc_get_base_currency();
		$query['where']	.= "AND meta__order_currency.meta_value='{$default_currency}' ";

		return $query;
	}

	/**
	 * Add to query active currency filter
	 * @param array $query
	 * @return array
	 */ 
	public function order_report_query( $query ) {
		global $wpdb;		
		
		$currency = is_null( $this->active_currency ) ? wcpbc_get_base_currency() : $this->active_currency;

		$query['join']	.= " INNER JOIN {$wpdb->postmeta} AS meta__order_currency ON posts.ID = meta__order_currency.post_id AND meta__order_currency.meta_key='_order_currency'";				
		$query['where']	.= "AND meta__order_currency.meta_value='{$currency}' ";

		return $query;
	}

	/**
	 * Show sales this month per installed currency
	 * @param object $reports
	 */
	public function dashboard_status_widget( $reports ) {

		 foreach ( wcpbc_get_installed_currencies() as $currency ) {

		 	$sales = $this->get_sales_month( $currency );
		 	
		 	$this->active_currency = $currency;

		 	?>
		 	<li class="sales-this-month">
				<a href="<?php echo admin_url( 'admin.php?page=wc-reports&tab=orders&range=month' ); ?>">
					<?php echo $reports->sales_sparkline( '', max( 7, date( 'd', current_time( 'timestamp' ) ) ) ); ?>
					<?php printf( __( "<strong>%s</strong> sales this month", 'woocommerce' ), wc_price( $sales, array( 'currency' => $currency ) ) ); ?>
				</a>
			</li>
		 	<?php

		 	$this->active_currency = NULL;
		 }
	}
}

endif;

return new WCPBC_Admin_Dashboard();