<tr>
    <td>
       <?php for($i=0;$i<$data['count'];$i++): ?>
            <?php if($data['original']): ?>
                <input type="text" value="<?php echo $data['files_data'][$i]['label']; ?>" class="" disabled="disabled">
                <input type="text" value="<?php echo $data['files_data'][$i]['value']; ?>" class="" disabled="disabled">
            <?php else: ?>
                <div>
                    <input type="text" value="<?php echo isset($data['files_data'][$i])?$data['files_data'][$i]['label']:''; ?>" name='<?php echo $data['product_content'].'_'.$data['lang'].'['.$i.'][name]'; ?>' class="wcml_file_paths_name" placeholder="<?php esc_attr_e('Enter translation for name', 'wpml-wcml') ?>">
                    <input type="text" value="<?php echo isset($data['files_data'][$i])?$data['files_data'][$i]['value']:''; ?>" name='<?php echo $data['product_content'].'_'.$data['lang'].'['.$i.'][file]'; ?>' class="wcml_file_paths_file" placeholder="<?php esc_attr_e('Enter translation', 'wpml-wcml') ?>"/>
                    <button type="button" class="button-secondary wcml_file_paths_button"><?php _e('Choose a file', 'wpml-wcml') ?></button>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    </td>
</tr>
