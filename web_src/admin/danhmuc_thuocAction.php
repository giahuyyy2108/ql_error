<?php

require_once 'web_src/bean/DanhMucThuocPeer.php';

class danhmuc_thuocAction
{
    public static $listRole = 'danhmuc_thuoc';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new DanhMucThuocPeer();
        $this->request->setTitle('Danh mục tân dược');
    }

    public function index()
    {
        $this->request->setAttribute('provinces', $this->peer->getProvinces());
        $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/danhmuc_thuoc.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
        $this->request->setModel('www/danhmuc_thuoc/index.php');
        return true;
    }

    public function getData()
    {
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
        $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
        $search = isset($_POST['searchText']) ? $_POST['searchText'] : '';
        $province = isset($_POST['province']) ? $_POST['province'] : '';
        try {
            $page = $this->peer->getPage($start, $length, $search, $province);
            return $this->request->json_response(json_encode(array(
                'draw' => $draw, 'recordsTotal' => $page['total'],
                'recordsFiltered' => $page['filtered'], 'data' => $page['data']
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Exception $exception) {
            return $this->request->json_response(json_encode(array(
                'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0,
                'data' => array(), 'error' => $exception->getMessage()
            ), JSON_UNESCAPED_UNICODE));
        }
    }
}
