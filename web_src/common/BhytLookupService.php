<?php

class BhytLookupService
{
    private $client = null;

    public function traCuuThongTin($maThe, $hoTen, $ngaySinh)
    {
        if ($this->client === null) {
            require_once __DIR__ . '/BHYT.php';
            $this->client = new BHXH();
        }
        return $this->client->traCuuThongTin($maThe, $hoTen, $ngaySinh);
    }
}
