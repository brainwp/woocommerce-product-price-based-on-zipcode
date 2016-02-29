<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="settings-panel">	
	<h3><?php _e( 'Region Data', 'wc-price-based-country' ); ?></h3>
	<table class="form-table">

		<!-- Region name -->
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="name"><?php _e( 'Region Name', 'wc-price-based-country' ); ?></label>
				<?php //echo $tip; ?>
			</th>
	        <td class="forminp forminp-text">
	        	<input name="name" id="name" type="text" value="<?php echo esc_attr( $region['name'] ); ?>"/> 
	        	<?php //echo $description; ?>
	        </td>
		</tr>

		<!-- Country multiselect -->			
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="zipcodes"><?php _e( 'ZipCodes (separated by commas)', 'wc-price-based-country' ); ?></label>
			</th>
			<td class="forminp">
				<textarea name="zipcodes"><?php echo esc_textarea( $region['zipcodes'] ); ?></textarea>
			</td>
		</tr>

		<!-- Currency select -->			
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="currency"><?php _e( 'Currency', 'woocommerce' ); ?></label>
				<?php //echo $tip; ?>
			</th>
			<td class="forminp forminp-select">
				<select name="currency" id="currency" class="chosen_select">
					<?php
						foreach ( get_woocommerce_currencies() as $code => $name ) {
							echo '<option value="' . esc_attr( $code ) . '" ' . selected( $region['currency'], $code ) .'>' . $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')' . '</option>';
						}
					?>
				</select>
			</td>
		</tr>
		

		<!-- Exchange rate -->			
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="exchange_rate"><?php _e( 'Exchange Rate', 'wc-price-based-country' ); ?></label>
				<img class="help_tip" data-tip="<?php echo esc_attr( __( "For each product, if select autocalculate, product's price will be the result of multiplying the default price by this exchange rate.", 'wc-price-based-country' ) ); ?>" src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
			</th>
	        <td class="forminp forminp-text">                	
	        	1 <?php echo get_option( 'woocommerce_currency' );	?> = <input name="exchange_rate" id="exchange_rate" type="text" class="short wc_input_decimal" value="<?php echo wc_format_localized_decimal( $region['exchange_rate'] ); ?>"/> 
	        	<?php //echo $description; ?>
	        </td>
		</tr>

	</table>

	<?php do_action( 'wc_price_based_country_admin_region_fields', $region ); ?>	
	
	<input type="hidden" name="page" value="wc-settings" />
	<input type="hidden" name="tab" value="wc_price_based_country" />
	<input type="hidden" name="section" value="regions" />	
				
	<p class="submit">
		<?php submit_button( __( 'Save Changes', 'woocommerce' ), 'primary', 'update_region', false ); ?>
		<?php if ( ! is_null($region_key) ) { ?>
		<a style="color: #a00; text-decoration: none; margin-left: 10px;" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'remove_region' => $region_key ), admin_url( 'admin.php?page=wc-settings&tab=price-based-country&section=regions' ) ), 'wc-price-based-country-remove-region' ) ); ?>"><?php _e( 'Remove region', 'wc-price-based-country' ); ?></a>
		<input type="hidden" name="edit_region" value="<?php echo $region_key ?>" />		
		<?php } else { ?>
		<input type="hidden" name="add_region" value="1" />				
		<?php } ?>
	</p>

</div>