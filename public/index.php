<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

date_default_timezone_set('Europe/Paris');

$maintenanceFile = dirname(__DIR__).'/var/maintenance.flag';

$allowedIps = [
    '127.0.0.1',
    '::1',
    '86.204.146.186',
];

$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;

if (file_exists($maintenanceFile) && !in_array($clientIp, $allowedIps, true)) {
    http_response_code(503);
    require __DIR__.'/maintenance.html';
    exit;
}

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};