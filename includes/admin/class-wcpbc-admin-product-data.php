<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WCPBC_Admin_Product_Data 
 *
 * @class 		WCPBC_Admin_Product_Data
 * @version		1.4.2
 * @author 		oscargare
 * @category	Class
 */
class WCPBC_Admin_Product_Data {

	/**
	 * Hook in methods
	 */
	public static function init() {

		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_options_countries_prices' ) );
		
		add_action( 'woocommerce_process_product_meta_simple', array( __CLASS__, 'process_product_simple_countries_prices' ) ) ;						

		add_action( 'woocommerce_process_product_meta_external', array( __CLASS__, 'process_product_simple_countries_prices' ) ) ;						
		
		add_action( 'woocommerce_product_after_variable_attributes', array( __CLASS__, 'product_variable_attributes_countries_prices') , 10, 3 );				
		
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_product_variation_countries_prices' ), 10, 2 );

		add_action( 'woocommerce_variable_product_sync', array( __CLASS__, 'variable_product_sync' ), 10, 2 );		
	}


	/**
	 * Add price input to product simple metabox
	 */
	public static function product_options_countries_prices() {					

		foreach ( WCPBZIP()->get_regions() as $key => $value ) {	

			$_regular_price = get_post_meta( get_the_ID(), '_' . $key . '_regular_price' , true );
			$_sale_price = get_post_meta( get_the_ID(), '_' . $key . '_sale_price' , true );

			$_price_method = empty($_regular_price) ? 'exchange_rate' : 'manual';	
			$_display = $_price_method == 'exchange_rate' ? 'none' : 'block';

			?>
				<div class="options_group show_if_simple show_if_external">					

					<?php
						woocommerce_wp_radio(
							array(
								'id' => '_' . $key . '_price_method',
								'value' => $_price_method,
								'class' => 'wcpbc_price_method',
								'label' => __( 'Price for', 'wc-price-based-country' )  . ' ' . $value['name']. ' (' . get_woocommerce_currency_symbol( $value['currency'] ) . ')',								
								'options' => array(
									'exchange_rate' => __('Calculate price by applying exchange rate.', 'wc-price-based-country'),
									'manual' => __('Set price manually.', 'wc-price-based-country')
								)
							)
						);
					?>										
					
					<div style="display:<?php echo $_display; ?>">
						<p class="form-field">
							<label><?php echo __( 'Regular Price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol($value['currency']) . ')'; ?></label>
							<input type="text" id="<?php echo '_' . $key . '_regular_price'; ?>" name="<?php echo '_' . $key . '_regular_price'; ?>" value="<?php echo wc_format_localized_price( $_regular_price ); ?>" class="short wc_input_price" placeholder="" />
						</p>

						<p class="form-field">								
							<label><?php echo __( 'Sale Price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol($value['currency']) . ')'; ?></label>
							<input type="text" id="<?php echo '_' . $key . '_sale_price'; ?>" name="<?php echo '_' . $key . '_sale_price'; ?>" value="<?php echo wc_format_localized_price( $_sale_price ); ?>" class="short wc_input_price wcpbc_sale_price" />
						</p>
					</div>

				</div>
				
			<?php		
		}								
	}

	
	/**
	 * Save meta data product simple
	 */
	public static function process_product_simple_countries_prices( $post_id, $i = false, $variable = '' ) {				
		
		foreach ( WCPBZIP()->get_regions() as $key => $value ) {
			
			$key_regular_price = '_' . $key . $variable . '_regular_price';
			$key_sale_price = '_' . $key . $variable . '_sale_price';			
			$key_price = '_' . $key . $variable . '_price';						
			$key_price_method = '_' . $key . $variable . '_price_method';
			

			if ( $i === false ) {
				
				$_regular_price = $_POST[$key_regular_price];
				$_sale_price 	= $_POST[$key_sale_price];
				$_price_method 	= $_POST[$key_price_method];	

			} else {

				$_regular_price = $_POST[$key_regular_price][$i];
				$_sale_price 	= $_POST[$key_sale_price][$i];
				$_price_method 	= $_POST[$key_price_method][$i];		
			}

			$_price_method = ( $_price_method == 'exchange_rate' || empty( $_regular_price ) ) ? 'exchange_rate' : 'manual';

			if ( $_price_method == 'exchange_rate') {

				$_regular_price = '';
				$_sale_price 	= '';
				$_price 		= '';

			} else {

				$_regular_price = wc_format_decimal( $_regular_price );
				$_sale_price 	= wc_format_decimal( $_sale_price );
				// Update price if on sale
				$_price 		= ( '' !== $_sale_price ) ? $_sale_price : $_regular_price;								
			}				

			update_post_meta( $post_id, $key_regular_price,  $_regular_price );							
			update_post_meta( $post_id, $key_sale_price, $_sale_price );				
			update_post_meta( $post_id, $key_price, $_price );							
			update_post_meta( $post_id, $key_price_method,  $_price_method );							
			
		}	
		
	}
	
	/**	
	 * Add price input to product variation metabox
	 */
	public static function product_variable_attributes_countries_prices( $loop, $variation_data, $variation ) {							

		foreach ( WCPBZIP()->get_regions() as $key => $value) {

			$_regular_price = wc_format_localized_price( get_post_meta( $variation->ID, '_' . $key . '_variable_regular_price', true) );
			$_sale_price = wc_format_localized_price( get_post_meta( $variation->ID, '_' . $key . '_variable_sale_price', true) );

			$_empty_method = empty($_regular_price) ? 'exchange_rate' : 'manual';
			$_display = $_empty_method == 'exchange_rate' ? 'none' : 'block';					

			?>
				<div style="width:100%; overflow:auto; padding-right:10px;border-top:1px solid #eee;">
					
					<p class="form-row form-row-first"><strong><?php echo __( 'Price for', 'wc-price-based-country' )  . ' ' . $value['name'] . ' (' . get_woocommerce_currency_symbol( $value['currency'] ) . ')'; ?></strong></p>

					<div class="form-row form-row-last <?php echo '_' . $key . '_variable_price_method_' . $loop . '_field'; ?>">
						<ul>
							<li style="padding:0;">
								<label>
									<input name="<?php echo '_' . $key . '_variable_price_method[' . $loop . ']'; ?>" value="exchange_rate" class="wcpbc_price_method" <?php echo ($_empty_method == 'exchange_rate' ? 'checked="checked"':'');?> type="radio">
									<?php _e('Calculate price by applying exchange rate.', 'wc-price-based-country') ?>
								</label>
							</li>
							<li style="padding:0;">
								<label>
									<input name="<?php echo '_' . $key . '_variable_price_method[' . $loop . ']'; ?>" value="manual" class="wcpbc_price_method" <?php echo ($_empty_method != 'exchange_rate' ? 'checked="checked"':'');?>type="radio">
									<?php _e('Set price manually.', 'wc-price-based-country') ?>
								</label>
							</li>
						</ul>
					</div>

					<div style="display:<?php echo $_display;?>">
						<p class="form-row form-row-first">
							<label><?php echo __( 'Regular Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol( $value['currency'] ) . ')'; ?></label>
							<input type="text" size="5" id="<?php echo '_' . $key . '_variable_regular_price_' . $loop; ?>" name="<?php echo '_' . $key . '_variable_regular_price[' . $loop. ']'; ?>" value="<?php if ( isset( $_regular_price ) ) echo esc_attr( $_regular_price ); ?>" class="wc_input_price" />
						</p>
						<p class="form-row form-row-last">
							<label><?php echo __( 'Sale Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol( $value['currency'] ) . ')'; ?></label>
							<input type="text" size="5" id="<?php echo '_' . $key . '_variable_sale_price_' . $loop; ?>" name="<?php echo '_' . $key . '_variable_sale_price[' . $loop. ']'; ?>" value="<?php if ( isset( $_sale_price ) ) echo esc_attr( $_sale_price ); ?>" class="wc_input_price wcpbc_sale_price" />
						</p>
					</div>
				</div>

			<?php			
		}
	}	
	
	/**
	 * Save meta data product variation
	 */
	public static function save_product_variation_countries_prices( $variation_id, $i ) {	

		self::process_product_simple_countries_prices( $variation_id, $i, '_variable');		
	}

	/**
	 * Sync product variation prices with parent
	 */
	public static function variable_product_sync( $product_id, $children ) {		
		
		foreach ( WCPBZIP()->get_regions() as $region_key => $region ) {
			
			// Main active prices
			$min_price            = null;
			$max_price            = null;
			$min_price_id         = null;
			$max_price_id         = null;

			// Regular prices
			$min_regular_price    = null;
			$max_regular_price    = null;
			$min_regular_price_id = null;
			$max_regular_price_id = null;

			// Sale prices
			$min_sale_price       = null;
			$max_sale_price       = null;
			$min_sale_price_id    = null;
			$max_sale_price_id    = null;

			foreach ( array( 'price', 'regular_price', 'sale_price' ) as $price_type ) {								

				foreach ( $children as $child_id ) {				

					$child_price_method = get_post_meta( $child_id, '_' . $region_key . '_variable_price_method', true );

					if ( $child_price_method !== 'manual' ) {

						$child_price = get_post_meta( $child_id, '_' . $price_type, true );					
						$child_price = ( !empty( $child_price ) && $region['exchange_rate'] ) ? ( $region['exchange_rate'] * $child_price ) : $child_price;							

					} else{

						$child_price = get_post_meta( $child_id, '_' . $region_key . '_variable_' . $price_type, true );					
					}					

					// Skip non-priced variations
					if ( $child_price === '' ) {
						continue;
					}

					// Skip hidden variations
					if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
						$stock = get_post_meta( $child_id, '_stock', true );
						if ( $stock !== "" && $stock <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
							continue;
						}
					}

					// Find min price
					if ( is_null( ${"min_{$price_type}"} ) || $child_price < ${"min_{$price_type}"} ) {
						${"min_{$price_type}"}    = $child_price;
						${"min_{$price_type}_id"} = $child_id;
					}

					// Find max price
					if ( is_null( ${"max_{$price_type}"} ) || $child_price > ${"max_{$price_type}"} ) {
						${"max_{$price_type}"}    = $child_price;
						${"max_{$price_type}_id"} = $child_id;
					}
				}
			
				// Store ids
				update_post_meta( $product_id, '_' . $region_key . '_min_' . $price_type . '_variation_id', ${"min_{$price_type}_id"} );
				update_post_meta( $product_id, '_' . $region_key . '_max_' . $price_type . '_variation_id', ${"max_{$price_type}_id"} );
			}			
		}		
	}
}

WCPBC_Admin_Product_Data::init();