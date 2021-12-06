<?php
require_once("inc/core.class.php");
$core = new Core();
$FieldClubAccess = new FieldClubAccess($core);

$FieldClubAccess->logOut();

Toolkit::redirect("?page=login");

?>
