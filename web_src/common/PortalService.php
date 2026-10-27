<?php

require_once __DIR__ . '/XmlFileDecoder.php';
require_once __DIR__ . '/FileStorageService.php';
require_once __DIR__ . '/../bean/LogPeer.php';

/**
 * Nghiệp vụ đẩy hồ sơ sang vùng dữ liệu cổng.
 */
class PortalService
{
    private $filePeer;
    private $storage;

    public function __construct(FilePeer $filePeer, FileStorageService $storage = null)
    {
        $this->filePeer = $filePeer;
        $this->storage = $storage !== null ? $storage : new FileStorageService();
    }

    public function push(array $ids, $confirmedWarning, $username, $idKhoa)
    {
        global $connect;

        $done = 0;
        $skipped = 0;
        try {
            foreach ($this->filePeer->getPortalFilesByIds($ids, $confirmedWarning) as $file) {
                try {
                    $path = $this->storage->resolveStoredFilePath($file['file_path']);
                } catch (RuntimeException $exception) {
                    $skipped++;
                    continue;
                }

                $content = file_get_contents($path);
                if ($content === false) {
                    $skipped++;
                    continue;
                }
                $decoded = XmlFileDecoder::decodeDanhSachHoSo($content);
                $decodedContent = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($decodedContent === false) {
                    $skipped++;
                    continue;
                }

                $values = XmlFileDecoder::extractValuesFromFileType(
                    $decoded,
                    'XML1',
                    array('CCCD', 'MA_BN', 'HO_TEN', 'NGAY_SINH', 'MA_THE_BHYT')
                );
                $cccd = trim((string) (isset($values['MA_BN']) ? $values['MA_BN'] : ''));
                if ($cccd === '') {
                    $skipped++;
                    continue;
                }

                $connect->begin_transaction();
                $this->savePatient($connect, $cccd, $values);
                $inserted = $this->savePortalRecord(
                    $connect,
                    $cccd,
                    $file,
                    $decodedContent,
                    $username,
                    $idKhoa
                );

                if ($inserted > 0) {
                    $archive = $this->storage->copyToPortal($path, $file['id']);
                    $this->filePeer->markPortalPushed($file['id']);
                    $this->writePortalLog($file, $archive['relative_path'], $confirmedWarning);
                    $done++;
                } else {
                    $skipped++;
                }
                $connect->commit();
            }
        } catch (Throwable $exception) {
            if ($connect instanceof mysqli) {
                @$connect->rollback();
            }
            throw $exception;
        }

        return array('done' => $done, 'skipped' => $skipped);
    }

    private function savePatient(mysqli $connection, $cccd, array $values)
    {
        $statement = $connection->prepare(
            'INSERT IGNORE INTO cong_benhnhan (cccd, hoten, namsinh, ma_the_bhyt) VALUES (?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu bệnh nhân.');
        }
        $name = (string) (isset($values['HO_TEN']) ? $values['HO_TEN'] : '');
        $birth = (string) (isset($values['NGAY_SINH']) ? $values['NGAY_SINH'] : '');
        $card = (string) (isset($values['MA_THE_BHYT']) ? $values['MA_THE_BHYT'] : '');
        $statement->bind_param('ssss', $cccd, $name, $birth, $card);
        $statement->execute();
        $statement->close();
    }

    private function savePortalRecord(
        mysqli $connection,
        $cccd,
        array $file,
        $decodedContent,
        $username,
        $idKhoa
    ) {
        $statement = $connection->prepare(
            'INSERT IGNORE INTO cong_hoso (cccd, malk, ten_file, noidung, nguoi_day, id_khoa) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$statement) {
            throw new RuntimeException('Không thể chuẩn bị dữ liệu hồ sơ cổng.');
        }
        $maLk = (string) $file['ma_lk'];
        $fileName = (string) $file['ten'];
        $username = (string) $username;
        $idKhoa = $idKhoa !== null ? (string) $idKhoa : null;
        $statement->bind_param('ssssss', $cccd, $maLk, $fileName, $decodedContent, $username, $idKhoa);
        $statement->execute();
        $inserted = $statement->affected_rows;
        $statement->close();
        return $inserted;
    }

    private function writePortalLog(array $file, $relativePath, $confirmedWarning)
    {
        $log = new Log();
        $log->set('chucnang', 'Đẩy cổng');
        $log->set(
            'noidung',
            'File: ' . $file['ten'] . ' | MALK: ' . $file['ma_lk']
            . ' | Đường dẫn mới: ' . $relativePath
            . ($confirmedWarning ? ' | Người dùng đã đồng ý đẩy hồ sơ có cảnh báo' : '')
        );
        $log->set('noidungcu', 'Đường dẫn cũ: ' . $file['file_path']);
        (new LogPeer())->ghiLog($log);
    }
}

