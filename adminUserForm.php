<?php

require_once("inc/core.class.php");
if (!isset($core))
	$core = new Core();

$fieldClubAccess = new FieldClubAccess($core);
if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("index.php?page=bookingTable");

$fieldClub = new FieldClub($core);

$fieldClubUser = NULL;

$editing = isset($_GET['UserId']);
if ($editing)
{
	$fieldClubUser = new FieldClubUser($core, $_GET['UserId']);
}
else
{
	$fieldClubUser = new FieldClubUser($core);
}
	
if (isset($_POST['UserFormSubmit']))
{
	$fieldClubUser->email = $_POST['email'];
	$fieldClubUser->username = $_POST['username'];
	$fieldClubUser->userType = $_POST['userType'];
	$fieldClubUser->userNotes = $_POST['userNotes'];
	$fieldClubUser->accessLevel = $_POST['accessLevel'];
	$fieldClubUser->loginType = $_POST['loginType'];
	$fieldClubUser->captainOfTeams = (isset($_POST['captainOfTeams']) ? $_POST['captainOfTeams'] : array());
	$fieldClubUser->name = $_POST['name'];

	if ($fieldClubUser->save())
	{
		$core->message(($editing ? "Saved." : "Added."));
		if (!$editing)
			$fieldClubUser = new FieldClubUser($core);
	}
	else
		$core->error("Couldn't ".($editing ? "save." : "add."));
}

$title = "FieldClub User";

$teamsHTML = "";
/* fetch the sports */
$res =& $fieldClub->getSportDB();
if ($res && $res->numRows() > 0) {
	while ($data = $res->fetchRow()) {
		$sport = new FieldClubSport($core,null,$data);
		/* for each sport we find the teams */
			$teamsHTML += "<ul>" + $sport->name + "</ul>";
			
	}
}

$teams = array();

$res =& $fieldClub->getTeamDB();

if ($res && $res->numRows() > 0)
{
	while ($data = $res->fetchRow())
	{
		$teams[] = new FieldClubTeam($core,null,$data);
	}

}


$teamsCheckBoxesHTML = array();
foreach ($teams as $team)
{
	$checked = (in_array($team->getId(),$fieldClubUser->captainOfTeams) ? "checked" : "");
	$teamsCheckBoxesHTML[] = "<label for='team{$team->getId()}'>{$team} ({$team->getSport()}):</label> <input id='team{$team->getId()}' type='checkbox' name='captainOfTeams[]' value='{$team->getId()}' $checked>";
}

$accessLevelsHTML = array();
foreach (FieldClubUser::$ACCESSLEVEL__MAP as $id => $accessLevel)
{
	$checked = ($fieldClubUser->accessLevel == $id ? "checked" : "");
	$accessLevelsHTML[] = "<label for='accessLevel$id'>$accessLevel</label><input id='accessLevel$id' type='radio' name='accessLevel' value='$id' $checked/>";
}


$loginTypesHTML = array();
foreach (FieldClubUser::$LOGINTYPE__MAP as $id => $loginType)
{
	$checked = ($fieldClubUser->loginType == $id ? "checked" : "");
	$loginTypesHTML[] = "<label for='loginType$id'>$loginType</label><input id='loginType$id' type='radio' name='loginType' value='$id' $checked/>";
}


$userTypesHTML = array();
foreach (FieldClubUser::$USERTYPE__MAP as $id => $userType)
{
	$checked = ($fieldClubUser->userType == $id ? "checked" : "");
	$userTypesHTML[] = "<label for='userType$id'>$userType</label><input id='userType$id' type='radio' name='userType' value='$id' $checked/>";
}

?>

<p><a href="?page=adminUser">&lt; Back</a>.</p>
<form class="adminForm" action="" method="post">
<h3><?php echo ($editing ? "Edit $title" : "New $title"); ?></h3>
<fieldset><legend>Access settings</legend>
<input type="hidden" id="id" name="id" value="<?php echo $fieldClubUser->getId(); ?>" />
<ol>
<li><label>User type:</label><span><?php echo join(", ",$userTypesHTML); ?></span></li>
<li><label>Access level:</label><span><?php echo join(", ",$accessLevelsHTML); ?></span></li>
<li><label>Login type:</label><span><?php echo join(", ",$loginTypesHTML); ?></span></li>
<li><label for="name">Name:</label><input name="name" id="name" type="text" value="<?php echo $fieldClubUser->name; ?>" /></li>
<li><label for="username">Username:</label><input name="username" id="username" value="<?php echo $fieldClubUser; ?>" /></li>
<li><label for="email">Email:</label><input name="email" id="email" type="text" value="<?php echo $fieldClubUser->email; ?>" /></li>
</ol>
</fieldset>
<fieldset><legend>General settings</legend>
<ol>
<li><label><b>Captain of team(s):</b></label><br /><span><?php echo join("<br />",$teamsCheckBoxesHTML); ?></span></li>
<li></li>
<li><label for='userNotes'>Notes:</label><br /><textarea id='userNotes' rows='6' cols='60' name='userNotes'><?php echo $fieldClubUser->userNotes; ?></textarea></li>
</ol>
</fieldset>
<p><input name="UserFormSubmit" type="submit" value="<?php echo ($editing ? "Save" : "Add"); ?>" /></p>

</form>
