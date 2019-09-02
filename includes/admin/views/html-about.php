<?php
/**
 * About page for WooCommerce Price Based Country 1.5.0
 *
 * @author		oscargare
 * @category	Admin 
 * @version		1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings_page = admin_url( 'admin.php?page=wc-settings&tab=price-based-country' );
$major_version = substr( WCPBZIP()->version, 0, 3 );
?>

<div class="wrap about-wrap">

	<h1><?php printf( __( 'Welcome to Price Based On Countries %s', 'wc-price-based-zipcode' ), $major_version ); ?></h1>
	
	<div class="about-text woocommerce-about-text">
		<?php _e( 'Thank you for installing the latest version of WooCommerce Product Price Based On Countries.', 'wc-price-based-zipcode' ); ?>
		<?php printf( __( 'Version %s is more powerful and secure than ever before. I hope you enjoy it!', 'wc-price-based-zipcode' ), $major_version ) ; ?>		
	</div>

	<p class="woocommerce-actions">
		<a href="<?php echo esc_url( $settings_page ); ?>" class="button button-primary"><?php _e( 'Settings', 'wc-price-based-zipcode' ); ?></a>
	</p>
	
	<div class="changelog">
	
		<h2><?php _e( "What's New", 'wc-price-based-zipcode' ); ?></h2>
		<hr/>
		
		<div class="feature-section three-col">
			<div class="col">
				<h3><?php _e( 'Price Based On Shipping Country', 'wc-price-based-zipcode' ); ?></h3>
				<p><?php printf( __( 'Now you can choose between products prices based on customer billing country or customer shipping country so improve integration with taxes settings. Enable this in the %ssettings%s.', 'wc-price-based-zipcode' ), '<a href="' . $settings_page . '">', '</a>' ); ?></p>
			</div>
			<div class="col">
				<h3><?php _e( 'Country Selector Widget', 'wc-price-based-zipcode' ); ?></h3>
				<p><?php printf( __( 'Add a country selector to store was very complex for non-developers in previous versions of WCPBC, I\'ve made simpler by adding a widget that allows add a country selector through Wordpress widgets interface. %sAdd a Country Selector Widget%s', 'wc-price-based-zipcode' ), '<a href="' . admin_url( 'widgets.php' ) . '">', '</a>' ) ; ?></p>	
			</div>
			<div class="col last-feature">
				<h3><?php _e( 'New Interface to Manage Regions', 'wc-price-based-zipcode' ); ?></h3>
				<p><?php _e( 'The new interface to manage regions is also built on WordPress list table.', 'wc-price-based-zipcode' ); ?></p>
			</div>						
		</div>

		<div class="feature-section two-col">		
			<div class="col">
				<h3><?php _e( 'Support to WooCommerce Products Widget', 'wc-price-based-zipcode' ); ?></h3>
				<p><?php _e( 'To improve integration with WooCommerce features I have added support to "Woocommerce Products Widget". Now you can add a list of products on sale to your store that lists different products depending on country.', 'wc-price-based-zipcode' ) ; ?></p>
			</div>
			<div class="col last-feature">
				<h3><?php _e( 'Shipping Currency Conversion', 'wc-price-based-zipcode' ); ?></h3>
				<p><?php _e( 'If you use Flat Rate and International Shipping you may have found did not apply a exchange rate. I have now introduced a automatically currency conversion for  Flat Rate and International Shipping.', 'wc-price-based-zipcode' ) ; ?></p>
			</div>
		</div>
				
	</div>
	
	<div class="return-to-dashboard">
		<a href="<?php echo esc_url( $settings_page ); ?>"><?php _e( 'Go to WooCommerce Price Based On Countries Settings', 'wc-price-based-zipcode' ); ?></a>		
	</div>
	
</div>