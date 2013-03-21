<div class="wrap">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2>WooCommerce Multilingual</h2>
	
	<table class="widefat general_options_table">
        <thead>
            <tr>
                <th><?php echo __('General options', 'wpml-wcml') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <p>
					<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="general_options">
						<ul id="general_options">
						
							<li><label for="multi_currency">Enable multi-currency: </label>
							<input type="checkbox" name="multi_currency" id="multi_currency" value="yes" <?php if(get_option('icl_enable_multi_currency') == 'yes'){ echo 'checked'; } ?> /></li>
							
							<li><input type="radio" name="currency_converting_option[]" id="currency_converting_option" value="1" <?php if(get_option('currency_converting_option') == '1'){ echo 'checked'; } ?>> <label for="currency_converting_option"><?php echo __('Automatically calculate pricing in different currencies, based on the exchange rate', 'wpml-wcml'); ?></label></li>
							
							<li><input type="radio" name="currency_converting_option[]" id="currency_converting_option_2" value="2" <?php if(get_option('currency_converting_option') == '2'){ echo 'checked'; } ?>> <label for="currency_converting_option_2"><?php echo __('I will manage the pricing in each currency myself', 'wpml-wcml'); ?></label></li>
							
							<input type='submit' name="general_options" value='<?php echo __('Save', 'wpml-wcml'); ?>' class='button-secondary' />
							<?php wp_nonce_field('general_options', 'general_options_nonce'); ?>
						</ul>
					</form>
					</p>
				</td>
			</tr>
		</tbody>
	</table>	
	
	<?php if(get_option('icl_enable_multi_currency') == 'yes'){ ?>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php if (!isset($_GET['edit'])): ?>Add currency<?php else: ?>Edit currency<?php endif; ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <p>
                       	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="add_currency_form">
							<ul id="add_currency">
								<li>
									<?php
									if(!isset($_GET['edit'])){
									?>
									<label for="language">Language: </label>
									<select name="language" id="language" class="lang_selector_width">
									<?php
									}
									
										if(isset($_GET['edit']) && $_GET['edit'] == $_GET['edit']){
											global $wpdb;
									
											$update_id = $_GET['edit'];
									
											$sql = "SELECT * FROM ". $wpdb->prefix ."icl_currencies WHERE id = '$update_id'";
											$currency = $wpdb->get_results($sql, OBJECT);
									
											$currency_id = $currency[0]->id;
											$currency_code = $currency[0]->code;
											$currency_exchange_rate = $currency[0]->value;
											$currency_language_code = $currency[0]->language_code;
										}
										
									if(!isset($_GET['edit'])){
										global $sitepress;
																			
										$languages = $sitepress->get_active_languages();
										//$path_to_flags = ICL_PLUGIN_URL . '/res/flags/';
										foreach($languages as $lang_code => $language){
											/*if(isset($_GET['edit'])){
												$selected = ($currency_language_code == $lang_code) ? $selected = 'selected' : $selected = null;
												
												echo "<option value=\"". $language['code'] ."\" $selected>". $language['english_name'] ."</option>\r\n";
											} else {
											*/
												echo "<option value=\"". $language['code'] ."\">". $language['english_name'] ."</option>\r\n";
											//}
										}
										
										?>                      
									</select>
									<?php
									}
									?>
								</li>   
							
								<li><label for="currency_code">Currency code: </label>
								<input type="text" name="currency_code" id="currency_code" maxlength="3" size="3" value="<?php if(isset($_GET['edit'])){ echo $currency_code; } ?>" /> <i><?php _e('3 letter code, ie: GBP', 'wpml-wcml'); ?></i></li>
								
								<li><label for="exchange_rate">Exchange rate: </label>
								<input type="text" name="exchange_rate" id="exchange_rate" maxlength="10" size="10" value="<?php if(isset($_GET['edit'])){ echo $currency_exchange_rate; } ?>" /></li>
								
								<?php if(isset($_GET['edit'])){ ?>
									<input type="text" name="currency_id" id="currency_id" maxlength="5" size="10" value="<?php echo $currency_id; ?>" style="display: none;" /></li>
								<?php } ?>
							</ul>
							
							<input type='submit' name="add_currency" value='<?php if(isset($_GET['edit'])){ echo __('Update', 'wpml-wcml'); } else { echo __('Add', 'wpml-wcml'); } ?>' class='button-secondary' />
							<?php wp_nonce_field('add_currency', 'add_currency_nonce'); ?>
					</form>
					</p>
                </td>
            </tr>
        </tbody>
    </table>
	<?php
	global $wpdb;
			
	$sql = "SELECT * FROM ". $wpdb->prefix ."icl_currencies";
	$result = $wpdb->get_results($sql);
			
	if($result){
	?>
	<h3><?php echo __('Manage currencies', 'wpml-wcml'); ?></h3>
	<table class="widefat">
	<thead>
		<tr>
			<th class="manage_table_th_width"><?php echo __('Language', 'wpml-wcml'); ?></th>
			<th class="manage_table_th_width"><?php echo __('Currency code', 'wpml-wcml'); ?></th>
			<th class="manage_table_th_width"><?php echo __('Exchange rate', 'wpml-wcml'); ?></th>
			<th class="manage_table_th_width"><?php echo __('Changed', 'wpml-wcml'); ?></th>
			<th><!-- spacer --></th>
			<th><!-- spacer --></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th><?php echo __('Language', 'wpml-wcml'); ?></th>
			<th><?php echo __('Currency code', 'wpml-wcml'); ?></th>
			<th><?php echo __('Exchange rate', 'wpml-wcml'); ?></th>
			<th><?php echo __('Changed', 'wpml-wcml'); ?></th>
			<th><!-- spacer --></th>
			<th><!-- spacer --></th>
		</tr>
	</tfoot>
	<tbody>
			<?php
			global $sitepress;
			
			$sql = "SELECT * FROM ". $wpdb->prefix ."icl_currencies ORDER BY `id` DESC";
			$currencies = $wpdb->get_results($sql, OBJECT);
			
			foreach($currencies as $key => $currency){
				$language = $sitepress->get_language_details($currency->language_code);
				echo "<tr>";
			
				echo "<td><!--<img src=\"". ICL_PLUGIN_URL ."/res/flags/".  $currency->language_code .".png\" width=\"18\" height=\"12\" class=\"flag_img\" />-->". $language['english_name'] ."</td>\r\n";
				echo "<td>". $currency->code ."</td>\r\n";
				echo "<td>". $currency->value ."</td>\r\n";
				echo "<td>". $currency->changed ."</td>\r\n";
				
				echo "<td><a href=\"". admin_url('admin.php?page=wpml-wcml&edit='. $currency->id) ."\" title=\"Edit\"><img src=\"". WCML_PLUGIN_URL ."/assets/images/edit.png\" width=\"16\" height=\"16\" alt=\"Edit\" /></a></td>\r\n";
				echo "<td><a href=\"". admin_url('admin.php?page=wpml-wcml&delete='. $currency->id) ."\" title=\"Delete\"><img src=\"". WCML_PLUGIN_URL ."/assets/images/delete.png\" width=\"16\" height=\"16\" alt=\"Delete\" /></a></td>\r\n";
				
				echo "</tr>";
			}
			?>
	</tbody>
	</table>
<?php
	}
?>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#add_currency_form').validate({
			rules: {
				currency_code: {
					required: true
				},
					
				exchange_rate: {
					required: true,
					number: true
				}
			},
				
			messages: {
				currency_code: "Please fill the currency code field.",
				exchange_rate: "Please enter the correct exchange rate."
			}
		});
	});
</script>
<?php
	}
?>