<?php
require_once __DIR__ . '/fileAction.php';

class baocaoAction extends fileAction
{
    public function index()
    {
        global $connect;
        $rows = array();
        $result = $connect->query('SELECT id, ten FROM khoa ORDER BY ten');
        if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
        $this->request->setAttribute('reportKhoaList', $rows);
        $this->request->setModel('www/baocao/index.php');
        $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/file.js?' . _DEFAULT_VERSION_JS_CSS_ . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/js/file.js') . '"></script><script src="' . _DEFAULT_URL_ . 'js/baocao.js?' . _DEFAULT_VERSION_JS_CSS_ . '&amp;v=' . filemtime(dirname(__DIR__, 2) . '/js/baocao.js') . '"></script>');
        return true;
    }
}
