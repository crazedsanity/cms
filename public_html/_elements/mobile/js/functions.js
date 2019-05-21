
function setLatLon(){
		latlon = getCookie('latlon');
		
		if(latlon != '' && latlon != undefined && latlon != null && latlon != 'nosupport'){
			latlon = latlon.split(',');
			
			latitude = latlon[0];
			longitude = latlon[1];
		}
		 
		 if(latlon == '' || latlon == undefined || latlon == null){ //if no latlon set from cookie try and get it
			$.geolocation.find(function(location){
				setCookie('latlon',location.latitude+','+location.longitude,1);
				window.location = window.location;
			}, function(){
				setCookie('latlon','nosupport',1);
			   	alert("Your device doesn't support Geolocation.");
			});
		 }	
}
function detectAndSetLatLon(){
	$.geolocation.find(function(location){
		setCookie('latlon',location.latitude+','+location.longitude,1);

		window.location = document.forms['detect'].prev.value;
		
	}, function(){
		setCookie('latlon','nosupport',1);
	   	alert("Your device doesn't support Geolocation.");
		
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
