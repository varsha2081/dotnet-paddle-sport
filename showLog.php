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

$res =& $fieldClub->getLog();

echo "<a href='?page=admin'>< Back</a>.";

echo "<h2>Last 50 log entries</h2>";
echo "<pre>";
if ($res && $res->numRows() > 0)
{
	while ($data = $res->fetchRow())
	{
		echo join("\t", $data)."<br />";
	}
}
echo "</pre>";
?>
