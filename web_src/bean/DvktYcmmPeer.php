<?php

class DvktYcmmPeer
{
    private $connection;

    public function __construct()
    {
        global $connect;
        $db = new db_mysql();
        $db->connect();
        $db->selectdb();
        if (!($connect instanceof mysqli)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
        $this->connection = $connect;
        $this->connection->set_charset('utf8mb4');
    }

    public function getList()
    {
        $result = $this->connection->query(
            'SELECT id, stt, ma_dvkt, ten_dvkt, hieu_luc, ngay_hieu_luc,
                    ngay_het_hieu_luc, tra_ket_qua_sau, created_at, updated_at
             FROM danh_muc_dvkt_ma_may ORDER BY stt ASC, id ASC'
        );
        if (!$result) throw new RuntimeException('Không thể tải danh mục DVKT yêu cầu mã máy.');
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['stt'] = (int) $row['stt'];
            $row['hieu_luc'] = (int) $row['hieu_luc'];
            $row['tra_ket_qua_sau'] = (int) $row['tra_ket_qua_sau'];
            $rows[] = $row;
        }
        $result->free();
        return $rows;
    }

    public function getOne($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, stt, ma_dvkt, ten_dvkt, hieu_luc, ngay_hieu_luc,
                    ngay_het_hieu_luc, tra_ket_qua_sau, created_at, updated_at
             FROM danh_muc_dvkt_ma_may WHERE id=? LIMIT 1'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) return null;
        $row['id'] = (int) $row['id'];
        $row['stt'] = (int) $row['stt'];
        $row['hieu_luc'] = (int) $row['hieu_luc'];
        $row['tra_ket_qua_sau'] = (int) $row['tra_ket_qua_sau'];
        return $row;
    }

    public function insert(array $item)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO danh_muc_dvkt_ma_may
             (stt, ma_dvkt, ten_dvkt, hieu_luc, ngay_hieu_luc, ngay_het_hieu_luc, tra_ket_qua_sau)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('ississi', $item['stt'], $item['ma_dvkt'], $item['ten_dvkt'],
            $item['hieu_luc'], $item['ngay_hieu_luc'], $item['ngay_het_hieu_luc'], $item['tra_ket_qua_sau']);
        $this->execute($statement);
        $id = (int) $this->connection->insert_id;
        $statement->close();
        return $id;
    }

    public function update($id, array $item)
    {
        $statement = $this->connection->prepare(
            'UPDATE danh_muc_dvkt_ma_may
             SET stt=?, ma_dvkt=?, ten_dvkt=?, hieu_luc=?, ngay_hieu_luc=?,
                 ngay_het_hieu_luc=?, tra_ket_qua_sau=?
             WHERE id=?'
        );
        $statement->bind_param('ississii', $item['stt'], $item['ma_dvkt'], $item['ten_dvkt'],
            $item['hieu_luc'], $item['ngay_hieu_luc'], $item['ngay_het_hieu_luc'],
            $item['tra_ket_qua_sau'], $id);
        $this->execute($statement);
        $statement->close();
        if (!$this->getOne($id)) throw new RuntimeException('Bản ghi không tồn tại.');
        return true;
    }

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM danh_muc_dvkt_ma_may WHERE id=?');
        $statement->bind_param('i', $id);
        $this->execute($statement);
        $deleted = $statement->affected_rows > 0;
        $statement->close();
        return $deleted;
    }

    public function setActive($id, $active)
    {
        $statement = $this->connection->prepare('UPDATE danh_muc_dvkt_ma_may SET hieu_luc=? WHERE id=?');
        $statement->bind_param('ii', $active, $id);
        $this->execute($statement);
        $statement->close();
        if (!$this->getOne($id)) throw new RuntimeException('Bản ghi không tồn tại.');
        return true;
    }

    private function execute($statement)
    {
        if (!$statement) throw new RuntimeException('Không thể chuẩn bị thao tác dữ liệu.');
        try {
            $statement->execute();
        } catch (mysqli_sql_exception $exception) {
            $statement->close();
            if ((int) $exception->getCode() === 1062) {
                throw new InvalidArgumentException('Số thứ tự đã tồn tại trong danh mục.');
            }
            throw new RuntimeException('Không thể lưu dữ liệu danh mục.');
        }
    }
}
