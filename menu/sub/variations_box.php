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
                        <?php if($template_data['all_file_paths'][$variation_id]['label'] == '' || isset($template_data['all_file_paths'][$variation_id]['not_translated'])){
                            echo '<td></td>';
                            continue;
                        }
                        if(get_post_meta($variation_id,'_downloadable',true) == 'yes'):
                            $file_paths_array = unserialize($template_data['all_file_paths'][$variation_id]['value']);
                            foreach($file_paths_array as $trn_file_paths){
                                $file_paths = $file_paths ? $file_paths . "\n" .$trn_file_paths : $trn_file_paths;
                            }
                            ?>
                            <td>
                                <?php if($template_data['original']): ?>
                                    <textarea value="<?php echo $file_paths; ?>" class="wcml_file_paths_textarea" disabled="disabled"><?php echo $file_paths; ?></textarea>
                                <?php else: ?>
                                    <textarea value="<?php echo $file_paths; ?>" name='<?php echo 'variations_file_paths['.$variation_id.']'; ?>' class="wcml_file_paths_textarea" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"><?php echo $file_paths; ?></textarea>
                                    <button type="button" class="button-secondary wcml_file_paths"><?php _e('Choose a file', 'wpml-wcml') ?></button>
                                <?php endif; ?>
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