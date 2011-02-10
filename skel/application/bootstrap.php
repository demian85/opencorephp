<?php

error_reporting(-1);

// Debug mode: shows detailed information about errors and exceptions when posible.
define('DEBUG_MODE', 1);

// Production mode: if enabled, no error or exception information is sent to the browser.
define('IN_PRODUCTION', 0);

// Absolute path to application directory
define('APPLICATION_DIR', dirname(__FILE__));

// Absolute path to framework root directory
define('FRAMEWORK_DIR', realpath(APPLICATION_DIR . "/../../opencorephp/branches/1.6"));

// Add framework root directory to the include path
set_include_path(FRAMEWORK_DIR . PATH_SEPARATOR . get_include_path());

// Include core functions
require_once 'functions/import.php';
require_once 'functions/l.php';
require_once 'functions/url.php';
require_once 'functions/src.php';
require_once 'functions/fb.php';

// Import core classes
import('core.*');

?>