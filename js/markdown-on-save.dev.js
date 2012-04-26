(function($){
	var mdoff = function(){
		$('#cws_using_markdown').removeAttr('checked');
		$('img.markdown-on').hide();
		$('img.markdown-off').show();				
	}
	var mdon = function() {
		$('#cws_using_markdown').attr('checked','checked');
		$('img.markdown-off').hide();
		$('img.markdown-on').show();
	}
	$('#cws-markdown').ready(function(){
		$('#cws-markdown img.markdown-on').click(function(e){
			e.stopPropagation();
			mdoff();
		});
		$('#cws-markdown img.markdown-off').click(function(e){
			e.stopPropagation();
			mdon();
		});
		$('#cws_using_markdown').change(function(){
			if ( $(this).attr('checked') )
				mdon();
			else
				mdoff();
		});
	});
})(jQuery);