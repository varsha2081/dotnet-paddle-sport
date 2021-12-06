<?php
require_once("inc/core.class.php");
require_once("inc/ucam_webauth.php");

if (!isset($core))
	$core = new Core();

// We want to make sure that the user is passed to the page they requested if they weren't logged in:
if (isset($_GET['goto']))
{
	$_SESSION['goto'] = $_GET['goto'];
}


if (isset($_POST['ravenLoginSubmit']) || (isset($_GET['WLS-Response']) && !isset($_POST['alternativeLoginSubmit'])))
{
	$webauth = new Ucam_Webauth(array(
                        'hostname' => $_SERVER['HTTP_HOST'],
                        'key_dir' => 'inc',
                        'cookie_key' => "balh",
						'do_session' => FALSE
                        ));

	$complete = $webauth->authenticate();

	if ($webauth->success()) 
	{ 
        $fieldClubAccess = new FieldClubAccess($core,$webauth->principal(),null,TRUE);
    } 
	else 
	{
		trigger_error("Raven error: {$webauth->status()}: {$webauth->msg()}", E_USER_ERROR);
    }
}
else if (isset($_POST['alternativeLoginSubmit']))
{
	$fieldClubAccess = new FieldClubAccess($core,$_POST['username'],$_POST['password']);
}

if (isset($_GET['debug']))
{
	$core->debug($fieldClubAccess);
}

if ($fieldClubAccess->isLoggedIn() && !$fieldClubAccess->accountDisabled())
{
	
    if (!isset($_SESSION['goto']))
	{
		if ($fieldClubAccess->hasAcceptedTandC())
			Toolkit::redirect("?page=bookingTable");
		else
			Toolkit::redirect("?page=tandc");
	}
	else
	{
		Toolkit::redirect("?page={$_SESSION['goto']}");
	}
}


?>

<p><a href="?page=bookingTable">Show booking table</a>.</p>

<div class="login">
	<form action="" method="post" id="ravenForm">
	<input type="hidden" value="<?php echo (isset($_REQUEST['goto']) ? $_REQUEST['goto'] : ""); ?>" name="goto" />
	<fieldset>
	<legend>Login using Raven</legend>
	<p>Go to the <b>Raven</b> login page:</p>
	<p><input type="submit" name="ravenLoginSubmit" value="Use Raven" /></p>
	<input type='hidden' value='true' name='noHeaders' />
	</fieldset>
	</form>
</div>
<div class="login">
	<form action="" method="post">
	<fieldset>
	<legend>Login using alternative login</legend>
	<p><label for="username">Username:</label><input type="text" id="username" name="username" /></p>
	<p><label for="password">Password:</label><input type="password" id="password" name="password" /></p>
	<input type="submit" name="alternativeLoginSubmit" value="Use alternative login" />
	</fieldset>
	</form>
</div>

<?php
/* 
<div style="float: left;"><p>The system is still in development. <a href="http://www.srcf.ucam.org/trinbadminton/fieldclub/doc/">Please post bugs and feature requests here</a>. Thanks. - Leif
   */
?>	
