<?php

if (!isset($_GET['teamId']))
	die();

require_once("inc/core.class.php");
$core = new Core();
$fieldClub = new FieldClub($core);

$fieldClubTeam = new FieldClubTeam($core,$_GET['teamId']);

$fieldClubCourts = $fieldClubTeam->getSport()->getCourts();

$checked = (count($fieldClubCourts) == 1 ? "checked" : "");

foreach ($fieldClubCourts as $court)
{
	$courtsHTML[] = "<label for='court{$court->getId()}'>{$court}</label><input id='court{$court->getId()}' type='checkbox' name='courts[]' value='{$court->getId()}' $checked/>";
}

echo join(" ",$courtsHTML);

?>
