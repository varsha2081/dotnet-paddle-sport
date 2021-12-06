<?php
require_once 'inc/ucam_webauth.php';
require_once "inc/core.class.php";

$core = new Core();
$fieldClub = new FieldClub($core);


	$webauth = new Ucam_Webauth(array(
		        'hostname' => 'leif.denby.eu',
			'key_dir' => 'inc',
			'cookie_key' => "some random string"
			));

	$complete = $webauth->authenticate();

	if (!$complete) exit();

        if ($webauth->success()) { 
          echo 'SUCCESS. Your are ' . $webauth->principal();
		  $fieldClubAccess = new FieldClubAccess($core,$webauth->principal(),null,TRUE);
        } else {
          echo 'FAIL - ' . $webauth->status() . ':' . $webauth->msg();
        }

?>
