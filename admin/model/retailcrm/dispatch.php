<?php

// Ensure $cli_action is set
if (!isset($cli_action)) {
    echo 'ERROR: $cli_action must be set in calling script.';
    $log->write('ERROR: $cli_action must be set in calling script.');
    http_response_code(400);
    exit;
}

// Version
define('VERSION', '1.5.6');

// Configuration (note we're using the admin config)
require_once(realpath(dirname(__FILE__)) . '/../../config.php');

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
require_once(DIR_SYSTEM . 'library/currency.php');
require_once(DIR_SYSTEM . 'library/user.php');
require_once(DIR_SYSTEM . 'library/weight.php');
require_once(DIR_SYSTEM . 'library/length.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
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
        $config->set($setting['key'], unserialize($setting['value']));
    }
}

// Url
$url = new Url(HTTP_SERVER, HTTPS_SERVER);
$registry->set('url', $url);

// Log
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

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
$registry->set('response', $response);
$cache = new Cache();
$registry->set('cache', $cache);
$session = new Session();
$registry->set('session', $session);
$languages = array();

$query = $db->query("SELECT * FROM " . DB_PREFIX . "language");
foreach ($query->rows as $result) {
    $languages[$result['code']] = $result;
}
$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);
$language = new Language($languages[$config->get('config_admin_language')]['directory']);
$language->load($languages[$config->get('config_admin_language')]['filename']);
$registry->set('language', $language);

$document = new Document();
$registry->set('document', $document);

$registry->set('currency', new Currency($registry));
$registry->set('weight', new Weight($registry));
$registry->set('length', new Length($registry));
$registry->set('user', new User($registry));

$controller = new Front($registry);
$action = new Action($cli_action);
$controller->dispatch($action, new Action('error/not_found'));

// Output
$response->output();
