<?php

$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;

// Ensure $cli_action is set
if (!isset($cli_action)) {
    echo 'ERROR: $cli_action must be set in calling script.';
    http_response_code(400);
    exit;
}

// Version
$version = '2.3.0';
$indexFile = file_get_contents(realpath(dirname(__FILE__)) . '/../../index.php');
preg_match("/define\([\s]*['\"]VERSION['\"][\s]*,[\s]*['\"](.*)['\"][\s]*\)[\s]*;/mi", $indexFile, $versionMatches);

if (isset($versionMatches[1])) {
    $version = $versionMatches[1];
}

define('VERSION', $version);

// Configuration (note we're using the admin config)
require_once(realpath(dirname(__FILE__)) . '/../../admin/config.php');

// Configuration check
if (!defined('DIR_APPLICATION')) {
    echo "ERROR: cli $cli_action call missing configuration.";
    $log->write("ERROR: cli $cli_action call missing configuration.");
    http_response_code(400);
    exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Application Classes
require_once(DIR_SYSTEM . 'library/cart/currency.php');
require_once(DIR_SYSTEM . 'library/cart/user.php');
require_once(DIR_SYSTEM . 'library/cart/weight.php');
require_once(DIR_SYSTEM . 'library/cart/length.php');



// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();

if (version_compare(VERSION, '3.0', '>=')) {
    $config->load('default');
    $config->load('catalog');
}

$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");

foreach ($query->rows as $setting) {
    if (!$setting['serialized']) {
        $config->set($setting['key'], $setting['value']);
    } else {
        $config->set($setting['key'], json_decode($setting['value']), true);
    }
}

// Url
$url = new Url(HTTP_SERVER, HTTPS_SERVER);
$registry->set('url', $url);

// Log
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);


// Event
$event = new Event($registry);
$registry->set('event', $event);


function error_handler($errno, $errstr, $errfile, $errline) {
    global $log, $config;

    switch ($errno) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $error = 'Notice';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error = 'Warning';
            break;
        case E_ERROR:
        case E_USER_ERROR:
            $error = 'Fatal Error';
            break;
        default:
            $error = 'Unknown';
            break;
    }

    if ($config->get('config_error_display')) {
        echo "\n".'PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline."\n";
    }

    if ($config->get('config_error_log')) {
        $log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
    }

    return true;
}

set_error_handler('error_handler');
$request = new Request();
$registry->set('request', $request);
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');

$cache = new Cache('file');
$registry->set('cache', $cache);

$registry->set('response', $response);

if (version_compare(VERSION, '3.0', '<')) {
    $session = new Session();
} else {
    $session = new Session($config->get('session_engine'), $registry);
}

$registry->set('session', $session);

$languages = array();
$query = $db->query("SELECT * FROM " . DB_PREFIX . "language");
foreach ($query->rows as $result) {
    $languages[$result['code']] = $result;
}

$adminLanguageCode = $config->get('config_admin_language');
$config->set('config_language_id', $languages[$adminLanguageCode]['language_id']);

if (version_compare(VERSION, '2.3', '<')) {
    $language = new Language($languages[$adminLanguageCode]['directory']);
} else {
    $language = new Language($adminLanguageCode);
}

if(isset($languages[$adminLanguageCode]['filename'])) {
    $language->load($languages[$adminLanguageCode]['filename']);
} else {
    $language->load($languages[$adminLanguageCode]['directory']);
}
$registry->set('language', $language);

$document = new Document();
$registry->set('document', $document);


$registry->set('currency', new Cart\Currency($registry));
$registry->set('weight', new Cart\Weight($registry));
$registry->set('length', new Cart\Length($registry));
$registry->set('user', new Cart\User($registry));

if (version_compare(VERSION, '3.0', '<')) {
    $controller = new Front($registry);
} else {
    $controller = new Router($registry);
}

$action = new Action($cli_action);
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();
