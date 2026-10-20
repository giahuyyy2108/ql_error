<?php
require_once 'web_src/bean/KhoiPeer.php';
class khoiAction {
    public static $listRole = 'khoi';

    private $request;
    private $peer;

    function __construct()
    {
        $this->request = new Request();
        $this->peer = new KhoiPeer();
        $this->request->setTitle('Danh mục khối');
    }

    function index()
    {
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/khoi.js?'
                . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/khoi/index.php');

        return true;
    }

    function getData()
    {
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
        $start = isset($_POST['start']) ? $_POST['start'] : 0;
        $length = isset($_POST['length']) ? $_POST['length'] : 25;
        $search = isset($_POST['searchText'])
            ? $_POST['searchText']
            : (isset($_POST['search']['value']) ? $_POST['search']['value'] : '');

        try {
            $page = $this->peer->getPage($start, $length, $search);

            return $this->request->json_response(json_encode(
                array(
                    'draw' => $draw,
                    'recordsTotal' => $page['total'],
                    'recordsFiltered' => $page['filtered'],
                    'data' => $page['data']
                ),
                JSON_UNESCAPED_UNICODE
            ));
        } catch (Throwable $exception) {
            return $this->request->json_response(json_encode(
                array(
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => array(),
                    'error' => $exception->getMessage()
                ),
                JSON_UNESCAPED_UNICODE
            ));
        }
    }
}
