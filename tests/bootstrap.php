<?php declare(strict_types=1);

use Dotenv\Dotenv;
use Careminate\Http\Responses\Response;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// Load application macros if they exist
$macrosPath = BASE_PATH . '/bootstrap/macros.php';
if (file_exists($macrosPath)) {
    require $macrosPath;
} else {
    // Define a default maintenance macro for tests
    Response::macro('maintenance', function () {
        return Response::json(['success' => false, 'message' => 'Service under maintenance.'], 503);
    });
}