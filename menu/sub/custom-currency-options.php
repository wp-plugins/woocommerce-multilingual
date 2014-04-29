<?php 
    $currency_name =  $wc_currencies[$code];
    $currency_symbol = get_woocommerce_currency_symbol($code);
?>
<table id="wcml_currency_options_<?php echo $code ?>" class="wcml_currency_options_popup">
    <tr>
        <td>
            <h4><?php printf(__('Currency options for %s', 'wpml-wcml'), '<strong>' . $currency_name . ' (' . $currency_symbol . ')</strong>') ?></h4>
            <hr />
            <table>
            
                <tr>
                    <td align="right"><?php _e('Exchange Rate', 'wpml-wcml') ?></td>
                    <td>
                        <?php printf("1 %s = %s %s", $wc_currency, '<input name="currency_options[' . $code . '][rate]" type="number" style="width:50px" step="0.01" value="' . $currency['rate'] .  '" />', $code) ?>
                    </td>        
                </tr>
                <tr>   
                    <td>&nbsp;</td>
                    <td><small><i><?php printf(__('Set on %s', 'wpml-wcml'), date('F j, Y, H:i', strtotime($currency['updated']))); ?></i></small></td>
                </tr>
            
                <tr>    
                    <td colspan="2"><hr /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Currency Position', 'wpml-wcml') ?></td>
                        <td>
                                <select name="currency_options[<?php echo $code ?>][position]">
                                    <option value="left" <?php selected('left', $currency['position'], 1); ?>><?php 
                                        echo $post_str['left'] = sprintf(__('Left (%s99.99)', 'wpml-wcml'), 
                                        $currency_symbol); ?></option>
                                    <option value="right" <?php selected('right', $currency['position'], 1); ?>><?php 
                                        echo $post_str['right'] = sprintf(__('Right (99.99%s)', 'wpml-wcml'), 
                                        $currency_symbol); ?></option>
                                    <option value="left_space" <?php selected('left_space', $currency['position'], 1); ?>><?php 
                                        echo $post_str['left_space'] = sprintf(__('Left with space (%s 99.99)', 'wpml-wcml'), 
                                        $currency_symbol); ?></option>
                                    <option value="right_space" <?php selected('right_space', $currency['position'], 1); ?>><?php 
                                        echo $post_str['right_space'] = sprintf(__('Right with space (99.99 %s)', 'wpml-wcml'), 
                                        $currency_symbol); ?></option>
                                </select>
                        </td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Thousand Separator', 'wpml-wcml') ?></td>
                    <td><input name="currency_options[<?php echo $code ?>][thousand_sep]" type="text" style="width:50px;" value="<?php echo esc_attr($currency['thousand_sep']) ?>" /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Decimal Separator', 'wpml-wcml') ?></td>
                    <td><input name="currency_options[<?php echo $code ?>][decimal_sep]" type="text" style="width:50px;" value="<?php echo esc_attr($currency['decimal_sep']) ?>" /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Number of Decimals', 'wpml-wcml') ?></td>
                    <td><input name="currency_options[<?php echo $code ?>][num_decimals]" type="number" style="width:50px;" value="<?php echo esc_attr($currency['num_decimals']) ?>" min="0" step="1" /></td>
                </tr>  
                
                <tr>    
                    <td colspan="2"><hr /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Rounding to the nearest integer', 'wpml-wcml') ?></td>
                    <td>    
                        <select name="currency_options[<?php echo $code ?>][rounding]">
                            <option value="disabled" <?php selected('disabled', $currency['rounding']) ?> ><?php _e('disabled', 'wpml-wcml') ?></option>
                            <option value="up" <?php selected('up', $currency['rounding']) ?>><?php _e('up', 'wpml-wcml') ?></option>
                            <option value="down" <?php selected('down', $currency['rounding']) ?>><?php _e('down', 'wpml-wcml') ?></option>
                            <option value="down" <?php selected('nearest', $currency['rounding']) ?>><?php _e('nearest', 'wpml-wcml') ?></option>
                        </select>
                    </td>
                </tr>  
                <tr>
                    <td align="right"><?php _e('Increment for nearest integer', 'wpml-wcml') ?></td>
                    <td>    
                        <select name="currency_options[<?php echo $code ?>][rounding_increment]">
                            <option value="1" <?php selected('1', $currency['rounding_increment']) ?> >1</option>
                            <option value="10" <?php selected('10', $currency['rounding_increment']) ?>>10</option>
                            <option value="100" <?php selected('100', $currency['rounding_increment']) ?>>100</option>
                            <option value="1000" <?php selected('1000', $currency['rounding_increment']) ?>>1000</option>
                        </select>
                    </td>
                </tr>                  
                <tr>
                    <td align="right"><?php _e('Autosubtract amount', 'wpml-wcml') ?></td>
                    <td>   
                        <input name="currency_options[<?php echo $code ?>][auto_subtract]" value="<?php echo $currency['auto_subtract'] ?>" type="number" value="0" style="width:50px;" />
                    </td>
                </tr>                  
            </table>            
            
        </td>
    </tr>
    
    
    <tr>
        <td colspan="2" align="right">
            <input type="button" class="button-secondary currency_options_cancel" value="<?php esc_attr_e('Cancel', 'wpml-wcml') ?>" data-currency="<?php echo $code ?>" />&nbsp;
            <input type="submit" class="button-primary currency_options_save" value="<?php esc_attr_e('Save', 'wpml-wcml') ?>" data-currency="<?php echo $code ?>" />
            <br /><br />
        </td>
    </tr>
</table>
