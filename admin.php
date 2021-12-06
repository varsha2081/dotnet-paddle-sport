<?php
require_once("inc/core.class.php");
if (!isset($core))
	$core = new Core();

$fieldClubAccess = new FieldClubAccess($core);



if (!$fieldClubAccess->hasAccessTo(FieldClubUser::$ACCESSLEVEL_PLODGE))
	Toolkit::redirect("?page=bookingTable");


$pages = array(
			"adminCourt" => array("adminCourt.php","Court admin",FieldClubUser::$ACCESSLEVEL_ADMIN),
			"adminSport" => array("adminSport.php","Sport admin",FieldClubUser::$ACCESSLEVEL_ADMIN),
			"adminTeam" => array("adminTeam.php","Team admin",FieldClubUser::$ACCESSLEVEL_ADMIN),
			"adminUser" => array("adminUser.php","User admin",FieldClubUser::$ACCESSLEVEL_ADMIN),
			"adminUserPlodge" => array("adminUserPlodge.php","Plodge user admin",FieldClubUser::$ACCESSLEVEL_PLODGE),
			"showLog" => array("showLog.php","Show log",FieldClubUser::$ACCESSLEVEL_PLODGE),
			"adminUserRequests" => array("adminUserRequests.php","User requests admin",FieldClubUser::$ACCESSLEVEL_PLODGE),
			"blockBooking" => array("blockBooking.php","Make block bookings",FieldClubUser::$ACCESSLEVEL_USER)
			
		);

$pagesHTML = array();


foreach ($pages as $key => $page)
{
	if ($fieldClubAccess->hasAccessTo($page[2]))
		$pagesHTML[] = "<a href='?page=$key'>{$page[1]}</a>";
}

?>


<p><a href="?page=bookingTable">Show booking table</a></p>

<fieldset id='admin'><legend>Select admin area:</legend>
<?php echo join("<br />",$pagesHTML); ?>
</fieldset>
