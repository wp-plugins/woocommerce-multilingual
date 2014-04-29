<?php if(!isset($template_data['empty_bundles'])){  ?>
    <td>
	<button id="prod_bundles_link_<?php echo $lang ?>" class="button-secondary js-table-toggle prod_bundles_link<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" data-text-opened="<?php _e('Collapse2','wpml-wcml'); ?>" data-text-closed="<?php _e('Expand2','wpml-wcml'); ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>>
	    <span><?php _e('Expand2','wpml-wcml'); ?></span>
	    <i class="icon-caret-down"></i>
	</button>
	<?php 	$bundles_ids = $template_data['product_bundles']; ?>

	<table id="prod_bundles_<?php echo $lang ?>" class="widefat prod_variations js-table">
	    <tbody>
	        <tr>
	            <?php //if($template_data['original']): ?>
	                <td></td>
	                <?php if(!isset($template_data['empty_bundles'])): ?>
	                    <?php foreach($template_data['bundles_data'] as $bundle_original_title=>$bundle_opts): ?>
	                        <td>
	                            <?php echo $bundle_original_title; ?>
	                        </td>
	                    <?php endforeach; ?>
	                <?php endif; ?>
	            <?php //endif; ?>
	        </tr>
	        <?php if(isset($template_data['empty_bundles'])): ?>
	            <tr>
	                <td><?php _e('Please set bundles for product','wpml-wcml'); ?></td>
	            </tr>
	        <?php elseif(isset($template_data['empty_translation'])): ?>
	            <tr>
	                <td><?php _e('Please save translation before translate bundles texts','wpml-wcml'); ?></td>
	            </tr>
	        <?php else: ?>
	            <?php $texts = array('bundle_title','bundle_desc'); ?>
	            <?php foreach($texts as $text): ?>
	                <tr>
	                    <td>
	                        <?php if($text == 'bundle_title'): ?>
	                            <?php _e('Title','wpml-wcml');  ?>
	                        <?php else: ?>
	                            <?php _e('Description','wpml-wcml'); ?>
	                        <?php endif; ?>
	                    </td>
	                    <?php $i = 0; ?>
	                    <?php foreach($template_data['bundles_data'] as $bundle_id=>$bundle_opts): ?>
	                        <?php if(!empty($bundle_opts)): ?>
	                        <td>
	                            <?php if($template_data['original']): ?>
	                                <input type="text" value="<?php echo $bundle_opts[$text]?>" readonly="readonly"/>
	                            <?php else: ?>
	                                <input type="text" name="bundles[<?php echo $bundles_ids[$i] ?>][<?php echo $text; ?>]" value="<?php echo $bundle_opts[$text]?>" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
	                            <?php endif; ?>
	                        </td>
	                        
	                        <?php endif; ?>
	                        <?php $i++; ?>
	                    <?php endforeach; ?>
	                </tr>
	                
	            <?php endforeach; ?>
	        <?php endif; ?>
	    </tbody>
	</table>
    </td>
<?php } ?>