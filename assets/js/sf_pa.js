let postcall;
jQuery(document).ready(
    function(){
        jQuery('#sf_pa_search_input').on('input', function(){
            var input_container = jQuery(this).closest('.sf_pa_search_input_container');
            if(jQuery(this).val().length > 2){
                if (postcall){
                    postcall.abort();
                }
                showLoader();
                jQuery('.sf_pa_autosuggest_response').remove();
                jQuery('.sf_pa_autosuggest_container').remove();

                var data = {
                    action: 'sf_pa_search',
                    sf_pa_search: jQuery(this).val()
                };

                postcall = jQuery.post(sf_pa_ajax_object.ajaxurl, data, function(response) {
                    hideLoader();
                    jQuery(input_container).append('<div class="sf_pa_autosuggest_container"></div>');
                    if(response.length > 0){
                        for (var i = 0; i < response.length; ++i) {
                            var to_append = '<div class="sf_pa_autosuggest_response"><div>';
                            
                            //Product Image
                            if(response[i].show_thumbnail == 'on' && response[i].type == 'product'){
                                to_append += '<div><img style="width:50px; height: auto;" src="'+response[i].img_url+'"></div>';
                            }
                            //Title and Link
                            to_append += '<div><div class="sf_pa_autosuggest_title"><a href="'+response[i].link+'">'+response[i].title+'</a>';
                            //Category title
                            if(response[i].show_cat == 'on' && response[i].type == 'product'){
                                to_append += '<p>'+response[i].cat_title+'</p>';
                            }
                            //Category title
                            if(response[i].show_price == 'on' && response[i].type == 'product'){
                                to_append += '<p>'+response[i].price+'</p>';
                            }
                            to_append += '</div></div>';
                            
                            jQuery('.sf_pa_autosuggest_container').append(to_append);
                        }
                    }
                    else{
                        jQuery('.sf_pa_autosuggest_container').append('<p>'+sf_pa_ajax_object.not_found+'</p>');
                    }
                    
                    jQuery('.sf_pa_autosuggest_container').append('<div class="sf_pa_autosuggest_all_res">'+sf_pa_ajax_object.show_results+'</div>');
            	});

            }
        });

        jQuery(document).mouseup(function(e)
        {
            var container =jQuery(".sf_pa_autosuggest_container");
            if (!container.is(e.target) && container.has(e.target).length === 0)
            {
                container.hide();
            }
        });

        jQuery('.sf_pa_search_input_container').on('click', '.sf_pa_autosuggest_response', function(e){
            var href = jQuery(this).find('a').attr('href');
            window.location.href = href;
        });

        jQuery('.sf_pa_search_input_container').on('click', '.sf_pa_autosuggest_all_res', function(e){
            jQuery(this).closest('.sf_pa_search_input_container').find('form').submit();
        });
    }
);

function showLoader(){
    jQuery('.sf_pa_loader').show();
}

function hideLoader(){
    jQuery('.sf_pa_loader').hide();
}
