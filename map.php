<?php
require_once("inc/core.class.php");
$core = new Core();
$fieldClub = new FieldClub($core);
?>

<script type="text/javascript" src= "http://maps.google.com/maps/api/js?sensor=false">
</script>
<script>
var map;
var cta_layer;
var loader;
var loaderId;

function initialize() {
	loader = document.getElementById("loader");
	var kmlUrl = '<?php echo $core->getConfVar('SITE_ROOT_URL'); ?>map_kml.php?dummy=' + (new Date()).getTime();
	var myLatlng = new google.maps.LatLng(52.20686,0.104300);
	var myOptions = {
		zoom: 18,
		center: myLatlng,
		disableDefaultUI: true,
		mapTypeId: google.maps.MapTypeId.HYBRID
	}
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

	cta_layer = new google.maps.KmlLayer(kmlUrl, {suppressInfoWindows: false,preserveViewport:true});
	cta_layer.setMap(map);
	loaderId = setInterval("kmlLoader()", 10)

	google.maps.event.addListener(cta_layer, 'click', function(kmlEvent) {
		    kmlEvent.featureData.description = kmlEvent.featureData.description.replace(/ target="_blank"/ig, "");
		  });
}

function kmlLoader() {

	if (typeof  cta_layer.getMetadata() == "object") {

		loader.style.display = "none";
		clearInterval(loaderId);
		return true;
	} else {
		return false;
	}
}
</script>


<?php
$res =& $fieldClub->getCourtDB();
$fieldClubCourts = array();
if ($res && $res->numRows() > 0)
	while($data = $res->fetchRow())
		$fieldClubCourts[] = new FieldClubCourt($core,null,$data);

$bookingTablesLinks = array();
foreach ($fieldClubCourts as $fieldClubCourt)
{
	$bookingTablesLinks[] = "<a href='?page=bookingTable&amp;showCourt={$fieldClubCourt->getId()}'>{$fieldClubCourt}</a>";
}

echo "<p><a href='?page=map'>Show map of courts</a><br />or view court: ".join(", ",$bookingTablesLinks)."</p>";
?>

<center>
<div id="loader" style="background: red; color:white;display:block; width:100px;">
	Loading map...
</div>
<div id="map_canvas"  style="height: 500px;width: 780px;">
</div>
</center>

<script type="text/javascript">initialize();</script>
