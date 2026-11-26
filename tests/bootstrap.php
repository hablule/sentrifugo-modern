<?php

define('BASE_PATH', realpath(dirname(__DIR__)));
defined('APPLICATION_PATH') || define('APPLICATION_PATH', BASE_PATH . '/application');
defined('APPLICATION_ENV')  || define('APPLICATION_ENV', 'development');

// Ensure library/ and models/ are on the include_path using Linux-safe '/'
set_include_path(implode(PATH_SEPARATOR, array(
    BASE_PATH . '/library',
    APPLICATION_PATH . '/modules/default/models',
    get_include_path(),
)));

// Load Composer autoloader (PHPUnit & any Composer packages)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Load Sentrifugo constant files if present (matching standard index.php order)
$constantFiles = array(
    BASE_PATH . '/public/constants.php',
    BASE_PATH . '/public/application_constants.php',
    BASE_PATH . '/public/db_constants.php',
    BASE_PATH . '/public/emptabconfigure.php',
    BASE_PATH . '/public/mail_settings_constants.php',
    BASE_PATH . '/public/site_constants.php',
    BASE_PATH . '/application/configs/constants.php',
);
foreach ($constantFiles as $constFile) {
    if (file_exists($constFile)) {
        require_once $constFile;
    }
}

// Boot Zend_Application & DB Adapter
require_once 'Zend/Application.php';
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// Bootstrap core autoloader and DB resource without executing HTTP routing
$application->getBootstrap()->bootstrap(array('db'));

// Register Default module resource autoloader so Default_Model_* classes resolve cleanly
new Zend_Application_Module_Autoloader(array(
    'namespace' => 'Default',
    'basePath'  => APPLICATION_PATH . '/modules/default',
));
