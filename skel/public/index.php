<?php

// Main core initialization
require_once(dirname(__FILE__) .  '/../application/bootstrap.php');

// Init config
Config::getInstance()->init();

// Custom setup
import('log.Logger', 'gui.*', 'db.DB', 'util.*');
ob_start();
session_start();
Lang::setupGettext();

// Initialize router
$router = Router::getInstance();
$router->dispatch();
?>
