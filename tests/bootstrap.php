<?php declare(strict_types=1);

// Define the project base path for all tests
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load Composer autoload
require BASE_PATH . '/vendor/autoload.php';

// Load helpers
require BASE_PATH . '/Careminate/Support/helpers.php';