function updateSort() {
	var num, sor;
	num = 1;
	sor = '0';
	$("#sortable").children().each( function() {
		sor = sor + ',' + $(this).attr('rel');
	});
	$("#sort").val( sor );
}

function updateFeaturesSort() {
	var num, sor;
	num = 1;
	sor = '0';
	$("#sortable-features").children().each(function () {
		sor = sor + ',' + $(this).attr('rel');
	});
	$("#featuresort").val( sor );
}

function mediaPreview(id, filename) {
	if( $('#row' + id + ' .preview').html() === '' ) {
		$('#row' + id + ' .preview').html('<img src="/_elements/thumb.php?i=' + filename + '&amp;x=200&amp;y=200">');
	}
}

$(document).ready(function(){
	$(".delete").not(".formdelete").click(function(e) {
		return(confirm("Are you sure?"));
	});
	
	$(".formdelete").click(function(e) {
		var id = $(this).attr("data-id");
		var type = $(this).attr("data-type");
		var keyname = $(this).attr("data-keyname");
		
		if(confirm("Are you sure (2)")) {
			$("#delete #theid").val(id);
			if(typeof type !== "undefined") {
				$("#delete #type").val(type);
			}
			if(typeof keyname !== "undefined") {
				$("#delete #theid").attr("name", keyname);
			}
			$("#delete #confirmation").val(id);
			$("#delete form").submit();
		}
	});
	
	$('#add, .add, .button.add').button({ icons: { primary: "ui-icon-plus" } });
	$('#sort, .sort, .button.sort').button({ icons: { primary: "ui-icon-carat-2-n-s" } });
	
	// Image preview on hover; moving the div is OK as long as it retains the appropriate classes
	// NOTE: this populates a preview the first time, then hides/shows it.
	$(".mediaRow.mediaPreview.image").hover(function(e){
		var img = $(this).find("a").first().attr("href");
		var id = $(this).attr("id");
		var previewSelector = ".preview."+ id
		var preview = $(previewSelector);
		var filename = img.substring(img.lastIndexOf('/')+1);
//		console.log("ID: "+ id +", preview selector=("+ previewSelector +")")
		
		if(e.type === 'mouseover' || e.type === 'mouseenter') {
//			console.log("handling action type: "+ e.type);
			if(preview.html().length > 0) {
//				console.log("showing selector");
			}
			else {
//				console.log("populating selector, length was: "+ preview.html().length);
				preview.html('<img src="/_elements/thumb.php?i='+ filename +'&x=200&y=200">');
			}
			$(previewSelector).css("top", "200px");
			$(previewSelector).show();
		}
		else {
//			console.log("hiding "+ previewSelector);
			$(previewSelector).hide();
		}
	});
});
