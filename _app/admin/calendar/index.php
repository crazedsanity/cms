<?php

use crazedsanity\core\ToolBox;
use cms\cms\core\calendar;
use cms\cms\core\calendarEvent;
use cms\cms\core\recurrenceType;

//ToolBox::$debugPrintOpt = 1;

ini_set('display_errors', true);

$calObj = new calendar($db);
$ceObj = new calendarEvent($db);
$rtObj = new recurrenceType($db);


$tmpl = getTemplate('update/calendar/index.html');
$_colors = $tmpl->setBlockRow('editCalendar');
$_colMenu = $tmpl->setBlockRow('colorMenu');
$tmpl->addVar('GENERATED_STYLES', $calObj->generateCalStyles());

if(!$acl->access($_SESSION['MM_Username'], 'calendar', 0, ADD)) {
	$tmpl->addVar('calendarControlsClass', 'hidden');
}


$calendars = $calObj->getAll();
debugPrint($calendars, "Calendars");
$tmpl->addVar($_colors->name, $_colors->renderRows($calendars));

$colorArray = $calObj->colorArray;
$colorMenuData = array();
foreach($colorArray as $color) {
	$colorMenuData[] = array(
		'color'		=> $color,
		'colorFG'	=> $calObj->getDarkColorHex($color, 5),
		'colorBG'	=> $calObj->getLightColorHex($color, 30),
	);
}
$tmpl->addVar($_colMenu->name, $_colMenu->renderRows($colorMenuData));

$allRecurrenceTypes = $rtObj->getAll_nvp();
debugPrint($allRecurrenceTypes, 'all recurrence types');
$tmpl->addVar('recurrenceTypeOptionList', ToolBox::array_as_option_list($allRecurrenceTypes));



echo $tmpl->render();




if(true === false) {
?>

	<div id="dialog-edit-cal" title="Edit calendar">
		<form>
			<input type="hidden" name="id" id="edit-cal-id">
			<input type="hidden" name="id" id="edit-cal-color">
			<label for="name">Name</label>
			<input type="text" name="name" id="edit-cal-name" class="text ui-widget-content ui-corner-all">
			<br>
			<label for="name">Calendar Color</label><br>
			<a class="select-color-menu" href="">Select Calendar Color</a>
			<ul class="color-menu">
				<?php
					foreach ( $colorArray as $color ) {
						$colorFG = $cal->getDarkColorHex( $color, 5 );
						$colorBG = $cal->getLightColorHex( $color, 30 );
						echo <<<HTML
							<li value="{$color}"><a href="#{$color}" style="background-color:#{$colorBG}; color:#{$colorFG};">Select</a></li>
HTML;
					}
				?>
			</ul>
		</form>
	</div>
	

	<div id="dialog-event" title="Event">
		<form>
			<input type="hidden" name="id" id="event-id">
			<ul>
				<li>
					<input type="text" name="name" id="event-title" class="text ui-widget-content ui-corner-all" placeholder="Untitled Event">
				</li>
				<li class="time">
					<p class="datepair" data-language="javascript">
						<input tabindex="-1" type="text" name="start-date" id="event-start-date" class="date start text ui-widget-content ui-corner-all datepicker2" placeholder="mm/dd/yyyy" />
						<input tabindex="-1" type="text" name="start-time" id="event-start-time" class="time start text ui-widget-content ui-corner-all" placeholder="hh:mm am/pm" />
						<span>to</span>
						<input tabindex="-1" type="text" name="end-time" id="event-end-time" class="time end text ui-widget-content ui-corner-all" placeholder="hh:mm am/pm" />
						<input tabindex="-1" type="text" name="end-date" id="event-end-date" class="date end text ui-widget-content ui-corner-all datepicker3" placeholder="mm/dd/yyyy" />
						
					</p>
					
					<div class="clear"></div>
				</li>
				<li>
					<input type="checkbox" name="all-day" id="event-all-day"><label for="all-day">All day</label>
				</li>
				
				<li>
					<label for="event-recurrence-type">Repeat</label><br>
						<select id="event-recurrence-type" name="event-recurrence-type">
							<?php
								
								$options=array();
								$options['none']='none';
								$options['daily']='daily';
								$options['weekly']='weekly';
								$options['monthly']='monthly';
								$options['yearly']='yearly';
								
								echo $base->makeOptionsList($options);
							?>
						</select>
				</li>
				<li class="recurringoptions">
					<ul>
						<li class="until">
							<label for="event-recurrence-last-timestamp">Repeat until</label><br />
								<input type="text" name="event-recurrence-last-timestamp" id="event-recurrence-last-timestamp" class="datepicker" placeholder="mm/dd/yyyy" />
							
						</li>
						

					</ul>
				</li>
		
				<hr>
				<li>
					<label for="calendar">Calendar</label><br>
					<select name="calendar" id="event-calendar"></select>
				</li>
				<li>
					<label for="location">Where</label><br>
					<input type="text" name="location" id="event-location" class="text ui-widget-content ui-corner-all" placeholder="Enter a location">
				</li>
				<li>
					<label for="description">Description</label><br>
					<textarea name="description" id="event-description" class="text ui-widget-content ui-corner-all"></textarea>
				</li>
			</ul>
		</form>
	</div>
			
<?php
	include( ROOT . '/update/_includes/footer.php' );
}

