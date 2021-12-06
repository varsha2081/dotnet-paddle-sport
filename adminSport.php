<?php
require_once("inc/core.class.php");
if (!isset($core)) {
	$core = new Core();
}

$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("?page=bookingTable");

$fieldClub = new FieldClub($core);

// DEAL WITH REQUEST
if (isset($_GET['deleteSport']))
{
	$fieldClubSport = new FieldClubSport($core, $_GET['deleteSport']);
	if ($fieldClubSport->delete())
		$core->message("Deleted.");
}
// END DEAL WITH REQUEST


$fieldClubSports = array();
$res =& $fieldClub->getSportDB();
if ($res && $res->numRows())
{
	foreach ($res->fetchAll() as $data)
	{
		$fieldClubSports[] = new FieldClubSport($core,null,$data);
	}
}

echo '<p><a href="?page=admin">< Back</a>.</p>';
echo "<p><a href='?page=adminSportForm'>Add new Sport</a></p>";
echo "<table id='userAdminTable'>";
echo "<tr><th>Actions</th><th>Name</th></tr>";
foreach ($fieldClubSports as $fieldClubSport)
{
	$actions = array();

	$actions[] = "<a href='?page=adminSportForm&SportId={$fieldClubSport->getId()}'><img src='images/edit.png' /></a>";
	$actions[] = "<a href='?page=adminSport&deleteSport={$fieldClubSport->getId()}' onclick='return confirmDelete()'><img src='images/remove.png' /></a>";

	echo "<tr><td class='adminActions'>".join("&nbsp;&nbsp;",$actions)."</td>";
	echo "<td>{$fieldClubSport}</td>";
}

echo "</table>";

?>
	
