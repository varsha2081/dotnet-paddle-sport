<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>

<?php
require_once("inc/core.class.php");
$core = new Core();
$fieldClub = new FieldClub($core);
$fieldClubAccess = new FieldClubAccess($core);

$startDateTime = new T_DateTime(date("H:i d-m-Y",$_GET['startTime']));
$endDateTime = new T_DateTime(date("H:i d-m-Y",$_GET['endTime']));
$courtId = $_GET['courtId'];

$fieldClubCourt = new FieldClubCourt($core,$courtId);

echo "<fieldset><legend>{$startDateTime->getTime()}-{$endDateTime->getTime()} on {$startDateTime->getDate()}</legend>";

$res =& $fieldClub->getBookingDB("startDateTime='{$startDateTime->mysqlFormat()}' && endDateTime='{$endDateTime->mysqlFormat()}' && courtId='$courtId'");
$courtState = $fieldClubCourt->getCourtStateBetween($startDateTime, $endDateTime);

if ($courtState == CourtState::ClosedForMaintenance)
{
	echo "<p>This court is closed for maintenance.</p>";
	
}
else if ($courtState != CourtState::Free)
{
	$fieldClubBooking = new FieldClubBooking($core,null,$res->fetchRow());

	if ($fieldClubBooking->userId == $fieldClubAccess->getId())
	{
		$team;
		if ($fieldClubBooking->teamId == -1)
			$team = "yourself";
		else
		{
			$fieldClubTeam = new FieldClubTeam($core, $fieldClubBooking->teamId);
			$team = "$fieldClubTeam->name";
		}

		$fieldClubSport = new FieldClubSport($core, $fieldClubBooking->sportId);

		echo "<p>You've booked this slot for $team ($fieldClubSport).</p>";
		if ($fieldClubBooking->notes != "")
			echo "<p>Notes: {$fieldClubBooking->notes}</p>";
		echo "<p><a onclick='return confirm(\"Are you sure you wish to remove this booking?\")' href='?page=bookingTable&action=deleteBooking&bookingId={$fieldClubBooking->getId()}'>Click here</a> to remove booking.</p>";
	}
	else
	{
		if ($fieldClubAccess->isLoggedIn())
		{
			if ($courtState == CourtState::Taken) {
				echo "<p>This slot is taken.</p>";
				$team = NULL;
				if ($fieldClubBooking->teamId == -1)
					$team = "own use";
				else
				{
					$fieldClubTeam = new FieldClubTeam($core, $fieldClubBooking->teamId);
					$team = "$fieldClubTeam->name";
				}

				$fieldClubSport = new FieldClubSport($core, $fieldClubBooking->sportId);
				$bookedFieldClubUser = new FieldClubUser($core,$fieldClubBooking->userId);
				echo "<p>This slot has been booked by <a href='mailto:{$bookedFieldClubUser->email}'>{$bookedFieldClubUser->name}</a> for $team ($fieldClubSport).</p>";

				if ($fieldClubAccess->hasAccessLevel(FieldClubUser::$ACCESSLEVEL_PLODGE))
				{
					if ($fieldClubBooking->notes != "")
						echo "<p>Notes: {$fieldClubBooking->notes}</p>";
					if ($fieldClubAccess->isAdmin())
					{
						echo "<p><a onclick='return confirm(\"This booking was not made by you, are you sure you wish to delete it?\"' href='?page=bookingTable&action=deleteBooking&bookingId={$fieldClubBooking->getId()}'>Click here</a> to remove booking.</p>";
					}
				}
			}
			else if ($courtState == CourtState::TakenByOverlap) {
				$overlapping_booking = $fieldClubCourt->getOverlappingBooking($startDateTime, $endDateTime);
				$overlapping_court = new FieldClubCourt($core, $overlapping_booking->courtId);

				echo "<p>{$overlapping_court->getDefiniteName()} has been booked during this slot and overlaps with {$fieldClubCourt->getDefiniteName()}.</p>";
			}

		}
		else {
			if ($courtState == CourtState::Taken) {
				echo "<p>This slot is taken.</p>";
			}
		}
	}
}
else
{  
	echo "<p>This slot is free.</p>";
	if (!$fieldClubAccess->isLoggedIn())
	{
		echo "<p>You need to be logged in to book this slot. <a href='?page=login'>Log in now</a>.</p>";
	}
	else
	{
		echo "<form action='?page=bookingTable' method='post'>";

        /*
           There are four distinct situations that can occur (labelled A,B,C and D):

                            Court used for 1 sport  |   Court used for multiple sports
           User is 
           not a captain    A                       |   B

           User is
           a captain        C                       |   D


           A: Show simple "book now" button and booking details as text
           B: Drop-down menu to chose sport
           C: Drop-down menu for team (that plays the sport that is played in this court
           D: Drop-down menu to chose "book for yourself" or a different "team", if "yourself" is selected
                the sport drop-down menu is shown

        */


        if (count($fieldClubCourt->usedBySports) == 1)
        {   // CASE A or C
            $fieldClubSport = new FieldClubSport($core, $fieldClubCourt->usedBySports[0]);
            echo "<p>Court is used for ".strtolower($fieldClubSport).".</p>";
            echo "<input type='hidden' value='{$fieldClubCourt->usedBySports[0]}' name='sportId'>";
        }

		// Find out if the user is captain of a team which may play in this court
		// this means that the teams that the user is captain of should be checked for
		// sport and it should then be checked if that court is used for that sport
		// (hope that makes sense)

		$captained_teams_that_use_court = array();
		foreach ($fieldClubAccess->captainOfTeams as $teamid) {
			$teamObj = new FieldClubTeam($teamid);
			if (in_array($teamObj->getSport(), $fieldClubCourt->usedBySports)) {
				$captained_teams_that_use_court = true;
			}
		}

        if (count($captained_teams_that_use_court) == 0)
        {   
            echo '<input type="hidden" value="-1" name="teamId" id="teamId" />';
            if (count($fieldClubCourt->usedBySports) == 1)
            {   // CASE A
                // Output dealt with above
            }
            else
            {   // CASE B
                echo '<p>Book slot for <span id="sportPicker"></span></p>';
            }
        }
        else
        {
            echo "Book slot for <select id='teamId' name='teamId'>";
            
            if (count($fieldClubCourt->usedBySports) == 1)
            {   // CASE C
                // Output dealt with above
            }
        
            $teams = array();
            $teams[] = array("-1", "yourself");
            if (count($captained_teams_that_use_court) > 0)
            {
                foreach ($captained_teams_that_use_court as $fieldClubTeam)
                {
                    $fieldClubSport = new FieldClubSport($core, $fieldClubTeam->sportId);
                    $teams[] = array("$teamId", "$fieldClubTeam ($fieldClubSport)");
                }
            }
            foreach ($teams as $team)
            {
                echo "<option value='{$team[0]}'>{$team[1]}</option>";
            }
            echo "</select>";

            if (count($fieldClubCourt->usedBySports) > 1)
            {   // CASE D
                echo '<span id="sportPicker"></span>';
            }
        }
        
		if ($fieldClubAccess->hasAccessTo(FieldClubUser::$ACCESSLEVEL_PLODGE))
		{
			echo "<p><label for='notes'>Notes:</label><textarea name='notes' id='notes' rows='3' cols='25'></textarea></p>";
		}
		else
		{
			echo "<input type='hidden' name='notes' value='' />";
		}

		echo "<input type='hidden' value='{$startDateTime->value}' name='startDateTime' />";
		echo "<input type='hidden' value='{$endDateTime->value}' name='endDateTime' />";
		echo "<input type='hidden' value='$courtId' name='courtId' />";
		echo "<p><input type='submit' value='Book now' name='bookingSubmit' /></p>";
		echo "</form>";
	}
	echo "</fieldset>";
}

?>
        

<script type="text/javascript">
// if the teamId form element exists we should bind to it
if ($("#teamId").length != 0) {
    $("#teamId").change(update_available_sports);
    update_available_sports();
}

function update_available_sports() {
    var team_id = $("#teamId").val();
    var sport_picker_target = $("#sportPicker");
    var url = "<?php echo $core->getConfVar('SITE_ROOT_URL')?>/ajax/getObjectJSON.php";

    if (team_id == <?php echo FieldClubTeam::$TEAM_SELF; ?>) {
        var court_id = <?php echo $courtId; ?>;
        $.getJSON(url, { "type" : "court", "id" : court_id}, function(j) {

                var picker_data = '';
                var sports = j.sports

                if (sports.length > 1)
                {
                var options = ' for playing <select name="sportId">';
                for (var i = 0; i < sports.length; i++) {
                options += '<option value="' + sports[i].id + '">' + sports[i].name + '</option>';
                }
                options += '</select>.';
                picker_data += options;
                }
                else
                {
                picker_data = '<input type="hidden" name="sportId" value="' + sports[0].id + '"/>';
                }
                sport_picker_target.html(picker_data);
                });
    }
    else
    {
        $.getJSON(url, { "type" : "team", "id" : team_id}, function(j) {
                var picker_data = '';
                var sport_id = j.sportId;

                picker_data = '<input type="hidden" name="sportId" value="' + sport_id + '"/>.';
                sport_picker_target.html(picker_data);
                });
    }
}

</script>
