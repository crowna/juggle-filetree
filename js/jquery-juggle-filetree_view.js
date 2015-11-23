(function($) {
	//correction for WP wysiwyg editor
	$('div.movable').each(function(){
		if ( !$(this).children(":first").hasClass('disclose') ){
			$(this).prepend('<span class="disclose"><span></span></span>');
		}
	});
	if ( $('#files_found span').length == 0 ) {
		$('#files_found').append('<span></span>');
	}
	
	//toggling
	$('.disclose').on('click', function() {
		var li = $(this).closest('li');
		if (li.hasClass('mjs-nestedSortable-collapsed') ){
			li.find('ol').first().show(300);
		}else{
			li.find('ol').first().hide(300);
		}
		li.toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
	});
	$('#files_found span').text( $('.msfile').length ); //file count update
	$('div').css('cursor','inherit');
	
	$('.hf').remove();
	if(nsn) $('.new-file').remove();
})(jQuery);