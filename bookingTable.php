

<?php
$pageTitle = "Booking Table";
require_once("inc/core.class.php");



$days = array("Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");

$core = new Core();
$fieldClub = new FieldClub($core);
$fieldClubAccess = new FieldClubAccess($core);


// DEAL WITH BOOKING REQUESTS

if (isset($_POST['bookingSubmit']))
{
	$fieldClubBooking = new FieldClubBooking($core);
	$fieldClubBooking->sportId = $_POST['sportId'];
	$fieldClubBooking->teamId = $_POST['teamId'];
	$fieldClubBooking->userId = $fieldClubAccess->getId();
	$fieldClubBooking->courtId = $_POST['courtId'];
	$fieldClubBooking->startDateTime = new T_DateTime(null,$_POST['startDateTime']);
	$fieldClubBooking->endDateTime = new T_DateTime(null,$_POST['endDateTime']);
	$fieldClubBooking->notes = $_POST['notes'];

	if ($fieldClubBooking->save())
		$core->message("Booking complete.");
}

if (isset($_GET['action']))
{
	if ($_GET['action'] == "deleteBooking")
	{
		$fieldClubBooking = new FieldClubBooking($core,$_GET['bookingId']);
		if ($fieldClubBooking->delete())
			$core->message("Booking deleted.");
	}
}

// END DEAL WITH BOOKING REQUESTS

// Load fieldclub courts
$res =& $fieldClub->getCourtDB();
$fieldClubCourts = array();
if ($res && $res->numRows() > 0)
	while($data = $res->fetchRow())
		$fieldClubCourts[] = new FieldClubCourt($core,null,$data);

$bookingTablesLinks = array();
foreach ($fieldClubCourts as $fieldClubCourt)
	$bookingTablesLinks[] = "<a href='?page=bookingTable&amp;showCourt={$fieldClubCourt->getId()}'>{$fieldClubCourt}</a>";

$bookingTablesHtml = NULL;


// Found out when the start of week is for the picked week
$todayIsMonday = date("D",time()) == "Mon";
if ($todayIsMonday)
{
    $startOfWeek = strtotime("this Monday +{$fieldClubAccess->preferredWeekOffset()} week");
}
else
{
    $startOfWeek = strtotime("previous Monday +{$fieldClubAccess->preferredWeekOffset()} week");
}

// We request bookings up to the end of the week, so need to find out when that is :)
$endOfWeek = $startOfWeek + 7*24*60*60;

$table = array(array());
	
if ((int)$fieldClubAccess->preferredCourt() != -1)
{
	$fieldClubCourt = new FieldClubCourt($core,$fieldClubAccess->preferredCourt());
		
	$bookingTablesHtml .= "<h1>$fieldClubCourt</h1>";
	$bookingTablesHtml .= "<p>Open {$fieldClubCourt->openFrom} to {$fieldClubCourt->openUntil}</p>";

	$bookings = array();
	if ($fieldClubCourt->statusOverride == -1) // hardcoded value for no override
	{
		$overlapping_courts = $fieldClubCourt->getOverlappingCourts();
		$court_search_mysql_string = "courtId='{$fieldClubCourt->getId()}'";
		foreach ($overlapping_courts as $overlapping_court_id)
		{
			$court_search_mysql_string .= sprintf(" OR courtId='%s'", $overlapping_court_id);
		}

		$weekStart_dateTime = new T_DateTime(date("H:i d-m-Y",$startOfWeek));
		$weekEnd_dateTime = new T_DateTime(date("H:i d-m-Y",$endOfWeek));
		$res =& $fieldClub->getBookingDB(sprintf("(%s) AND startDateTime >= '%s' AND startDateTime <= '%s' ORDER BY startDateTime", 
					$court_search_mysql_string,$weekStart_dateTime->mysqlFormat(), $weekEnd_dateTime->mysqlFormat()));

		if ($res && $res->numRows() > 0)
		{
			while ($data = $res->fetchRow())
			{
				$bookings[] = new FieldClubBooking($core,null,$data);
			}
		}
	}

	$obj = new ArrayObject($bookings);
	$bookings_it = $obj->getIterator();

	// lookup every day in a week
	for ($i = 0; $i < 7; $i++)
	{
		for ($t = $fieldClubCourt->openFrom->value(), $n=0; $t < $fieldClubCourt->openUntil->value(); $t += $fieldClubCourt->slotDuration, $n++)
		{
			$slotStartTime = new T_DateTime(date("H:i d-m-Y",strtotime("+$i days +$t seconds", $startOfWeek)));
			$slotEndTime = new T_DateTime(date("H:i d-m-Y",strtotime("+$i days +" . ($t+$fieldClubCourt->slotDuration). " seconds", $startOfWeek)));
			
            if ($i == 0) 
			{ // The time column only needs setting once
				$table[$n][0] = sprintf("<td class='slotTime'>%s-%s</td>", date("H:i", $slotStartTime->value), date("H:i", $slotEndTime->value));
			}


			if ($fieldClubCourt->statusOverride == -1)
			{
				
				while ($bookings_it->valid() && $bookings_it->current()->startDateTime->value < $slotStartTime->value)
				{
					$bookings_it->next();
				}
				
				if ($bookings_it->valid())
				{
					$closest_booking = $bookings_it->current();
					if ($slotStartTime->value <= $closest_booking->startDateTime->value
							&& $closest_booking->startDateTime->value < $slotEndTime->value)
					{
						if ($closest_booking->courtId == $fieldClubCourt->getId())
						{
							$courtState = CourtState::Taken;
						}
						else
						{
							$courtState = CourtState::TakenByOverlap;
						}
					}
					else
					{
						$courtState = CourtState::Free;
					}
				}
				else
				{
					$courtState = CourtState::Free;
				}
			}
			else
			{
				$courtState = $fieldClubCourt->statusOverride;
			}
		
			$showEditBookingString = sprintf('showEditBooking($(this,"td"),"%s","%s","%s"); return false;', $slotStartTime->value, 
											$slotEndTime->value, $fieldClubCourt->getId());

			$table[$n][$i+1] = sprintf("<td class='%s'><a href='#' onclick='%s'>%s</a></td>",
					$courtState == CourtState::Free ? "slot" : "slotTaken",  
					$showEditBookingString,
					$courtState == CourtState::Free ? "Free" : "Taken"); 
		}
	}
	
	$bookingTablesHtml .= "<div id='tableContainer'>".
							"<div class='weekSelectPrevious'>".
								"<a href='?page=bookingTable&amp;weekOffset=".($fieldClubAccess->preferredWeekOffset()-1)."'>&lt;&lt; Previous week</a>".
							"</div>".
							"<div class='weekSelectNext'>".
								"<a href='?page=bookingTable&amp;weekOffset=".($fieldClubAccess->preferredWeekOffset()+1)."'>Next week &gt;&gt;</a>".
							"</div>".
							"<div class='weekSelectCurrent'>".
								"<a href='?page=bookingTable&amp;weekOffset=0'>&gt; This week &lt;</a>".
							"</div>".
						  "</div>";
								

	$bookingTablesHtml .= "<table class='bookingTable'>";
	$bookingTablesHtml .= "<tr><th></th>";

	for ($i = 0; $i < 7; $i++)
	{
		$bookingTablesHtml .= "<th>".date("D (jS M)",$startOfWeek+$i*60*60*24)."</th>";
	}
	$bookingTablesHtml .= "</tr>\n";

	foreach ($table as $tableRow)
	{
		$bookingTablesHtml .= "<tr>";
		foreach ($tableRow as $tableCell)
			$bookingTablesHtml .= $tableCell;
		$bookingTablesHtml .= "</tr>\n";
	}
	$bookingTablesHtml .= "</table>";
}
?>



<?php
if ($fieldClubAccess->hasAccessTo(FieldClubUser::$ACCESSLEVEL_PLODGE))
{
	echo "<a href='?page=admin'>Admin interface</a>";
}
else if (count($fieldClubAccess->captainOfTeams) > 0) {
    echo "<a href='?page=blockBooking'>Make block bookings</a>";
}

echo "<p><a href='?page=map'>Show map of courts</a><br />or view court: ".join(", ",$bookingTablesLinks)."</p>";

echo $bookingTablesHtml;


?>
