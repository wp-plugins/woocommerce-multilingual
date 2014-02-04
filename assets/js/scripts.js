jQuery(document).ready(function($){

    var discard = false;

    window.onbeforeunload = function(e) {
        if(discard){
            return $('#wcml_warn_message').val();
        }
    }

    $('.wcml-section input[type="submit"]').click(function(){
        discard = false;
    });

    $('.wcml-section input[type="radio"],#wcml_products_sync_date').click(function(){
        discard = true;
        $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
    });

   $('#wcmp_hide').click(function(){
       $('.wcml_miss_lang').slideUp('3000',function(){$('#wcmp_show').show();});
   });

   $('#wcmp_show').click(function(){
       $('#wcmp_show').hide();
       $('.wcml_miss_lang').slideDown('3000');
   });

   $('.wcml_check_all').click(function(){
      if($(this).is(":checked")){
          $("table.wcml_products input[type='checkbox']").each(function(){
             $(this).attr("checked","checked");
          });
      }else{
          $("table.wcml_products input[type='checkbox']").each(function(){
             $(this).removeAttr("checked");
          });
      }
   });

   $('.wcml_search').click(function(){
       window.location = $('.wcml_products_admin_url').val()+'&s='+$('.wcml_product_name').val()+'&cat='+$('.wcml_product_category').val()+'&trst='+$('.wcml_translation_status').val()+'&st='+$('.wcml_product_status').val()+'&slang='+$('.wcml_translation_status_lang').val();
   });

   $('.wcml_reset_search').click(function(){
       window.location = $('.wcml_products_admin_url').val();
   });
   
    $('.wcml_pagin').keypress(function(e) {
        if(e.which == 13) {
            window.location = $('.wcml_pagination_url').val()+$(this).val();
            return false;
        }
    });
   
   $('.wcml_details').click(function(e){
        e.preventDefault();
        var textClosed = $(this).data('text-closed');
        var textOpened = $(this).data('text-opened');
        var $table = $( $(this).attr('href') );

        if ( $table.is(':visible') ){
            $table.find('input').each(function(){
                $(this).val($(this).data('def'));
            });
            $table.closest('.outer').hide();
            $(this).text(textClosed);
        }
        else {
            //set def data
            $table.find('input').each(function(){
                $(this).data('def',$(this).val());
            });
            $table.closest('.outer').show();
            $(this).text(textOpened);
        }
        return false;
   });

   $('button[name="cancel"]').click(function(){
       var $outer = $(this).closest('.outer');

       $outer.find('input').each(function(){
           $(this).val($(this).data('def'));
       });

       var prid = $outer.data('prid');
       $outer.hide('fast', function(){
            var $closeButton = $('#wcml_details_' + prid);
            $closeButton.text( $closeButton.data('text-closed') );
       });

   });


   $('button[name="cancel"]').click(function(){
       $(this).parent().find('input').each(function(){
           $(this).val($(this).data('def'));
       });
       $(this).closest('.outer').slideUp('3000');
   });

   $('.wcml_action_top').click(function(){
       if($(this).val() == 'apply' && $(this).parent().find('select[name="test_action"]').val() == 'to_translation'){
           var ids = '',i = 0;
           $('input[name="product[]"]').each(function(){
               if($(this).is(':checked')){
                  ids += i+"="+$(this).val()+"&";
                  i++;
               }
           });
           $('.icl_selected_posts').val(ids);
           $('.wcml_send_to_trnsl').click();
           return false;
       }
   });

    $('.wcml_action_bottom').click(function(){
        if($(this).val() == 'apply' && $(this).parent().find('select[name="test_action_bottom"]').val() == 'to_translation'){
            var ids = '',i = 0;
            $('input[name="product[]"]').each(function(){
                if($(this).is(':checked')){
                    ids += i+"="+$(this).val()+"&";
                    i++;
                }
            });
            $('.icl_selected_posts').val(ids);
            $('.wcml_send_to_trnsl').click();
            return false;
        }
    });


   $(".wcml_update").click( function() {
      var field = $(this);

      var spl = $(this).attr('name').split('#');

      var product_id = spl[1];
      var language   = spl[2];

      var records = '';
       field.closest('.outer').find("input").each(function(){
          records += $(this).serialize()+"&";
      });
       field.closest('.outer').find("textarea").each(function(){
           records += $(this).serialize()+"&";
       });
       field.hide();
       field.parent().find('.wcml_spinner').css('display','inline-block');

      $.ajax({
         type : "post",
         url : ajaxurl,
         dataType: 'json',
         data : {
             action: "wcml_update_product",
             product_id : product_id,
             language   : language,
             records    : records,
             slang      : $('.wcml_translation_status_lang').val(),
             wcml_nonce: $('#upd_product_nonce').val()
         },
         success: function(response) {
             if(typeof response.error !== "undefined"){
                 alert(response.error);
             }else{
             //update status block
             $('.translations_statuses.prid_'+product_id).html(response.status);


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
                 $(this).data('def',$(this).val());
             });



                field.val($('#wcml_product_update_button_label').html());

             }
             field.parent().find('.wcml_spinner').hide();
             field.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
             field.show();
             
             $('#prid_' + product_id + ' .js-wcml_duplicate_product_undo_' + language).fadeOut();
             
         }
      });

      return false;
   });
   
   if(typeof WPML_Translate_taxonomy != 'undefined' && typeof WPML_Translate_taxonomy.callbacks != 'undefined'){
       
       WPML_Translate_taxonomy.callbacks.add(function(func, taxonomy){
          
          if($('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').length){
              
              $.ajax({
                 type : "post",
                 url : ajaxurl,
                 dataType: 'json',
                 data : {
                     action: "wcml_update_term_translated_warnings",
                     taxonomy: taxonomy, 
                     wcml_nonce: $('#wcml_update_term_translated_warnings_nonce').val()
                 },
                 success: function(response) {
                     if(response.hide){
                        $('.js-tax-tab-' + taxonomy).removeAttr('title');
                        $('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                     }
                 }
              })       
              
          }
          
          return false;
           
       });
   }
   
   $(document).on('click', '.wcml_duplicate_product_notice a[href^=#edit-]', function(){
       
       var spl = $(this).attr('href').replace(/#edit-/, '').split('_');
       var pid = spl[0];
       var lng = spl[1];
       
       $('#prid_' + pid + ' tr[rel=' + lng + '] .js-dup-disabled').removeAttr('disabled');
       $('#prid_' + pid + ' tr[rel=' + lng + '] input[name^=end_duplication]').val(1);
       $('#prid_' + pid + ' .js-wcml_duplicate_product_notice_'+lng).hide();
       $('#prid_' + pid + ' .js-wcml_duplicate_product_undo_'+lng).show();
       
       return false;
       
   });

   $(document).on('click', '.wcml_duplicate_product_notice a[href^=#undo-]', function(){
       
       var spl = $(this).attr('href').replace(/#undo-/, '').split('_');
       var pid = spl[0];
       var lng = spl[1];
       
       $('#prid_' + pid + ' tr[rel=' + lng + '] .js-dup-disabled').attr('disabled', 'disabled');
       $('#prid_' + pid + ' tr[rel=' + lng + '] input[name^=end_duplication]').val(0);
       $('#prid_' + pid + ' .js-wcml_duplicate_product_undo_'+lng).hide();
       $('#prid_' + pid + ' .js-wcml_duplicate_product_notice_'+lng).show();
       
       return false;
       
   });
   
   $(document).on('click', '.js-tax-translation li a[href^=#ignore-]', function(){
                
       var taxonomy = $(this).attr('href').replace(/#ignore-/, '');
                
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_ingore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               
               if(response.html){
                   
                   $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   
                   $('.js-tax-tab-' + taxonomy).removeAttr('title');
                   $('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                   
                   
               }
               
           }
       })       

       return false;
   })
   
   $(document).on('click', '.js-tax-translation li a[href^=#unignore-]', function(){
                
       var taxonomy = $(this).attr('href').replace(/#unignore-/, '');
                
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_uningore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               if(response.html){
                   $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   if(response.warn){
                        $('.js-tax-tab-' + taxonomy).append('&nbsp;<i class="icon-warning-sign"></i>');
                   }
                   
               }
           }
       })       

       return false;
   })
   
   
   $(document).on('submit', '#icl_tt_sync_variations', function(){

       var this_form = $('#icl_tt_sync_variations');
       var data = this_form.serialize();
       this_form.find('.wpml_tt_spinner').fadeIn();
       this_form.find('input[type=submit]').attr('disabled', 'disabled');
       
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : data,
           success: function(response) {
               this_form.find('.icl_tt_sycn_preview').html(response.progress);    
               if(response.go){                   
                   this_form.find('input[name=last_post_id]').val(response.last_post_id);
                   this_form.find('input[name=languages_processed]').val(response.languages_processed);
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
           string += $(this).val();
       });       
       
       return string;
   }

   $(document).on('focus','.wcml_products_translation input[type=text], .wcml_products_translation textarea',function(){

       var row_lang = $(this).closest('tr[rel]').attr('rel');
       var prod_id  = $(this).closest('div.wcml_product_row').attr('id');
       
       wcml_product_rows_data[prod_id + '_' + row_lang] = wcml_get_product_fields_string($(this).closest('tr'));

   });

   $(document).on('input keyup change paste mouseup','.wcml_products_translation input[type=text], .wcml_products_translation textarea',function(){
       
       if($(this).attr('disabled')) return;
        
       var row_lang = $(this).closest('tr[rel]').attr('rel');
       var prod_id  = $(this).closest('div.wcml_product_row').attr('id');
       
       if($(this).closest('tr[rel]').find('.wcml_update').prop('disabled')){       
           
           if(wcml_product_rows_data[prod_id + '_' + row_lang] != wcml_get_product_fields_string($(this).closest('tr'))){
               $(this).closest('tr[rel]').find('.wcml_update').prop('disabled',false).removeClass('button-secondary').addClass('button-primary');;
           }
           
       }

   })

   $(".wcml_edit_conten").click(function(){
        $(".wcml_fade").show();
        $(this).parent().find('.wcml_editor').show();
        $(this).parent().find('.wcml_editor table.mceLayout').css('height','auto');
        $(this).parent().find('.wcml_editor table.mceLayout iframe').css('min-height','150px');
        var txt_height = '90%';
        $(this).parent().find('textarea.wcml_content_tr').data('def',$(this).parent().find('textarea.wcml_content_tr').val());
        $(this).parent().find('.wcml_original_content').cleditor({
                    height: txt_height,
                    controls:     // controls to add to the toolbar
                    " source "
                    });
        $(this).parent().find('.wcml_original_content').cleditor()[0].disable(true);

        $(document).on('click','.cleditorButton',function(){
            if($(this).closest('.cleditorMain').find('textarea').is(':visible')){
                $(this).closest('.cleditorMain').find('textarea').hide();
                $(this).closest('.cleditorMain').find('iframe').show();
            }else{
                $(this).closest('.cleditorMain').find('textarea').show();
                $(this).closest('.cleditorMain').find('iframe').hide();
            }
        });
    });

    $(".wcml_close_cross,.wcml_popup_cancel").click(function(){
        $(".wcml_fade").hide();
        if(tinyMCE.activeEditor != null){
            if($(this).closest('.wcml_editor').find('textarea.wcml_content_tr').size() >0){
            tinyMCE.activeEditor.setContent($(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').data('def'));
        }
        }
        $(this).closest('.wcml_editor').css('display','none');
        $(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').val($(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').data('def'));
    });

    $(".wcml_popup_close").click(function(){
        $(".wcml_fade").hide();
        $(this).closest('.wcml_editor').css('display','none');
    });


    $(".wcml_popup_ok").click(function(){
        var text_area = $(this).closest('.wcml_editor').find('.wcml_editor_translation textarea');
        $(".wcml_fade").hide();

        if(text_area.size()>0 && !text_area.is(':visible')){
            text_area.val(window.parent.tinyMCE.get(text_area.attr('id')).getContent());
        }
        $(this).closest('.wcml_editor').css('display','none');


        var row_lang = $(this).closest('tr[rel]').attr('rel');
        var prod_id  = $(this).closest('div.wcml_product_row').attr('id');

        if(text_area.val() != ''){
            $(this).closest('tr').find('.wcml_field_translation_' + text_area.attr('name')).hide();
        }else{
            if($(this).closest('tr').find('.wcml_field_translation_' + text_area.attr('name')).length){
                $(this).closest('tr').find('.wcml_field_translation_' + text_area.attr('name')).show();
            }
        }

        if(wcml_product_rows_data[prod_id + '_' + row_lang] != wcml_get_product_fields_string($(this).closest('tr'))){
            $(this).closest('tr[rel]').find('.wcml_update').prop('disabled',false);
        }

    });


    if($('.wcml_file_paths').size()>0){
        // Uploading files
        var downloadable_file_frame;
        var file_path_field;
        var file_paths;

        $(document).on( 'click', '.wcml_file_paths', function( event ){

            var $el = $(this);

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
                $.removeCookie('_icl_current_language', { path: '/wp-admin' });
            });

            // Finally, open the modal.
            downloadable_file_frame.open();
        });
    }

    if($(".wcml_editor_original").size() > 0 ){
        $(".wcml_editor_original").resizable({
            handles: 'n, s',
            resize: function( event, ui ) {
                $(this).find('.cleditorMain').css('height',$(this).height() - 60)
            },
            start: function(event, ui) {
                $('<div class="ui-resizable-iframeFix" style="background: #FFF;"></div>')
                    .css({
                        width:'100%', height: '100%',
                        position: "absolute", opacity: "0.001", zIndex: 160001
                    })
                    .prependTo(".wcml_editor_original");
            },
            stop: function(event, ui) {
                $('.ui-resizable-iframeFix').remove()
            }
        });
    }

    $('#multi_currency_option_select input[name=multi_currency]').change(function(){
        
        if($(this).attr('id') != 'multi_currency_independent'){
            $('#multi-currency-per-language-details').fadeOut();    
        }else{
            $('#multi-currency-per-language-details').fadeIn();
        }
        
    })
    
    $('#wcml_custom_exchange_rates').submit(function(){
        
        var thisf = $(this);
        
        thisf.find(':submit').parent().prepend(icl_ajxloaderimg + '&nbsp;')
        thisf.find(':submit').prop('disabled', true);
        
        $.ajax({
            
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: thisf.serialize(),
            success: function(){
                thisf.find(':submit').prev().remove();    
                thisf.find(':submit').prop('disabled', false);
            }
            
        })
        
        return false;
    })
    
    function wcml_remove_custom_rates(post_id){
        
        var thisa = $(this);
        
        $.ajax({
            
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {action: 'wcml_remove_custom_rates', 'post_id': post_id},
            success: function(){
                thisa.parent().parent().parent().fadeOut(function(){ $(this).remove()});
            }
            
        })
        
        return false;
        
    }
    
    $(document).on('click', '#wcml_fix_strings_language', function(){
        
        var thisb = $(this);
        thisb.prop('disabled', true);
        var $ajaxLoader = $('<span>&nbsp;</span>' + icl_ajxloaderimg);
        $ajaxLoader.insertAfter(thisb).show();
        
        $.ajax({
            
            type : "post",
            dataType:'json',
            url : ajaxurl,
            data : {
                action: "wcml_fix_strings_language",
                wcml_nonce: $('#wcml_fix_strings_language_nonce').val()
            },
            error: function(respnse) {
                thisb.prop('disabled', false);
            },
            success: function(response) {
                
                var sucess_1 = response.success_1;
                
                $.ajax({                    
                    type : "post",
                    dataType:'json',
                    url : icl_ajx_url,
                    data : {
                        iclt_st_sw_save: 1,
                        icl_st_sw: {strings_language: 'en'},
                        _wpnonce: response._wpnonce
                    },
                    complete: function(response){
                        $ajaxLoader.remove();
                        thisb.after(sucess_1);
                    }
                });
                

            }
        })
        
    });
    
    $(document).on('click','.edit_currency',function(){
        var $tableRow = $(this).closest('tr');
        $tableRow.addClass('edit-mode');
        $tableRow.find('.currency_code .code_val').hide();
        $tableRow.find('.currency_code select').show();
        $tableRow.find('.currency_value span.curr_val').hide();
        $tableRow.find('.currency_value input').show();
        $tableRow.find('.currency_changed').hide();
        $tableRow.find('.edit_currency').hide();
        $tableRow.find('.delete_currency').hide();
        $tableRow.find('.save_currency').show();
        $tableRow.find('.cancel_currency').show();
    });

    $(document).on('click','.cancel_currency',function(){
        var $tableRow = $(this).closest('tr');
        $tableRow.removeClass('edit-mode');
        if($tableRow.find('.currency_id').val() > 0){
            $tableRow.find('.currency_code .code_val').show();
            $tableRow.find('.currency_code select').hide();
            $tableRow.find('.currency_value span.curr_val').show();
            $tableRow.find('.currency_value input').hide();
            $tableRow.find('.currency_changed').show();
            $tableRow.find('.edit_currency').show();
            $tableRow.find('.delete_currency').show();
            $tableRow.find('.save_currency').hide();
            $tableRow.find('.cancel_currency').hide();
            $tableRow.find('.wcml-error').remove();
        }else{
            var index = $tableRow[0].rowIndex;
            $('#currency-lang-table tr').eq(index).remove();
            $tableRow.remove();
        }
    });

    $(document).on('change','.currency_code select',function(){
        $(this).parent().find('.curr_val_code').html($(this).val());
    });

    $('.wcml_add_currency button').click(function(){
        $('.js-table-row-wrapper .curr_val_code').html($('.js-table-row-wrapper select').val());
        var pluginurl = $('.wcml_plugin_url').val();
        var $tableRow = $('.js-table-row-wrapper .js-table-row').clone();
        var $LangTableRow = $('.js-currency_lang_table tr').clone();
        $('#currency-table').find('tr.default_currency').before( $tableRow );
        $('#currency-lang-table').find('tr.default_currency').before( $LangTableRow );
    });

    $(document).on('click','.save_currency',function(e){
        e.preventDefault();

        var $this = $(this);
        var $ajaxLoader = $('<span class="spinner">');
        var $messageContainer = $('<span class="wcml-error">');

        $this.prop('disabled',true);

        var parent = $(this).closest('tr');

        parent.find('.save_currency').hide();
        parent.find('.cancel_currency').hide();
        $ajaxLoader.insertBefore($this).show();

        if(parent.find('.currency_id').val() > 0){
            var currency_id =  parent.find('.currency_id').val();
        }else{
            var currency_id = 0;
        }

        $currencyCodeWraper = parent.find('.currency_code');
        $currencyValueWraper = parent.find('.currency_value');

        var currency_code = $currencyCodeWraper.find('select').val();
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
            parent.find('.save_currency').show();
            parent.find('.cancel_currency').show();
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


        $.ajax({
            type : "post",
            url : ajaxurl,
            dataType: 'json',
            data : {
                action: "wcml_update_currency",
                wcml_nonce: $('#upd_currency_nonce').val(),
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
                    parent.find('.currency_id').val(response.id);
                }
                var curr_code = parent.closest('tr').find('.currency_code select').val();
                parent.find('.currency_code .code_val').html(parent.closest('tr').find('.currency_code select').find(":selected").text()+response.symbol).show();
                parent.find('.currency_code select').hide();
                parent.find('.currency_value span.curr_val').html(parent.closest('tr').find('.currency_value input').val());
                parent.find('.currency_value span.curr_val_code').html(curr_code);
                parent.find('.currency_value span.curr_val').show();
                parent.find('.currency_value input').hide();
                parent.find('.currency_changed').html('('+today+')').show();
                parent.find('.edit_currency').show();
                parent.find('.delete_currency').show();

                $this.closest('.edit-mode').removeClass('edit-mode');
                $('.js-table-row-wrapper select option[value="'+curr_code+'"]').remove();    
                $('.currency_languages select').each(function(){
                   $(this).append('<option value="'+curr_code+'">'+curr_code+'</option>');
                });
            },
            complete: function() {
                $ajaxLoader.remove();
                $this.prop('disabled',false);
            }
        });

        return false;
});


    $(document).on('click','.delete_currency',function(e){
        e.preventDefault();

        var parent = $(this).closest('tr');
        var $this = $(this);
        var $ajaxLoader = $('<span class="spinner">');
        var currency_id =  parent.find('.currency_id').val();
        $this.hide();
        $this.parent().append($ajaxLoader).show();

        $.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "wcml_delete_currency",
                wcml_nonce: $('#del_currency_nonce').val(),
                currency_id : currency_id,
                code: parent.find('.currency_code select').val()
            },
            success: function(response) {
                var index = parent[0].rowIndex;

                $('.currency_languages select').each(function(){
                   if(parent.find('select').val() == $(this).val()){
                       update_default_currency($(this).attr('rel'),0);
                   }
                   $(this).find('option[value="'+parent.find('select').val()+'"]').remove();
                });
                $('#currency-lang-table tr').eq(index).remove();
                parent.remove();
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

    $(document).on('click','.currency_languages a.on_btn',function(e){
        $(this).closest('ul').find('.on').removeClass('on');
        $(this).parent().addClass('on');
        var index = $(this).closest('tr')[0].rowIndex;
        var lang =  $(this).attr('rel');
        var code = $('.currency_table tr').eq(index).find('.currency_code select').val();
        $('.currency_languages select[rel="'+lang+'"]').append('<option value="'+code+'">'+code+'</option>');
        update_currency_lang(1,lang,code,0);
    });

    $(document).on('click','.currency_languages a.off_btn',function(e){
        $(this).closest('ul').find('.on').removeClass('on');
        $(this).parent().addClass('on');
        var index = $(this).closest('tr')[0].rowIndex;
        var lang =  $(this).attr('rel');
        var code = $('.currency_table tr').eq(index).find('.currency_code select').val();
        if($('.currency_languages select[rel="'+lang+'"]').val() == code){
            update_currency_lang(0,lang,code,1);
        }else{
            update_currency_lang(0,lang,code,0);
        }
        $('.currency_languages select[rel="'+lang+'"] option[value="'+code+'"]').remove();
    });

    function update_currency_lang(value,lang,code,upd_def){
        $.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'wcml_update_currency_lang',
                value: value,
                lang: lang,
                code: code,
                wcml_nonce: $('#update_currency_lang_nonce').val()
            },
            success: function(){
                if(upd_def){
                    update_default_currency(lang,0);
                }
            }
        });
    }

    $('.default_currency select').change(function(){
        update_default_currency($(this).attr('rel'),$(this).val());
    });

    function update_default_currency(lang,code){
        $.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'wcml_update_default_currency',
                lang: lang,
                code: code,
                wcml_nonce: $('#wcml_update_default_currency_nonce').val()
            },
            success: function(){
            }
        });
    }

function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

});

