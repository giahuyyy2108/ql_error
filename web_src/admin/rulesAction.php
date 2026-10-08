<?php

require_once 'web_src/bean/XmlValidationRulePeer.php';
require_once 'web_src/common/TableLookupService.php';
require_once 'web_src/bean/LogPeer.php';

class rulesAction
{
    public static $listRole = 'rules,save,update,delete';

    private $request;
    private $rulePeer;

    public function __construct()
    {
        $this->request = new Request();
        $this->rulePeer = new XmlValidationRulePeer();
        $this->request->setTitle('Quản lý XML Validation Rules');
    }

    public function index()
    {
        if (empty($_SESSION['rules_csrf'])) {
            $_SESSION['rules_csrf'] = bin2hex(random_bytes(32));
        }
        $this->request->setAttribute('rulesCsrf', $_SESSION['rules_csrf']);
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/rules.js?' . _DEFAULT_VERSION_JS_CSS_
            . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/js/rules.js') . '"></script>'
        );
        $this->request->setModel('www/rules/index.php');
        return true;
    }

    public function getData()
    {
        try {
            return $this->json(array('data' => $this->rulePeer->getList()));
        } catch (RuntimeException $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    public function getRuleTypes()
    {
        try {
            return $this->json(array('success' => true, 'data' => $this->rulePeer->getSupportedRuleTypes()));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'data' => array(), 'message' => $exception->getMessage()));
        }
    }

    public function save()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }

        try {
            $rule = $this->readRule();
            $id = $this->rulePeer->insert($rule);
            $rule['id'] = $id;
            $this->writeRuleLog('Thêm rule', $rule, false);
            return $this->json(array('success' => true, 'id' => $id, 'message' => 'Thêm rule thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function update()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã rule không hợp lệ.'));
        }

        try {
            $oldRule = $this->rulePeer->getById($id);
            if ($oldRule === false) {
                return $this->json(array('success' => false, 'message' => 'Rule không tồn tại.'));
            }
            $newRule = $this->readRule();
            $this->rulePeer->update($id, $newRule);
            $newRule['id'] = $id;
            $this->writeRuleLog('Cập nhật rule', $newRule, $oldRule);
            return $this->json(array('success' => true, 'message' => 'Cập nhật rule thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function delete()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã rule không hợp lệ.'));
        }

        try {
            $oldRule = $this->rulePeer->getById($id);
            if ($oldRule === false) {
                return $this->json(array('success' => false, 'message' => 'Rule không tồn tại.'));
            }
            if (!$this->rulePeer->delete($id)) {
                return $this->json(array('success' => false, 'message' => 'Rule không tồn tại.'));
            }
            $this->writeRuleLog('Xóa rule', false, $oldRule);
            return $this->json(array('success' => true, 'message' => 'Xóa rule thành công.'));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    public function updateActive()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        $isAdmin = isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1;
        if (!$isAdmin && !$this->request->checkRole('rules.update')) {
            return $this->json(array('success' => false, 'message' => 'Bạn không có quyền sửa rule.'));
        }

        $id = (int) $this->request->getParameter('id', false);
        $isActive = $this->request->getParameter('is_active', false);
        if ($id <= 0 || !in_array((string) $isActive, array('0', '1'), true)) {
            return $this->json(array('success' => false, 'message' => 'Trạng thái rule không hợp lệ.'));
        }

        try {
            $oldRule = $this->rulePeer->getById($id);
            if ($oldRule === false) {
                return $this->json(array('success' => false, 'message' => 'Rule không tồn tại.'));
            }
            if (!$this->rulePeer->updateActive($id, (int) $isActive)) {
                return $this->json(array('success' => false, 'message' => 'Rule không tồn tại.'));
            }
            $newRule = $oldRule;
            $newRule['is_active'] = (int) $isActive;
            $this->writeRuleLog((int) $isActive === 1 ? 'Bật rule' : 'Tắt rule', $newRule, $oldRule);
            return $this->json(array(
                'success' => true,
                'is_active' => (int) $isActive,
                'message' => (int) $isActive === 1 ? 'Đã bật rule.' : 'Đã tắt rule.'
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    private function readRule()
    {
        $rule = array(
            'file_type' => strtoupper(trim($this->request->getParameter('file_type', false))),
            'field_name' => trim($this->request->getParameter('field_name', false)),
            'display_name' => trim($this->request->getParameter('display_name', false)),
            'rule_type' => strtoupper(trim($this->request->getParameter('rule_type', false))),
            'rule_value' => trim($this->request->getParameter('rule_value', false)),
            'error_message' => trim($this->request->getParameter('error_message', false)),
            'is_active' => $this->request->getParameter('is_active', false) === '1' ? 1 : 0
        );

        if ($rule['file_type'] === '' || $rule['field_name'] === '' || $rule['display_name'] === ''
            || $rule['rule_type'] === '' || $rule['error_message'] === '') {
            throw new RuntimeException('Vui lòng nhập đầy đủ các trường bắt buộc.');
        }
        if (strlen($rule['file_type']) > 10 || strlen($rule['field_name']) > 100
            || strlen($rule['display_name']) > 255 || strlen($rule['rule_type']) > 50) {
            throw new RuntimeException('Dữ liệu vượt quá độ dài cho phép.');
        }
        if (!preg_match('/^XML[0-9]+$/', $rule['file_type'])) {
            throw new RuntimeException('Loại file phải có dạng XML1, XML2, XML3...');
        }
        $supportedRule = $this->rulePeer->getSupportedRuleType($rule['rule_type']);
        if ($supportedRule === false) {
            throw new RuntimeException('Loại rule không được hỗ trợ hoặc đã ngừng hoạt động.');
        }
        if ($supportedRule['requires_value'] === 1 && $rule['rule_value'] === '') {
            throw new RuntimeException('Loại rule ' . $rule['rule_type'] . ' bắt buộc phải có giá trị cấu hình.');
        }
        if (in_array($rule['rule_type'], array(
            'FIELD_COMPARE', 'TABLE_EXISTS', 'API', 'SUBSTRING', 'CCCD_GENDER_CENTURY'
        ), true)) {
            $config = json_decode($rule['rule_value'], true);
            if (!is_array($config)) {
                throw new RuntimeException('Giá trị của rule ' . $rule['rule_type'] . ' phải là JSON hợp lệ.');
            }
            if ($rule['rule_type'] === 'FIELD_COMPARE'
                && (empty($config['other_field']) || empty($config['operator']))) {
                throw new RuntimeException('FIELD_COMPARE cần có other_field và operator.');
            }
            if ($rule['rule_type'] === 'TABLE_EXISTS') {
                TableLookupService::normalizeConfig($config);
                if (array_key_exists('remove_suffix', $config)
                    && (!is_scalar($config['remove_suffix']) || (string) $config['remove_suffix'] === '')) {
                    throw new RuntimeException('remove_suffix của TABLE_EXISTS phải là chuỗi khác rỗng.');
                }
            }
            if ($rule['rule_type'] === 'API'
                && (empty($config['api_config_id']) || !isset($config['request_mapping']))) {
                throw new RuntimeException('API rule cần có api_config_id và request_mapping.');
            }
            if ($rule['rule_type'] === 'API' && !is_array($config['request_mapping'])) {
                throw new RuntimeException('request_mapping của API phải là JSON object.');
            }
            if ($rule['rule_type'] === 'API') {
                foreach ($config['request_mapping'] as $parameter => $mapping) {
                    if (is_array($mapping) && isset($mapping['in'])
                        && !in_array(strtolower($mapping['in']), array('path', 'query', 'body'), true)) {
                        throw new RuntimeException('Vị trí tham số ' . $parameter . ' chỉ nhận path, query hoặc body.');
                    }
                    if (is_array($mapping) && !isset($mapping['field']) && !array_key_exists('value', $mapping)) {
                        throw new RuntimeException('Tham số ' . $parameter . ' cần có field hoặc value.');
                    }
                }
            }
            if ($rule['rule_type'] === 'SUBSTRING') {
                $operators = array('=', '==', '!=', '<>', 'IN', 'NOT_IN', 'REGEX');
                if (!array_key_exists('start', $config) || !array_key_exists('length', $config)
                    || !array_key_exists('expected', $config) || empty($config['operator'])) {
                    throw new RuntimeException('SUBSTRING cần có start, length, operator và expected.');
                }
                if ((int) $config['length'] <= 0) {
                    throw new RuntimeException('Độ dài SUBSTRING phải lớn hơn 0.');
                }
                if (!in_array(strtoupper($config['operator']), $operators, true)) {
                    throw new RuntimeException('Toán tử SUBSTRING không được hỗ trợ.');
                }
            }
            if ($rule['rule_type'] === 'CCCD_GENDER_CENTURY') {
                if (empty($config['birth_field']) || empty($config['gender_field'])) {
                    throw new RuntimeException('CCCD_GENDER_CENTURY cần có birth_field và gender_field.');
                }
                foreach (array('male_values', 'female_values') as $valueKey) {
                    if (isset($config[$valueKey]) && !is_array($config[$valueKey])) {
                        throw new RuntimeException($valueKey . ' phải là một mảng JSON.');
                    }
                }
            }
        }

        return $rule;
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['rules_csrf']) && is_string($token)
            && hash_equals($_SESSION['rules_csrf'], $token);
    }

    private function json($data)
    {
        return $this->request->json_response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function writeRuleLog($action, $newRule, $oldRule)
    {
        $logPeer = new LogPeer();
        $logPeer->ghiLogObj($newRule, $oldRule, $action);
    }
}
