UPDATE `rule`
SET `description` = 'Gửi path parameters, query parameters hoặc body đến API; hỗ trợ cắt chuỗi trước khi gửi.',
    `value_hint` = '{"api_config_id":1,"request_mapping":{"id":{"in":"path","field":"MA_LK"},"type":{"in":"query","value":"HS"},"ma":{"in":"body","field":"MA_NOI_DEN"}},"expected":true}'
WHERE `code` = 'API';

