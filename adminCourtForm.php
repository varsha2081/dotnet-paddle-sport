<?php

require_once("inc/core.class.php");
if (!isset($core))
	$core = new Core();

$fieldClubAccess = new FieldClubAccess($core);
if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("index.php?page=bookingTable");

$fieldClub = new FieldClub($core);

$fieldClubCourt = NULL;

$editing = isset($_GET['courtId']);
if ($editing)
{
	$fieldClubCourt = new FieldClubCourt($core, $_GET['courtId']);
}
else
{
	$fieldClubCourt = new FieldClubCourt($core);
}

# Get all other courts so that we can define overlaps
$courts = array();
$res =& $fieldClub->getCourtDB("id!={$_GET['courtId']}");
if ($res && $res->numRows() > 0) 
{
	while ($data = $res->fetchRow())
	{
		$courts[] = new FieldClubCourt($core,null,$data);
	}
}


if (isset($_POST['courtFormSubmit']))
{
	$fieldClubCourt->name = $_POST['name'];
	$fieldClubCourt->usedBySports = $_POST['usedBySports'];
	$fieldClubCourt->overlapsWith = (isset($_POST['overlappingCourts']) ? $_POST['overlappingCourts'] : array());
	$fieldClubCourt->openFrom = new T_Time($_POST['openFrom']);
	$fieldClubCourt->openUntil = new T_Time($_POST['openUntil']);
	$fieldClubCourt->slotDuration = $_POST['slotDuration']*60;

	if ($fieldClubCourt->save())
	{
		$core->message(($editing ? "Saved." : "Added."));
		if (!$editing)
			$fieldClubCourt = new FieldClubCourt($core);
	}
	else
		$core->error("Couldn't ".($editing ? "save." : "add."));

	$fieldClubCourt = new FieldClubCourt($core,$_GET['courtId']);
}

$title = "FieldClub Court";

$overlappingCourtsCheckBoxesHTML = array();
foreach ($courts as $court)
{
	$checked = (in_array($court->getId(),$fieldClubCourt->getOverlappingCourts()) ? "checked" : "");
	$courtsCheckBoxesHTML[] = "<label for='court{$court->getId()}'>{$court}:<input id='court{$court->getId()}' type='checkbox' name='overlappingCourts[]' value='{$court->getId()}' $checked></label>";
}

$sports = array();
$res =& $fieldClub->getSportDB();

if ($res && $res->numRows() > 0)
{
	while ($data = $res->fetchRow())
	{
		$sports[] = new FieldClubSport($core,null,$data);
	}

}


$sportsCheckBoxesHTML = array();
foreach ($sports as $sport)
{
	$checked = (in_array($sport->getId(),$fieldClubCourt->usedBySports) ? "checked" : "");
	$sportsCheckBoxesHTML[] = "<label for='sport{$sport->getId()}'>{$sport}:<input id='sport{$sport->getId()}' type='checkbox' name='usedBySports[]' value='{$sport->getId()}' $checked></label>";
}

?>
<p><a href="?page=adminCourt">< Back</a>.</p>
<form class="adminForm" action="" method="post">
<fieldset><legend><?php echo ($editing ? "Edit $title" : "New $title"); ?></legend>
<ol>
<input type="hidden" id="id" name="id" value="<?php echo $fieldClubCourt->getId(); ?>" />
<li><label for="name">Name:</label><input name="name" id="name" value="<?php echo $fieldClubCourt; ?>" /></li>
<li><label>Used by sports:</label><span><?php echo join(", ",$sportsCheckBoxesHTML); ?></span></li>
<li><label for="openFrom">Open from:</label><input type="text" name="openFrom" id="openFrom" value="<?php echo $fieldClubCourt->openFrom; ?>"/> (hh:mm)</li>
<li><label for="openUntil">Open until:</label><input type="text" name="openUntil" id="openUntil" value="<?php echo $fieldClubCourt->openUntil; ?>" /> (hh:mm)</li>
<li><label for="slotDuration">Slot duration (in minutes):</label><input type="text" name="slotDuration" id="slotDuration" value="<?php echo $fieldClubCourt->slotDurationMins(); ?>" /> (warning: changing this will mess up bookings that already exist!)</li>
<li><label>Overlapping courts:</label><span><?php echo join(", ",$courtsCheckBoxesHTML); ?></span></li>
<li><input name="courtFormSubmit" type="submit" value="<?php echo ($editing ? "Save" : "Add"); ?>" /></li> 
</ol>

</form>
</div>

<?php echo $core->outputErrors(); ?>
