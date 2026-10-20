<?php
require_once 'web_src/bean/KhoaPhongCatalogPeer.php';
class khoaphongAction
{
    public static $listRole = 'khoaphong';
    private $request; private $peer;
    function __construct() { $this->request = new Request(); $this->peer = new KhoaPhongCatalogPeer(); $this->request->setTitle('Danh mục khoa phòng'); }
    function index() { $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/khoaphong-page.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'); $this->request->setModel('www/khoaphong/index.php'); return true; }
    function getData()
    {
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;

        try {
            $page = $this->peer->getPage($_POST['searchText'] ?? '');

            return $this->request->json_response(json_encode(array(
                'draw' => $draw,
                'recordsTotal' => $page['total'],
                'recordsFiltered' => $page['filtered'],
                'data' => $page['data']
            ), JSON_UNESCAPED_UNICODE));
        } catch (Throwable $exception) {
            return $this->request->json_response(json_encode(array(
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => array(),
                'error' => $exception->getMessage()
            ), JSON_UNESCAPED_UNICODE));
        }
    }
}
