
<?php

require_once("inc/core.class.php");
$core = new Core();
$fieldClub = new FieldClub($core);
$fieldClubAccess = new FieldClubAccess($core);

$fieldClubTeams = array();
foreach ($fieldClubAccess->captainOfTeams as $team)
{
	$fieldClubTeams[] = new FieldClubTeam($core, $team);
}

echo '<p><a href="?page=admin">< Back</a>.</p>';
if (count($fieldClubTeams) == 0)
{
	echo "<p>You are currently not captain of any teams, you must be captain of a team to make block bookings.</p>";
}
else
{
	// HANDLE BOOKING REQUESTS

	if (isset($_POST['formSubmit']))
	{
		if (!isset($_POST['dates']) || $_POST['dates'] == "")
			$core->error("You must select at least one date.");
		else
			$dates = split(",",$_POST['dates']);
		if (!isset($_POST['courts']))
			$core->error("You must select at least one court.");
		else
			$courts = $_POST['courts'];
		if (!isset($_POST['startTime']) || $_POST['startTime'] == "")
			$core->error("You must provide a start time.");
		else
			$startTime = new T_Time($_POST['startTime']);
		if (!isset($_POST['endTime']) || $_POST['endTime'] == "")
			$core->error("You must provide an end time.");
		else
			$endTime = new T_Time($_POST['endTime']);	

		if (!$core->areErrors())
		{
			$slotDuration = $endTime->value() - $startTime->value();
			$log = "";
			foreach ($courts as $courtId)
			{
				$fieldClubCourt = new FieldClubCourt($core,$courtId);
				$log .= "Making bookings for {$fieldClubCourt}\n";
				foreach ($dates as $date)
				{
					$startDateTime = new T_DateTime(null, $startTime->value()+$date);
					$endDateTime = new T_DateTime(null, $endTime->value()+$date);
					$log .= "> Bookings on {$startDateTime->getDate()}\n";
					for ($i = 0; $i < (ceil($slotDuration / $fieldClubCourt->slotDuration)); $i++)
					{
						$slotStartDateTime = new T_DateTime(null, $startDateTime->value()+$i*$fieldClubCourt->slotDuration);
						$slotEndDateTime = new T_DateTime(null, $startDateTime->value()+($i+1)*$fieldClubCourt->slotDuration);

						$fieldClubTeam = new FieldClubTeam($core,$_POST['teamId']);

						$fieldClubBooking = new FieldClubBooking($core);
						$fieldClubBooking->userId = $fieldClubAccess->getId();
						$fieldClubBooking->sportId = $fieldClubTeam->getSport()->getId();
						$fieldClubBooking->teamId = $_POST['teamId'];
						$fieldClubBooking->courtId = $courtId;
						$fieldClubBooking->startDateTime = $slotStartDateTime;
						$fieldClubBooking->endDateTime = $slotEndDateTime;
						$fieldClubBooking->notes = "";
						
						$result = "";
						$error = NULL;
						if ($fieldClubBooking->save())
							$result = "<span style='color:green'>OK</span>";
						else
						{
							$result = "<span style='color:red'>FAILED</span>";
							$error = $core->popMostRecentError();
						}

						$log .= "  > Slot from {$slotStartDateTime} to {$slotEndDateTime} ($result)\n";
						if ($error)
							$log .= "    > $error\n";
					}
				}
			}

			echo "<pre>$log</pre>";
		}	
	}

	// END HANDLE BOOKING REQUESTS


	// load fieldclub sports
	$fieldClubSports = array();
	$res =& $fieldClub->getSportDB();
	if ($res && $res->numRows())
	{
		foreach ($res->fetchAll() as $data)
		{
			$fieldClubSports[] = new FieldClubSport($core,null,$data);
		}
	}

	$datePickerHTML = "<table class='datePickerTable'><tr>";
	for ($m = 0; $m < 12; $m++)
	{
		$startOfMonth = mktime(0,0,0,date("m")+$m,1,date("Y"));
		$datePickerHTML .= "<td>";
		$datePickerHTML .= "<h2>".date("M Y",$startOfMonth)."</h2>\n";

		$datePickerHTML .= "<table>\n<tr>";
		foreach (array("S","M","T","W","T","F","S") as $day)
			$datePickerHTML .= "<th>$day</th>";
		$datePickerHTML .= "</tr><tr>";
		$printingWeekDay = 0;

		while ($printingWeekDay != date("w",$startOfMonth))
		{
			$datePickerHTML .= "<td></td>";
			$printingWeekDay++;
			if ($printingWeekDay > 7)
				die();
		}
	
		$dates = array();
		$daysInMonth = date('t', mktime(0, 0, 0, date("m")+$m, 1, date("Y")));

		for ($d = 0; $d < $daysInMonth; $d++)
		{
			$t = mktime(0,0,0,date("m")+$m, $d+1, date("Y"));
			$datePickerHTML .= "<td class='date'><a href='#' onclick='return false' class='dateLink' date='$t'>".date("d",$t)."</a></td>";
			// check if we've reached end of week, should be new row in table
			if (($printingWeekDay+1) % 7 == 0)
			{
				$datePickerHTML .= "</tr>\n<tr>";
			}
			$printingWeekDay++;
		}
		$datePickerHTML .= "</tr>\n</table>";
		$datePickerHTML .= "</td>";
		if (($m+1) % 4 == 0)
			$datePickerHTML .= "</tr><tr>";
	}
	$datePickerHTML .=  "</tr></table>";
	?>
	<form class="adminForm" action="" method="post">
	<fieldset><legend>New block booking</legend>
	<ol>
	<li><label for="teamId">Select team:</label><select id='teamId' name='teamId'>
	<?php			
	foreach ($fieldClubTeams as $team)
	{
		echo "<option value='{$team->getId()}'>$team ({$team->getSport()})</option>";
	}
	echo "</select></li>";
	?>
	<li><label>Choose courts:</label><span id='courts'></span></li>
	<li><label>Duration:</label>
		<span><label for="startTime">from:</label><input type='text' name='startTime' id='startTime' /><i>(hh:mm)</i> <label for="endTime"> to:</label><input type='text' name='endTime' id='endTime' /><i>(hh:mm)</i></span><br /><i>(depending on the duration per slot in the courts chosen a number of slots will be allocated)</i>
		</li>
	<li>Choose dates: <?php echo $datePickerHTML; ?></li>
	<li><input type="submit" value="Make block booking" name="formSubmit"/></li>
	</ol>
	<input type='hidden' id='dates' name='dates' />
	</form>
	</fieldset>
	<?php
}
?>

<script type="text/javascript">


function updateDatesField()
{
    var clicked_dates = $(".dateLinkSelected").map(function() {
            return $(this).attr('date');
            }).get();
	$('#dates').val(clicked_dates.join(",")); 
}

function updateCourts()
{
    var url = 'blockBooking_ajax_courts.php?teamId='+$('#teamId').val();
    $.get(url, function(data) {
            $("#courts").html(data);
            }
         );
}

$(".dateLink").click(function() {
        $(this).toggleClass("dateLinkSelected");
        updateDatesField();
        }
        );
	
$('#teamId').change(updateCourts);
updateCourts();

</script>
