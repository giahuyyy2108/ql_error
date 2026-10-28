<?php

// Database
define('_DATABASE_HOST_', 'localhost');
define('_DATABASE_NAME_', 'ql_error');
define('_DATABASE_USERNAME_', 'root');
define('_DATABASE_PASSWORD_', '');
define('_DATABASE_CONNECT_TIMEOUT_', '3600');
define('_DATABASE_READ_TIMEOUT_', '3600');

$db_host = _DATABASE_HOST_;
$db_name = _DATABASE_NAME_;
$db_username = _DATABASE_USERNAME_;
$db_password = _DATABASE_PASSWORD_;

// Cấu hình hiển thị
define('_LANGUAGE_SYS_', '1');
define('_DIR_SYS_', 'vn');
define('_ITEMS_PER_PAGE_SYS', 10);
define('_ITEMS_PER_PAGE_ADMIN_', 50);

// File backup tối đa 25 MB
define('_BACKUP_SIZE_', '26214400');

// XML watcher. Đường dẫn tương đối được tính từ thư mục gốc của project.
define('_XML_WATCHER_PHP_PATH_', 'C:/xampp/php/php.exe');
define('_XML_WATCHER_SCRIPT_PATH_', 'scripts/watch_xml.php');
define('_XML_WATCHER_STORAGE_PATH_', 'storage/xml');
define('_XML_WATCHER_RUNTIME_PATH_', 'storage/watcher');

// Controller / upload paths
define('_FILE_HANDLE_', './web_src/admin/');
define('_FILE_UPLOAD_', './hsct_upload/');
define('_FILE_IMPORT_', './import_upload/');
define('_DEFAULT_HANDLE_', 'dashboard');
define('_CLASS_HANDLE_', 'Action');

// URL hệ thống
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https');

$protocol = $isHttps ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('_DEFAULT_URL_', $protocol . $domain . '/ql_error/');
define('_DEFAULT_VERSION_', 'v1.3');

define('_DEFAULT_LOGO_', _DEFAULT_URL_ . 'images/favicon.png');
define('_DEFAULT_TITLE_', 'Quản lý lỗi - BỆNH VIỆN THỐNG NHẤT ' . _DEFAULT_VERSION_);
define('_DEFAULT_LOGIN_', 'login');
define('_DEFAULT_LIBS_', _DEFAULT_URL_ . 'libs/');

// Giá trị mặc định
define('_DEFAULT_TON_MIN_', '10');
define('_DEFAULT_TON_MAX_', '100');
define('_DEFAULT_MAX_DAY_', '25');
define('_DEFAULT_MAX_LIABILITY_', '5000000');
define('_DEFAULT_VERSION_JS_CSS_', '44');
