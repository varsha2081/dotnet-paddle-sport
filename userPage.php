<?php

$core = new Core();
$core->setDebugging(TRUE);
$fieldClub = new FieldClub($core);
$fieldClubAccess = new FieldClubAccess($core);

if (!$fieldClubAccess->isLoggedIn())
	Toolkit::redirect("?page=login&goto=userPage");

// DEAL WITH REQUEST

if (isset($_GET['deleteBooking']))
{
	$fieldClubBooking = new FieldClubBooking($core,$_GET['deleteBooking']);
	if ($fieldClubBooking->delete())
		$core->message("Booking deleted.");
}

/*
if (isset($_GET['deleteKitOrder'])) {
	$fieldClubPlacedKitOrder = new FieldClubPlacedKitOrder($core, $_GET['deleteKitOrder']);
	if ($fieldClubPlacedKitOrder->delete()) {
		$core->message("Kit order deleted.");
	}
}
*/

//

$bookings = array();
$res =& $fieldClub->getBookingDB("userId='{$fieldClubAccess->getId()}' AND NOW() < startDateTime ORDER BY teamId, startDateTime ASC");
if ($res && $res->numRows() > 0)
	while ($data = $res->fetchRow())
		$bookings[] = new FieldClubBooking($core,null,$data);

$pastBookings = array();
$res =& $fieldClub->getBookingDB("userId='{$fieldClubAccess->getId()}' AND NOW() > startDateTime ORDER BY startDateTime ASC LIMIT 20");
if ($res && $res->numRows() > 0)
	while ($data = $res->fetchRow())
		$pastBookings[] = new FieldClubBooking($core,null,$data);

?>

<a href="?page=bookingTable">< Back to booking table</a>.</p>

<h2>Details</h2>
<?php 
echo "{$fieldClubAccess->name}, $fieldClubAccess(".FieldClubUser::$LOGINTYPE__MAP[$fieldClubAccess->loginType].")"; 

if (count($fieldClubAccess->captainOfTeams) > 0)
{
	$teams = array();
	foreach ($fieldClubAccess->captainOfTeams as $teamId)
	{
		$team = new FieldClubTeam($core, $teamId);
		$teams[] = "$team ({$team->getSport()})";
	}
	echo "<p>You are captain of: ".join(", ",$teams)."</p>";
	echo "<p>Since you are the captain of a team you may make <a href='?page=blockBooking'>block bookings</a>.</p>";
}


/*
?>
<h2>Kit Order</h2>
<?php

// Check to see if there are any current kit orders 

$res =& $fieldClub->getKitOrderDB("NOW() < closingDate");
$kitOrdersHTML = array();
$kitOrders = array();

if ($res && $res->numRows() > 0) {
	while ($data = $res->fetchRow()) {
		$kitOrder = new FieldClubKitOrder($core, null, $data); 
		$kitOrders[] = $kitOrder;
		$kitOrdersHTML[] = "<a href='?page=kitOrder&id={$kitOrder->getId()}'>{$kitOrder}</a> (closes {$kitOrder->closingDate->getDate()})";
	}
}

// Check to see if the user has placed any kit orders 

$res =& $fieldClub->getPlacedKitOrderDB("userid = {$fieldClubAccess->getId()}");
$placedOrdersHTML = array();
if ($res && $res->numRows()) {
	while ($data = $res->fetchRow()) {
		$order = new FieldClubPlacedKitOrder($core, null, $data);
		$items = array();
		$totalPrice = 0.0;
		foreach($order->orderedItems as $itemId => $sizeId) {
			$item = new FieldClubKitOrderItem($core, $itemId);
			$size = new FieldClubKitOrderSize($core, $sizeId);
			$items[] = "<li>{$item} ({$size}) £{$item->price}</li>";
			$totalPrice += $item->price;
		}

		$placedOrdersHTML[] = "<li><ul>".join($items,"")."<li>Total: £{$totalPrice} - <span style='color:green'>{$order->getStatus()}</span> - ".
								"<a href='?page=userPage&deleteKitOrder={$order->getId()}' onclick='return confirmDelete()'>delete kit order</a></li></ul></li>";
	}
}

if (count($placedOrdersHTML) == 0) {
	echo "<p>You haven't placed any kit orders</p>";
}
else {
	echo "<p>Your kit orders:<ol>".join($placedOrdersHTML, " ")."</ol></p>";
}


if (count($kitOrdersHTML) > 0) {
	echo "The ".join($kitOrdersHTML," and ")." ".(count($kitOrdersHTML) > 1 ? "are" : "is")." now open.<br />";
}

$res =& $fieldClub->getKitOrderDB();
$kitOrdersHTML = array();
$kitOrders = array();

if ($res && $res->numRows() > 0) {
	while ($data = $res->fetchRow()) {
		$kitOrder = new FieldClubKitOrder($core, null, $data); 
		$kitOrders[] = $kitOrder;
	}
}

foreach ($kitOrders as $k) {
	if ($fieldClubAccess->isAdmin() || $k->getAdmin()->getId() == $fieldClubAccess->getId())	{
		echo "<p><a href='?page=adminKitOrder&id={$k->getId()}'>{$k} Admin</a></p>";
	}
}
*/
?>



<h2>Current bookings</h2>
<ul>
<?php

$showOwner = (count($fieldClubAccess->captainOfTeams) > 0 ? TRUE : FALSE);

$currentTeamId = -2; // keep track of which team we're currently outputting for, so that we may make spaces between them
foreach ($bookings as $booking)
{
	if ($currentTeamId == -2) {
		$currentTeamId = $booking->teamId;
	}
	else if ($currentTeamId != $booking->teamId) {
		echo "</ul><ul>";
		$currentTeamId = $booking->teamId;
	}

	$fieldClubCourt = new FieldClubCourt($core, $booking->courtId);
	$fieldClubTeam = new FieldClubTeam($core, $booking->teamId);
	$fieldClubSport = new FieldClubSport($core, $booking->sportId);

	echo "<li>{$booking->getTimeSpan()} on {$booking->getDate()} playing {$fieldClubSport} in the {$fieldClubCourt} ".($showOwner ? "(for $fieldClubTeam) " : "").
			"<a href='?page=userPage&deleteBooking={$booking->getId()}' onclick='return confirmDelete()'><img src='images/remove.png' />Delete booking</a></li>".
			($booking->notes != "" ? "<ul><li>{$booking->notes}</li></ul>" : "");
}
?>
</ul>


<h2>Past bookings</h2>
<ul>
<?php

$currentTeamId = -2; // keep track of which team we're currently outputting for, so that we may make spaces between them
foreach ($pastBookings as $booking)
{
	if ($currentTeamId == -2) {

		$currentTeamId = $booking->teamId;
	}
	else if ($currentTeamId != $booking->teamId) {
		echo "</ul><ul>";
		$currentTeamId = $booking->teamId;
	}

	$fieldClubCourt = new FieldClubCourt($core, $booking->courtId);
	$fieldClubTeam = new FieldClubTeam($core, $booking->teamId);
	$fieldClubSport = new FieldClubSport($core, $booking->sportId);

	echo "<li>{$booking->getTimeSpan()} on {$booking->getDate()} playing {$fieldClubSport} in the {$fieldClubCourt} ".($showOwner ? "(for $fieldClubTeam)" : "")."</li>";
}

?>
</ul>
