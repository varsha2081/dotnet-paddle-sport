<?php
require_once("inc/core.class.php");
$core = new Core();
$fieldClubAccess = new FieldClubAccess($core);

// Make it possible for admin users to change to another user if needed
if (isset($_GET['sudo']))
{
    $fieldClubAccess->changeUser($_GET['sudo']);
}

$pages = array(
			"login" => array("login.php","Login to Field Club"),
			"bookingTable" => array("bookingTable.php","Booking Table"),
			"bookingTable_new" => array("bookingTable_new.php","Booking Table"),
			"logout" => array("logout.php","Logging out"),
			"userPage" => array("userPage.php","User page"),
			"admin" => array("admin.php","Admin interface"),
			"showLog" => array("showLog.php","Log"),
			"adminCourt" => array("adminCourt.php","Admin interface: Court admin"),
			"adminCourtForm" => array("adminCourtForm.php","Admin interface: Add/edit court"),
			"adminSport" => array("adminSport.php","Admin interface: Sport admin"),
			"adminSportForm" => array("adminSportForm.php","Admin interface: Add/edit sport"),
			"adminTeam" => array("adminTeam.php","Admin interface: Team admin"),
			"adminTeamForm" => array("adminTeamForm.php","Admin interface: Add/edit Team"),
			"adminUser" => array("adminUser.php","Admin interface: User admin"),
			"adminUserForm" => array("adminUserForm.php","Admin interface: Add/edit user"),
			"adminUserPlodge" => array("adminUserPlodge.php","Admin interface: Plodge user admin"),
			"blockBooking" => array("blockBooking.php","Block Booking Interface"),
			"tandc" => array("tandc.php","Terms and Conditions"),
			"requestNewUser" => array("requestNewUser.php","Request a new user"),
			"adminUserRequests" => array("adminUserRequests.php","User requests admin"),
			"map" => array("map.php","Map of courts"),
		);


$includePage = NULL;
if (isset($_GET['page']))
{
	if (isset($pages[$_GET['page']]))
	{
		$includePage = $_GET['page'];
	}
	else
	{
		$includePage = "login";
	}
}
else
{
	$includePage = "login";
}

// some people have to accept the term and conditions
if ($fieldClubAccess->isLoggedIn() && $includePage != "tandc" && $includePage != "requestNewUser" && !$fieldClubAccess->hasAcceptedTandC())
{
	Toolkit::redirect("?page=tandc");
}


// output header
if (!isset($_POST['noHeaders']))
{
	header ('Content-type: text/html; charset=utf-8');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs" lang="cs">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />

<script type="text/javascript" src="https://www.trin.cam.ac.uk/fieldclub/js/jquery.js"></script>
<script type="text/javascript" src="https://www.trin.cam.ac.uk/fieldclub/js/jquery-ui.js"></script>
<script type="text/javascript" src="https://www.trin.cam.ac.uk/fieldclub/js/fieldclub.js"></script>

<title><?php echo $pages[$includePage][1]; ?></title>
<link href="main.css" rel="stylesheet" type="text/css" media="screen" />
</head>
<body>
<div id="main">
<div id="header"><?php echo $core->getSiteName(); ?><div id="title"><?php echo $pages[$includePage][1]; ?></div>
    <div id="userStatus">
        <?php
            if ($fieldClubAccess->isLoggedIn())
            {
                echo "logged in as {$fieldClubAccess} (<a href='?page=logout'>log out</a>, <a href='?page=userPage'>user page</a>)";
            }
            else if (!$fieldClubAccess->isLoggedIn() && $includePage != "login")
            {
                echo "not logged in, <a href='?page=login'>click here to log in</a>";
            }
        ?>
    </div>
</div>
<div id="content">

<?php
}

require_once($pages[$includePage][0]);
// output footer
if (!isset($_POST['noHeaders']))
{
	if ($core->areErrors())
		echo "<div id='error'>{$core->outputErrors()}<br /></div>";
	if ($core->areMessages())
		echo "<div id='message'>{$core->outputMessages()}<br /></div>";
?>

</div>
<div id="footer">
Written by <script type="text/javascript" language="javascript">
<!--
ML="oLyf i><arhn.d/\"lD=t:umb@e";
MI="784:9I3B?F85@C0D@I53H=I;G2<IE?61I534AI;G27>86";
OT="";
for(j=0;j<MI.length;j++){
OT+=ML.charAt(MI.charCodeAt(j)-48);
}document.write(OT);
// --></script>
</div>
</div>
</body>
</html>

<?php
}
?>

