<?php

require_once 'web_src/bean/FilePeer.php';
require_once 'web_src/common/XmlFileDecoder.php';
require_once 'web_src/common/XmlRulesValidator.php';
require_once 'web_src/bean/XmlValidationRulePeer.php';
require_once 'web_src/bean/ApiValidationConfigPeer.php';
require_once 'web_src/common/ApiValidationService.php';

class fileAction
{
    private $request;
    private $filePeer;
    private $maxFileSize = 5242880; // 5 MB

    public function __construct()
    {
        $this->request = new Request();
        $this->filePeer = new FilePeer();
    }

    public function index()
    {
        if (empty($_SESSION['xml_file_csrf'])) {
            $_SESSION['xml_file_csrf'] = bin2hex(random_bytes(32));
        }

        $this->request->setAttribute('xmlFileCsrf', $_SESSION['xml_file_csrf']);
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/file.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/file/index.php');
        return true;
    }

    public function getData()
    {
        try {
            return $this->json(array('data' => $this->filePeer->getList()));
        } catch (RuntimeException $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    public function upload()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        if (!isset($_FILES['xmlFile']) || !is_array($_FILES['xmlFile'])) {
            return $this->json(array('success' => false, 'message' => 'Vui lòng chọn file XML.'));
        }

        $upload = $_FILES['xmlFile'];
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            return $this->json(array('success' => false, 'message' => $this->uploadErrorMessage($upload['error'])));
        }
        if ($upload['size'] <= 0 || $upload['size'] > $this->maxFileSize) {
            return $this->json(array('success' => false, 'message' => 'File XML phải có dung lượng từ 1 byte đến 5 MB.'));
        }

        $fileName = $this->safeFileName($upload['name']);
        if ($fileName === false) {
            return $this->json(array('success' => false, 'message' => 'Tên file không hợp lệ hoặc file không có đuôi .xml.'));
        }

        $content = file_get_contents($upload['tmp_name']);
        $xmlError = $this->validateXml($content);
        if ($xmlError !== '') {
            return $this->json(array('success' => false, 'message' => 'Nội dung XML không hợp lệ: ' . $xmlError));
        }

        try {
            $decodedContent = XmlFileDecoder::decodeDanhSachHoSo($content);
            $maLk = XmlFileDecoder::extractMaLk($decodedContent);
            if ($this->filePeer->maLkExists($maLk)) {
                return $this->json(array(
                    'success' => false,
                    'message' => 'Hồ sơ có MA_LK ' . $maLk . ' đã tồn tại.'
                ));
            }
            $this->filePeer->insert($fileName, $maLk, $upload['size'], $content, $decodedContent);
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array('success' => true, 'message' => 'Đã lưu file XML vào cơ sở dữ liệu.'));
    }

    public function getContent()
    {
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã file không hợp lệ.'));
        }

        try {
            $file = $this->filePeer->getById($id);
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
        if ($file === false) {
            return $this->json(array('success' => false, 'message' => 'File không tồn tại.'));
        }

        $decodedContent = json_decode($file['decode'], true);
        if (!is_array($decodedContent)) {
            return $this->json(array('success' => false, 'message' => 'File chưa có nội dung giải mã hợp lệ.'));
        }

        try {
            $rulePeer = new XmlValidationRulePeer();
            $apiService = new ApiValidationService(new ApiValidationConfigPeer());
            $validation = XmlRulesValidator::validate(
                $decodedContent,
                $rulePeer->getRulesForValidation(),
                $apiService
            );
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array(
            'success' => true,
            'name' => $file['ten'],
            'decoded' => $decodedContent,
            'validation' => $validation
        ));
    }

    public function delete()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) {
            return $this->json(array('success' => false, 'message' => 'Mã file không hợp lệ.'));
        }

        try {
            if (!$this->filePeer->delete($id)) {
                return $this->json(array('success' => false, 'message' => 'File không tồn tại.'));
            }
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }

        return $this->json(array('success' => true, 'message' => 'Xóa file thành công.'));
    }

    private function safeFileName($fileName)
    {
        $fileName = trim((string) $fileName);
        if ($fileName === '' || $fileName !== basename($fileName) || strlen($fileName) > 180) {
            return false;
        }
        if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _.()-]*\.xml$/iu', $fileName)) {
            return false;
        }
        return $fileName;
    }

    private function validateXml($content)
    {
        if (!is_string($content) || trim($content) === '') {
            return 'File rỗng.';
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new DOMDocument();
        $valid = $document->loadXML($content, LIBXML_NONET);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($valid) {
            return '';
        }

        if (!empty($errors)) {
            $error = $errors[0];
            return trim($error->message) . ' (dòng ' . $error->line . ').';
        }
        return 'Không thể phân tích nội dung XML.';
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['xml_file_csrf']) && is_string($token)
            && hash_equals($_SESSION['xml_file_csrf'], $token);
    }

    private function uploadErrorMessage($code)
    {
        if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
            return 'File vượt quá dung lượng cho phép.';
        }
        if ($code === UPLOAD_ERR_NO_FILE) {
            return 'Vui lòng chọn file XML.';
        }
        return 'Tải file thất bại (mã lỗi ' . (int) $code . ').';
    }

    private function json($data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
