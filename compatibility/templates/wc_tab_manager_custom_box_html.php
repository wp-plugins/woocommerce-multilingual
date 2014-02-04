<?php if(isset($template_data['orig_tabs'])): ?>
    <?php foreach($template_data['orig_tabs']['ids'] as $key=>$id):
        $trnsl_tab_id = isset($template_data['tr_tabs']['ids'][$key])?$template_data['tr_tabs']['ids'][$key]:'';
        ?>
        <tr>
            <td>
                <?php if(!$template_data['original']): ?><input type="hidden" name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[id][]'; ?>" value="<?php echo $trnsl_tab_id; ?>" /><?php endif;?>
                <textarea rows="1" <?php if(!$template_data['original']): ?>name="<?php echo $template_data['product_content'].'_'.$template_data['lang'].'[title][]'; ?>"<?php endif;?> <?php if($template_data['original']): ?> disabled="disabled"<?php endif;?>><?php echo $template_data['original']?get_the_title($id):get_the_title($trnsl_tab_id); ?></textarea>
            </td>
            <td>
                <?php if($template_data['original']): ?>
                    <button type="button" class="button-secondary wcml_edit_conten"><?php _e('Show content', 'wpml-wcml') ?></button>
                <?php else: ?>
                    <button type="button" class="button-secondary wcml_edit_conten<?php if($template_data['is_duplicate_product']): ?> js-dup-disabled<?php endif;?>"<?php if($template_data['is_duplicate_product']): ?> disabled="disabled"<?php endif;?>><?php _e('Edit translation', 'wpml-wcml') ?></button>
                <?php endif;?>
                <div class="wcml_editor">
                    <a class="media-modal-close wcml_close_cross" href="javascript:void(0);" title="<?php esc_attr_e('Close', 'wpml-wcml') ?>"><span class="media-modal-icon"></span></a>
                    <div class="wcml_editor_original">
                        <h3><?php _e('Original content:', 'wpml-wcml') ?></h3>
                        <textarea class="wcml_original_content"><?php echo get_post($id)->post_content; ?></textarea>
                    </div>
                    <div class="wcml_line"></div>
                    <div class="wcml_editor_translation">
                        <?php if(!$template_data['original']): ?>
                            <?php
                            if($trnsl_tab_id){
                                $content = get_post($trnsl_tab_id)->post_content;
                            }else{
                                $content = '';
                            }

                            wp_editor($content, 'wcmleditor'.$template_data['product_content'].$id.$template_data['lang'], array('textarea_name'=>$template_data['product_content'] .
                            '_'.$template_data['lang'].'[content][]','textarea_rows'=>20,'editor_class'=>'wcml_content_tr')); ?>
                        <?php endif; ?>
                    </div>
                    <div class="wcml_editor_buttons">
                        <?php if($template_data['original']): ?>
                            <button type="button" class="button-secondary wcml_popup_close"><?php _e('Close', 'wpml-wcml') ?></button>
                        <?php else: ?>
                            <h3><?php printf(__('%s translation', 'wpml-wcml'),$template_data['lang_name']); ?></h3>
                            <button type="button" class="button-secondary wcml_popup_cancel"><?php _e('Cancel', 'wpml-wcml') ?></button>
                            <button type="button" class="button-secondary wcml_popup_ok"><?php _e('Ok', 'wpml-wcml') ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
