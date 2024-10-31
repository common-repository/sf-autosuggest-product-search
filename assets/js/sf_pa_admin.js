jQuery(document).ready(function($){
    $('.sf_pa_color_picker').wpColorPicker();
    $('#sf_remove_transient').on('click', function(){
        /* TODO: ajax to delete transients */
        var data = {
			'action': 'delete_sf_pa_transient'
		};
		jQuery.post(ajaxurl, data, function(response) {
            location.reload();
        });
			
    });
});