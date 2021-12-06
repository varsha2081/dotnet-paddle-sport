<?php
require_once("inc/core.class.php");
if (!isset($core))
{
	$core = new Core();
}
$core->setDebugging(TRUE);

$statusFiles = array (0 => "images/green.gif", 1 => "images/yellow.gif", 2 => "images/red.gif");

$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->isAdmin())
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
echo "<p><a href='?page=adminUserForm'>Add new user</a></p>";
echo "<p><form action='' method='post'><input type='text' name='search' /><input type='submit' value='search' /></form></p>";
echo "<table id='userAdminTable'>";
echo "<tr><th style='width:140px;'>Actions</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th style='width: 200px;'>Captain of teams</th></tr>";
foreach ($fieldClubUsers as $fieldClubUser)
{
	$actions = array();
	foreach ($statusFiles as $id => $statusFile)
	{
		$actions[] = "<a href='?page=adminUser&action=changeUserStatus&newStatus=$id&userId={$fieldClubUser->getId()}'><img src='$statusFile' /></a>";
	}
	
	$actions[] = "<a href='?page=adminUserForm&UserId={$fieldClubUser->getId()}'><img src='images/edit.png' /></a>";
	$actions[] = "<a href='?page=adminUser&deleteUser={$fieldClubUser->getId()}' onclick='return confirmDelete()'><img src='images/remove.png' /></a>";

	$teams = array();
	foreach ($fieldClubUser->captainOfTeams as $teamId)
	{
		$teams[] = new FieldClubTeam($core, $teamId);
	}
	echo "<tr><td class='adminActions'>".join("&nbsp;&nbsp;",$actions)."</td>";
	echo "<td>{$fieldClubUser->name}</td><td>{$fieldClubUser->username}</td><td>{$fieldClubUser->email}</td><td><img src='{$statusFiles[$fieldClubUser->userStatus]}' /></td><td>".join(", ",$teams)."</td></tr>";
}

?>
	

