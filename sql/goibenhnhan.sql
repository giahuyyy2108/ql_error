-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2024 at 09:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `goibenhnhan`
--

-- --------------------------------------------------------

--
-- Table structure for table `bacsi`
--

CREATE TABLE `bacsi` (
  `MaBacSi` int(111) NOT NULL,
  `TenBacSi` varchar(200) NOT NULL,
  `GioiTinh` varchar(10) NOT NULL,
  `soDienThoai` varchar(30) NOT NULL,
  `MaKhoaPhong` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `benhnhan`
--

CREATE TABLE `benhnhan` (
  `id` int(11) NOT NULL,
  `maBN` varchar(255) NOT NULL,
  `tenBN` longtext DEFAULT NULL,
  `namSinh` int(11) DEFAULT NULL,
  `gioiTinh` varchar(255) DEFAULT NULL,
  `soDienThoai` varchar(11) DEFAULT NULL,
  `chuanDoan` longtext DEFAULT NULL,
  `ngayTao` datetime DEFAULT NULL,
  `ngayGoi` datetime DEFAULT NULL,
  `ngayGoiMoiNhat` datetime DEFAULT NULL,
  `maTrangThai` int(11) NOT NULL,
  `bacSi` longtext DEFAULT NULL,
  `quayTiepNhan` longtext DEFAULT NULL,
  `trangThaiXoa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chucnang`
--

CREATE TABLE `chucnang` (
  `maChucNang` int(11) NOT NULL,
  `tenChucNang` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `logo` varchar(200) NOT NULL,
  `parentQuyen` varchar(255) NOT NULL,
  `tenChucNangCon` text NOT NULL,
  `urlChucNangCon` text NOT NULL,
  `order` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `chucnang`
--

INSERT INTO `chucnang` (`maChucNang`, `tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`) VALUES
(1, 'HỆ THỐNG', 0, '', '', 'hethong', '', '', 64, 0),
(2, 'Người dùng', 1, 'user', 'fa fa-user', '', 'Xem,Thêm/Sửa,Xóa,Phân quyền, Khóa', 'user,user.saveUser,user.deleteUser,user.phanquyen,user.lock,', 66, 1),
(4, 'Xem Log', 1, 'log', 'fa fa-street-view', '', 'Xem,Xóa', 'log,log.deleteLog,', 70, 1),
(5, 'Nhóm quyền', 1, 'nhomquyen', 'fa fa-group', '', 'Xem,Thêm/Sửa,Xóa,Phân quyền', 'nhomquyen,nhomquyen.save,nhomquyen.delete,nhomquyen.phanquyen,', 66, 1),
(6, 'Gọi Người Nhà Bệnh Nhân', 1, 'benhnhan', 'fa fa-bell', '', 'Xem,Thêm/Sửa,Xóa', 'benhnhan,benhnhan.saveBenhNhan,benhnhan.deleteBenhNhan,', 75, 1),
(10, 'QL Trạng Thái', 1, 'trangthai', 'fa fa-signal', '', 'Xem,Thêm/Sửa,Xóa', 'trangthai,trangthai.saveTrangThai,trangthai.deleteTrangThai', 76, 1),
(7, 'Bác sĩ', 1, 'bacsi', 'fa fa-user-md', '', 'Xem,Thêm/Sửa,Xóa', 'bacsi,bacsi.saveBacSi,bacsi.deleteBacSi,', 72, 1),
(8, 'Khoa Phòng', 1, 'khoaphong', 'fa fa-hospital-o', '', 'Xem,Thêm/Sửa,Xóa', 'khoaphong,khoaphong.saveKhoaPhong,khoaphong.deleteKhoaPhong,', 73, 1),
(88, 'Quầy tiếp nhận', 1, 'quaytiepnhan', 'fa fa-house', '', 'Xem,Thêm/Sửa,Xóa', 'quaytiepnhan,quaytiepnhan.saveQuayTiepNhan,quaytiepnhan.deleteQuayTiepNhan', 76, 1),
(87, 'Về Giao Diện', 1, 'giaodien', 'fa fa-arrow-right', '', 'Xem', 'giaodien', 65, 1);

-- --------------------------------------------------------

--
-- Table structure for table `codemaster`
--

CREATE TABLE `codemaster` (
  `id` varchar(10) NOT NULL,
  `year` varchar(2) NOT NULL,
  `curvalue` int(11) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='luu so nhay cua cac phieu';

--
-- Dumping data for table `codemaster`
--

INSERT INTO `codemaster` (`id`, `year`, `curvalue`, `active`, `description`) VALUES
('PCK', '20', 9, 1, ''),
('DDH', '20', 21, 1, ''),
('PGC', '20', 5, 1, ''),
('PX', '20', 52, 1, ''),
('PN', '20', 16, 1, ''),
('HS', '20', 8, 1, ''),
('GH', '20', 3, 1, ''),
('DH', '20', 25, 1, ''),
('HD', '20', 30, 1, ''),
('pn', '20', 2, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `khoaphong`
--

CREATE TABLE `khoaphong` (
  `MaKhoaPhong` int(11) NOT NULL,
  `TenKhoaPhong` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `khoaphong`
--

INSERT INTO `khoaphong` (`MaKhoaPhong`, `TenKhoaPhong`) VALUES
(2, 'Cấp cứu');

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `logID` int(11) NOT NULL,
  `ngay` varchar(100) NOT NULL,
  `ten` varchar(100) NOT NULL,
  `chucnang` varchar(100) NOT NULL,
  `noidung` text NOT NULL,
  `noidungcu` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip` varchar(20) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `lastlogin` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nhomquyen`
--

CREATE TABLE `nhomquyen` (
  `maNQ` int(11) NOT NULL,
  `tenNQ` varchar(255) NOT NULL,
  `quyen` varchar(500) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `nhomquyen`
--

INSERT INTO `nhomquyen` (`maNQ`, `tenNQ`, `quyen`) VALUES
(5, 'Administrator', 'hethong,giaodien,user,user,user.saveUser,user.deleteUser,user.phanquyen,user.lock,nhomquyen,nhomquyen,nhomquyen.save,nhomquyen.delete,nhomquyen.phanquyen,log,log,log.deleteLog,bacsi,bacsi,bacsi.saveBacSi,bacsi.deleteBacSi,khoaphong,khoaphong,khoaphong.saveKhoaPhong,khoaphong.deleteKhoaPhong,benhnhan,benhnhan,benhnhan.saveBenhNhan,benhnhan.deleteBenhNhan,trangthai,trangthai,trangthai.saveTrangThai,trangthai.deleteTrangThai'),
(51, 'Điều dưỡng', 'hethong,benhnhan,benhnhan,benhnhan.saveBenhNhan,benhnhan.deleteBenhNhan'),
(52, 'Bác sĩ', 'hethong,benhnhan,benhnhan,benhnhan.saveBenhNhan,benhnhan.deleteBenhNhan'),
(67, 'Cấp cứu', 'hethong,bacsi,bacsi,bacsi.saveBacSi,bacsi.deleteBacSi,khoaphong,khoaphong,khoaphong.saveKhoaPhong,khoaphong.deleteKhoaPhong,benhnhan,benhnhan,benhnhan.saveBenhNhan,benhnhan.deleteBenhNhan,trangthai,trangthai,trangthai.saveTrangThai,trangthai.deleteTrangThai,quaytiepnhan,quaytiepnhan,quaytiepnhan.saveQuayTiepNhan,quaytiepnhan.deleteQuayTiepNhan'),
(69, 'Giao diện', 'hethong,giaodien,giaodien');

-- --------------------------------------------------------

--
-- Table structure for table `quaytiepnhan`
--

CREATE TABLE `quaytiepnhan` (
  `maQuay` int(11) NOT NULL,
  `tenQuayTiepNhan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quaytiepnhan`
--

INSERT INTO `quaytiepnhan` (`maQuay`, `tenQuayTiepNhan`) VALUES
(2, 'Quầy hành chính'),
(3, 'Quầy tư vấn dinh dưỡng'),
(4, 'Quầy tiếp nhận hồ sơ bệnh án'),
(5, 'Quầy đăng ký khám bảo hiểm y tế'),
(6, 'Quầy lấy mẫu xét nghiệm'),
(7, 'Quầy dịch vụ khách hàng'),
(8, 'Quầy phát kết quả xét nghiệm'),
(9, 'Quầy thuốc'),
(10, 'Quầy thu ngân');

-- --------------------------------------------------------

--
-- Table structure for table `trangthai`
--

CREATE TABLE `trangthai` (
  `maTrangThai` int(11) NOT NULL,
  `tenTrangThai` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trangthai`
--

INSERT INTO `trangthai` (`maTrangThai`, `tenTrangThai`) VALUES
(1, 'Đang cấp cứu'),
(2, 'Mời người nhà'),
(3, 'Hoàn thành'),
(83, 'Đang hồi sức tích cực'),
(84, 'Đang hồi sức tích cực phục hồi'),
(85, 'Đang chờ kết quả cận lâm sàng');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `hoTen` varchar(255) NOT NULL,
  `diaChi` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `dienThoai` varchar(100) NOT NULL,
  `adminType` varchar(10) NOT NULL DEFAULT '0',
  `quyen` text DEFAULT NULL,
  `maNQ` int(11) NOT NULL,
  `nd_block` tinyint(4) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `hoTen`, `diaChi`, `email`, `dienThoai`, `adminType`, `quyen`, `maNQ`, `nd_block`, `token`) VALUES
(4, 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'Administrator', '', '', '', '1', '', 5, 0, 's1e8RPPhB'),
(11, 'vietkhoi', '25f9e794323b453885f5181f1b624d0b', 'Viết Khôi', '', '', '', '1', '', 5, 0, '0dIWVf8CE'),
(189, 'capcuu', '202cb962ac59075b964b07152d234b70', 'Cấp cứu', '', '', '', '0', 'hethong,bacsi,bacsi,bacsi.saveBacSi,bacsi.deleteBacSi,khoaphong,khoaphong,khoaphong.saveKhoaPhong,khoaphong.deleteKhoaPhong,benhnhan,benhnhan,benhnhan.saveBenhNhan,benhnhan.deleteBenhNhan,trangthai,trangthai,trangthai.saveTrangThai,trangthai.deleteTrangThai,quaytiepnhan,quaytiepnhan,quaytiepnhan.saveQuayTiepNhan,quaytiepnhan.deleteQuayTiepNhan', 67, 0, 'MjDpmE5lc'),
(192, 'gd', 'c4ca4238a0b923820dcc509a6f75849b', 'Giao diện', '', '', '', '0', 'hethong,giaodien,giaodien', 69, 0, '5xuXQq4Jo');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bacsi`
--
ALTER TABLE `bacsi`
  ADD PRIMARY KEY (`MaBacSi`),
  ADD KEY `MaKhoaPhong` (`MaKhoaPhong`);

--
-- Indexes for table `benhnhan`
--
ALTER TABLE `benhnhan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `benhnhan_ibfk_1` (`maTrangThai`);

--
-- Indexes for table `chucnang`
--
ALTER TABLE `chucnang`
  ADD PRIMARY KEY (`maChucNang`);

--
-- Indexes for table `codemaster`
--
ALTER TABLE `codemaster`
  ADD PRIMARY KEY (`id`,`year`);

--
-- Indexes for table `khoaphong`
--
ALTER TABLE `khoaphong`
  ADD PRIMARY KEY (`MaKhoaPhong`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`logID`);

--
-- Indexes for table `nhomquyen`
--
ALTER TABLE `nhomquyen`
  ADD PRIMARY KEY (`maNQ`);

--
-- Indexes for table `quaytiepnhan`
--
ALTER TABLE `quaytiepnhan`
  ADD PRIMARY KEY (`maQuay`);

--
-- Indexes for table `trangthai`
--
ALTER TABLE `trangthai`
  ADD PRIMARY KEY (`maTrangThai`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bacsi`
--
ALTER TABLE `bacsi`
  MODIFY `MaBacSi` int(111) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benhnhan`
--
ALTER TABLE `benhnhan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chucnang`
--
ALTER TABLE `chucnang`
  MODIFY `maChucNang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `khoaphong`
--
ALTER TABLE `khoaphong`
  MODIFY `MaKhoaPhong` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nhomquyen`
--
ALTER TABLE `nhomquyen`
  MODIFY `maNQ` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `quaytiepnhan`
--
ALTER TABLE `quaytiepnhan`
  MODIFY `maQuay` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `trangthai`
--
ALTER TABLE `trangthai`
  MODIFY `maTrangThai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bacsi`
--
ALTER TABLE `bacsi`
  ADD CONSTRAINT `bacsi_ibfk_1` FOREIGN KEY (`MaKhoaPhong`) REFERENCES `khoaphong` (`MaKhoaPhong`);

--
-- Constraints for table `benhnhan`
--
ALTER TABLE `benhnhan`
  ADD CONSTRAINT `benhnhan_ibfk_1` FOREIGN KEY (`maTrangThai`) REFERENCES `trangthai` (`maTrangThai`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
