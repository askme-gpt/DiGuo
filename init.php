<?php
define("D_S", DIRECTORY_SEPARATOR);
$root = dirname(dirname(__FILE__)) . D_S;

define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
switch (ENVIRONMENT) {
case 'development':
	error_reporting(-1);
	ini_set('display_errors', 1);
	break;

case 'testing':
case 'production':
	ini_set('display_errors', 0);
	if (version_compare(PHP_VERSION, '5.3', '>=')) {
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
	} else {
		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
	}
	break;

default:
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'The application environment is not set correctly.';
	exit(1); // EXIT_ERROR
}
// $system_path = $root . 'system/';

// define('BASEPATH', $system_path);
$application_folder = $root . 'application';

define('APPPATH', $application_folder . D_S);

$view_folder = '';
// The path to the "views" directory
if (!isset($view_folder[0]) && is_dir(APPPATH . 'views' . DIRECTORY_SEPARATOR)) {
	$view_folder = APPPATH . 'views';

} elseif (is_dir($view_folder)) {
	if (($_temp = realpath($view_folder)) !== FALSE) {
		$view_folder = $_temp;
	} else {
		$view_folder = strtr(
			rtrim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
		);

	}
} elseif (is_dir(APPPATH . $view_folder . DIRECTORY_SEPARATOR)) {
	$view_folder = APPPATH . strtr(
		trim($view_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	);
} else {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: ' . SELF;
	exit(3); // EXIT_CONFIG
}

define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);
