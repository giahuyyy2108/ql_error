CREATE TABLE IF NOT EXISTS `rule` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `value_hint` VARCHAR(255) DEFAULT NULL,
    `requires_value` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rule_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rule` (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`) VALUES
('REQUIRED',   'Bắt buộc',              'Trường phải tồn tại và không được để trống.',              NULL,                 0, 1),
('LENGTH',     'Độ dài chính xác',       'Độ dài của giá trị phải bằng số ký tự cấu hình.',           'Ví dụ: 15',          1, 1),
('MIN_LENGTH', 'Độ dài tối thiểu',       'Độ dài của giá trị không được nhỏ hơn cấu hình.',           'Ví dụ: 3',           1, 1),
('MAX_LENGTH', 'Độ dài tối đa',          'Độ dài của giá trị không được lớn hơn cấu hình.',           'Ví dụ: 255',         1, 1),
('REGEX',      'Biểu thức chính quy',    'Giá trị phải khớp với biểu thức chính quy PHP.',            'Ví dụ: /^\\d{8}$/', 1, 1),
('NUMERIC',    'Kiểu số',                'Giá trị phải là một số hợp lệ.',                             NULL,                 0, 1),
('MIN',        'Giá trị tối thiểu',      'Giá trị số không được nhỏ hơn cấu hình.',                   'Ví dụ: 0',           1, 1),
('MAX',        'Giá trị tối đa',         'Giá trị số không được lớn hơn cấu hình.',                   'Ví dụ: 100',         1, 1),
('RANGE',      'Khoảng giá trị',         'Giá trị số phải nằm trong khoảng min,max.',                 'Ví dụ: 0,100',       1, 1),
('IN',         'Thuộc danh sách',        'Giá trị phải thuộc danh sách phân cách bằng dấu phẩy.',     'Ví dụ: 1,2,3',       1, 1),
('DATE',       'Ngày tháng',             'Giá trị phải đúng định dạng ngày tháng PHP đã cấu hình.',   'Ví dụ: Ymd',         1, 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

