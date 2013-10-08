jQuery(document).ready(function($){

   jQuery('#wcmp_hide').click(function(){
       jQuery('.wcml_miss_lang').slideUp('3000',function(){jQuery('#wcmp_show').show();});
   });

   jQuery('#wcmp_show').click(function(){
       jQuery('#wcmp_show').hide();
       jQuery('.wcml_miss_lang').slideDown('3000');
   });

   jQuery('.wcml_check_all').click(function(){
      if(jQuery(this).is(":checked")){
          jQuery("table.wcml_products input[type='checkbox']").each(function(){
             jQuery(this).attr("checked","checked");
          });
      }else{
          jQuery("table.wcml_products input[type='checkbox']").each(function(){
             jQuery(this).removeAttr("checked");
          });
      }
   });

   jQuery('.wcml_search').click(function(){
       window.location = jQuery('.wcml_products_admin_url').val()+'&s='+jQuery('.wcml_product_name').val()+'&cat='+jQuery('.wcml_product_category').val()+'&status='+jQuery('.wcml_translation_status').val()+'&slang='+jQuery('.wcml_translation_status_lang').val();
   });

   jQuery('.wcml_reset_search').click(function(){
       window.location = jQuery('.wcml_products_admin_url').val();
   });
   
    jQuery('.wcml_pagin').keypress(function(e) {
        if(e.which == 13) {
            window.location = jQuery('.wcml_pagination_url').val()+jQuery(this).val();
            return false;
        }
    });
   
   jQuery('.wcml_details').click(function(e){
        e.preventDefault();
        var textClosed = $(this).data('text-closed');
        var textOpened = $(this).data('text-opened');
        var $table = $( $(this).attr('href') );

        if ( $table.is(':visible') ){
            $table.find('input').each(function(){
                jQuery(this).val(jQuery(this).data('def'));
            });
            $table.closest('.outer').hide();
            $(this).text(textClosed);
        }
        else {
            //set def data
            $table.find('input').each(function(){
                jQuery(this).data('def',jQuery(this).val());
            });
            $table.closest('.outer').show();
            $(this).text(textOpened);
        }
        return false;
   });

   jQuery('button[name="cancel"]').click(function(){
       var $outer = jQuery(this).closest('.outer');

       $outer.find('input').each(function(){
           jQuery(this).val(jQuery(this).data('def'));
       });

       var prid = $outer.data('prid');
       $outer.hide('fast', function(){
            var $closeButton = $('#wcml_details_' + prid);
            $closeButton.text( $closeButton.data('text-closed') );
       });

   });


   jQuery('button[name="cancel"]').click(function(){
       jQuery(this).parent().find('input').each(function(){
           jQuery(this).val(jQuery(this).data('def'));
       });
       jQuery(this).closest('.outer').slideUp('3000');
   });

   jQuery('.wcml_action_top').click(function(){
       if(jQuery(this).val() == 'apply' && jQuery(this).parent().find('select[name="test_action"]').val() == 'to_translation'){
           var ids = '',i = 0;
           jQuery('input[name="product[]"]').each(function(){
               if(jQuery(this).is(':checked')){
                  ids += i+"="+jQuery(this).val()+"&";
                  i++;
               }
           });
           jQuery('.icl_selected_posts').val(ids);
           jQuery('.wcml_send_to_trnsl').click();
           return false;
       }
   });

    jQuery('.wcml_action_bottom').click(function(){
        if(jQuery(this).val() == 'apply' && jQuery(this).parent().find('select[name="test_action_bottom"]').val() == 'to_translation'){
            var ids = '',i = 0;
            jQuery('input[name="product[]"]').each(function(){
                if(jQuery(this).is(':checked')){
                    ids += i+"="+jQuery(this).val()+"&";
                    i++;
                }
            });
            jQuery('.icl_selected_posts').val(ids);
            jQuery('.wcml_send_to_trnsl').click();
            return false;
        }
    });


   jQuery(".wcml_update").click( function() {
      var field = jQuery(this);

      var spl = jQuery(this).attr('name').split('#');

      var product_id = spl[1];
      var language   = spl[2];

      var records = '';
       field.closest('.outer').find("input").each(function(){
          records += jQuery(this).serialize()+"&";
      });
       field.closest('.outer').find("textarea").each(function(){
           records += jQuery(this).serialize()+"&";
       });
       field.hide();
       field.parent().find('.wcml_spinner').css('display','inline-block');

      jQuery.ajax({
         type : "post",
         url : ajaxurl,
         dataType: 'json',
         data : {
             action: "wcml_update_product",
             product_id : product_id,
             language   : language,
             records    : records,
             wcml_nonce: jQuery('#upd_product_nonce').val()
         },
         success: function(response) {
             if(typeof response.error !== "undefined"){
                 alert(response.error);
             }else{
             //update status block
             jQuery('.translations_statuses.prid_'+product_id).html(response.status);


             //update images block
             if(language in response.images){
             var value = response.images[language];
             field.closest('.outer').find('tr[rel="'+language+'"] .prod_images').closest('td').html(value).find('.prod_images').css('display','none');
             }

             //update variations block

             if(typeof response.variations !== "undefined" && (language in response.variations)){
             var value = response.variations[language];
             field.closest('.outer').find('tr[rel="'+language+'"] .prod_variations').closest('td').html(value).find('.prod_variations').css('display','none');
             }

             //set def data
             field.closest('.outer').find('input').each(function(){
                 jQuery(this).data('def',jQuery(this).val());
             });



                field.val(jQuery('#wcml_product_update_button_label').html());

             }
             field.parent().find('.wcml_spinner').hide();
             field.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
             field.show();
             
             jQuery('#prid_' + product_id + ' .js-wcml_duplicate_product_undo_' + language).fadeOut();
             
         }
      });

      return false;
   });
   
   if(typeof WPML_Translate_taxonomy != 'undefined' && typeof WPML_Translate_taxonomy.callbacks != 'undefined'){
       
       WPML_Translate_taxonomy.callbacks.add(function(func, taxonomy){
          
          if(jQuery('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').length){
              
              jQuery.ajax({
                 type : "post",
                 url : ajaxurl,
                 dataType: 'json',
                 data : {
                     action: "wcml_update_term_translated_warnings",
                     taxonomy: taxonomy, 
                     wcml_nonce: jQuery('#wcml_update_term_translated_warnings_nonce').val()
                 },
                 success: function(response) {
                     if(response.hide){
                        jQuery('.js-tax-tab-' + taxonomy).removeAttr('title');
                        jQuery('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                     }
                 }
              })       
              
          }
          
          return false;
           
       });
   }
   
   jQuery(document).on('click', '.wcml_duplicate_product_notice a[href^=#edit-]', function(){
       
       var spl = jQuery(this).attr('href').replace(/#edit-/, '').split('_');
       var pid = spl[0];
       var lng = spl[1];
       
       jQuery('#prid_' + pid + ' tr[rel=' + lng + '] .js-dup-disabled').removeAttr('disabled');
       jQuery('#prid_' + pid + ' tr[rel=' + lng + '] input[name^=end_duplication]').val(1);
       jQuery('#prid_' + pid + ' .js-wcml_duplicate_product_notice_'+lng).hide();
       jQuery('#prid_' + pid + ' .js-wcml_duplicate_product_undo_'+lng).show();
       
       return false;
       
   });

   jQuery(document).on('click', '.wcml_duplicate_product_notice a[href^=#undo-]', function(){
       
       var spl = jQuery(this).attr('href').replace(/#undo-/, '').split('_');
       var pid = spl[0];
       var lng = spl[1];
       
       jQuery('#prid_' + pid + ' tr[rel=' + lng + '] .js-dup-disabled').attr('disabled', 'disabled');
       jQuery('#prid_' + pid + ' tr[rel=' + lng + '] input[name^=end_duplication]').val(0);
       jQuery('#prid_' + pid + ' .js-wcml_duplicate_product_undo_'+lng).hide();
       jQuery('#prid_' + pid + ' .js-wcml_duplicate_product_notice_'+lng).show();
       
       return false;
       
   });
   
   jQuery(document).on('click', '.js-tax-translation li a[href^=#ignore-]', function(){
                
       var taxonomy = jQuery(this).attr('href').replace(/#ignore-/, '');
                
       jQuery.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_ingore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: jQuery('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               
               if(response.html){
                   
                   jQuery('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   
                   jQuery('.js-tax-tab-' + taxonomy).removeAttr('title');
                   jQuery('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                   
                   
               }
               
           }
       })       

       return false;
   })
   
   jQuery(document).on('click', '.js-tax-translation li a[href^=#unignore-]', function(){
                
       var taxonomy = jQuery(this).attr('href').replace(/#unignore-/, '');
                
       jQuery.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_uningore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: jQuery('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               if(response.html){
                   jQuery('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   if(response.warn){
                        jQuery('.js-tax-tab-' + taxonomy).append('&nbsp;<i class="icon-warning-sign"></i>');
                   }
                   
               }
           }
       })       

       return false;
   })
   
   
   jQuery(document).on('submit', '#icl_tt_sync_variations', function(){

       var this_form = jQuery('#icl_tt_sync_variations');
       var data = this_form.serialize();
       this_form.find('.wpml_tt_spinner').fadeIn();
       this_form.find('input[type=submit]').attr('disabled', 'disabled');
       
       jQuery.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : data,
           success: function(response) {
               this_form.find('.icl_tt_sycn_preview').html(response.progress);    
               if(response.go){                   
                   this_form.find('input[name=last_post_id]').val(response.last_post_id)
                   this_form.trigger('submit');
               }else{
                   this_form.find('input[name=last_post_id]').val(0);
                   this_form.find('.wpml_tt_spinner').fadeOut();
                   this_form.find('input').removeAttr('disabled');               
               }
               
           }
       });
       
       return false;       
       
       
   });

   var wcml_product_rows_data = new Array();
   var wcml_get_product_fields_string = function(row){
       var string = '';
       row.find('input[type=text], textarea').each(function(){
           string += jQuery(this).val();
       });       
       
       return string;
   }

   jQuery(document).on('focus','.wcml_products_translation input[type=text], .wcml_products_translation textarea',function(){

       var row_lang = jQuery(this).closest('tr[rel]').attr('rel');
       var prod_id  = jQuery(this).closest('div.wcml_product_row').attr('id');
       
       wcml_product_rows_data[prod_id + '_' + row_lang] = wcml_get_product_fields_string(jQuery(this).closest('tr'));

   });

   jQuery(document).on('input keyup change paste mouseup','.wcml_products_translation input[type=text], .wcml_products_translation textarea',function(){
       
       if(jQuery(this).attr('disabled')) return;
        
       var row_lang = jQuery(this).closest('tr[rel]').attr('rel');
       var prod_id  = jQuery(this).closest('div.wcml_product_row').attr('id');
       
       if(jQuery(this).closest('tr[rel]').find('.wcml_update').prop('disabled')){       
           
           if(wcml_product_rows_data[prod_id + '_' + row_lang] != wcml_get_product_fields_string(jQuery(this).closest('tr'))){
               jQuery(this).closest('tr[rel]').find('.wcml_update').prop('disabled',false).removeClass('button-secondary').addClass('button-primary');;
           }
           
       }

   })

   jQuery(".wcml_edit_conten").click(function(){
        jQuery(".wcml_fade").show();
        jQuery(this).parent().find('.wcml_editor').show();
        jQuery(this).parent().find('.wcml_editor table.mceLayout').css('height','auto');
        jQuery(this).parent().find('.wcml_editor table.mceLayout iframe').css('min-height','150px');
        var txt_height = '90%';
        jQuery(this).parent().find('textarea.wcml_content_tr').data('def',jQuery(this).parent().find('textarea.wcml_content_tr').val());
        jQuery(this).parent().find('.wcml_original_content').cleditor({
                    height: txt_height,
                    controls:     // controls to add to the toolbar
                    " source "
                    });
        jQuery(this).parent().find('.wcml_original_content').cleditor()[0].disable(true);

        jQuery(document).on('click','.cleditorButton',function(){
            if(jQuery(this).closest('.cleditorMain').find('textarea').is(':visible')){
                jQuery(this).closest('.cleditorMain').find('textarea').hide();
                jQuery(this).closest('.cleditorMain').find('iframe').show();
            }else{
                jQuery(this).closest('.cleditorMain').find('textarea').show();
                jQuery(this).closest('.cleditorMain').find('iframe').hide();
            }
        });
    });

    jQuery(".wcml_close_cross,.wcml_popup_cancel").click(function(){
        jQuery(".wcml_fade").hide();
        if(tinyMCE.activeEditor != null){
            if(jQuery('textarea.wcml_content_tr')>0){
            tinyMCE.activeEditor.setContent(jQuery(this).parent().find('textarea.wcml_content_tr').data('def'));
        }
        }
        jQuery(this).parent().css('display','none');
        jQuery(this).parent().find('textarea.wcml_content_tr').val(jQuery(this).parent().find('textarea.wcml_content_tr').data('def'));
    });

    jQuery(".wcml_popup_close").click(function(){
        jQuery(".wcml_fade").hide();
        jQuery(this).parent().css('display','none');
    });


    jQuery(".wcml_popup_ok").click(function(){
        var text_area = jQuery(this).parent().find('textarea.wcml_content_tr');
        jQuery(".wcml_fade").hide();

        if(text_area.size()>0 && !text_area.is(':visible')){
            text_area.val(window.parent.tinyMCE.get(text_area.attr('id')).getContent());
        }
        jQuery(this).parent().css('display','none');


        var row_lang = jQuery(this).closest('tr[rel]').attr('rel');
        var prod_id  = jQuery(this).closest('div.wcml_product_row').attr('id');

        if(text_area.val() != ''){
            jQuery('#wcml_field_translation_' + text_area.attr('name')).hide();
        }else{
            if(jQuery('#wcml_field_translation_' + text_area.attr('name')).length){
                jQuery('#wcml_field_translation_' + text_area.attr('name')).show();
            }
        }

        if(wcml_product_rows_data[prod_id + '_' + row_lang] != wcml_get_product_fields_string(jQuery(this).closest('tr'))){
            jQuery(this).closest('tr[rel]').find('.wcml_update').prop('disabled',false);
        }

    });


    if(jQuery('.wcml_file_paths').size()>0){
        // Uploading files
        var downloadable_file_frame;
        var file_path_field;
        var file_paths;

        jQuery(document).on( 'click', '.wcml_file_paths', function( event ){

            var $el = jQuery(this);

            file_path_field = $el.parent().find('textarea');
            file_paths      = file_path_field.val();

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if ( downloadable_file_frame ) {
                downloadable_file_frame.open();
                return;
            }

            var downloadable_file_states = [
                // Main states.
                new wp.media.controller.Library({
                    library:   wp.media.query(),
                    multiple:  true,
                    title:     $el.data('choose'),
                    priority:  20,
                    filterable: 'uploaded'
                })
            ];

            // Create the media frame.
            downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
                // Set the title of the modal.
                title: $el.data('choose'),
                library: {
                    type: ''
                },
                button: {
                    text: $el.data('update')
                },
                multiple: true,
                states: downloadable_file_states
            });

            // When an image is selected, run a callback.
            downloadable_file_frame.on( 'select', function() {

                var selection = downloadable_file_frame.state().get('selection');

                selection.map( function( attachment ) {

                    attachment = attachment.toJSON();

                    if ( attachment.url )
                        file_paths = file_paths ? file_paths + "\n" + attachment.url : attachment.url

                } );

                file_path_field.val( file_paths );
            });

            // Set post to 0 and set our custom type
            downloadable_file_frame.on( 'ready', function() {
                downloadable_file_frame.uploader.options.uploader.params = {
                    type: 'downloadable_product'
                };
            });

            downloadable_file_frame.on( 'close', function() {
                // TODO: /wp-admin should be a variable. Some plugions, like WP Better Security changes the name of this dir.
                jQuery.removeCookie('_icl_current_language', { path: '/wp-admin' });
            });

            // Finally, open the modal.
            downloadable_file_frame.open();
        });
    }

    if(jQuery(".wcml_editor_original").size() > 0 ){
        jQuery(".wcml_editor_original").resizable({
            handles: 'n, s',
            resize: function( event, ui ) {
                jQuery(this).find('.cleditorMain').css('height',jQuery(this).height() - 60)
            },
            start: function(event, ui) {
                jQuery('<div class="ui-resizable-iframeFix" style="background: #FFF;"></div>')
                    .css({
                        width:'100%', height: '100%',
                        position: "absolute", opacity: "0.001", zIndex: 160001
                    })
                    .prependTo(".wcml_editor_original");
            },
            stop: function(event, ui) {
                jQuery('.ui-resizable-iframeFix').remove()
            }
        });
    }

    jQuery(document).on('click','.edit_currency',function(){
        var $tableRow = jQuery(this).closest('tr');
        $tableRow.addClass('edit-mode');
        $tableRow.find('.currency_code span').hide();
        $tableRow.find('.currency_code input').show();
        $tableRow.find('.currency_value span').hide();
        $tableRow.find('.currency_value input').show();
        $tableRow.find('.currency_changed span').hide();
        $tableRow.find('.edit_currency').hide();
        $tableRow.find('.delete_currency').hide();
        $tableRow.find('.save_currency').show();
        $tableRow.find('.cancel_currency').show();
    });

    jQuery(document).on('click','.cancel_currency',function(){
        var $tableRow = jQuery(this).closest('tr');
        $tableRow.removeClass('edit-mode');
        if($tableRow.find('.currency_id').val() > 0){
            $tableRow.find('.currency_code span').show();
            $tableRow.find('.currency_code input').hide();
            $tableRow.find('.currency_value span').show();
            $tableRow.find('.currency_value input').hide();
            $tableRow.find('.currency_changed span').show();
            $tableRow.find('.edit_currency').show();
            $tableRow.find('.delete_currency').show();
            $tableRow.find('.save_currency').hide();
            $tableRow.find('.cancel_currency').hide();
            $tableRow.find('.wcml-error').remove();
        }else{
            $tableRow.remove();
        }
    });

    jQuery('.wcml_add_currency button').click(function(){
        var pluginurl = jQuery('.wcml_plugin_url').val();
        var $tableRow = $('.js-table-row-wrapper .js-table-row').clone();
        jQuery('#currency-table').find('tbody').append( $tableRow );
    });

    jQuery(document).on('click','.save_currency',function(e){
        e.preventDefault();

        var $this = jQuery(this);
        var $ajaxLoader = $('<span class="spinner">');
        var $messageContainer = jQuery('<span class="wcml-error">');
        $ajaxLoader.insertBefore($this).show();

        $this.prop('disabled',true);

        var parent = jQuery(this).closest('tr');
        if(parent.find('.currency_id').val() > 0){
            var currency_id =  parent.find('.currency_id').val();
        }else{
            var currency_id = 0;
        }

        $currencyCodeWraper = parent.find('.currency_code');
        $currencyValueWraper = parent.find('.currency_value');

        var currency_code = $currencyCodeWraper.find('input').val();
        var currency_value = $currencyValueWraper.find('input').val();
        var flag = false;

        if(currency_code == ''){
            if(parent.find('.currency_code .wcml-error').size() == 0){
                parent.find('.currency_code').append( $messageContainer );
                $messageContainer.text( $currencyCodeWraper.data('message') );
                // empty
            }
            flag = true;
        }else{
            if(parent.find('.currency_code .wcml-error').size() > 0){
                parent.find('.currency_code .wcml-error').remove();
            }
        }

        if(currency_value == ''){
            if(parent.find('.currency_value .wcml-error').size() == 0){

                parent.find('.currency_value').append( $messageContainer );
                $messageContainer.text( $currencyCodeWraper.data('message') );
                // empty
            }
            flag = true;
        }else{
            if(parent.find('.currency_value .wcml-error').size() > 0){
                parent.find('.currency_value .wcml-error').remove();
            }
        }

        if(!isNumber(currency_value)){
            if(parent.find('.currency_value .wcml-error').size() == 0){
                parent.find('.currency_value').append( $messageContainer );
                $messageContainer.text( $currencyValueWraper.data('message') );
                // numeric
            }
            flag = true;
        }else{
            if(parent.find('.currency_value .wcml-error').size() > 0){
                parent.find('.currency_value .wcml-error').remove();
            }
        }

        if(flag){
            $ajaxLoader.remove();
            $this.prop('disabled',false);
            return false;
        }

        var today = new Date();
        var dd = today.getDate();
        var mm = today.getMonth()+1; //January is 0!
        var yyyy = today.getFullYear();
        if(dd<10){
            dd='0'+dd
        }
        if(mm<10){
            mm='0'+mm
        }
        var today = dd+'/'+mm+'/'+yyyy;


        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "wcml_update_currency",
                wcml_nonce: jQuery('#upd_currency_nonce').val(),
                currency_id : currency_id,
                currency_code : currency_code,
                currency_value : currency_value,
                today : today
            },
            error: function(respnse) {
                // TODO: add error handling
            },
            success: function(response) {
                if(currency_id == 0){
                    parent.find('.currency_id').val(response);
                }

                parent.find('.currency_code span').html(parent.closest('tr').find('.currency_code input').val()).show();
                parent.find('.currency_code input').hide();
                parent.find('.currency_value span').html(parent.closest('tr').find('.currency_value input').val()).show();
                parent.find('.currency_value input').hide();
                parent.find('.currency_changed span').html(today).show();
                parent.find('.edit_currency').show();
                parent.find('.delete_currency').show();
                parent.find('.save_currency').hide();
                parent.find('.cancel_currency').hide();
                $this.closest('.edit-mode').removeClass('edit-mode');
                update_languages_curencies();
            },
            complete: function() {
                $ajaxLoader.remove();
                $this.prop('disabled',false);
            }
        });

        return false;
});


    jQuery(document).on('click','.delete_currency',function(e){
        e.preventDefault();

        var parent = jQuery(this).closest('tr');
        var $this = jQuery(this);
        var $ajaxLoader = $('<span class="spinner">');
        var currency_id =  parent.find('.currency_id').val();
        $this.hide();
        $this.parent().append($ajaxLoader).show();

        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "wcml_delete_currency",
                wcml_nonce: jQuery('#del_currency_nonce').val(),
                currency_id : currency_id
            },
            success: function(response) {
                parent.remove();
                update_languages_curencies();
            },
            done: function() {
                $ajaxLoader.remove();
            }
        });

        return false;
    });

    // expand|collapse for product images and product variations tables
    $(document).on('click','.js-table-toggle',function(e){

        e.preventDefault();

        var textOpened = $(this).data('text-opened');
        var textClosed = $(this).data('text-closed');
        var $table = $(this).next('.js-table');
        
        var this_id = $(this).attr('id');
        if($(this).hasClass('prod_images_link')){
            var id_and_language = this_id.replace(/^prod_images_link_/, '');
        }else{
            var id_and_language = this_id.replace(/^prod_variations_link_/, '');
        }
        var spl = id_and_language.split('_');
        var language    = spl[1];
        var product_id  = spl[0];
        
        if ( $table.is(':visible') ) {
            $table.hide();
            $(this)
                .find('span')
                .text( textClosed );
            $(this)
                .find('i')
                .removeClass('icon-caret-up')
                .addClass('icon-caret-down');
            if($(this).hasClass('prod_images_link')){
                $('#prod_images_' + product_id + '_' + language).hide();
            }else{
                $('#prod_variations_' + product_id  + language).hide();
            }

        }
        else {
            $table.show();
            if($(this).hasClass('prod_images_link')){
                $('#prod_images' + product_id  + language).show();
            }else{
                $('#prod_variations_' + product_id  + language).show();
            }
            $(this)
                .find('span')
                .text( textOpened );
            $(this)
                .find('i')
                .removeClass('icon-caret-down')
                .addClass('icon-caret-up');
        }

        return false;
    });

    // wp-pointers
    $('.js-display-tooltip').click(function(){
        var $thiz = $(this);

        // hide this pointer if other pointer is opened.
        $('.wp-pointer').fadeOut(100);

        $(this).pointer({
            content: '<h3>'+$thiz.data('header')+'</h3><p>'+$thiz.data('content')+'</p>',
            position: {
                edge: 'left',
                align: 'center',
                offset: '15 0'
            }
        }).pointer('open');
    });


function update_languages_curencies(){
    jQuery.ajax({
        type : "post",
        url : ajaxurl,
        data : {
            action: "wcml_update_languages_curencies",
            id: '1'
        },
        success: function(response) {
            jQuery('.wcml_languages_currency tbody').html(response);
        }
    })
}

function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

});

