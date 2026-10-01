UPDATE `rule`
SET `description` = 'Gửi trường hoặc đoạn chuỗi đã cắt đến API và kiểm tra giá trị phản hồi.',
    `value_hint` = '{"api_config_id":1,"request_mapping":{"ma":{"field":"MA_LK","start":0,"length":2}},"operator":"=","expected":true,"on_error":"FAIL"}'
WHERE `code` = 'API';

