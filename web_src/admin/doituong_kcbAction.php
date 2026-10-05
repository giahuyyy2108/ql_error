<?php

require_once __DIR__ . '/../bean/DoiTuongKcbPeer.php';

class doituong_kcbAction
{
    public static $listRole = 'doituong_kcb';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new DoiTuongKcbPeer();
        $this->request->setTitle('Danh mục mã loại hình KCB');
    }

    public function index()
    {
        $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/doituong_kcb.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
        $this->request->setModel('www/doituong_kcb/index.php');
        return true;
    }

    public function getData()
    {
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
        $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
        $search = isset($_POST['searchText']) ? $_POST['searchText'] : '';
        try {
            $page = $this->peer->getPage($start, $length, $search);
            $data = array('draw'=>$draw,'recordsTotal'=>$page['total'],'recordsFiltered'=>$page['filtered'],'data'=>$page['data']);
        } catch (RuntimeException $e) {
            $data = array('draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>array(),'error'=>$e->getMessage());
        }
        return $this->request->json_response(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
