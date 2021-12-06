<?php
require_once("inc/core.class.php");
if (!isset($core))
	$core = new Core();

$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("?page=bookingTable");

$fieldClub = new FieldClub($core);

// DEAL WITH REQUEST
if (isset($_GET['deleteTeam']))
{
	$fieldClubTeam = new FieldClubTeam($core, $_GET['deleteTeam']);
	if ($fieldClubTeam->loaded())
		if ($fieldClubTeam->delete())
			$core->message("Deleted.");
}
// END DEAL WITH REQUEST


$fieldClubTeams = array();
$res =& $fieldClub->getTeamDB();
if ($res && $res->numRows())
{
	foreach ($res->fetchAll() as $data)
	{
		$fieldClubTeams[] = new FieldClubTeam($core,null,$data);
	}
}
echo '<p><a href="?page=admin">< Back</a>.</p>';
echo "<p><a href='?page=adminTeamForm'>Add new team</p></a>";
echo "<table id='userAdminTable'>";
echo "<tr><th>Actions</th><th>Name</th><th>Sport</th><th>Current captain</th></tr>";
foreach ($fieldClubTeams as $fieldClubTeam)
{
	$actions = array();
	$actions[] = "<a href='?page=adminTeamForm&teamId={$fieldClubTeam->getId()}'><img src='images/edit.png' /></a>";
	$actions[] = "<a href='?page=adminTeam&deleteTeam={$fieldClubTeam->getId()}' onclick='return confirmDelete()'><img src='images/remove.png' /></a>";

	$fieldClubSport = new FieldClubSport($core, $fieldClubTeam->sportId);

	$fieldClubUser = $fieldClubTeam->getCaptain();

	echo "<tr><td class='adminActions'>".join("&nbsp;&nbsp;",$actions)."</td>";
	echo "<td>{$fieldClubTeam}</td><td>{$fieldClubSport}</td><td>{$fieldClubUser}</td></tr>";
}

?>
	
