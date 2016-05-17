/* global wcpbc_regions_keys */
jQuery(document).ready(function($){

	var wcpbc_wpml_lock_fields = {

		lock_input : function(input_name, disable_attr ) {		
			for(var i = 0; i<wcpbc_regions_keys.length; i++ ) {	
				$('input[name^="_'+wcpbc_regions_keys[i]+input_name+'"]').attr(disable_attr, disable_attr);
	        	$('input[name^="_'+wcpbc_regions_keys[i]+input_name+'"]').after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
	        }
		},

		init: function(){
			this.lock_input('_regular_price', 'readonly');
			this.lock_input('_sale_price', 'readonly');
			this.lock_input('_price_method', 'disabled');

			var that = this;

			$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function() {
				that.lock_input('_variable_regular_price', 'readonly');
				that.lock_input('_variable_sale_price', 'readonly');
				that.lock_input('_variable_price_method', 'disabled');
			});
			
		}
	};

	wcpbc_wpml_lock_fields.init();	
			
});