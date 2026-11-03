<?php

require_once __DIR__ . '/../common/Request.php';
require_once __DIR__ . '/../bean/FilePeer.php';
require_once __DIR__ . '/../bean/HeThongPeer.php';
require_once __DIR__ . '/../common/FileStorageService.php';
require_once __DIR__ . '/../common/XmlFileDecoder.php';
require_once __DIR__ . '/../common/UploadQueueService.php';

/** Trang Hồ sơ riêng, tất cả truy vấn phải thuộc id_khoa của tài khoản đăng nhập. */
class hosoAction
{
    public static $listRole = 'hoso';
    private $maxFileSize = 5242880; // 5 MB/file, giống trang File.

    private $request;
    private $filePeer;
    private $storage;

    public function __construct()
    {
        $this->request = new Request();
        $this->filePeer = new FilePeer();
    }

    public function index()
    {
        if (empty($_SESSION['hoso_csrf'])) {
            $_SESSION['hoso_csrf'] = bin2hex(random_bytes(32));
        }
        $this->request->setAttribute('hosoCsrf', $_SESSION['hoso_csrf']);
        $this->request->setAttribute('hosoDate', date('Y-m-d'));
        $this->request->setAttribute('css',
            '<link rel="stylesheet" href="' . _DEFAULT_URL_ . 'css/file.css?v=' . filemtime(dirname(__DIR__, 2) . '/css/file.css') . '">'
            . '<link rel="stylesheet" href="' . _DEFAULT_URL_ . 'css/hoso.css?v=' . filemtime(dirname(__DIR__, 2) . '/css/hoso.css') . '">'
        );
        $this->request->setAttribute('script',
            '<script src="' . _DEFAULT_URL_ . 'js/hoso-export.js?v=' . filemtime(dirname(__DIR__, 2) . '/js/hoso-export.js') . '"></script>'
            . '<script src="' . _DEFAULT_URL_ . 'js/hoso.js?v=' . filemtime(dirname(__DIR__, 2) . '/js/hoso.js') . '"></script>'
        );
        $this->request->setModel('www/hoso/index.php');
        return true;
    }

    public function getData()
    {
        try {
            $search = $this->request->getParameter('search', false);
            if (!is_string($search) || strlen($search) > 1000) {
                throw new RuntimeException('Từ khóa tìm kiếm không hợp lệ hoặc quá dài.');
            }
            // Khi tìm toàn khoa, không phụ thuộc vào giá trị ô chọn ngày.
            $date = trim($search) === '' ? $this->selectedDate() : date('Y-m-d');
            return $this->json(array('data' => $this->filePeer->getListByKhoa($date, $this->userKhoa(), $search)));
        } catch (Throwable $e) {
            return $this->json(array('data' => array(), 'error' => $e->getMessage()));
        }
    }

    /** Ngày: hồ sơ + chi tiết lỗi. Tháng: tần suất lỗi theo ngày, chỉ trong khoa đăng nhập. */
    public function getExportData()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }
        try {
            $date = $this->request->getParameter('date', false);
            $period = $this->request->getParameter('period', false);
            if (!is_string($date) || !is_string($period)) {
                throw new RuntimeException('Ngày hoặc tháng xuất Excel không hợp lệ.');
            }
            $khoa = $this->userKhoa();
            // Luôn xác thực ngày/tháng, kể cả khi trong khoa chưa có dữ liệu.
            if ($period !== 'day' && $period !== 'month') {
                throw new RuntimeException('Hình thức xuất Excel không hợp lệ.');
            }
            $errors = $this->filePeer->getHosoExportErrorsByKhoa($date, $khoa, $period);
            // Báo cáo tháng cần danh sách hồ sơ để tính số hồ sơ lỗi và chi phí theo từng ngày.
            $data = $this->filePeer->getListByKhoa($date, $khoa, '', $period);
            return $this->json(array('success' => true, 'data' => $data, 'errors' => $errors));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /** Nhận từng XML từ giao diện kéo thả; worker chung sẽ import vào DB. */
    public function upload()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }

        try {
            // Không nhận id_khoa từ trình duyệt, kể cả khi tài khoản là admin.
            $khoa = $this->userKhoa();
            if (!isset($_FILES['xmlFile']) || !is_array($_FILES['xmlFile'])) {
                throw new RuntimeException('Vui lòng chọn file XML.');
            }
            $file = $_FILES['xmlFile'];
            if (!isset($file['error']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
                $error = isset($file['error']) && is_numeric($file['error']) ? (int) $file['error'] : -1;
                throw new RuntimeException($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
                    ? 'File vượt quá dung lượng cho phép.' : 'Tải file thất bại (mã ' . $error . ').');
            }
            if (!isset($file['size']) || !is_numeric($file['size'])
                || (int) $file['size'] <= 0 || (int) $file['size'] > $this->maxFileSize) {
                throw new RuntimeException('File XML phải có dung lượng từ 1 byte đến 5 MB.');
            }
            $name = isset($file['name']) && is_string($file['name']) ? trim($file['name']) : '';
            if ($name === '' || $name !== basename($name) || strlen($name) > 180
                || !preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _.()-]*\.xml$/iu', $name)) {
                throw new RuntimeException('Tên file không hợp lệ hoặc file không có đuôi .xml.');
            }
            if (empty($file['tmp_name']) || !is_string($file['tmp_name'])
                || !is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('File tải lên không hợp lệ.');
            }

            // Importer xác định khoa theo MA_KHOA của XML1, không phải theo metadata
            // hàng đợi. Kiểm tra trước để không nạp nhầm hồ sơ sang khoa khác.
            $decoded = XmlFileDecoder::decodeDanhSachHoSoPathWithMetadata($file['tmp_name']);
            $xml1 = XmlFileDecoder::extractValuesFromFileType($decoded['content'], 'XML1', array('MA_KHOA'));
            $xmlKhoa = isset($xml1['MA_KHOA']) ? trim((string) $xml1['MA_KHOA']) : '';
            $rootKhoa = $xmlKhoa !== '' ? $this->filePeer->resolveRootKhoaCode($xmlKhoa) : null;
            if ($rootKhoa === null || (string) $rootKhoa !== $khoa) {
                throw new RuntimeException('Mã khoa trong XML1 không thuộc khoa được gán cho tài khoản.');
            }
            unset($decoded);

            $queuedName = (new UploadQueueService($this->filePeer, $this->storage()))->enqueue(
                $file['tmp_name'], $name, $khoa, trim((string) $_SESSION['sUserName'])
            );
            return $this->json(array(
                'success' => true, 'queued' => true, 'name' => $queuedName,
                'message' => 'Đã đưa file XML vào hàng đợi xử lý.'
            ));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /** Chỉ theo dõi các file đang ở hàng đợi thuộc khoa của tài khoản. */
    public function uploadProgress()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ.'));
        }
        try {
            $names = json_decode((string) $this->request->getParameter('files', false), true);
            if (!is_array($names) || array_values($names) !== $names || count($names) > 100) {
                throw new RuntimeException('Danh sách file không hợp lệ.');
            }
            $remaining = $this->filePeer->countQueuedUploadFilesForKhoa($names, $this->userKhoa());
            return $this->json(array('success' => true, 'remaining' => $remaining));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function getContent()
    {
        try {
            $file = $this->scopedFile();
            $validation = json_decode((string) $file['validation_result'], true);
            if (!is_array($validation)) $validation = array();

            // Chỉ lấy thông tin bệnh nhân đã lưu trong CSDL, không đọc hay trả nội dung XML.
            $stored = json_decode((string) ($file['thongtin_bn'] ?? ''), true);
            $patient = array();
            foreach (array('maBn', 'hoTen', 'maThe', 'maSoBHXH', 'ngaySinh', 'gioiTinh', 'diaChi') as $key) {
                if (is_array($stored) && isset($stored[$key]) && is_scalar($stored[$key])) {
                    $patient[$key] = trim((string) $stored[$key]);
                }
            }
            // Hỗ trợ hồ sơ cũ lưu mã thẻ BHYT theo tên trường XML/thống kê.
            if (empty($patient['maThe']) && is_array($stored)) {
                foreach (array('MA_THE_BHYT', 'ma_the_bhyt') as $key) {
                    if (isset($stored[$key]) && is_scalar($stored[$key]) && trim((string) $stored[$key]) !== '') {
                        $patient['maThe'] = trim((string) $stored[$key]);
                        break;
                    }
                }
            }
            // Các cột của hồ sơ là nguồn ưu tiên cho mã BN và họ tên.
            foreach (array('ma_bn' => 'maBn', 'ho_ten' => 'hoTen') as $column => $key) {
                if (isset($file[$column]) && trim((string) $file[$column]) !== '') {
                    $patient[$key] = trim((string) $file[$column]);
                }
            }

            // Dự phòng dữ liệu bệnh nhân và ngày điều trị từ kết quả kiểm tra XML1.
            $patientFieldMap = array('MA_THE_BHYT' => 'maThe', 'MA_SO_BHXH' => 'maSoBHXH',
                'NGAY_SINH' => 'ngaySinh', 'GIOI_TINH' => 'gioiTinh', 'DIA_CHI' => 'diaChi',
                'NGAY_VAO' => 'ngayVao', 'NGAY_RA' => 'ngayRa');
            foreach ($validation as $group) {
                if (!is_array($group) || strtoupper((string) ($group['file_type'] ?? '')) !== 'XML1') continue;
                foreach (array('valid_fields', 'errors') as $collection) {
                    if (!isset($group[$collection]) || !is_array($group[$collection])) continue;
                    foreach ($group[$collection] as $field) {
                        if (!is_array($field) || !isset($field['field_name'], $field['value']) || !is_scalar($field['value'])) continue;
                        $fieldName = (string) $field['field_name'];
                        $key = isset($patientFieldMap[$fieldName]) ? $patientFieldMap[$fieldName] : null;
                        if ($key !== null && empty($patient[$key])) $patient[$key] = trim((string) $field['value']);
                    }
                }
            }

            $khoaCode = trim((string) ($file['id_khoa'] ?? ''));
            $khoa = $khoaCode !== '' ? $this->filePeer->getKhoaByCode($khoaCode) : null;
            return $this->json(array(
                'success' => true,
                'name' => $file['ten'],
                'ma_bn' => $file['ma_bn'],
                'thong_tin_bn' => $patient,
                'khoa_quan_ly' => array('ma' => $khoaCode, 'ten' => $khoa !== null ? $khoa['ten'] : ''),
                'validation' => $validation
            ));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function download()
    {
        try {
            $file = $this->scopedFile();
            $path = $this->storage()->resolveStoredFilePath($file['file_path']);
        } catch (Throwable $e) {
            http_response_code(404);
            echo 'Không tìm thấy file hoặc bạn không có quyền truy cập.';
            return null;
        }

        $name = basename((string) $file['ten']);
        if ($name === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'xml') {
            $name = 'hoso-' . $file['id'] . '.xml';
        }
        $asciiName = preg_replace('/[^A-Za-z0-9_.()-]/', '_', $name);
        if ($asciiName === '') $asciiName = 'hoso-' . $file['id'] . '.xml';
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    public function delete()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }
        try {
            $id = (int) $this->request->getParameter('id', false);
            if ($id <= 0) throw new RuntimeException('Mã hồ sơ không hợp lệ.');
            $deleted = $this->deleteFile($id, $this->selectedDate(), $this->userKhoa());
            return $this->json(array('success' => $deleted, 'message' => $deleted ? 'Đã xóa hồ sơ.' : 'Hồ sơ đã thay đổi hoặc không thể xóa.'));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    /** Mỗi request xóa tối đa 100 hồ sơ để tránh timeout; client tiếp tục đến khi done. */
    public function deleteAll()
    {
        if (!$this->validCsrf()) {
            return $this->json(array('success' => false, 'message' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'));
        }
        $deleted = 0;
        try {
            $date = $this->selectedDate();
            $khoa = $this->userKhoa();
            $ids = $this->filePeer->getDeletableByKhoaDate($date, $khoa, 100);
            foreach ($ids as $id) {
                if ($this->deleteFile($id, $date, $khoa)) $deleted++;
            }
            $remaining = $this->filePeer->getDeletableByKhoaDate($date, $khoa, 1);
            return $this->json(array('success' => true, 'deleted' => $deleted, 'done' => !$remaining));
        } catch (Throwable $e) {
            return $this->json(array('success' => false, 'deleted' => $deleted, 'message' => $e->getMessage()));
        }
    }

    private function deleteFile($id, $date, $khoa)
    {
        $file = $this->filePeer->getByIdForKhoa($id, $khoa);
        if (!$file) throw new RuntimeException('Không tìm thấy hồ sơ thuộc khoa của bạn.');
        if ((int) $file['da_day_cong'] !== 0) {
            throw new RuntimeException('Hồ sơ đã đẩy cổng chỉ được xem, không thể xóa.');
        }
        if (substr((string) $file['update_at'], 0, 10) !== $date) {
            throw new RuntimeException('Hồ sơ không nằm trong ngày đã chọn.');
        }

        $move = null;
        try {
            $storage = $this->storage();
            $source = $storage->resolveStoredFilePath($file['file_path']);
            $outputPath = $this->filePeer->getKhoaOutputPath($khoa);
            $move = $storage->moveToDeleted($source, $outputPath);
            $deleted = $this->filePeer->deleteOneForKhoaDate($id, $date, $khoa);
            if (!$deleted) $this->restoreMove($move);
            return $deleted;
        } catch (Throwable $e) {
            if ($move !== null) $this->restoreMove($move);
            throw $e;
        }
    }

    private function restoreMove(array $move)
    {
        if (is_file($move['destination']) && !file_exists($move['source'])) {
            @rename($move['destination'], $move['source']);
        }
        if ($move['error_destination'] !== null && is_file($move['error_destination'])) {
            @rename($move['error_destination'], $move['error_source']);
        }
    }

    private function scopedFile()
    {
        $id = (int) $this->request->getParameter('id', false);
        if ($id <= 0) throw new RuntimeException('Mã hồ sơ không hợp lệ.');
        $file = $this->filePeer->getByIdForKhoa($id, $this->userKhoa());
        if ($file === false) throw new RuntimeException('Không tìm thấy hồ sơ thuộc khoa của bạn.');
        return $file;
    }

    private function userKhoa()
    {
        $username = isset($_SESSION['sUserName']) ? trim((string) $_SESSION['sUserName']) : '';
        if ($username === '') throw new RuntimeException('Vui lòng đăng nhập lại.');
        $khoa = $this->filePeer->getCurrentUserKhoa($username);
        if ($khoa === null || trim((string) $khoa) === '') {
            throw new RuntimeException('Tài khoản chưa được gán khoa; không thể truy cập hồ sơ.');
        }
        return (string) $khoa;
    }

    private function storage()
    {
        if ($this->storage !== null) return $this->storage;
        $root = null;
        try {
            $config = (new HeThongPeer())->getInputPath();
            if (!empty($config['path'])) $root = $config['path'];
        } catch (Throwable $ignored) {
            // Sử dụng storage/xml mặc định nếu chưa có cấu hình đầu vào.
        }
        $this->storage = new FileStorageService(
            dirname(__DIR__, 2), $root, $this->filePeer->getConfiguredKhoaOutputPaths()
        );
        return $this->storage;
    }

    private function selectedDate()
    {
        $value = trim((string) $this->request->getParameter('date', false));
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new RuntimeException('Ngày được chọn không hợp lệ.');
        }
        return $value;
    }

    private function validCsrf()
    {
        $token = $this->request->getParameter('csrf_token', false);
        return !empty($_SESSION['hoso_csrf']) && is_string($token)
            && hash_equals($_SESSION['hoso_csrf'], $token);
    }

    private function json(array $data)
    {
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
