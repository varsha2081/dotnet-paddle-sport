<?php

require_once("inc/core.class.php");
if (!isset($core))
	$core = new Core();

$fieldClubAccess = new FieldClubAccess($core);
if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("index.php?page=bookingTable");

$fieldClub = new FieldClub($core);

$fieldClubSport = NULL;

$editing = isset($_GET['SportId']);
if ($editing)
{
	$fieldClubSport = new FieldClubSport($core, $_GET['SportId']);
}
else
{
	$fieldClubSport = new FieldClubSport($core);
}
	
if (isset($_POST['SportFormSubmit']))
{
	$fieldClubSport->name = $_POST['name'];
	$fieldClubSport->maxFutureSlots = $_POST['maxFutureSlots'];

	if ($fieldClubSport->save())
	{
		$core->message(($editing ? "Saved." : "Added."));
		if (!$editing)
			$fieldClubSport = new FieldClubSport($core);
	}
	else
		$core->error("Couldn't ".($editing ? "save." : "add."));
}

$title = "FieldClub Sport";

?>
<p><a href="?page=adminSport">< Back</a>.</p>
<form class="adminForm" action="" method="post">
<fieldset><legend><?php echo ($editing ? "Edit $title" : "New $title"); ?></legend>
<ol>
<input type="hidden" id="id" name="id" value="<?php echo $fieldClubSport->getId(); ?>" />
<li><label for="name">Name:</label><input name="name" id="name" value="<?php echo $fieldClubSport; ?>" /></li>
<li><label for="maxFutureSlots">Max future slots:</label><input name="maxFutureSlots" id="maxFutureSlots" value="<?php echo $fieldClubSport->maxFutureSlots; ?>" /></li>
<li><input name="SportFormSubmit" type="submit" value="<?php echo ($editing ? "Save" : "Add"); ?>" /></li> 
</ol>
</fieldset>
</form>
</div>

<?php echo $core->outputErrors(); ?>
