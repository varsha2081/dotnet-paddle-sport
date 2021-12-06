<?php
require_once("inc/core.class.php");
if (!isset($core)) {
	$core = new Core();
}

if (!(php_sapi_name()==="cli")) {
	echo "Error: this code should only be run in CLI-mode, not through a browser.";
	return -1;
}

$USERPAGE = $core->getConfVar('SITE_ROOT_URL')."?page=userPage";
$SITE_NAME = $core->getConfVar('SITE_NAME');

$fieldClub = new FieldClub($core);

$res =& $fieldClub->getBookingDB("startDateTime > CURDATE() && startDateTime < DATE_ADD(CURDATE(), INTERVAL 1 DAY) ORDER BY userId");

$fieldClubBookings = array(array());
$groupedBookings = array();

if ($res && $res->numRows() > 0)
{
	echo "There are {$res->numRows()} bookings tomorrow, sending reminder emails... ";
	while ($data = $res->fetchRow())
	{
		$fieldClubBooking = new FieldClubBooking($core,null,$data);
		if (!isset($groupedBookings[$fieldClubBooking->userId]))
		{
			// create array entry for this user
			$groupedBookings[$fieldClubBooking->userId] = array();
		}

		$groupedBookings[$fieldClubBooking->userId][] = $fieldClubBooking;
	}
}

foreach ($groupedBookings as $groupedBooking)
{
	$fieldClubUser = new FieldClubUser($core,$groupedBooking[0]->userId);

	$mailContent = "Dear {$fieldClubUser->name}\n\n";

	$s = ((count($groupedBooking) > 1) ? "s" : "");
	$itThem = ((count($groupedBooking) > 1) ? "them" : "it");

	$mailContent .= "Just a friendly reminder that you have made the following booking{$s} for tomorrow:\n";
	foreach ($groupedBooking as $booking)
	{
		$fieldClubSport = new FieldClubSport($core, $booking->sportId);
		$fieldClubCourt = new FieldClubCourt($core, $booking->courtId);
		$mailContent .= "* {$booking->getTimeSpan()} playing {$fieldClubSport} in the {$fieldClubCourt}.\n";
	}

	
	$mailContent .= "\nIf you do no longer wish to keep your booking{$s}, you can cancel {$itThem} by visiting your user page here: {$USERPAGE}.\n\n";
	$mailContent .= "Best wishes,\n{$SITE_NAME}";

	$core->sendEmail($core->getAdminEmail(),$SITE_NAME,$fieldClubUser->email,$fieldClubUser->name,"Reminder: Court booking{$s} tomorrow",$mailContent);
}

$fieldClub->log("Sent off ".count($groupedBookings)." reminder emails.");
echo "Done.";

?>
