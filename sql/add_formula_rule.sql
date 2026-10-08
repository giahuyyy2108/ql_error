INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
SELECT
    'FORMULA',
    'Công thức nhiều biến',
    'Tính công thức an toàn trên các trường cùng một dòng và so sánh với trường kết quả.',
    '{"result_field":"T_BHTT","expression":{"operation":"MULTIPLY","values":[{"operation":"SUBTRACT","values":[{"field":"THANH_TIEN"},{"field":"T_BNTT"},{"field":"T_NGUONKHAC"}]},{"operation":"DIVIDE","values":[{"field":"MUC_HUONG"},100]},{"operation":"DIVIDE","values":[{"field":"TYLE_TT"},100]}]},"tolerance":0.01,"round":2}',
    1,
    1
WHERE NOT EXISTS (SELECT 1 FROM `rule` WHERE `code` = 'FORMULA');
