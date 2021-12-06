<?php

if (isset($_POST['acceptTAndC']))
{
	$fieldClubAccess->hasAcceptedTAndC(true);
}

if ($fieldClubAccess->hasAcceptedTAndC())
	Toolkit::redirect("?page=bookingTable");

?>

<h2>Badminton Court Rules</h2>
<p>The following rules apply to the booking and use of the Badminton Court situated at Old Fields on Grange Road:</p>
<ol>
<li>Only members of Trinity College may book the Badminton Court, this can be done via the Online Court Booking System, or by telephone (fellows only).</li>
<li>Wolfson College are not permitted to use the Badminton Court.</li>
<li>There is no advance or block-booking except for the Trinity College Badminton Club. <u>You can only book for the current week.</u></li>
<li><u>No person or group of players is allowed to book the Court for more than one hour.</u></li>
<li>The person who has booked the Court must be present him or herself, and should limit their number of guests to three.</li>
<li>Use by members of Trinity, with up to three guests each is free subject to usual booking procedures. Use by a non-trinity body requires the hiring of the Court; Problems have arisen in the past because of the use of the Court by completely non-Trinity groups (though the booking was done by Trinity members). This practice had been regarded as an abuse of the system and is not permitted.</li>
<li>The Court should not be used after 22:00 during the week and 22:00 at weekends and Bank Holidays.</li>
<li>Only non-marking indoor shoes should be worn on the Court.</li>
<li>If you book the Court please ensure that you are able to use it, if not then cancel your bookings so others are able to book that session.</li>
<li>The last session on weekdays is 21:00->22:00, Monday-Sunday. 19:00->20:00 weekends in Bank Holidays</li>
<li>The court is only to be used for badminton. The Badminton Court is not to be booked for dance practice. There are plenty of other places available for other activities.</li>
<li>Players must tidy the court and switch off the lights after using it, removing any used shuttlecocks or other debris from the court.</li>
<li>The door to the court must not be held open for safety and security reasons.</li>
<li>The porters have the right to remove players from the court or suspend or terminate anyone's account at their own discretion.</li>
</ol>
<h2>Squash Club Rules</h2>
<p>The following rules apply to the booking and use of the Squash Courts situated at Old Fields on Grange Road.</p>
<ol>
<li>Only members of Trinity College may book the Courts, this can be done using the Online Courtbooking System, or telephone (fellows only). There is no priority given to Trinity Students. The priority in booking is to the Trinity Squash Captain who may book Court for the College Team's use.</li>
<li>Members of Wolfson and Newnham College are not allowed to use the Squash Courts.</li>
<li>The last session is 21:30->22:00.</li>
<li>Fellows can use the Courts after 21:00, and there is no need for them to book after 21:00.</li>
<li>The named person who has booked the Court must be present him or herself.</li>
<li>Bidwells have a block booking between October and March, every Tuesday on Court 1+2 from 18:00-19:00, please ensure that thse courts are not booked by anyone else during these periods.</li>
<li>If you book the courts please ensure that you are able to use it, if not then cancel your booking so that others are able to book that session.</li>
</ol>

<h2>Tennis Court Rules</h2>
<p>The following rules apply to the booking and use of the tennis courts situated at Old Fields on Grange Road:</p>
<ol>
<li>Only members of Trinity College may book the courts, this can be done via the Online Court Booking System, or telephone (fellows only).</li>
<li>There is no block-booking except for the Trinity College Tennis Club, Hockey Club and Netball Club. Everyone else eligible to book courts may do so up to 48 hours in advance.</li>
<li>The Trinity College Tennis Club, Hockey Club and Netball Club have the right to remove players in case the courts are required for a match fixture that has been scheduled with less than 48 hours notice.</li>
<li>No person or group of players is allowed to book the Court for more than 2 hours, except for the Trinity College Tennis Club, Hockey Club and Netball Club.</li>
<li>From the beginning of Michaelmas term to the end of Lent term the hardcourts are designated netball courts and do not have tennis nets set up on them.</li>
<li>If the astroturf courts are used for any sport that requires the temporary dismantling of the tennis nets these are to be put up again by whoever took them down as soon as they have finished using the courts. If you do not know how to take down and put up the nets properly you are not allowed to dismantle them; previously problems have arisen due to people damaging the nets.</li>
<li>The person who has booked the Court must be present him or herself.</li>
<li>Use by members of Trinity is free subject to usual booking procedures. Use by a non-trinity body requires the hiring of the Court.</li>
<li>They keys to the courts can be signed out at the porter's lodge at Burrell's Field on the production of a university card and a £10 deposit.The courts are to be locked after use and the keys return immediately.</li>
<li>If you book the Court please ensure that you are able to use it, if not then cancel your bookings so others are able to book that session.</li>
<li>The porters have the right to remove players from the court or suspend or terminate anyone's account at their own discretion.</li>
</ol>


<form action="" method="post">
<p><i><input type="checkbox" name="acceptTAndC" id="acceptTAndC" /> <label for="acceptTAndC">I have read and accept the Terms & Conditions </label></i>
<input type="submit" value="Continue" id="loginButton" disabled="disabled"/></p></form>

<script type="text/javascript" language="javascript">
$('#acceptTAndC').change(
        function(e) { 
            if($('#acceptTAndC').is(":checked")) {
                $('#loginButton').attr('disabled', false);
            } 
            else {
                $('#loginButton').attr('disabled', true);
            } 
        }
);
</script>
