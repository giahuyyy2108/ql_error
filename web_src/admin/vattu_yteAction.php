<?php

require_once 'web_src/bean/VatTuYTePeer.php';

class vattu_yteAction
{
    public static $listRole = 'vattu_yte';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new VatTuYTePeer();
        $this->request->setTitle('Vật tư y tế');
    }

    public function index()
    {
        $this->request->setAttribute('hieuLucList', $this->peer->getHieuLucList());
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/vattu_yte.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/vattu_yte/index.php');
        return true;
    }

    public function getData()
    {
        $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
        $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
        $length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
        $search = isset($_POST['searchText']) ? $_POST['searchText'] : '';
        $hieuLuc = isset($_POST['hieuLuc']) ? $_POST['hieuLuc'] : '';

        try {
            $page = $this->peer->getPage($start, $length, $search, $hieuLuc);
            return $this->json(array(
                'draw' => $draw,
                'recordsTotal' => $page['total'],
                'recordsFiltered' => $page['filtered'],
                'data' => $page['data']
            ));
        } catch (Exception $exception) {
            return $this->json(array(
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => array(),
                'error' => $exception->getMessage()
            ));
        }
    }

    private function json($data)
    {
        return $this->request->json_response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
