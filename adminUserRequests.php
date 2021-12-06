<?php
require_once("inc/core.class.php");
$core = new Core();
$fieldClub = new FieldClub($core);

$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->isAdmin())
	Toolkit::redirect("?page=bookingTable");

$fieldClub = new FieldClub($core);

// DEAL WITH REQUEST
if (isset($_POST['addUserSubmit']))
{
	if (!isset($_POST['userType']))
		$core->error("You must select a user type.");
	else
	{
		$fieldClubUserRequest = new FieldClubUserRequest($core,$_POST['requestId']);
		if ($fieldClubUserRequest->loaded())
		{
			$fieldClubUser = new FieldClubUser($core);
			$fieldClubUser->username = $fieldClubUserRequest->username;
			$fieldClubUser->loginType = FieldClubUser::$LOGINTYPE_RAVEN;
			$fieldClubUser->userType = $_POST['userType'];

			if ($fieldClubUser->save())
			{
				$core->message("User created.");
				$fieldClubUserRequest->delete();
				$mailContent = "Dear {$fieldClubUser->name},\n\n";
				$mailContent .= "Your Field Club user has been created. You may now log in.\n\n";
				$mailContent .= "Best wishes,\nTrinity Field Club";
				if ($core->sendEmail($core->getAdminEmail(),"Trinity FieldClub",$fieldClubUser->email,$fieldClubUser->name,"Field Club user created",$mailContent))
				{
					$core->message("Email sent to new user OK.");
				}
				else
				{
					$core->message("There was an error sending an email to the new user ($fieldClubUser->email)");
				}
			}
		}
	}
}



if (isset($_GET['action']))
{
	if ($_GET['action'] == "deleteRequest")
	{
		$fieldClubUserRequest = new FieldClubUserRequest($core,$_GET['requestId']);
		$email = "{$fieldClubUserRequest->username}@cam.ac.uk";
		$name = Toolkit::nameFromLdapLookup($fieldClubUserRequest->username);
		if ($fieldClubUserRequest->delete())
		{
			$mailContent = "Dear $name,\n\nYour user request for the Field Club Booking system has not been approved. Please contant the admin on {$core->getAdminEmail()} if you have further questions.\n\nBest wishes,\nTrinity Field Club";
			if ($core->sendEmail($core->getAdminEmail(),"Trinity Field Club","{$fieldClubUserRequest->username}@cam.ac.uk","","Field Club user request deleted", $mailContent))
			{
				$core->message("Request deleted and email sent to inform of this.");
			}
		}
		else
		{
			$core->error("Could not delete user request");
		}
	}
}

// END DEAL WITH REQUEST
$userRequests = array();
$res =& $fieldClub->getUserRequestDB();
if ($res)
{
	while ($data = $res->fetchRow())
		$userRequests[] = new FieldClubUserRequest($core,null,$data);
}

echo '<p><a href="?page=admin">< Back</a>.</p>';
echo "<table id='userAdminTable'>";
echo "<tr><th>Add user</th><th>Delete request</th><th>Name</th><th>Username</th></tr>";
foreach ($userRequests as $userRequest)
{
	echo "<tr><td><form action='' method='post'>";
	$userTypeRadios = array();
	foreach (FieldClubUser::$USERTYPE__MAP as $id => $userType)
	{
		$userTypeRadios[] = "<label for='userType_{$id}_{$userRequest->getId()}'>{$userType}</label> <input type='radio' id='userType_{$id}_{$userRequest->getId()}' name='userType' value='{$id}' />";
	}
	echo join("", $userTypeRadios);
	echo "<input type='hidden' name='requestId' value='{$userRequest->getId()}' />";
	echo "<input type='submit' name='addUserSubmit' value='Add user' />";	
	echo "</form></td>";
	echo "<td><a href='?page=adminUserRequests&action=deleteRequest&requestId={$userRequest->getId()}'>delete request</a></td>";
	echo "<td>".Toolkit::nameFromLdapLookup($userRequest->username)."</td><td>{$userRequest->username}</td></tr>";
}
echo "</table>";

?>


