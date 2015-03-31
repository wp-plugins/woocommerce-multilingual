<button id="prod_variations_link_<?php echo $product_id ?>_<?php echo $lang ?>" class="button-secondary js-table-toggle<?php if($is_duplicate_product): ?> js-dup-disabled<?php endif;?>" data-text-opened="<?php esc_attr_e('Collapse','wpml-wcml'); ?>" data-text-closed="<?php esc_attr_e('Expand','wpml-wcml'); ?>"<?php if($is_duplicate_product): ?> disabled="disabled"<?php endif;?>>
    <span><?php _e('Expand','wpml-wcml'); ?></span>
    <i class="icon-caret-down"></i>
</button>

<table id="prod_variations_<?php echo $product_id ?>_<?php echo $lang ?>" class="widefat prod_variations js-table">
<?php if(isset($template_data['empty_variations'])): ?>
    <tr>
        <td><?php _e('Please add variations to product','wpml-wcml'); ?></td>
    <tr>
<?php else: ?>
    <tbody>
        <tr>
            <?php if($is_downloable): ?>
            <th></th>
            <?php endif; ?>
            <?php foreach($template_data['all_variations_ids'] as $variation_id): ?>
                <th>
                    <?php echo $template_data['all_file_paths'][$variation_id]['label']; ?>
                </th>
            <?php endforeach; ?>
        </tr>

        <?php if(isset($template_data['empty_translation'])): ?>
            <tr>
                <td><?php _e('Please save translation before translate variations file paths','wpml-wcml'); ?></td>
            </tr>
        <?php elseif(isset($template_data['not_downloaded'])): ?>
                <tr>
                <td><?php _e('Variations are not downloadable','wpml-wcml'); ?></td>
                </tr>
        <?php else: ?>
            <?php if($is_downloable): ?>
                <tr>
                    <td><?php _e('Download URL','wpml-wcml'); ?></td>
                    <?php foreach($template_data['all_variations_ids'] as $variation_id): $file_paths = ''; ?>
                        <?php if(isset($template_data['all_file_paths'][$variation_id]['not_translated'])){
                            echo '<td></td>';
                            continue;
                        }
                        if(get_post_meta($variation_id,'_downloadable',true) == 'yes'): ?>
                            <td>
                            <?php if(version_compare(preg_replace('#-(.+)$#', '', $woocommerce->version), '2.1', '<')){
                                $file_paths_array = maybe_unserialize($template_data['all_file_paths'][$variation_id]['value']);
                            if($file_paths_array)
                            foreach($file_paths_array as $trn_file_paths){
                                $file_paths = $file_paths ? $file_paths . "\n" .$trn_file_paths : $trn_file_paths;
                            }
                                 if($template_data['original']): ?>
                                    <textarea value="<?php echo $file_paths; ?>" class="wcml_file_paths_textarea" disabled="disabled"><?php echo $file_paths; ?></textarea>
                                <?php else: ?>
                                    <textarea value="<?php echo $file_paths; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']'; ?>' class="wcml_file_paths_textarea" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"><?php echo $file_paths; ?></textarea>
                                    <button type="button" class="button-secondary wcml_file_paths"><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                <?php endif;
                            }else{
                                for($i=0;$i<$template_data['all_file_paths']['count'];$i++): ?>
                                    <?php if($template_data['original']): ?>
                                        <input type="text" value="<?php echo $template_data['all_file_paths'][$variation_id][$i]['label']; ?>" class="" disabled="disabled">
                                        <input type="text" value="<?php echo $template_data['all_file_paths'][$variation_id][$i]['value']; ?>" class="" disabled="disabled">
                                    <?php else: ?>
                                        <div>
                                            <input type="text" value="<?php echo isset($template_data['all_file_paths'][$variation_id][$i])?$template_data['all_file_paths'][$variation_id][$i]['label']:''; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']['.$i.'][name]'; ?>' class="wcml_file_paths_name" placeholder="<?php esc_attr_e('Enter translation for name', 'wpml-wcml') ?>">
                                            <input type="text" value="<?php echo isset($template_data['all_file_paths'][$variation_id][$i])?$template_data['all_file_paths'][$variation_id][$i]['value']:''; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']['.$i.'][file]'; ?>' class="wcml_file_paths_file" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                                            <button type="button" class="button-secondary wcml_file_paths_button"><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                        </div>
                                <?php endif; ?>
                                <?php endfor; ?>
                            <?php }
                            ?>
                            </td>
                        <?php else: ?>
                            <td><?php _e('Variation is not downloadable','wpml-wcml'); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        <?php endif; ?>
    </tbody>
<?php endif; ?>
</table>