<?php

require_once 'web_src/bean/LoaiKcbPeer.php';

class loai_kcbAction
{
    public static $listRole = 'loai_kcb';
    private $request;
    private $peer;

    public function __construct()
    {
        $this->request = new Request();
        $this->peer = new LoaiKcbPeer();
        $this->request->setTitle('Danh mục loại khám chữa bệnh');
    }

    public function index()
    {
        $this->request->setAttribute(
            'script',
            '<script src="' . _DEFAULT_URL_ . 'js/loai_kcb.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>'
        );
        $this->request->setModel('www/loai_kcb/index.php');
        return true;
    }

    public function getData()
    {
        try {
            return $this->json(array('data' => $this->peer->getList()));
        } catch (RuntimeException $exception) {
            return $this->json(array('data' => array(), 'error' => $exception->getMessage()));
        }
    }

    private function json($data)
    {
        return $this->request->json_response(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
