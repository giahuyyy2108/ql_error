-- Bổ sung diện mặc định vào bảng liên kết; không xóa diện thứ hai đã được gán.
INSERT IGNORE INTO `nhom_BHYT_dien` (`ma_bhyt`, `dien`)
SELECT `id`, CASE
    WHEN `id` IN ('CC', 'TE') THEN 1
    WHEN `id` IN ('CK', 'CB', 'KC', 'HN', 'DT', 'DK', 'XD', 'BT', 'TS') THEN 2
    WHEN `id` IN ('HT', 'TC', 'CN') THEN 3
    WHEN `id` IN (
        'DN', 'HX', 'CH', 'NN', 'TK', 'HC', 'XK', 'TB', 'NO', 'CT', 'XB', 'TN',
        'CS', 'XN', 'MS', 'HD', 'TQ', 'TA', 'TY', 'HG', 'LS', 'PV', 'HS', 'SV', 'GB', 'GD'
    ) THEN 4
    WHEN `id` IN ('QN', 'CA', 'CY') THEN 5
END
FROM `nhom_BHYT`
WHERE `id` IN (
    'CC', 'TE', 'CK', 'CB', 'KC', 'HN', 'DT', 'DK', 'XD', 'BT', 'TS', 'HT', 'TC', 'CN',
    'DN', 'HX', 'CH', 'NN', 'TK', 'HC', 'XK', 'TB', 'NO', 'CT', 'XB', 'TN', 'CS', 'XN',
    'MS', 'HD', 'TQ', 'TA', 'TY', 'HG', 'LS', 'PV', 'HS', 'SV', 'GB', 'GD', 'QN', 'CA', 'CY'
);
