<?php

require_once('/var/web/---/app/api-v3/init.php');

// pull local config paths
$config = parse_ini_file('config.ini', true);

// pull actual application settings
$config = array_merge_recursive($init, 
                                $config);

if ( $config['dev']['debug'] ) {
    ini_set('display_errors', true);
    error_reporting(E_ALL ^ E_NOTICE);
}

unset($init);

?>