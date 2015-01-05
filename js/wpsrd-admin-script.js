/**
 * Plugin Name: Simple Revisions Delete by b*web
 * Plugin URI: http://b-website.com/
 * Author: Brice CAPOBIANCO - b*web
 */	
jQuery(document).ready(function($) {
	//Ajax clear revisions
	if ($('.misc-pub-revisions > b').length > 0){
		if ($('.misc-pub-revisions > b').length > 0){
			$('#wpsrd-clear-revisions').appendTo('.misc-pub-revisions').show();
		}
		$('#wpsrd-clear-revisions a.once').live("click", function(event){
			event.preventDefault();
			$(this).removeClass('once').html($(this).data('action')).blur();
			$('#wpsrd-clear-revisions a.wpsrd-link').css({'text-decoration' : 'none'})
			$('#wpsrd-clear-revisions .wpsrd-loading').css('display','inline-block');
			$.ajax({
				url: ajaxurl, 
				dataType: "json",
				data: {
					'action': 'async_wpsrd_remove_revisions',
					'wpsrd-nonce' : $('#wpsrd-clear-revisions a').data('nonce'),
					'wpsrd-post_ID' : $('#post #post_ID').val()
				}, 
				success: function(response) {
					if( response.success) {
						$('#wpsrd-clear-revisions .wpsrd-loading, .misc-pub-revisions > a').remove();
						$('.misc-pub-revisions > b').text('0');
						$('#wpsrd-clear-revisions a.wpsrd-link').css({'color' : '#444', 'font-weight': '600'}).html(response.message);
					} else { 
						$('#wpsrd-clear-revisions .wpsrd-loading').remove();
						$('#wpsrd-clear-revisions a.wpsrd-link').css({'display' : 'block', 'color' : '#a00', 'font-weight': 'normal'}).html(response.message);
					}
					setTimeout( function () {
						$('#wpsrd-clear-revisions a.wpsrd-link').fadeOut();
					}, 3500);
				},
				error: function(response){
					$('#wpsrd-clear-revisions .wpsrd-loading').remove();
					$('#wpsrd-clear-revisions a').html($('#wpsrd-clear-revisions a').data('error')).css({'display' : 'block', 'color' : '#a00', 'font-weight': 'normal'});
				}
			});
		});
	}
});