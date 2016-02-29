<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WCPBC_Product_Price class.
 *
 * @class 		WCPBC_Product_Price
 * @version		1.5.0
 * @author 		oscargare
 */
class WCPBC_Product_Price {
	
	private static $current_product_id;

	/**
	 * Hook actions and filters
	 */
	public static function init(){
		global $wp_version;
		
		/* Currency */
		add_filter( 'woocommerce_currency',  array( __CLASS__ , 'currency' ) );

		/* WC_Product */
		add_filter( 'woocommerce_get_price', array( __CLASS__ , 'get_price' ), 10, 2 );

		add_filter( 'woocommerce_get_regular_price', array( __CLASS__ , 'get_regular_price') , 10, 2 );

		add_filter( 'woocommerce_get_sale_price', array( __CLASS__ , 'get_sale_price') , 10, 2 );								

		add_filter( 'woocommerce_adjust_non_base_location_prices', array( __CLASS__, 'adjust_non_base_location_prices' ) );

		/* WC_Product_Variable */
		add_filter( 'woocommerce_get_variation_prices_hash', array( __CLASS__ , 'get_variation_prices_hash' ), 10, 3 );
		
		add_filter( 'woocommerce_variation_prices_price', array( __CLASS__ , 'get_price' ), 10, 3 );

		add_filter( 'woocommerce_variation_prices_regular_price', array( __CLASS__ , 'get_regular_price' ), 10, 3 );

		add_filter( 'woocommerce_variation_prices_sale_price', array( __CLASS__ , 'get_sale_price' ), 10, 3 );
		
		/* WC_Product_Variation */		
		add_filter( 'woocommerce_get_variation_price', array( __CLASS__ , 'get_variation_price' ), 10, 4 );		

		add_filter( 'woocommerce_get_variation_regular_price', array( __CLASS__ , 'get_variation_regular_price' ), 10, 4 );	

		add_filter( 'woocommerce_get_variation_sale_price', array( __CLASS__ , 'get_variation_sale_price' ), 10, 4 );				
		
		/* WC_Product_Grouped */
		add_filter( 'woocommerce_get_price_html', array( __CLASS__ , 'get_grouped_price_html' ), 10, 2 );
		
		/* Flat rate shipping */
		add_filter( 'woocommerce_flat_rate_shipping_add_rate', array( __CLASS__ , 'flat_rate_shipping_conversion' ), 30, 3 );
		
		/* Widget Price Filter */
		add_filter( 'woocommerce_price_filter_results', array( __CLASS__ , 'price_filter_results' ), 10, 3 );

		add_filter( 'woocommerce_price_filter_widget_min_amount', array( __CLASS__ , 'price_filter_widget_min_amount' ) );

		add_filter( 'woocommerce_price_filter_widget_max_amount', array( __CLASS__ , 'price_filter_widget_max_amount' ) );				
		
		/* Products on sale */
		add_filter( 'pre_transient_wc_products_onsale', array( __CLASS__ , 'product_ids_on_sale' ), 10, ( version_compare( $wp_version, '4.4', '<' ) ? 1 : 2 ) );
	}
	
	/**
	 * Return currency
	 * @return string currency
	 */
	public static function currency( $currency ) {

		$_currency = $currency;

		if ( WCPBZIP()->customer->currency ) {
			$_currency = WCPBZIP()->customer->currency;
		}

		return $_currency;
	}		
	
	/**
	 * Returns WCPBC price.
	 * @return string price
	 */
	protected static function wcpbc_get_price( $meta_key_preffix, $price_type, $post_id, $price ){
		
		$wcpbc_price = $price;
		
		$price_method = get_post_meta( $post_id, $meta_key_preffix . '_price_method', true ); 

		if ( $price_method === 'manual') {

			$wcpbc_price = get_post_meta( $post_id, $meta_key_preffix . $price_type, true );

		} elseif ( WCPBZIP()->customer->exchange_rate && !empty( $price ) ) {

				$wcpbc_price = ( $price * WCPBZIP()->customer->exchange_rate );							
		}
		
		return $wcpbc_price;
	}
	
	/**
	 * Returns the product price.
	 * @param decimal $price
	 * @param WC_Product $product
	 * @param string $price_type
	 * @return string
	 */
	protected static function get_product_price( $price, $product, $price_type ) {	
		
		$wcpbc_price = $price;					
		
		if ( WCPBZIP()->customer->group_key && apply_filters( 'wc_price_based_country_get_product_price', true, $product ) ) {

			$meta_key_preffix = '_' . WCPBZIP()->customer->group_key;

			if ( get_class( $product ) == 'WC_Product_Variation' ) {
				
				$post_id = $product->variation_id;	

				$meta_key_preffix .= '_variable';
				
			} else {
				$post_id = $product->id; 
			}
			
			$wcpbc_price = self::wcpbc_get_price( $meta_key_preffix, $price_type, $post_id, $price );			
		}

		return $wcpbc_price;
	}

	/**
	 * Returns the product's price.
	 * @return string price
	 */
	public static function get_price( $price, $product, $parent = NULL) {	
		/**
		 * Store product Id for later use in filter "woocommerce_adjust_non_base_location_prices", 
		 * $product must be a parameter in this filter
		 */		
		self::$current_product_id = $product->id;

		return self::get_product_price( $price, $product, '_price');
	}

	/**
	 * Returns the product's regular price.
	 * @return string price
	 */
	public static function get_regular_price($price, $product, $parent = NULL) {					
		return self::get_product_price( $price, $product, '_regular_price');
	}	
	
	/**
	 * Returns the product's sale price
	 * @return string price
	 */
	public static function get_sale_price( $price, $product, $parent = NULL ) {			
		return self::get_product_price( $price, $product, '_sale_price');
	}
	
	/**
     * Stop base taxes being taken off when dealing with out of base locations
     * @param bool $adjust    
     * @return bool
     */   
    public static function adjust_non_base_location_prices( $adjust ){        
        if ( $meta_key_preffix = WCPBZIP()->customer->group_key ) {        	       
            $adjust = ( get_post_meta( self::$current_product_id , '_' . $meta_key_preffix . '_price_method', true ) !== 'manual' );
        }   
        return $adjust;
    }

	/**
	 * Returns unique cache key to store variation child prices
	 * @param array $hash
	 * @param WC_Product $product
	 * @param  bool $display
	 * @return array
	 */
	public static function get_variation_prices_hash( $hash, $product, $display ) {			
		
		if ( WCPBZIP()->customer->group_key ) {

			$hash[] = 'wcpbc_region_key_' . WCPBZIP()->customer->group_key;
			$hash[] = 'wcpbc_exchange_rate_' . WCPBZIP()->customer->exchange_rate;
		}

		return $hash;
	}
	
	/**
	 * Get the min or max variation active price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string price
	 */		
	public static function get_variation_price( $price, $product, $min_or_max, $display, $price_type = '_price' ) {		
		
		$wcpbc_price = $price;	
		
		if ( WCPBZIP()->customer->group_key ) {

			$variation_id = get_post_meta( $product->id, '_' . WCPBZIP()->customer->group_key . '_' . $min_or_max . $price_type . '_variation_id', true );

			if ( $variation_id ) {

				$variation = $product->get_child( $variation_id );

				if ( $variation) {

					$price_function = 'get' . $price_type;

					$wcpbc_price = $variation->$price_function();

					if ( $display ) {
						$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
						$wcpbc_price      = $tax_display_mode == 'incl' ? $variation->get_price_including_tax( 1, $wcpbc_price ) : $variation->get_price_excluding_tax( 1, $wcpbc_price );
					}
				}

			} elseif( $wcpbc_price && WCPBZIP()->customer->exchange_rate && $price_type !== '_price') {

				$wcpbc_price = $wcpbc_price * WCPBZIP()->customer->exchange_rate;
			}		
		}

		return $wcpbc_price;
	}	
	
	/**
	 * Get the min or max variation regular price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string price
	 */
	public static function get_variation_regular_price( $price, $product, $min_or_max, $display ) {		
		
		return self::get_variation_price( $price, $product, $min_or_max, $display, '_regular_price' );
	}		

	/**
	 * Get the min or max variation sale price.
	 * @param  string $min_or_max - min or max
	 * @param  boolean  $display Whether the value is going to be displayed
	 * @return string price
	 */
	public static function get_variation_sale_price( $price, $product, $min_or_max, $display ) {		
		
		return self::get_variation_price( $price, $product, $min_or_max, $display, '_sale_price' );
	}	
	
	/**
	 * Returns the price in html format for product grouped	 	
	 * @param string $price
	 * @param WC_Product_Grouped $product
	 * @return string
	 */
	public static function get_grouped_price_html( $price, $product ) {
		
		if ( get_class( $product ) == 'WC_Product_Grouped' && WCPBZIP()->customer->group_key ) {	
		
			$price = '';
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$child_prices     = array();
			$meta_key_preffix = '_' . WCPBZIP()->customer->group_key;
			
			foreach ( $product->get_children() as $child_id ){			
				$child_prices[] = self::wcpbc_get_price( $meta_key_preffix, '_price', $child_id, get_post_meta( $child_id, '_price', true ) );
			}			 

			$child_prices     = array_unique( $child_prices );
			$get_price_method = 'get_price_' . $tax_display_mode . 'uding_tax';

			if ( ! empty( $child_prices ) ) {
				$min_price = min( $child_prices );
				$max_price = max( $child_prices );
			} else {
				$min_price = '';
				$max_price = '';
			}

			if ( $min_price ) {
				if ( $min_price == $max_price ) {
					$display_price = wc_price( $product->$get_price_method( 1, $min_price ) );
				} else {
					$from          = wc_price( $product->$get_price_method( 1, $min_price ) );
					$to            = wc_price( $product->$get_price_method( 1, $max_price ) );
					$display_price = sprintf( _x( '%1$s&ndash;%2$s', 'Price range: from-to', 'woocommerce' ), $from, $to );
				}

				$price .= $display_price . $product->get_price_suffix();			
			}
		}
		
		return $price;		
	}
	
	/**
     * Apply currency conversion to flat rate shipping method
     * @param WC_Shipping_Flat_Rate $method
     * @param array $rate     
     * @param array $packages          
     */
    public static function flat_rate_shipping_conversion( $method, $rate, $packages ) {       

        if ( WCPBZIP()->customer->exchange_rate && WCPBZIP()->customer->exchange_rate != '1' && get_option('wc_price_based_shipping_conversion') === 'yes' ) {           
            for( $i=0; $i< count($method->rates); $i++ ) {
				if ( in_array( $method->rates[$i]->method_id, array( 'flat_rate', 'international_delivery' ) ) ) {
					$method->rates[$i]->cost = $method->rates[$i]->cost * WCPBZIP()->customer->exchange_rate;                                                   
				}
            }           
        }       
    }
	
	/**
	 * Return matched produts where price between min and max
	 *
	 * @param array $matched_products_query
	 * @param int $min 
	 * @param int $max
	 * @return array
	 */
	public static function price_filter_results( $matched_products_query, $min, $max ){		
		
		if ( WCPBZIP()->customer->group_key && WCPBZIP()->customer->exchange_rate )	{
			
			global $wpdb;

			$_price_method = '_' . WCPBZIP()->customer->group_key . '_price_method';
			$_price = '_' . WCPBZIP()->customer->group_key . '_price';

			$sql = $wpdb->prepare('SELECT DISTINCT ID, post_parent, post_type FROM %1$s 
					INNER JOIN %2$s wc_price ON ID = wc_price.post_id and wc_price.meta_key = "_price" AND wc_price.meta_value != ""
					LEFT JOIN %2$s price_method ON ID = price_method.post_id and price_method.meta_key = "%3$s"
					LEFT JOIN %2$s price ON ID = price.post_id and price.meta_key = "%4$s" AND price.meta_value != ""
					WHERE post_type IN ( "product", "product_variation" )
					AND post_status = "publish"					
					AND IF(IFNULL(price_method.meta_value, "exchange_rate") = "exchange_rate", wc_price.meta_value * %5$s, price.meta_value + 0) BETWEEN %6$d AND %7$d'
			, $wpdb->posts, $wpdb->postmeta, $_price_method, $_price, WCPBZIP()->customer->exchange_rate, $min, $max);

			$matched_products_query = $wpdb->get_results( $sql, OBJECT_K );						
		}

		return $matched_products_query;
	}

	/**
	 * Return de min and max value of product prices
	 *
	 * @param string $min_or_max
	 * @param double $amount
	 * @return double
	 */	
	private static function _price_min_amount( $amount, $min_or_max = 'min' ) {				

		global $wpdb;

		if ( WCPBZIP()->customer->group_key && WCPBZIP()->customer->exchange_rate ) {		

			$cache_key = md5( json_encode( array(						
				'wcpbc_amount',
				$min_or_max,
				WCPBZIP()->customer->group_key,
				WCPBZIP()->customer->exchange_rate,
				implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ),				
			) ) ) . WC_Cache_Helper::get_transient_version( 'product' );			

			if ( false === ( $amount = get_transient( $cache_key ) ) ) {					

				$_price_method = '_' . WCPBZIP()->customer->group_key . '_price_method';
				$_price = '_' . WCPBZIP()->customer->group_key . '_price';

				$sql = 'SELECT ' . $min_or_max .'( IF(IFNULL(price_method.meta_value, "exchange_rate") = "exchange_rate", wc_price.meta_value * %1$s, price.meta_value + 0) ) as amount
						FROM %2$s posts
						INNER JOIN %3$s wc_price ON ID = wc_price.post_id and wc_price.meta_key = "_price" AND wc_price.meta_value != ""
						LEFT JOIN %3$s price_method ON ID = price_method.post_id and price_method.meta_key = "%4$s"
						LEFT JOIN %3$s price ON ID = price.post_id and price.meta_key = "%5$s" AND price.meta_value != ""
						WHERE post_type IN ( "product", "product_variation" ) AND post_status = "publish"';

				if ( count( WC()->query->layered_nav_product_ids )!== 0 ) {

					$sql .= ' AND ( ';
					$sql .= ' posts.ID IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')';
					$sql .= ' OR ( posts.post_parent IN (' . implode( ',', array_map( 'absint', WC()->query->layered_nav_product_ids ) ) . ')';
					$sql .= ' AND posts.post_parent != 0 )';
					$sql .= ' )';
				}

				$sql = $wpdb->prepare( $sql, WCPBZIP()->customer->exchange_rate, $wpdb->posts, $wpdb->postmeta, $_price_method, $_price);								

				$amount = $wpdb->get_var( $sql);

				set_transient( $cache_key, $amount, HOUR_IN_SECONDS );
			}
		}

		return $amount;
	}
	
	/** 
	 * Filter for price_filter_widget_min_amount
	 * @param $amount Min amount
	 */
	 public static function price_filter_widget_min_amount( $amount ) {	 
	 	return floor( self::_price_min_amount( $amount ) );	 	
	 }

	 /** 
	 * Filter for price_filter_widget_max_amount
	 * @param $amount Max amount
	 */
	 public static function price_filter_widget_max_amount( $amount ) {	 
	 	 	return ceil( self::_price_min_amount( $amount, 'max' ) );
	 }	 
	 
	/**
	 * Returns an array containing the IDs of the products that are on sale. Filter through get_transient
	 * @return array
	 */
	public static function product_ids_on_sale( $value, $transient = false ) {
		global $wpdb;

		if ( WCPBZIP()->customer->group_key ) {

			$cache_key = 'wcpbc_products_onsale_' . WCPBZIP()->customer->group_key;
			
			// Load from cache
			$product_ids_on_sale = get_transient( $cache_key );

			// Valid cache found
			if ( false !== $product_ids_on_sale ) {			
				return $product_ids_on_sale;
			}
			
			$_price_method = '_' . WCPBZIP()->customer->group_key . '_price';
			$_variable_price_method = '_' . WCPBZIP()->customer->group_key . '_variable_price';
			$_price = '_' . WCPBZIP()->customer->group_key . '_price';				
			$_variable_price = '_' . WCPBZIP()->customer->group_key . '_variable_price';
			$_sale_price = '_' . WCPBZIP()->customer->group_key . '_sale_price';
			$_variable_sale_price = '_' . WCPBZIP()->customer->group_key . '_variable_sale_price';
			
			
			$sql = $wpdb->prepare( '
				SELECT posts.ID, posts.post_parent FROM %1$s AS posts 
				LEFT JOIN %2$s AS wc_price ON wc_price.post_id = posts.ID AND wc_price.meta_key = "_price"
				LEFT JOIN %2$s AS wc_sale_price ON wc_sale_price.post_id = posts.ID AND wc_sale_price.meta_key = "_sale_price"
				LEFT JOIN %2$s AS price_method ON price_method.post_id = posts.ID AND price_method.meta_key in ("%3$s" , "%4$s")
				LEFT JOIN %2$s AS price ON price.post_id = posts.ID AND price.meta_key in ("%5$s" , "%6$s")
				LEFT JOIN %2$s AS sale_price ON sale_price.post_id = posts.ID AND sale_price.meta_key in ("%7$s" , "%8$s")
				WHERE posts.post_type IN ( "product", "product_variation" ) AND posts.post_status = "publish"
				AND ( IF(IFNULL(price_method.meta_value, "exchange_rate") = "exchange_rate", (IFNULL(wc_price.meta_value, 0) + 0) * %9$s, IFNULL(price.meta_value, 0) + 0) ) != 0
				AND	( IF(IFNULL(price_method.meta_value, "exchange_rate") = "exchange_rate", (IFNULL(wc_price.meta_value, 0) + 0) * %9$s, IFNULL(price.meta_value, 0) + 0) ) = 
					( IF(IFNULL(price_method.meta_value, "exchange_rate") = "exchange_rate", (IFNULL(wc_sale_price.meta_value, 0) + 0) * %9$s, IFNULL(sale_price.meta_value, 0) + 0) )
			', $wpdb->posts, $wpdb->postmeta, $_price_method, $_variable_price_method, $_price, $_variable_price, $_sale_price, $_variable_sale_price, WCPBZIP()->customer->exchange_rate ) ;
			
			$on_sale_posts = $wpdb->get_results( $sql );
			
			$product_ids_on_sale = array_unique( array_map( 'absint', array_merge( wp_list_pluck( $on_sale_posts, 'ID' ), array_diff( wp_list_pluck( $on_sale_posts, 'post_parent' ), array( 0 ) ) ) ) );

			set_transient( $cache_key, $product_ids_on_sale, DAY_IN_SECONDS * 30 );

			return $product_ids_on_sale;

		} else {
			return false;
		}
	}	
}

WCPBC_Product_Price::init();