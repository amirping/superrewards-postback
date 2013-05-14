<?php

/*
	SuperRewards.com postback handling script for App and Game Publishers.

	You will need a web server running PHP and a MySQL database (or MySQL-like database). 
	This script uses PHP's PDO which can be configured to use different database types.

	Installation Instructions:

	1. Fill in the configuration options below.
	2. Place the script on your web server and make sure it is accessible from the Internet. 
		Ex: http://www.example.com/app/postback.php
	3. Automatically setup the database tables for use with this script by passing the setup option in the URL. 
		Ex: http://www.example.com/app/postback.php?setup=1 
	4. Test your integration by sending a Test Postback. 
		See: http://support.playerize.com/entries/22612522-Publishers-Signing-Up-and-Getting-Started#postback_test
	5. Use the information in the database to award in-game currency to your users.

	For more details, see our documentation at: 
	http://support.playerize.com/entries/22612522-Publishers-Signing-Up-and-Getting-Started
*/

define('APP_SECRET', ''); // App Secret Key. Find it by going to the Apps page, select Edit on the App of your choice, then Integrate.
define('DB_USER', ''); // Your database user.
define('DB_PASSWORD', ''); // Your database password.
define('DB_HOST', ''); // Your database host (usually 127.0.0.1).
define('DB_HOST_PORT', ''); // Your database host port (usually 3306).
define('DB_NAME', ''); // Your database name.
define('DB_PREFIX', ''); // OPTIONAL: A database table prefix, such as 'app1_'. This easily allows multiple apps to be served from the same database.

error_reporting(E_WARNING);

require_once('./SRPostbackHandler.class.php');

// *** No more configuration below this line. ***

header('Content-Type:text/plain');

$sr_postback_handler = new SRPostbackHandler(APP_SECRET, DB_USER, DB_PASSWORD, DB_HOST, DB_HOST_PORT, DB_NAME, DB_PREFIX);

// If &setup is passed in, setup tables needed to use this script.
if(isset($_REQUEST['setup']))
	$sr_postback_handler->SetupTables();
else
	echo $sr_postback_handler->HandlePostback($_REQUEST['id'], $_REQUEST['uid'], $_REQUEST['oid'], $_REQUEST['new'], $_REQUEST['total'], $_REQUEST['sig']);

// This script will output a status code of 1 (Success) or 0 (Try sending again later).

?>