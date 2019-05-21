
//var places = new Array(); //corresponding array of place objects
/*
places[1]={
	name:'Musical',
	category:'Do',
	subcategory:'Entertainment',
	dates: 'startadultschildren'
};
places[56]={
	name:'Rough Riders Hotel',
	category:'Stay',
	subcategory:'Hotel',
	dates: 'startendadultschildren'
};
places[987]={
	name:'Theodore\'s Dining',
	category:'Eat',
	subcategory:'Fine Dining',
	dates:'start'
};
*/ 
/*
id: id of the location
name: name of place
category: main category
subcategory: secondary category
*/

var mymedora = getMyMedora();


function myMedoraToggle(id, div){
	
	
	
	if(id.search('itinerary') >= 0){
			
			mymedora={places:{}, mymedora_id:0, start:'', end:''};
			
			setMyMedora(mymedora);
			
			
			showItineraryOptions(div, id);

	}
	else{
			
		id = new Number(id.replace('location-','')).valueOf();
	
	
		if(checkDuplicatePlace(id, mymedora.places)){ //if already in object, then delete
			status = removePlace(id);
					
			updateTrip(div);
			
			
		}
		else{
			
			status = addPlace(id);
			
			showOptions(div, id);
		
		}
	}
	
	
	return status;
}
function addPlace(id){
	
	var thisplace={
		id:id,
		status:1,
		name: places[id].name,
		category: places[id].category,
		subcategory: places[id].subcategory,
		adults:0,
		children:0,
		start:'',
		end:''
	};
	
	mymedora.places[id] = thisplace;
	
	return 1;
	
}
function removePlace(id){
	
	delete mymedora.places[id];
	
	
	return 0;
}


function getMyMedora(){
	
	// TODO: look at the contents of this, and ensure the data isn't mangled.
	mymedora = getCookie('mymedora');
		
	if(mymedora=='' || mymedora==undefined || mymedora==false){
		mymedora={places:{}, mymedora_id:0, start:'', end:''};
	}
	else{
		mymedora = $.evalJSON(mymedora);
	}
	
	return mymedora;
}

function setMyMedora(){
	
	var mymedora_json = $.toJSON(mymedora);
	
	$.ajax(
		{
			url: "/_elements/ajax/mymedora.php",
			type: 'POST',
			data: {info: mymedora_json, mymedora_id:mymedora.mymedora_id}
		}
	).done(function ( data ) {
		mymedora.mymedora_id = data;
	});
	
	mymedora_json = $.toJSON(mymedora);
	
	setCookie('mymedora',mymedora_json,365);
	
	
}

function myMedoraLinks(target, thisclass){
	$(target).each(function(){
		
		
		if($(this).attr('id').search('itinerary')>=0){
			var id = $(this).attr('id');
			
			id = new Number(id.replace('itinerary-','')).valueOf();
			
			//do nothing
			
		}else{
			var id = $(this).attr('id');
			
			id = new Number(id.replace('location-','')).valueOf();
			
					
			if(checkDuplicatePlace(id, mymedora.places)){
				$(this).addClass(thisclass);
			}
			else{
				$(this).removeClass(thisclass);
			}
		}
	});
	
	

}


function setCookie(c_name,value,exdays)
{
var exdate=new Date();
exdate.setDate(exdate.getDate() + exdays);
var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString()+"; path=/");
document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name)
{
var i,x,y,ARRcookies=document.cookie.split(";");
for (i=0;i<ARRcookies.length;i++)
{
  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
  x=x.replace(/^\s+|\s+$/g,"");
  if (x==c_name)
    {
    return unescape(y);
    }
  }
}
function getObjectLength(my_object){
	var len = 0;
	for (var o in my_object) {
		len++;
	}
	return len;
}

function checkDuplicatePlace(needle, haystack){
	for (var p in haystack) {
		
		thisid = haystack[p].id;
		
		if(needle == thisid){
			return true;
		}
	}
	return false;
	
}

function showOptions(divid, lastadded){
	
	var top = window.scrollY;
	
	
	if($('#overlay').length <= 0){
		createOverlay();
	}
	
	var place = places[lastadded];
	var current = mymedora.places[lastadded];
	
	
	var html ='';
		
	if(place.category=='Do' && place.subcategory=='Events'){
		
		
		
			mymedora.places[lastadded].start = place.start;
			mymedora.places[lastadded].end = place.end;
			mymedora.places[lastadded].adults = 0;
			mymedora.places[lastadded].children = 0;
			
			updateTrip(divid);
			
			
			
		
				showSuggested(3);
				
				$('html').css('overflow','hidden');
		
				window.scrollTo(0, top);
				$('#overlay').css('top',top+'px');
				$('#overlay').fadeIn(500);
			
			
				
	}
	else{
	
	
		html+='<form onsubmit="return false;">';
		html+='<h2>'+place.name+'</h2>';
		
		
		if(place.dates.search('start') > -1 && place.dates.search('end') > -1){
			html+='<p><label>Date Checking In:<input type="text" name="start" class="mymedora datepicker start" value="'+current.start+'" /></label></p><p><label>Date Checking Out:<input name="end" type="text" class="mymedora datepicker end" value="'+current.end+'" /></label></p>';
			
		}
		else if(place.dates.search('start') > -1){
			html+='<p>Date:<input type="text" name="start" class="mymedora datepicker start" value="'+current.start+'" /></p>';
		}
		
		if(place.dates.search('adults') > -1 && place.dates.search('children') > -1){
			html+='<p><label># of Adults <input type="text" class="mymedora" name="adults" value="'+current.adults+'" /></label></p><p><label># of Children <input name="children" class="mymedora" type="text" value="'+current.children+'" /></label></p>';
		}
		
		html+='<p><input class="mymedora-btn" onclick="updateDetails(\''+divid+'\', '+lastadded+'); return false;" type="submit" value="" /></p>';
		html+='</form>';
		
	
	
		$('#overlay .box .content').html(html);
		
		$('html').css('overflow','hidden');
		
		window.scrollTo(0, top);
		$('#overlay').css('top',top+'px');
		$('#overlay').fadeIn(500);
		
		
		
		if(place.start !='0000-00-00' && place.end !='0000-00-00'){
			thisstarttime = Date.parse(place.start);
			thisstart = new Date();
			thisstart.setTime(thisstarttime);
			
			thisstartmonth = thisstart.getMonth()+1;
			thisstartdate = thisstart.getDate();
			thisstartyear = thisstart.getFullYear();
			
			thisstart = thisstartmonth+'/'+thisstartdate+'/'+thisstartyear;
			
			thisendtime = Date.parse(place.end);
			thisend = new Date();
			thisend.setTime(thisendtime);
			
			thisendmonth = thisend.getMonth()+1;
			thisenddate = thisend.getDate();
			thisendyear = thisend.getFullYear();
			
			thisend = thisendmonth+'/'+thisenddate+'/'+thisendyear;
			
			
			$('#overlay .datepicker.start').datepicker({ minDate: thisstart, maxDate: thisend, dateFormat: "mm/dd/yy"});
		}
		else if(place.category == 'Stay'){
			
			today = new Date();
			thismonth = today.getMonth()+1;
			thisdate = today.getDate();
			thisyear = today.getFullYear();
			
			today = thismonth+'/'+thisdate+'/'+thisyear;
			
			$('#overlay .datepicker.start').datepicker(
				{
					minDate: 0,
					onClose: function( selectedDate ) {
						$('#overlay .datepicker.end').datepicker({ minDate: selectedDate, dateFormat: "mm/dd/yy"});
					}
				}
			);
		
			//$('#overlay .datepicker.start').datepicker({ minDate: 0,dateFormat: "mm/dd/yy"});
			//$('#overlay .datepicker.end').datepicker({ minDate: 0,dateFormat: "mm/dd/yy"});
		
		
		}
		else{
			$('#overlay .datepicker').datepicker({dateFormat: "mm/dd/yy"});
			
		}
	
	
	}
	
}
function showItineraryOptions(divid, itinerary_id){
	
	var top = window.scrollY;
	
	
	if($('#overlay').length <= 0){
		createOverlay();
	}
	
	//var place = places[lastadded];
	//var current = mymedora.places[lastadded];
	
	
	var html ='';
		
		id = itinerary_id.replace('itinerary-','');
		
		$.ajax({
			url: '/_elements/ajax/itineraries.php?type=name&itinerary_id='+id,
			success: function(data) {
				itinerary_name = data;

	
	
		html+='<form onsubmit="return false;">';
		html+='<h2>'+itinerary_name+'</h2>';
		
		

		html+='<p><label>Date Checking In:<input type="text" name="start" class="mymedora datepicker start" value="" /></label></p><p><label>Date Checking Out:<input name="end" type="text" class="mymedora datepicker end" value="" /></label></p>';
			
		html+='<p><label># of Adults <input type="text" class="mymedora" name="adults" value="" /></label></p><p><label># of Children <input name="children" class="mymedora" type="text" value="" /></label></p>';
		
		html+='<p><input class="mymedora-btn" onclick="updateItineraryDetails(\''+divid+'\', \''+itinerary_id+'\'); return false;" type="submit" value="" /></p>';
		html+='</form>';
		
	
	
		$('#overlay .box .content').html(html);
		
		$('html').css('overflow','hidden');
		
		window.scrollTo(0, top);
		$('#overlay').css('top',top+'px');
		$('#overlay').fadeIn(500);
		
		
			now = new Date();
			nowyear = now.getFullYear();
		
			thisstarttime = Date.parse(nowyear+'-06-05');
			thisstart = new Date();
			thisstart.setTime(thisstarttime);
			
			thisstartmonth = thisstart.getMonth()+1;
			thisstartdate = thisstart.getDate();
			thisstartyear = thisstart.getFullYear();
			
			thisstart = thisstartmonth+'/'+thisstartdate+'/'+thisstartyear;
			
			thisendtime = Date.parse(nowyear+'-09-05');
			thisend = new Date();
			thisend.setTime(thisendtime);
			
			thisendmonth = thisend.getMonth()+1;
			thisenddate = thisend.getDate();
			thisendyear = thisend.getFullYear();
			
			thisend = thisendmonth+'/'+thisenddate+'/'+thisendyear;
			
			
			$('#overlay .datepicker.start').datepicker({ minDate: thisstart, maxDate: thisend, dateFormat: "mm/dd/yy"});
			$('#overlay .datepicker.end').datepicker({ minDate: thisstart, maxDate: thisend, dateFormat: "mm/dd/yy"});
		
	
				}
		});
	
	
}
function updateTrip(div) {

	var loc = window.location.toString();

	if (loc.search("my-medora") > -1) {
		var full = true;
	}
	else {
		var full = false;
	}


	var months = Array();
	months[0] = 'January';
	months[1] = 'February';
	months[2] = 'March';
	months[3] = 'April';
	months[4] = 'May';
	months[5] = 'June';
	months[6] = 'July';
	months[7] = 'August';
	months[8] = 'September';
	months[9] = 'October';
	months[10] = 'November';
	months[11] = 'December';

	if (getObjectLength(mymedora.places) > 0) {
		
		cleanDates();
		
		var placesordered = reorderTrip();

		var html = '<h4>';
		html += mymedora.start;
		html += ' - ';
		html += mymedora.end;
		html += '</h4>';
		html += '<ul>';


		for (var time in placesordered) {


			date = new Date();
			date.setTime(time);
			htmldate = months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();

			if (getObjectLength(placesordered[time]) > 0) {
				html += '<li class="date"><b>' + htmldate + '</b></li>';
			}

			for (var p in placesordered[time]) {


				thisplace = placesordered[time][p];


				if (full === true) {

					html += '<li><div class="name">' + thisplace.name + '</div>';

					html += '<div class="hours">' + thisplace.hours + '</div><div class="mmbuttons">';



					html += '<div class="btn-left"></div>';
					html += '<div class="btn-bg"><a class="edit" href="#" onclick="showOptions(\'#mymedora\', ' + thisplace.id + ');return false;"><span>edit</span></a></div>';
					html += '<div class="btn-right" style="margin-right: 20px;"></div>';

					html += '<div class="btn-left"></div>';
					html += '<div class="btn-bg"><a class="remove" href="#" onclick="removePlace(' + thisplace.id + ');updateTrip(\'#mymedora\');return false;"><span>remove</span></a></div>';
					html += '<div class="btn-right" style="margin-right: 20px;"></div>';

					if (thisplace.category == 'Stay') {
						html += '<div class="btn-left"></div>';
						html += '<div class="btn-bg"><a class="hotel book" href="/reservations/hotels-and-camping/" target="_blank"><span>book room now</span></a></div>';
						html += '<div class="btn-right" style="margin-right: 20px;"></div>';

						html += '<div class="resnum"><span>Confirmation #</span><div class="block"></div></div>';

					}
					else if (thisplace.special == 'bullypulpit') {
						html += '<div class="btn-left"></div>';
						html += '<div class="btn-bg"><a class="golf book" href="https://webres.goibs.com/WebResNext/Webres/WRDefault.aspx?ID=FCB5A49F" target="_blank" onclick="return popUpRes(this.href,\'golf\');"><span>book tee time!</span></a></div>';
						html += '<div class="btn-right" style="margin-right: 20px;"></div>';

						html += '<div class="resnum"><span>Confirmation #</span><div class="block"></div></div>';
					}
					else if (thisplace.special == 'musical' || thisplace.special == 'fondue') {
						html += '<div class="btn-left"></div>';
						html += '<div class="btn-bg"><a class="musical book" href="/reservations/attractions/" target="_blank"><span>book tickets now!</span></a></div>';
						html += '<div class="btn-right" style="margin-right: 20px;"></div>';
						html += '<div class="resnum"><span>Confirmation #</span><div class="block"></div></div>';
					}

					html += '</div></li>';

				}
				else {
					html += '<li>';
					html += '<a class="edit" href="#" onclick="showOptions(\'#mymedora\', ' + thisplace.id + ');return false;">' + thisplace.name + '</a>';
					html += '</li>';


				}


			}
		}



		html += '</ul>';



		if (full === true && $('body').hasClass('mobile') === false) {
			$('.right ' + div).html(html);
		}
		else {
			$(div).html(html);
		}



	}

	/*
	 else{
	 $(div).html('<a href="/plan-your-trip/my-medora">Get a $50 gift card when you use <strong>My Medora</strong> to book one of our special Medora Musical packages.</a>	');
	 }
	 */





	setMyMedora();
	if (full === true) {
		setMap();
	}

}




function reorderTrip(){
	var newplaces = {};
	
	
	
	var start = new Date(mymedora.start);
	var startmilli = start.getTime();
	
	var end = new Date(mymedora.end);
	var endmilli = end.getTime();
	
		
	var onehour = 3600000;
	var oneday = onehour * 24;
	
	var milli = startmilli;
	
	
	
	while(milli <= endmilli){
		
		thisdate = {};

		for ( var p in mymedora.places){
			thisplace = mymedora.places[p];
			
			
			var thisstart = new Date(thisplace.start);
			var thisstartmilli = thisstart.getTime();
			
			var thisend = new Date(thisplace.end);
			var thisendmilli = thisend.getTime();

			if(thisstartmilli == milli || thisplace.category == 'Stay'){
				
				thisdate[thisplace.id]=places[thisplace.id];
			}
			
		}
		
		
		
		newplaces[milli]=thisdate;
		
		milli += oneday;
		
	}
	
	return newplaces;
}

function removeInvalidPlaces(){
	for ( var p in mymedora.places){
		
		if(formatDate(mymedora.places[p].start) == 'NaN/NaN/NaN'){
			
			delete mymedora.places[p];
		}
	}
	
	myMedoraLinks('.addmm', 'include');
}

function cleanDates(){
	
	removeInvalidPlaces();

mymedora.start='';
mymedora.end='';

	for ( var p in mymedora.places){
		if(mymedora.places[p].end==undefined){
			mymedora.places[p].end = mymedora.places[p].start;
		}
		 mymedora.places[p].start = formatDate(mymedora.places[p].start);
		 mymedora.places[p].end = formatDate(mymedora.places[p].end);
		
		
		if(mymedora.start == ''){
			mymedora.start = mymedora.places[p].start;
		}
		if(mymedora.end == ''){
			mymedora.end = mymedora.places[p].end;
		}
		
		var d = Date.parse(mymedora.places[p].start);
		var d2 = Date.parse(mymedora.places[p].end);
		
		var compare = Date.parse(mymedora.start);
		var compare2 = Date.parse(mymedora.end);
		
		if(d < compare || compare == 0){
			mymedora.start = mymedora.places[p].start;
		}
		if(d2 > compare2 || compare2 == 0){
			mymedora.end = mymedora.places[p].end;
		}
	}
	
	mymedora.start = formatDate(mymedora.start);
	mymedora.end = formatDate(mymedora.end);
	
}

function formatDate(olddate){
	

	if(typeof olddate !== "string" || olddate === null || olddate.length < 5) {
		return null;
	}
	else {
	
		var parsed = olddate.replace('-','/').replace('-','/').replace('-','/').replace('-','/');
		parsed = Date.parse(parsed);
		
		
		var date = new Date();
			date.setTime(parsed);
		
		var month = date.getMonth()+1;
		var day = date.getDate();
		var year = date.getFullYear();
	
		var newdate = month+'/'+day+'/'+year;
		
		return newdate;
	}
	
}

function createOverlay(){
	var html = '<div id="overlay" style="display: none;"><div class="box"><div class="close"><a href="#" onclick="closeOverlay();"><img style="float: right; margin-top: -30px; margin-right: -30px;" src="/_elements/img/btn-close.png"></a></div><div class="content"></div></div></div>';
	$('body').append(html);
}
function closeOverlay(){
	var top = window.scrollY;
	
	
	
	
	
	
	$('html').css('overflow','auto');
	$('#overlay').fadeOut(500);
	
	removeInvalidPlaces();
	
	
	window.scrollTo(0, top);
	
	
	
}

function updateDetails(divid, id){
	
	if(places[id].category=='Stay'){
		
		oneday = 1000*60*60*24;
		
		endval = Date.parse($('#overlay form input[name=end]').val());
		endval = endval - oneday;
		
		end = new Date();
		end.setTime(endval);
		
		end = (end.getMonth()+1)+'/'+end.getDate()+'/'+end.getFullYear();
	}
	else {
		end = $('#overlay form input[name=end]').val();	
	}
	
	
	mymedora.places[id].start = $('#overlay form input[name=start]').val();
	mymedora.places[id].end = end;
	mymedora.places[id].adults = $('#overlay form input[name=adults]').val();
	mymedora.places[id].children = $('#overlay form input[name=children]').val();
	
	
	
	
	updateTrip(divid);
	
	
	showSuggested(3);
	
	//closeOverlay();
	
}

function updateItineraryDetails(divid, itinerary_id){
	
	
	itinerary_id = new Number(itinerary_id.replace('itinerary-','')).valueOf();
	
	
	
	start = $('#overlay form input[name=start]').val();
	staystart = $('#overlay form input[name=start]').val();
	
	end = $('#overlay form input[name=end]').val();
	stayend = $('#overlay form input[name=end]').val();
	
	adults = $('#overlay form input[name=adults]').val();
	children = $('#overlay form input[name=children]').val();
				
				
	
	$.ajax({
		url: '/_elements/ajax/itineraries.php?itinerary_id='+itinerary_id,
		success: function(data) {
		
			itplaces = $.parseJSON(data);
			
			
			for(i=0;i<itplaces.length;i++){
				
				
			
				thisid = itplaces[i].place_id;
				day = itplaces[i].day;
				
				
				addPlace(thisid);
				
				if(mymedora.places[thisid].category=='Stay'){
					start = staystart;
					end = stayend;	
				}
				else{
					startday = day * 86400000; //time in miliseconds from start of trip
					startday = startday - 86400000; //zero it out if needed
					
					
					difference = Date.parse(staystart)+startday;
					
					startday = new Date(difference);
					start = (startday.getMonth()+1)+'/'+startday.getDate()+'/'+startday.getFullYear();
					end = start;
					

				}
				
				mymedora.places[thisid].start = start;
				mymedora.places[thisid].end = end;
				mymedora.places[thisid].adults = adults;
				mymedora.places[thisid].children = children;
				
				
	
	
				
				
			}
			
			//updateTrip(divid);
			
			setMyMedora();
						
			window.location = '/plan-your-trip/my-medora/';
			
			
			
			
			//showSuggested(3);
			
			//closeOverlay();
			
		
		}
	});
	
		
	
	
	
	
	
	
}




function getSuggested(){
	
	var suggested = new Array();
	var stay = false;
	
	for ( var p in mymedora.places){
		if(mymedora.places[p].category=='Stay'){
			stay=true;
			break;		
		}
	}
	for ( var p in places){
		if(mymedora.places[p]===undefined && places[p].subcategory!='Events'){ //check if place is in list already
			if(places[p].category=='Do'){
				suggested.push(p);
			}
			else if(places[p].category=='Stay' && stay === false){ //only suggest if they don't have a place to stay
				suggested.push(p);
			}
			else if(places[p].category=='Eat'){
				suggested.push(p);
			}
		}
	}
	
	return suggested;
	
}

function showSuggested(maxsuggestions){
	if($('body.mobile').length>=1){
		closeOverlay();
		return false;
	}

	var suggested = getSuggested();
	
	var indices = new Array();
	var i = 0;
	while(i<maxsuggestions){
		thisrand = Math.floor(Math.random()*suggested.length);
		
		indices.push(suggested[thisrand]);
		suggested.splice(thisrand, 1);
		

		i++;
	}
	
	for(i=0;i<indices.length;i++){
		if(indices[i]===undefined || indices[i]===null || indices[i]===''){
			indices.splice(i,1);
		}
	}

	
	if(indices.length > 0){
		var html = '';
		i = 0;
	html+='<div class="suggested">';
		while(i<indices.length){
			thisplace = places[indices[i]];
			
			if(thisplace!=undefined){
				html+='<div class="item"><a href="'+thisplace.link+'"><img src="'+thisplace.thumb+'" /></a><h3><a href="'+thisplace.link+'">'+thisplace.name+'</a></h3></a><a href="#" class="addmm" id="location-'+thisplace.id+'"><span>Add to Trip</span></a></div>';
				
			}
			i++;
		}
		
		html+='</div>';
		if(html!=''){
			html = '<h3>May we suggest...</h3>'+html;
			$('#overlay .box .content').html(html);
			setMyMedoraLinks();
		}
		else{
			closeOverlay();	
		}
	}
		
}
function setMyMedoraLinks(){

	$('a.addmm').click(function(){
		
		
				
		
		if($(this).attr('id').search('itinerary')<0){
			
			status = myMedoraToggle($(this).attr('id'), '#mymedora', '');
		
		
			$(this).removeClass('include');
			if(status===1){
				$(this).addClass('include');
			}
		}
		else{
			status = myMedoraToggle($(this).attr('id'), '#mymedora', $(this).attr('title'));	
		}
			
		return false;
				
	});	
}



function setMap(){
	
	if($('#map').length > 0){
	
			//google.maps.event.addDomListener(window, 'load', function() {
					var map = new google.maps.Map(document.getElementById('map'), {
				  		zoom: 15,
				  		center: new google.maps.LatLng(46.9139028,-103.5243536),
				  		mapTypeId: google.maps.MapTypeId.ROADMAP,
						scrollwheel: false
						
					});
			//});
				
				
				var infoWindow = new google.maps.InfoWindow;
				var onMarkerClick = function() {
					var marker = this;
					
					var latLng = marker.getPosition();
					var content = '<div class="markertext"><h4 class="markerheading"><a href="'+this.mlink+'">'+this.mtitle+'</a></h4>';
					content += '<p>'+this.maddress+'</p></div>';
					infoWindow.setContent(content);
					
					infoWindow.open(map, marker);
			
				};
				google.maps.event.addListener(map, 'click', function() {
					infoWindow.close();
				});
				
				google.maps.event.addListener(infoWindow, 'closeclick', function() {
					
				});
				
				
			var markers = {};
			
			for ( var p in mymedora.places){
				thisplace = places[mymedora.places[p].id];
								
				if(thisplace.lat!=0 && thisplace.lng!=0){

					index = getObjectLength(markers)+1;
	
					markers[index] = new google.maps.Marker({
							map: map,
							position: new google.maps.LatLng(thisplace.lat,thisplace.lng), 
							mindex: 1,
							mtitle:thisplace.name,
							maddress:thisplace.address,
							mtype:thisplace.category,
							mplace_id: thisplace.id,
							mlink: thisplace.link
					});
					
					
					google.maps.event.addListener(markers[index], 'click', onMarkerClick);
				}
			}
				
	}
			
}


function popUpRes(href,name){

window.open(href,name,'scrollbars=1,height=600,width=1000,left=200,top=200,location=0,toolbar=0');

return false;

	
}