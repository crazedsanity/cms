<?php 
		if(isset($_GET['year']) && isset($_GET['month'])){
			$date=array(
				'year'=>intval($_GET['year']),
				'mon'=>intval($_GET['month']),
				'mday'=>1
			);
		}else{
			$date=getdate();
		}
	?>
	$(document).ready(function() {	

		var date = new Date();
		var y = <?=$date['year']?>;
		var m = <?=($date['mon'])?>;
		var d = <?=$date['mday']?>;
		
		
		$("#year").val(y).attr('selected','selected');
		$("#month").val(m).attr('selected','selected');


		$('#calendar').fullCalendar({
			year:y,
			month:m-1,
			date:d,
			header: {
				left: 'prev,next',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			currentTimezone: 'America/Chicago',
			timeFormat:'h:mmt{-h:mmt}',
			theme: true,
			editable: false,
			lazyFetching: true,
			loading: function(bool) {
				if (bool) $('#loading').show();
				else $('#loading').hide();
			}
		});
		$('#calendar').fullCalendar('addEventSource', '../update/calendar/eventfeed.php');
	   // ajaxBUEvents();
	
		$('form').submit(function(){
			var m=$("select[name='month'] option:selected").val()-1;
			var y=$("select[name='year'] option:selected").val();
			$('#calendar').fullCalendar( 'gotoDate', y,m);
			return false;
		});


	});

	function getURLParameter(name) {
	    return decodeURI(
	        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search)||[,null])[1]
	    );
	}

		
	
	function refreshEvents(){
		$('#calendar').fullCalendar('removeEventSource','eventfeed.php');
		$('#calendar').fullCalendar('removeEventSource','eventfeed.php?refresh=1');
		$('#calendar').fullCalendar('addEventSource', 'eventfeed.php?refresh=1');
		return false;	
	}
	