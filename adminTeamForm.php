<?php

require_once("inc/core.class.php");
if (!isset($core)) {
	$core = new Core();
}

$fieldClubAccess = new FieldClubAccess($core);
if (!$fieldClubAccess->hasAccessTo(FieldClubAccess::$PROGRAMLEVEL_GENERAL_ADMIN))
	Toolkit::redirect("?page=bookingTable");

$fieldClub = new FieldClub($core);

$fieldClubTeam = NULL;

$editing = isset($_GET['teamId']);
if ($editing)
	$fieldClubTeam = new FieldClubTeam($core, $_GET['teamId']);
else
	$fieldClubTeam = new FieldClubTeam($core);

if (isset($_POST['teamFormSubmit']))
{
	$fieldClubTeam->name = $_POST['name'];
	$fieldClubTeam->sportId = $_POST['sportId'];

	if ($fieldClubTeam->save())
	{
		$core->message(($editing ? "Saved." : "Added."));
		if (!$editing)
			$fieldClubTeam = new FieldClubTeam($core);
	}
	else
		$core->error("Couldn't ".($editing ? "save." : "add."));
}

$title = "FieldClub Team";

$sports = array();
$res =& $fieldClub->getSportDB();

if ($res && $res->numRows() > 0)
{
	while ($data = $res->fetchRow())
	{
		$sports[] = new FieldClubSport($core,null,$data);
	}
}

$sportsOptionHTML = NULL;
foreach ($sports as $sport)
{
	$selected = ($sport->getId() == $fieldClubTeam->sportId ? "selected" : "");
	$sportsOptionHTML .= "<option value='{$sport->getId()}' $selected>{$sport}</option>";
}

?>

<p><a href='?page=adminTeam'>< Back</a>.</p>
<form class="adminForm" action="" method="post">
<fieldset><legend><?php echo ($editing ? "Edit $title" : "New $title"); ?></legend>
<input type="hidden" id="teamId" name="teamId" value="<?php echo $fieldClubTeam->getId(); ?>" />
<li><label for="name">Name:</label><input name="name" id="name" value="<?php echo $fieldClubTeam->name; ?>" /></li>
<li><label for="sportId">Sport:</label><select name="sportId" id="sportId"><?php echo $sportsOptionHTML; ?></select></li>
<li>To assign a captain to this team edit the user account of the user who is to become captain of this team.</li>
<li><input name="teamFormSubmit" type="submit" value="<?php echo ($editing ? "Save" : "Add"); ?>" /> 
</ul>
</fieldset>

</form>
</div>


