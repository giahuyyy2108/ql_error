UPDATE `nhom_BHYT`
SET `dien` = CASE
    WHEN `id` IN ('CC', 'TE') THEN '1'
    WHEN `id` IN ('CK', 'CB', 'KC', 'HN', 'DT', 'DK', 'XD', 'BT', 'TS') THEN '2'
    WHEN `id` IN ('HT', 'TC', 'CN') THEN '3'
    WHEN `id` IN (
        'DN', 'HX', 'CH', 'NN', 'TK', 'HC', 'XK', 'TB', 'NO', 'CT', 'XB', 'TN',
        'CS', 'XN', 'MS', 'HD', 'TQ', 'TA', 'TY', 'HG', 'LS', 'PV', 'HS', 'SV', 'GB', 'GD'
    ) THEN '4'
    WHEN `id` IN ('QN', 'CA', 'CY') THEN '5'
    ELSE `dien`
END;

ALTER TABLE `nhom_BHYT`
    MODIFY COLUMN `dien` TINYINT UNSIGNED NOT NULL;

