CREATE TABLE IF NOT EXISTS `doituong_kcb_backup_3276` LIKE `doituong_kcb`;

INSERT INTO `doituong_kcb_backup_3276`
SELECT * FROM `doituong_kcb`
WHERE NOT EXISTS (SELECT 1 FROM `doituong_kcb_backup_3276` LIMIT 1)
ON DUPLICATE KEY UPDATE
    `truong_hop`=VALUES(`truong_hop`), `quy_dinh`=VALUES(`quy_dinh`),
    `muc_huong`=VALUES(`muc_huong`), `ghi_chu`=VALUES(`ghi_chu`),
    `nguon`=VALUES(`nguon`), `ngay_hieu_luc`=VALUES(`ngay_hieu_luc`),
    `is_active`=VALUES(`is_active`);

DELETE FROM `doituong_kcb`;

INSERT INTO `doituong_kcb`
    (`ma`, `truong_hop`, `quy_dinh`, `muc_huong`, `ghi_chu`, `nguon`, `ngay_hieu_luc`, `is_active`)
VALUES
('01','Khám bệnh',NULL,NULL,NULL,'https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('02','Điều trị ngoại trú',NULL,NULL,'Điều trị ngoại trú các bệnh không thuộc các bệnh cần chữa trị dài ngày theo Phụ lục I Thông tư 25/2025/TT-BYT','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('03','Điều trị nội trú',NULL,NULL,'Thời gian điều trị nội trú dưới 4 giờ thì sử dụng mã 09','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('04','Điều trị ban ngày',NULL,NULL,'Không áp dụng cho trường hợp điều trị nội trú dưới 4 giờ (mã 09)','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('05','Điều trị ngoại trú các bệnh cần chữa trị dài ngày liên tục trong năm, có khám bệnh và lĩnh thuốc',NULL,NULL,'Nếu có thực hiện dịch vụ kỹ thuật thì sử dụng mã 08','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('06','Lưu người bệnh tại phòng khám đa khoa, phòng khám đa khoa khu vực, nhà hộ sinh, trạm y tế xã, phường',NULL,NULL,NULL,'https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('07','Nhận thuốc theo hẹn',NULL,NULL,'Không thuộc trường hợp người bệnh đi khám bệnh, chữa bệnh','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('08','Điều trị ngoại trú bệnh cần chữa trị dài ngày, có khám bệnh, thực hiện dịch vụ kỹ thuật và/hoặc sử dụng thuốc',NULL,NULL,NULL,'https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('09','Điều trị nội trú dưới 04 giờ',NULL,NULL,'Không áp dụng cho trường hợp điều trị ban ngày (mã 04)','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('10','Các trường hợp khác',NULL,NULL,NULL,'https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('11','Khám bệnh, chữa bệnh lưu động',NULL,NULL,'Áp dụng khi cung cấp dịch vụ ngoài địa điểm ghi trong giấy phép hoạt động, không gồm KCB tại nhà mã 12','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('12','Khám bệnh, chữa bệnh tại nhà',NULL,NULL,NULL,'https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('13','Khám bệnh, chữa bệnh y học gia đình',NULL,NULL,'Áp dụng tại cơ sở KCB y học gia đình hoặc cơ sở có phạm vi chuyên môn y học gia đình','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('14','Khám bệnh, chữa bệnh từ xa',NULL,NULL,NULL,'https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('15','Khám sức khoẻ định kỳ',NULL,NULL,'BHYT chỉ áp dụng khi có quy định của cấp có thẩm quyền về thanh toán từ Quỹ BHYT','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1),
('16','Khám sàng lọc',NULL,NULL,'BHYT chỉ áp dụng khi có quy định của cấp có thẩm quyền về thanh toán từ Quỹ BHYT','https://xaydungchinhsach.chinhphu.vn/quy-dinh-danh-muc-ma-kham-chua-benh-va-ma-khoa-phuc-vu-ket-noi-du-lieu-bhyt-119260630165800133.htm','2026-08-01',1);
