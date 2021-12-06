<?php
require_once("inc/core.class.php");
if (!isset($core)) {
	$core = new Core();
}

$statusFiles = array (0 => "images/green.gif", 1 => "images/yellow.gif", 2 => "images/red.gif");

$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->hasAccessTo(FieldClubUser::$ACCESSLEVEL_PLODGE))
	Toolkit::redirect("?page=bookingTable");

$fieldClub = new FieldClub($core);

// DEAL WITH REQUEST
if (isset($_GET['action']))
{
	if ($_GET['action'] = "changeUserStatus")
	{
		$fieldClubUser = new FieldClubUser($core,$_GET['userId']);

		$fieldClubUser->userStatus = $_GET['newStatus'];
		
		if ($fieldClubUser->save())
			$core->message("Status updated.");
	}
}

if (isset($_POST['userNotes']))
{
	$fieldClubUser = new FieldClubUser($core,$_POST['id']);
	$fieldClubUser->userNotes = $_POST['userNotes'];

	if ($fieldClubUser->save())
		$core->message("Notes updated.");
}

// END DEAL WITH REQUEST

$searchFor = (isset($_POST['search']) ? "email LIKE '%{$_POST['search']}%' OR name LIKE '%{$_POST['search']}%'" : "");

$fieldClubUsers = array();
$res =& $fieldClub->getUserDB($searchFor);
if ($res && $res->numRows())
{
	foreach ($res->fetchAll() as $data)
	{
		$fieldClubUsers[] = new FieldClubUser($core,null,$data);
	}
}
echo '<p><a href="?page=admin">< Back</a>.</p>';
echo "<p><form action='' method='post'><input type='text' name='search' /><input type='submit' value='search' /></form></p>";
echo "<table id='userAdminTable'>";
echo "<tr><th style='width: 100px'>Change status</th><th>Name</th><th>Email</th><th>Status</th><th>Notes</th></tr>";
foreach ($fieldClubUsers as $fieldClubUser)
{
	$actions = array();
	foreach ($statusFiles as $id => $statusFile)
	{
		$actions[] = "<a href='?page=adminUserPlodge&action=changeUserStatus&newStatus=$id&userId={$fieldClubUser->getId()}'><img src='$statusFile' /></a>";
	}
	

	$teams = array();
	foreach ($fieldClubUser->captainOfTeams as $teamId)
	{
		$teams[] = new FieldClubTeam($core, $teamId);
	}

	echo "<form action='?page=adminUserPlodge' method='post'>";
	echo "<input type='hidden' name='id' value='{$fieldClubUser->getId()}' />";
	
	echo "<tr><td class='adminActions'>".join("&nbsp;&nbsp;",$actions)."</td>";
	echo "<td>{$fieldClubUser->name}</td><td>{$fieldClubUser->email}</td><td><img src='{$statusFiles[$fieldClubUser->userStatus]}' /></td><td><input type='text' name='userNotes' value='{$fieldClubUser->userNotes}' style='width: 250px;' /><input type='submit' value='Save' /></td></tr>";
	echo "</form>";
}

?>
	

