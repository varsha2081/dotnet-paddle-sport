<?php
require_once("inc/core.class.php");
if (!isset($core))
	$core = new Core();

$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("?page=bookingTable");

$fieldClub = new FieldClub($core);

// DEAL WITH REQUEST
if (isset($_GET['deleteCourt']))
{
	$fieldClubCourt = new FieldClubCourt($core, $_GET['deleteCourt']);
	if ($fieldClubCourt->delete())
		$core->message("Deleted.");
}
// END DEAL WITH REQUEST


$fieldClubCourts = array();
$res =& $fieldClub->getCourtDB();
if ($res && $res->numRows())
{
	foreach ($res->fetchAll() as $data)
	{
		$fieldClubCourts[] = new FieldClubCourt($core,null,$data);
	}
}

echo '<p><a href="?page=admin">< Back</a>.</p>';
echo "<p><a href='?page=adminCourtForm'>Add new court</a></p>";
echo "<table id='userAdminTable'>";
echo "<tr><th>Actions</th><th>Name</th><th>Used for sport</th><th>Open from</th><th>Open until</th></tr>";
foreach ($fieldClubCourts as $fieldClubCourt)
{
	$actions = array();

	$actions[] = "<a href='?page=adminCourtForm&courtId={$fieldClubCourt->getId()}'><img src='images/edit.png' /></a>";
	$actions[] = "<a href='?page=adminCourt&deleteCourt={$fieldClubCourt->getId()}' onclick='return confirmDelete()'><img src='images/remove.png' /></a>";

	$usedBySports = array();
	foreach ($fieldClubCourt->usedBySports as $sportId)
	{
		$usedBySports[] = new FieldClubSport($core, $sportId);
	}

	echo "<tr><td class='adminActions'>".join("&nbsp;&nbsp;",$actions)."</td>";
	echo "<td>{$fieldClubCourt}</td><td>".join(", ",$usedBySports)."</td><td>{$fieldClubCourt->openFrom}</td><td>{$fieldClubCourt->openUntil}</td></tr>";
}

echo "</table>";

?>
	
