<?php
// HTTP
define('HTTP_SERVER', 'http://localhost/matsuri/admin/');
define('HTTP_CATALOG', 'http://localhost/matsuri/');

// HTTPS
define('HTTPS_SERVER', 'http://localhost/matsuri/admin/');
define('HTTPS_CATALOG', 'http://localhost/matsuri/');

// DIR
define('DIR_APPLICATION', '/Applications/XAMPP/xamppfiles/htdocs/matsuri/admin/');
define('DIR_SYSTEM', '/Applications/XAMPP/xamppfiles/htdocs/matsuri/system/');
define('DIR_IMAGE', '/Applications/XAMPP/xamppfiles/htdocs/matsuri/image/');
define('DIR_STORAGE', DIR_SYSTEM . 'storage/');
define('DIR_CATALOG', '/Applications/XAMPP/xamppfiles/htdocs/matsuri/catalog/');
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
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'matsuri');
define('DB_PORT', '3306');
define('DB_PREFIX', 'ac_');

// OpenCart API
define('OPENCART_SERVER', '');
