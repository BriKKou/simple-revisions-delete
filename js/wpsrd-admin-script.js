/**
 * Plugin Name: Simple Revisions Delete by b*web
 * Plugin URI: http://b-website.com/
 * Author: Brice CAPOBIANCO - b*web
 */	
jQuery(document).ready(function($) {
	//Ajax clear revisions
	if ($('.misc-pub-revisions b').length > 0){
		$('#wpsrd-clear-revisions').appendTo('.misc-pub-revisions').show();
		$('#wpsrd-clear-revisions a.once').live("click", function(event){
			event.preventDefault();
			$(this).removeClass('once').html($(this).data('action')).blur();
			$('#wpsrd-clear-revisions a.wpsrd-link').css({'text-decoration' : 'none'})
			$('#wpsrd-clear-revisions .wpsrd-loading').css('display','inline-block');
			$.ajax({
				url: ajaxurl, 
				data: {
					'action': 'wpsrd_purge_revisions',
					'wpsrd-nonce' : $('#wpsrd-clear-revisions a').data('nonce'),
					'wpsrd-post_ID' : $('#post #post_ID').val()
				}, 
				success: function(response) {
					if( response.success) {
						$('#wpsrd-clear-revisions .wpsrd-loading, .misc-pub-revisions > a').remove();
						$('.misc-pub-revisions b').text('0');
						$('#wpsrd-clear-revisions a.wpsrd-link').addClass('sucess').html('<span class="dashicons dashicons-yes" style="color:#7ad03a;"></span> ' + response.data);
					} else { 
						$('#wpsrd-clear-revisions .wpsrd-loading').remove();
						$('#wpsrd-clear-revisions a.wpsrd-link').addClass('error').html(response.data);
					}
					setTimeout( function () {
						$('#wpsrd-clear-revisions a.wpsrd-link').fadeOut();
					}, 3500);
				},
				error: function(response){
					$('#wpsrd-clear-revisions .wpsrd-loading').remove();
					$('#wpsrd-clear-revisions a').html($('#wpsrd-clear-revisions a').data('error')).addClass('error');
				}
			});
		});
	}
});