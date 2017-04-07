<?php
$cli_action = 'extension/module/retailcrm/export';
require_once('dispatch.php');
$file = fopen(DIR_SYSTEM . '/cron/export_done.txt', "x");