<?php

if (php_sapi_name() != 'cli') {
	die('Must run from command line');
}

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
ini_set('html_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';

function test_notify(cli\Notify $notify, $cycle = 1000000, $sleep = null) {
	for ($i = 0; $i <= $cycle; $i++) {
		$notify->tick();
		if ($sleep) usleep($sleep);
	}
	$notify->finish();
}
