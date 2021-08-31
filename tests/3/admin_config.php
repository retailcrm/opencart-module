<?php

$rootDir = realpath(__DIR__ . '/../../www');
$rootUri = sprintf('%s:%s', getenv('SERVER_URL'), getenv('SERVER_PORT'));

define('HTTP_SERVER', $rootUri . '/admin/');
define('HTTP_CATALOG', $rootUri . '/');

// DIR
define('DIR_APPLICATION', $rootDir . '/admin/');
define('DIR_SYSTEM', $rootDir . '/system/');
define('DIR_IMAGE', $rootDir . '/image/');
define('DIR_CATALOG', $rootDir . '/catalog/');
define('DIR_STORAGE',  DIR_SYSTEM . 'storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', getenv('OC_DB_DRIVER'));
define('DB_HOSTNAME', getenv('OC_DB_HOSTNAME'));
define('DB_USERNAME', getenv('OC_DB_USERNAME'));
define('DB_PASSWORD', getenv('OC_DB_PASSWORD'));
define('DB_DATABASE', getenv('OC_DB_DATABASE'));
define('DB_PORT', getenv('OC_DB_DRIVER'));
define('DB_PREFIX', 'oc_');

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
