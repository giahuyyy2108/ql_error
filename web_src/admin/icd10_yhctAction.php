<?php

require_once __DIR__ . '/../bean/Icd10YhctPeer.php';

class icd10_yhctAction
{
    public static $listRole = 'icd10_yhct';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new Icd10YhctPeer();
        $this->request->setTitle('Danh mục ICD-10 Y học cổ truyền');
    }

    public function index()
    {
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/icd10_yhct.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/icd10_yhct/index.php');
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
            $data = array(
                'draw' => $draw,
                'recordsTotal' => $page['total'],
                'recordsFiltered' => $page['filtered'],
                'data' => $page['data']
            );
        } catch (RuntimeException $exception) {
            $data = array(
                'draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0,
                'data' => array(), 'error' => $exception->getMessage()
            );
        }
        return $this->request->json_response(json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }
}
