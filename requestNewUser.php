<a href="?page=bookingTable">Show booking table</a>.
<?php
require_once("inc/core.class.php");
if (!isset($_GET['username'])) {
	die();
}

$core = new Core();
$fieldClub = new FieldClub($core);

$username = $_GET['username'];
$name = Toolkit::nameFromLdapLookup($username);

echo "<p>Hi $name</p>";

$res = $fieldClub->getUserRequestDB("username='$username'");
if ($res && $res->numRows() == 0)
{
	$fieldClub->addUserRequestDB($username);

	$mailContent = "Dear Field Club Admin,\n\n";
	$mailContent .= "$name ($username) has requested a user on the Field Club system. Please tend to this request at your leisure ";
	$mailContent .= "(".$core->getConfVar('SITE_ROOT_URL')."/?page=adminUserRequests)";
	$mailContent .= ".\n\nField Club Booking system";
	if ($core->sendEmail($core->getAdminEmail(),"Trinity FieldClub",$core->getAdminEmail(),"Field Club Admin","Field Club: New user request",$mailContent)) {
		echo "<p>A request for a user has been made for your Raven-id. You will receive an email when your account is ready.</p>";
	}
	else {
		echo "<p>We're very sorry, but there was a technical problem with sending your user request. This will be dealt with soon.";
		$fieldclub->log("There was a problem with sending the user request for $username");
	}
}
else {
	echo "<p>You have already made a request for a user. Your request will be dealt with soon.</p>";
}

?>


