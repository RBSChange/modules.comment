jQuery('document').ready(function(){
	if (jQuery('ol.star-rating-accessible>input').length > 0)
	{
		var inputName = jQuery('ol.star-rating-accessible>input').get(0).getAttribute('name');
		var inputValue = jQuery('ul.star-rating>li.star>a').index(jQuery('ul.star-rating>li.star>a.clicked'))+1;
		jQuery('ol.star-rating-accessible').remove();
		jQuery('ul.star-rating').show();
		jQuery('ul.star-rating').parents('form').prepend('<input type="hidden" name="' + inputName + '" value="' + inputValue +  '" />');
	
		jQuery('ul.star-rating>li.star>a').click(function() {
	  		jQuery('input[name="'+inputName+'"]').val(jQuery('ul.star-rating>li.star>a').index(jQuery(this))+1);
	  		jQuery('ul.star-rating>li.star>a.clicked').each(function(i) { jQuery(this).removeClass('clicked'); });
			jQuery(this).addClass('clicked');
	 	});
	}
});
