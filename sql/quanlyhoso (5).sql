-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 06, 2024 at 08:59 AM
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
-- Database: `quanlyhoso`
--

-- --------------------------------------------------------

--
-- Table structure for table `chucnang`
--

CREATE TABLE `chucnang` (
  `maChucNang` int(11) NOT NULL,
  `tenChucNang` varchar(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `logo` varchar(20) NOT NULL,
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
(1, 'HỆ THỐNG', 0, '', '', 'hethong', '', '', 63, 0),
(2, 'Người dùng', 1, 'user', 'fa fa-user', '', 'Xem,Thêm/Sửa,Xóa,Phân quyền', 'user,user.saveUser,user.deleteUser,user.phanquyen', 66, 1),
(90, 'Hồ sơ ', 1, 'hosotb', 'fa fa-user', '', 'Xem', 'hosotb', 3, 1),
(4, 'Xem Log', 1, 'log', 'fa fa-street-view', '', 'Xem,Xóa', 'log,log.deleteLog', 70, 1),
(68, 'Nhóm quyền', 1, 'nhomquyen', 'fa fa-group', '', 'Xem,Thêm/Sửa,Xóa,Phân quyền', 'nhomquyen,nhomquyen.save,nhomquyen.delete,nhomquyen.phanquyen', 66, 1),
(91, 'Kho', 1, 'kho', 'fa fa-user', '', 'Xem', 'hoso', 3, 1),
(89, 'Hồ sơ 1', 1, 'hoso', 'fa fa-user', '', 'Xem', 'hoso', 2, 1),
(88, 'QUẢN LÝ', 0, '', '', 'quanly', '', '', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `danhsachmuon`
--

CREATE TABLE `danhsachmuon` (
  `id_Muon` int(11) NOT NULL,
  `tenNguoiMuon` varchar(255) NOT NULL,
  `sdt` varchar(20) NOT NULL,
  `ghiChu` varchar(255) NOT NULL,
  `lyDoMuon` varchar(500) NOT NULL,
  `ngayMuon` datetime NOT NULL,
  `ngayHetHan` datetime NOT NULL,
  `trangThai` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gia`
--

CREATE TABLE `gia` (
  `id_Gia` int(11) NOT NULL,
  `tenGia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hoso`
--

CREATE TABLE `hoso` (
  `id_HoSo` int(11) NOT NULL,
  `tenHoSo` varchar(255) NOT NULL,
  `moTa` varchar(255) NOT NULL,
  `ngayLuuHoSo` datetime NOT NULL,
  `ngayHoSo` date NOT NULL,
  `ngayHetHanHoSo` datetime NOT NULL,
  `id_Kho` int(11) NOT NULL,
  `id_Ngan` int(11) NOT NULL,
  `id_Ke` int(11) NOT NULL,
  `id_UserTaoHoso` int(11) NOT NULL,
  `id_LoaiHoSo` int(11) NOT NULL,
  `checkBanCung` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hoso`
--

INSERT INTO `hoso` (`id_HoSo`, `tenHoSo`, `moTa`, `ngayLuuHoSo`, `ngayHoSo`, `ngayHetHanHoSo`, `id_Kho`, `id_Ngan`, `id_Ke`, `id_UserTaoHoso`, `id_LoaiHoSo`, `checkBanCung`) VALUES
(41, 'mua thiết bị văn phòng', 'mua cho phòng cntt', '2024-09-06 09:56:20', '2024-09-03', '0000-00-00 00:00:00', 0, 0, 0, 4, 1, 0),
(42, 'thiết bị văn phòng phẩm', 'mua hồ, mực, viết', '2024-09-06 10:10:58', '2024-08-29', '0000-00-00 00:00:00', 0, 0, 0, 4, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ke`
--

CREATE TABLE `ke` (
  `id_Ke` int(11) NOT NULL,
  `tenKe` varchar(255) NOT NULL,
  `id_Kho` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ke`
--

INSERT INTO `ke` (`id_Ke`, `tenKe`, `id_Kho`) VALUES
(7, 'Kệ VT-YT', 3),
(8, 'Kệ CNTT', 1),
(9, 'Kệ Quản trị', 4),
(10, 'Kệ năm 2023', 5),
(14, 'Kệ góc tường', 7),
(16, 'Kệ VT-YT 1', 3),
(17, 'Kệ VT-YT 2', 3),
(18, 'Kệ VT-YT 3', 3),
(19, 'Kệ VT-YT 4', 3),
(20, 'Kệ VT-YT 5', 3),
(21, 'Kệ VT-YT 6', 3),
(22, 'Kệ VT-YT 7', 3),
(23, 'Kệ VT-YT 8', 3),
(24, 'Kệ VT-YT 9', 3);

-- --------------------------------------------------------

--
-- Table structure for table `kho`
--

CREATE TABLE `kho` (
  `id_Kho` int(11) NOT NULL,
  `tenKho` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kho`
--

INSERT INTO `kho` (`id_Kho`, `tenKho`) VALUES
(1, 'Kho CNTT'),
(3, 'Kho VT-YT'),
(4, 'Kho Quản Trị'),
(5, 'Kho TCKT'),
(7, 'Kho PKA'),
(8, 'Kho Thuốc'),
(9, 'Kho Thuốc 1'),
(10, 'Kho Thuốc 2');

-- --------------------------------------------------------

--
-- Table structure for table `loaihoso`
--

CREATE TABLE `loaihoso` (
  `id_LoaiHoSo` int(11) NOT NULL,
  `tenLoaiHoSo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `ngan`
--

CREATE TABLE `ngan` (
  `id_Ngan` int(11) NOT NULL,
  `tenNgan` varchar(255) NOT NULL,
  `id_Ke` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ngan`
--

INSERT INTO `ngan` (`id_Ngan`, `tenNgan`, `id_Ke`) VALUES
(1, 'Ngăn giữa', 8),
(6, 'Ngăn A', 14),
(11, 'szxc', 8);

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
(1, 'Quản kho', 'nghiepvu,khohang,phieuxuatnhap,phieuchuyenkho,kiemtrakho,kiemtrakho,kiemtrakho.kiemtra,dieuchinhkho,dieuchinhkho,dieuchinhkho.dieuchinh,xemkho,lichsukhohang'),
(2, 'Kinh doanh', ''),
(3, 'Kế toán', ''),
(4, 'Lãnh đạo', ''),
(5, 'Administrator', 'hethong,user,user,user.saveUser,user.deleteUser,user.phanquyen,nhomquyen,nhomquyen,nhomquyen.save,nhomquyen.delete,nhomquyen.phanquyen,backup,log,log,log.deleteLog');

-- --------------------------------------------------------

--
-- Table structure for table `taptin`
--

CREATE TABLE `taptin` (
  `id_File` int(11) NOT NULL,
  `tenFile` varchar(255) NOT NULL,
  `uploadFile` varchar(500) NOT NULL,
  `moTa` varchar(255) NOT NULL,
  `ngayTaoFile` datetime NOT NULL,
  `tenUserTaoFile` varchar(255) NOT NULL,
  `id_HoSo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `taptin`
--

INSERT INTO `taptin` (`id_File`, `tenFile`, `uploadFile`, `moTa`, `ngayTaoFile`, `tenUserTaoFile`, `id_HoSo`) VALUES
(121, '4. Kết quả thí sinh đăng ký dự tuyển viên chức.pdf', '', '', '2024-09-06 09:56:20', 'Administrator', 41),
(122, 'nurse.png', '', '', '2024-09-06 10:10:58', 'Administrator', 42),
(123, 'doctor.png', '', '', '2024-09-06 10:10:58', 'Administrator', 42),
(124, 'qrcode.png', '', '', '2024-09-06 10:10:58', 'Administrator', 42);

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
(4, 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'Administrator', '', '', '', '1', '', 0, 0, 'TZdXllZfR'),
(11, 'vietkhoi', '25f9e794323b453885f5181f1b624d0b', 'Viết Khôi', '', '', '', '1', '', 0, 0, '0dIWVf8CE');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chucnang`
--
ALTER TABLE `chucnang`
  ADD PRIMARY KEY (`maChucNang`);

--
-- Indexes for table `gia`
--
ALTER TABLE `gia`
  ADD PRIMARY KEY (`id_Gia`);

--
-- Indexes for table `hoso`
--
ALTER TABLE `hoso`
  ADD PRIMARY KEY (`id_HoSo`);

--
-- Indexes for table `ke`
--
ALTER TABLE `ke`
  ADD PRIMARY KEY (`id_Ke`);

--
-- Indexes for table `kho`
--
ALTER TABLE `kho`
  ADD PRIMARY KEY (`id_Kho`);

--
-- Indexes for table `loaihoso`
--
ALTER TABLE `loaihoso`
  ADD PRIMARY KEY (`id_LoaiHoSo`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`logID`);

--
-- Indexes for table `ngan`
--
ALTER TABLE `ngan`
  ADD PRIMARY KEY (`id_Ngan`);

--
-- Indexes for table `nhomquyen`
--
ALTER TABLE `nhomquyen`
  ADD PRIMARY KEY (`maNQ`);

--
-- Indexes for table `taptin`
--
ALTER TABLE `taptin`
  ADD PRIMARY KEY (`id_File`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chucnang`
--
ALTER TABLE `chucnang`
  MODIFY `maChucNang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `gia`
--
ALTER TABLE `gia`
  MODIFY `id_Gia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hoso`
--
ALTER TABLE `hoso`
  MODIFY `id_HoSo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `ke`
--
ALTER TABLE `ke`
  MODIFY `id_Ke` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `kho`
--
ALTER TABLE `kho`
  MODIFY `id_Kho` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `loaihoso`
--
ALTER TABLE `loaihoso`
  MODIFY `id_LoaiHoSo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=214;

--
-- AUTO_INCREMENT for table `ngan`
--
ALTER TABLE `ngan`
  MODIFY `id_Ngan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nhomquyen`
--
ALTER TABLE `nhomquyen`
  MODIFY `maNQ` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `taptin`
--
ALTER TABLE `taptin`
  MODIFY `id_File` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
