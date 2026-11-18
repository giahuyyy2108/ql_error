<?php

require_once __DIR__ . '/../common/Request.php';
require_once __DIR__ . '/../bean/FilePeer.php';

class baocaoAction
{
    private $request;
    private $filePeer;

    public function __construct()
    {
        $this->request = new Request();
        $this->filePeer = new FilePeer();
    }

    public function index()
    {
        global $connect;
        $rows = array();
        $result = $connect->query("SELECT ma AS id, ten FROM khoa_phong WHERE ma_khoa_goc IS NULL AND is_active = 1 ORDER BY ten");
        if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
        $this->request->setAttribute('reportKhoaList', $rows);
        $this->request->setModel('www/baocao/index.php');
        $this->request->setAttribute(
            'css',
            '<link href="' . _DEFAULT_URL_ . 'css/file.css?' . _DEFAULT_VERSION_JS_CSS_
            . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/css/file.css') . '" rel="stylesheet">'
        );
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/baocao.js?' . _DEFAULT_VERSION_JS_CSS_
            . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/js/baocao.js') . '"></script>'
        );
        return true;
    }

    public function getErrorReport()
    {
        $period = trim((string) $this->request->getParameter('period', false));
        $value = trim((string) $this->request->getParameter('date', false));
        $fromValue = trim((string) $this->request->getParameter('from_date', false));
        $toValue = trim((string) $this->request->getParameter('to_date', false));
        $reportScope = trim((string) $this->request->getParameter('report_scope', 'hospital'));
        $reportKhoa = trim((string) $this->request->getParameter('report_khoa', ''));
        $month = $period === 'month' ? DateTime::createFromFormat('!Y-m', $value) : false;
        $date = null;
        $to = null;

        if ($period === 'range') {
            $fromDate = DateTime::createFromFormat('!Y-m-d', $fromValue);
            $endDate = DateTime::createFromFormat('!Y-m-d', $toValue);
            if (!$fromDate || $fromDate->format('Y-m-d') !== $fromValue
                || !$endDate || $endDate->format('Y-m-d') !== $toValue
                || $fromDate > $endDate) {
                return $this->json(array('success' => false, 'message' => 'Khoảng ngày được chọn không hợp lệ.'));
            }
            $date = $fromDate->format('Y-m-d');
            $to = (clone $endDate)->modify('+1 day')->format('Y-m-d');
        } elseif ($period === 'month') {
            if (!$month || $month->format('Y-m') !== $value) {
                return $this->json(array('success' => false, 'message' => 'Tháng được chọn không hợp lệ.'));
            }
            $date = $month->format('Y-m-01');
            $to = (clone $month)->modify('+1 month')->format('Y-m-01');
        } else {
            $date = $this->selectedDate();
            if ($date === null) {
                return $this->json(array('success' => false, 'message' => 'Ngày được chọn không hợp lệ.'));
            }
        }

        try {
            if ($period !== 'range' && !$month && $toValue !== '') {
                $end = DateTime::createFromFormat('!Y-m-d', $toValue);
                if (!$end || $end->format('Y-m-d') !== $toValue) {
                    return $this->json(array('success' => false, 'message' => 'Khoảng ngày không hợp lệ.'));
                }
                $to = $end->modify('+1 day')->format('Y-m-d');
            }

            $idKhoa = $this->resolveReportKhoa($reportScope, $reportKhoa);
            if (is_array($idKhoa)) {
                return $this->json($idKhoa);
            }

            return $this->json(array(
                'success' => true,
                'data' => $this->filePeer->getErrorReportRows($date, $to, $idKhoa),
                'files' => $this->filePeer->getAllFileReportRows($date, $to, $idKhoa)
            ));
        } catch (RuntimeException $exception) {
            return $this->json(array('success' => false, 'message' => $exception->getMessage()));
        }
    }

    private function resolveReportKhoa($reportScope, $reportKhoa)
    {
        $isAdmin = isset($_SESSION['AdminType']) && (int) $_SESSION['AdminType'] === 1;
        $userKhoa = null;
        if (!$isAdmin) {
            $username = isset($_SESSION['sUserName']) ? (string) $_SESSION['sUserName'] : '';
            $userKhoa = $this->filePeer->getCurrentUserKhoa($username);
            if ($userKhoa !== null && trim((string) $userKhoa) === '') {
                $userKhoa = null;
            }
        }

        if ($reportScope === 'khoa' && ($isAdmin || $userKhoa === null)) {
            if ($reportKhoa === '' || $reportKhoa === '0') {
                return array('success' => false, 'message' => 'Vui lòng chọn khoa cần thống kê.');
            }
            $khoa = $this->filePeer->getKhoaByCode($reportKhoa);
            if (!$khoa) {
                return array('success' => false, 'message' => 'Mã khoa được chọn không hợp lệ.');
            }
            return $khoa['ma'];
        }

        return $isAdmin ? null : $userKhoa;
    }

    private function selectedDate()
    {
        $value = trim((string) $this->request->getParameter('date', false));
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function json($data)
    {
        return $this->request->json_response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
