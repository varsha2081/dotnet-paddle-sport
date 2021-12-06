<?php
if (!(php_sapi_name()==="cli")) {
	echo "Error: this code should only be run in CLI-mode, not through a browser.";
	return -1;
}

//Create an admin user

chdir('../');
require_once('inc/core.class.php');
$core = new Core();

$user = new FieldClubUser($core);
$user->accessLevel = FieldClubUser::$ACCESSLEVEL_ADMIN;
$user->loginType = FieldClubUser::$LOGINTYPE_ALTERNATIVE_LOGIN;
$user->name = 'Admin user';
$user->username = 'lcd33';
$user->password = 'password';
$user->email = 'myemail@gmail.com';

if ($user->save()) {
	echo "Done!";
}
else {
	echo $core->outputErrors('');
}

?>
