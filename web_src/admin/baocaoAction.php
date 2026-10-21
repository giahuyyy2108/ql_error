<?php
require_once __DIR__ . '/fileAction.php';

class baocaoAction extends fileAction
{
    public function index()
    {
        $this->request->setModel('www/baocao/index.php');
        $this->request->setAttribute('script', '<script src="' . _DEFAULT_URL_ . 'js/file.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script><script src="' . _DEFAULT_URL_ . 'js/baocao.js?' . _DEFAULT_VERSION_JS_CSS_ . '"></script>');
        return true;
    }
}
