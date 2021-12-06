<?php 

require_once("inc/core.class.php");
$core = new Core();
$core->setDebugging(TRUE);

$SITE_ROOT = $core->getConfVar('SITE_ROOT_URL');
$SITE_NAME = $core->getConfVar('SITE_NAME');

echo '<?xml version="1.0" encoding="UTF-8"?>';

?>
<kml xmlns="http://earth.google.com/kml/2.2">
<Document>
  <name><?php echo $SITE_NAME; ?></name>
  <description><![CDATA[Map of the courts at <?php echo $SITE_NAME; ?>]]></description>
  <Style id="style1">
    <LineStyle>
      <color>40000000</color>
      <width>3</width>
    </LineStyle>
    <PolyStyle>
      <color>7333FF33</color>
      <fill>1</fill>
      <outline>1</outline>
    </PolyStyle>
  </Style>
  <Style id="style2">
    <LineStyle>
      <color>40000000</color>
      <width>3</width>
    </LineStyle>
    <PolyStyle>
      <color>73FF0000</color>
      <fill>1</fill>
      <outline>1</outline>
    </PolyStyle>
  </Style>
  <Style id="style3">
    <LineStyle>
      <color>40000000</color>
      <width>3</width>
    </LineStyle>
    <PolyStyle>
      <color>7333FFFF</color>
      <fill>1</fill>
      <outline>1</outline>
    </PolyStyle>
  </Style>
  <Style id="style4">
    <LineStyle>
      <color>40000000</color>
      <width>3</width>
    </LineStyle>
    <PolyStyle>
      <color>730000FF</color>
      <fill>1</fill>
      <outline>1</outline>
    </PolyStyle>
  </Style>
  <Placemark>
    <name>Badminton court</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=1" target="_blank">show court</a>]]></description>
    <styleUrl>#style1</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.105639,52.207493,0.000000
            0.105617,52.207386,0.000000
            0.105733,52.207375,0.000000
            0.105760,52.207481,0.000000
            0.105639,52.207493,0.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Squash Court 1</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=2" target="_blank">show court</a>]]></description>
    <styleUrl>#style2</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
		  0.105761,52.207419,0 
		  0.105745,52.207366,0 
		  0.1059,52.20735,0 
		  0.105913,52.207408,0 
		  0.105761,52.207419,0 
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Squash Court 2</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=3" target="_blank">show court</a>]]></description>
    <styleUrl>#style2</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
		  0.105771,52.207477,0 
		  0.105760,52.207427,0 
		  0.105913,52.207412,0 
		  0.105927,52.207466,0 
		  0.105772,52.207477,0 
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Squash Court 3</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=4" target="_blank">show court</a>]]></description>
    <styleUrl>#style2</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.105456,52.207439,0.000000
            0.105448,52.207390,0.000000
            0.105593,52.207378,0.000000
            0.105604,52.207424,0.000000
            0.105456,52.207439,0.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Tennis Court 1</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=7" target="_blank">show court</a>]]></description>
    <styleUrl>#style3</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
		  0.1033,52.206626,10 
		  0.103227,52.206417,10 
		  0.103386,52.206398,10 
		  0.103461,52.206605,10 
		  0.1033,52.206626,10 
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Tennis Court 2</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=8" target="_blank">show court</a>]]></description>
    <styleUrl>#style3</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.103099,52.206654,10.000000
            0.103029,52.206444,10.000000
            0.103185,52.206425,10.000000
            0.103256,52.206635,10.000000
            0.103099,52.206654,10.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Tennis Court 3</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=9" target="_blank">show court</a>]]></description>
    <styleUrl>#style3</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.102899,52.206680,10.000000
            0.102828,52.206474,10.000000
            0.102982,52.206451,10.000000
            0.103053,52.206661,10.000000
            0.102899,52.206680,10.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Tennis Court 4</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=10" target="_blank">show court</a>]]></description>
    <styleUrl>#style3</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
		  0.1033,52.206948,10 
		  0.103227,52.20674,10 
		  0.103383,52.206721,10 
		  0.103454,52.206929,10 
		  0.1033,52.206948,10 
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Tennis Court 5</name>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=11" target="_blank">show court</a>]]></description>
    <styleUrl>#style3</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
		  0.103099,52.206975,10 
		  0.103027,52.206767,10 
		  0.103184,52.206746,10 
		  0.103255,52.206954,10 
		  0.103099,52.206975,10 
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Tennis Court 6</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=12" target="_blank">show court</a>]]></description>
    <styleUrl>#style3</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.102896,52.207001,10.000000
            0.102824,52.206791,10.000000
            0.102985,52.206772,10.000000
            0.103054,52.206982,10.000000
            0.102896,52.207001,10.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Netball Court 1</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=13" target="_blank">show court</a>]]></description>
    <styleUrl>#style4</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.103278,52.206982,0.000000
            0.103186,52.206718,0.000000
            0.103403,52.206688,0.000000
            0.103494,52.206955,0.000000
            0.103278,52.206982,0.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
  <Placemark>
    <name>Netball Court 2</name>
    <Snippet></Snippet>
    <description><![CDATA[<a href="<?php echo $SITE_ROOT?>?page=bookingTable&showCourt=14" target="_blank">show court</a>]]></description>
    <styleUrl>#style4</styleUrl>
    <Polygon>
	<altitudeMode>relativeToGround</altitudeMode>
      <outerBoundaryIs>
        <LinearRing>
          <tessellate>1</tessellate>
          <coordinates>
            0.103036,52.207012,20.000000
            0.102940,52.206749,20.000000
            0.103158,52.206718,20.000000
            0.103250,52.206985,20.000000
            0.103036,52.207012,20.000000
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
</Document>
</kml>
