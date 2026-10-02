<?php
define('_DATABASE_HOST_', 'localhost');
//define('_DATABASE_NAME_','mauadmin');
define('_DATABASE_NAME_', 'ql_error');
define('_DATABASE_USERNAME_', 'root');
//define('_DATABASE_PASSWORD_','Admin123');
define('_DATABASE_PASSWORD_', '');
define('_DATABASE_CONNECT_TIMEOUT_', '3600');
define('_DATABASE_READ_TIMEOUT_', '3600');

$db_host = _DATABASE_HOST_;        //Database host
$db_name = _DATABASE_NAME_;        //Database name
$db_username = _DATABASE_USERNAME_;        //Database username
$db_password = _DATABASE_PASSWORD_;        //Database password

// default ngon ngu
define('_LANGUAGE_SYS_', '1');
define('_DIR_SYS_', 'vn');
// default cac trang hien thi
define('_ITEMS_PER_PAGE_SYS', 10);
define('_ITEMS_PER_PAGE_ADMIN_', 50);
// File backup toi da 25MB
define('_BACKUP_SIZE_', '26214400');

define('_FILE_HANDLE_', './web_src/admin/');
define('_FILE_UPLOAD_', './hsct_upload/');
define('_FILE_IMPORT_', './import_upload/');
define('_DEFAULT_HANDLE_', 'dashboard');
define('_CLASS_HANDLE_', 'Action');

// Tự động nhận diện giao thức (http hoặc https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";

// Tự động lấy tên miền hiện tại (localhost hoặc ://trycloudflare.com)
$domain = $_SERVER['HTTP_HOST'];

// Cấu hình URL mặc định động
define('_DEFAULT_URL_', $protocol . $domain . '/ql_error/');


// define('_DEFAULT_URL_', 'http://192.168.31.2:80/vpdt/');
// define('_DEFAULT_URL_', 'http://localhost/vpdt/');
define('_DEFAULT_LOGO_', _DEFAULT_URL_ . 'images/favicon.png');
define('_DEFAULT_TITLE_', 'Quản lý lỗi - BỆNH VIỆN THỐNG NHẤT');
define('_DEFAULT_LOGIN_', 'login');
define('_DEFAULT_LIBS_', _DEFAULT_URL_ . 'libs/');
// san pham
define('_DEFAULT_TON_MIN_', '10');
define('_DEFAULT_TON_MAX_', '100');

define('_DEFAULT_MAX_DAY_', '25');
define('_DEFAULT_MAX_LIABILITY_', '5000000');

define('_DEFAULT_VERSION_JS_CSS_', '1');
?>