$("document").ready(function(){

if ($("ol.star-rating-accessible>input").length > 0)
{
	var inputName = $("ol.star-rating-accessible>input").get(0).getAttribute("name");
	var clickedIndex = $("ul.star-rating>li.star>a").index($("ul.star-rating>li.star>a.clicked"));
	var inputValue = -1;
	if (clickedIndex != -1)
	{
		inputValue = $("ul.star-rating>li.star>a").index($("ul.star-rating>li.star>a.clicked"))+1;
	}
	else
	{
		inputValue = 0;
	}
	$("ol.star-rating-accessible").remove();
	$("ul.star-rating").show();
	$("ul.star-rating").parents('form').prepend('<input type="hidden" name="' + inputName + '" value="' + inputValue +  '" />');

	$("ul.star-rating>li.star>a").click(function(){
  		$("input:hidden[name="+inputName+"]").val($("ul.star-rating>li.star>a").index($(this))+1);
  		$("ul.star-rating>li.star>a.clicked").each(function(i){
			$(this).removeClass('clicked');
  		});
		$(this).addClass('clicked');
 	});
}
});
