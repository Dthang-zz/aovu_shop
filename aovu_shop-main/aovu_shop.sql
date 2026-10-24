-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2026 at 04:21 AM
-- Server version: 8.0.30
-- PHP Version: 8.5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aovu_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `bien_thes`
--

CREATE TABLE `bien_thes` (
  `id` int NOT NULL,
  `san_pham_id` int DEFAULT NULL,
  `mau_sac_id` int DEFAULT NULL,
  `kich_thuoc_id` int DEFAULT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gia_bien_the` decimal(12,0) DEFAULT NULL,
  `so_luong_ton` int DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bo_suu_taps`
--

CREATE TABLE `bo_suu_taps` (
  `id` int NOT NULL,
  `ten_bo_suu_tap` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hinh_anh` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hinh_banner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mo_ta` text COLLATE utf8mb4_unicode_ci,
  `thu_tu` int DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chi_tiet_don_hangs`
--

CREATE TABLE `chi_tiet_don_hangs` (
  `id` int NOT NULL,
  `don_hang_id` int DEFAULT NULL,
  `bien_the_id` int DEFAULT NULL,
  `ten_san_pham` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `don_gia` decimal(12,0) DEFAULT NULL,
  `so_luong` int DEFAULT NULL,
  `thanh_tien` decimal(12,0) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chuc_vus`
--

CREATE TABLE `chuc_vus` (
  `id` int NOT NULL,
  `ten_chuc_vu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mo_ta` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `danh_gias`
--

CREATE TABLE `danh_gias` (
  `id` int NOT NULL,
  `san_pham_id` int DEFAULT NULL,
  `tai_khoan_id` int DEFAULT NULL,
  `chi_tiet_don_hang_id` int DEFAULT NULL,
  `so_sao` tinyint DEFAULT NULL,
  `noi_dung` text COLLATE utf8mb4_unicode_ci,
  `ngay_dang` datetime DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `danh_mucs`
--

CREATE TABLE `danh_mucs` (
  `id` int NOT NULL,
  `ten_danh_muc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `danh_muc_cha_id` int DEFAULT NULL,
  `mo_ta` text COLLATE utf8mb4_unicode_ci,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dia_chis`
--

CREATE TABLE `dia_chis` (
  `id` int NOT NULL,
  `tai_khoan_id` int DEFAULT NULL,
  `ten_nguoi_nhan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sdt_nguoi_nhan` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tinh_thanh` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quan_huyen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phuong_xa` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dia_chi_chi_tiet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_dinh` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `don_hangs`
--

CREATE TABLE `don_hangs` (
  `id` int NOT NULL,
  `ma_don_hang` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tai_khoan_id` int DEFAULT NULL,
  `ten_nguoi_nhan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_nguoi_nhan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sdt_nguoi_nhan` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dia_chi_nguoi_nhan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ngay_dat` datetime DEFAULT NULL,
  `tien_hang` decimal(12,0) DEFAULT NULL,
  `phi_van_chuyen` decimal(12,0) DEFAULT NULL,
  `voucher_id` int DEFAULT NULL,
  `tien_giam` decimal(12,0) DEFAULT NULL,
  `tong_tien` decimal(12,0) DEFAULT NULL,
  `ghi_chu` text COLLATE utf8mb4_unicode_ci,
  `phuong_thuc_thanh_toan_id` int DEFAULT NULL,
  `trang_thai_thanh_toan` tinyint DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gio_hangs`
--

CREATE TABLE `gio_hangs` (
  `id` int NOT NULL,
  `tai_khoan_id` int DEFAULT NULL,
  `bien_the_id` int DEFAULT NULL,
  `so_luong` int DEFAULT NULL,
  `ngay_cap_nhat` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hinh_anh_san_phams`
--

CREATE TABLE `hinh_anh_san_phams` (
  `id` int NOT NULL,
  `san_pham_id` int DEFAULT NULL,
  `mau_sac_id` int DEFAULT NULL,
  `link_hinh_anh` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thu_tu` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kich_thuocs`
--

CREATE TABLE `kich_thuocs` (
  `id` int NOT NULL,
  `ten_kich_thuoc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chieu_cao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `can_nang` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `so_do_ba_vong` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thu_tu` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lich_su_trang_thai_don_hangs`
--

CREATE TABLE `lich_su_trang_thai_don_hangs` (
  `id` int NOT NULL,
  `don_hang_id` int DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL,
  `ghi_chu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nguoi_cap_nhat_id` int DEFAULT NULL,
  `ngay_cap_nhat` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mau_sacs`
--

CREATE TABLE `mau_sacs` (
  `id` int NOT NULL,
  `ten_mau` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ma_mau` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phuong_thuc_thanh_toans`
--

CREATE TABLE `phuong_thuc_thanh_toans` (
  `id` int NOT NULL,
  `ten_phuong_thuc` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `san_phams`
--

CREATE TABLE `san_phams` (
  `id` int NOT NULL,
  `ma_san_pham` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ten_san_pham` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gia_san_pham` decimal(12,0) DEFAULT NULL,
  `gia_khuyen_mai` decimal(12,0) DEFAULT NULL,
  `hinh_anh` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mo_ta` text COLLATE utf8mb4_unicode_ci,
  `luot_xem` int DEFAULT NULL,
  `da_ban` int DEFAULT NULL,
  `noi_bat` tinyint DEFAULT NULL,
  `ngay_nhap` datetime DEFAULT NULL,
  `khuyen_mai_bat_dau` datetime DEFAULT NULL,
  `khuyen_mai_ket_thuc` datetime DEFAULT NULL,
  `danh_muc_id` int DEFAULT NULL,
  `bo_suu_tap_id` int DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tai_khoans`
--

CREATE TABLE `tai_khoans` (
  `id` int NOT NULL,
  `ho_ten` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anh_dai_dien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `so_dien_thoai` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gioi_tinh` tinyint DEFAULT NULL,
  `mat_khau` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chuc_vu_id` int DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL,
  `ngay_tao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int NOT NULL,
  `ma_voucher` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mo_ta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loai_giam` tinyint DEFAULT NULL,
  `gia_tri_giam` decimal(12,0) DEFAULT NULL,
  `giam_toi_da` decimal(12,0) DEFAULT NULL,
  `don_toi_thieu` decimal(12,0) DEFAULT NULL,
  `so_luong` int DEFAULT NULL,
  `da_su_dung` int DEFAULT NULL,
  `ngay_bat_dau` datetime DEFAULT NULL,
  `ngay_ket_thuc` datetime DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `yeu_cau_doi_tras`
--

CREATE TABLE `yeu_cau_doi_tras` (
  `id` int NOT NULL,
  `don_hang_id` int DEFAULT NULL,
  `chi_tiet_don_hang_id` int DEFAULT NULL,
  `tai_khoan_id` int DEFAULT NULL,
  `loai_yeu_cau` tinyint DEFAULT NULL,
  `so_luong` int DEFAULT NULL,
  `ly_do` text COLLATE utf8mb4_unicode_ci,
  `hinh_anh` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phan_hoi` text COLLATE utf8mb4_unicode_ci,
  `ngay_tao` datetime DEFAULT NULL,
  `trang_thai` tinyint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `yeu_thichs`
--

CREATE TABLE `yeu_thichs` (
  `id` int NOT NULL,
  `tai_khoan_id` int DEFAULT NULL,
  `san_pham_id` int DEFAULT NULL,
  `ngay_them` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bien_thes`
--
ALTER TABLE `bien_thes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `san_pham_id` (`san_pham_id`),
  ADD KEY `mau_sac_id` (`mau_sac_id`),
  ADD KEY `kich_thuoc_id` (`kich_thuoc_id`);

--
-- Indexes for table `bo_suu_taps`
--
ALTER TABLE `bo_suu_taps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chi_tiet_don_hangs`
--
ALTER TABLE `chi_tiet_don_hangs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `don_hang_id` (`don_hang_id`),
  ADD KEY `bien_the_id` (`bien_the_id`);

--
-- Indexes for table `chuc_vus`
--
ALTER TABLE `chuc_vus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `danh_gias`
--
ALTER TABLE `danh_gias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `san_pham_id` (`san_pham_id`),
  ADD KEY `tai_khoan_id` (`tai_khoan_id`),
  ADD KEY `chi_tiet_don_hang_id` (`chi_tiet_don_hang_id`);

--
-- Indexes for table `danh_mucs`
--
ALTER TABLE `danh_mucs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `danh_muc_cha_id` (`danh_muc_cha_id`);

--
-- Indexes for table `dia_chis`
--
ALTER TABLE `dia_chis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tai_khoan_id` (`tai_khoan_id`);

--
-- Indexes for table `don_hangs`
--
ALTER TABLE `don_hangs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_don_hang` (`ma_don_hang`),
  ADD KEY `tai_khoan_id` (`tai_khoan_id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `phuong_thuc_thanh_toan_id` (`phuong_thuc_thanh_toan_id`);

--
-- Indexes for table `gio_hangs`
--
ALTER TABLE `gio_hangs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tai_khoan_id` (`tai_khoan_id`),
  ADD KEY `bien_the_id` (`bien_the_id`);

--
-- Indexes for table `hinh_anh_san_phams`
--
ALTER TABLE `hinh_anh_san_phams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `san_pham_id` (`san_pham_id`),
  ADD KEY `mau_sac_id` (`mau_sac_id`);

--
-- Indexes for table `kich_thuocs`
--
ALTER TABLE `kich_thuocs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lich_su_trang_thai_don_hangs`
--
ALTER TABLE `lich_su_trang_thai_don_hangs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `don_hang_id` (`don_hang_id`),
  ADD KEY `nguoi_cap_nhat_id` (`nguoi_cap_nhat_id`);

--
-- Indexes for table `mau_sacs`
--
ALTER TABLE `mau_sacs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phuong_thuc_thanh_toans`
--
ALTER TABLE `phuong_thuc_thanh_toans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `san_phams`
--
ALTER TABLE `san_phams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_san_pham` (`ma_san_pham`),
  ADD KEY `danh_muc_id` (`danh_muc_id`),
  ADD KEY `bo_suu_tap_id` (`bo_suu_tap_id`);

--
-- Indexes for table `tai_khoans`
--
ALTER TABLE `tai_khoans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `chuc_vu_id` (`chuc_vu_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_voucher` (`ma_voucher`);

--
-- Indexes for table `yeu_cau_doi_tras`
--
ALTER TABLE `yeu_cau_doi_tras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `don_hang_id` (`don_hang_id`),
  ADD KEY `chi_tiet_don_hang_id` (`chi_tiet_don_hang_id`),
  ADD KEY `tai_khoan_id` (`tai_khoan_id`);

--
-- Indexes for table `yeu_thichs`
--
ALTER TABLE `yeu_thichs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tai_khoan_id` (`tai_khoan_id`),
  ADD KEY `san_pham_id` (`san_pham_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bien_thes`
--
ALTER TABLE `bien_thes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bo_suu_taps`
--
ALTER TABLE `bo_suu_taps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chi_tiet_don_hangs`
--
ALTER TABLE `chi_tiet_don_hangs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chuc_vus`
--
ALTER TABLE `chuc_vus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danh_gias`
--
ALTER TABLE `danh_gias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `danh_mucs`
--
ALTER TABLE `danh_mucs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dia_chis`
--
ALTER TABLE `dia_chis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `don_hangs`
--
ALTER TABLE `don_hangs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gio_hangs`
--
ALTER TABLE `gio_hangs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hinh_anh_san_phams`
--
ALTER TABLE `hinh_anh_san_phams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kich_thuocs`
--
ALTER TABLE `kich_thuocs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lich_su_trang_thai_don_hangs`
--
ALTER TABLE `lich_su_trang_thai_don_hangs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mau_sacs`
--
ALTER TABLE `mau_sacs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `phuong_thuc_thanh_toans`
--
ALTER TABLE `phuong_thuc_thanh_toans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `san_phams`
--
ALTER TABLE `san_phams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tai_khoans`
--
ALTER TABLE `tai_khoans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `yeu_cau_doi_tras`
--
ALTER TABLE `yeu_cau_doi_tras`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `yeu_thichs`
--
ALTER TABLE `yeu_thichs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bien_thes`
--
ALTER TABLE `bien_thes`
  ADD CONSTRAINT `bien_thes_ibfk_1` FOREIGN KEY (`san_pham_id`) REFERENCES `san_phams` (`id`),
  ADD CONSTRAINT `bien_thes_ibfk_2` FOREIGN KEY (`mau_sac_id`) REFERENCES `mau_sacs` (`id`),
  ADD CONSTRAINT `bien_thes_ibfk_3` FOREIGN KEY (`kich_thuoc_id`) REFERENCES `kich_thuocs` (`id`);

--
-- Constraints for table `chi_tiet_don_hangs`
--
ALTER TABLE `chi_tiet_don_hangs`
  ADD CONSTRAINT `chi_tiet_don_hangs_ibfk_1` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hangs` (`id`),
  ADD CONSTRAINT `chi_tiet_don_hangs_ibfk_2` FOREIGN KEY (`bien_the_id`) REFERENCES `bien_thes` (`id`);

--
-- Constraints for table `danh_gias`
--
ALTER TABLE `danh_gias`
  ADD CONSTRAINT `danh_gias_ibfk_1` FOREIGN KEY (`san_pham_id`) REFERENCES `san_phams` (`id`),
  ADD CONSTRAINT `danh_gias_ibfk_2` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoans` (`id`),
  ADD CONSTRAINT `danh_gias_ibfk_3` FOREIGN KEY (`chi_tiet_don_hang_id`) REFERENCES `chi_tiet_don_hangs` (`id`);

--
-- Constraints for table `danh_mucs`
--
ALTER TABLE `danh_mucs`
  ADD CONSTRAINT `danh_mucs_ibfk_1` FOREIGN KEY (`danh_muc_cha_id`) REFERENCES `danh_mucs` (`id`);

--
-- Constraints for table `dia_chis`
--
ALTER TABLE `dia_chis`
  ADD CONSTRAINT `dia_chis_ibfk_1` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoans` (`id`);

--
-- Constraints for table `don_hangs`
--
ALTER TABLE `don_hangs`
  ADD CONSTRAINT `don_hangs_ibfk_1` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoans` (`id`),
  ADD CONSTRAINT `don_hangs_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  ADD CONSTRAINT `don_hangs_ibfk_3` FOREIGN KEY (`phuong_thuc_thanh_toan_id`) REFERENCES `phuong_thuc_thanh_toans` (`id`);

--
-- Constraints for table `gio_hangs`
--
ALTER TABLE `gio_hangs`
  ADD CONSTRAINT `gio_hangs_ibfk_1` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoans` (`id`),
  ADD CONSTRAINT `gio_hangs_ibfk_2` FOREIGN KEY (`bien_the_id`) REFERENCES `bien_thes` (`id`);

--
-- Constraints for table `hinh_anh_san_phams`
--
ALTER TABLE `hinh_anh_san_phams`
  ADD CONSTRAINT `hinh_anh_san_phams_ibfk_1` FOREIGN KEY (`san_pham_id`) REFERENCES `san_phams` (`id`),
  ADD CONSTRAINT `hinh_anh_san_phams_ibfk_2` FOREIGN KEY (`mau_sac_id`) REFERENCES `mau_sacs` (`id`);

--
-- Constraints for table `lich_su_trang_thai_don_hangs`
--
ALTER TABLE `lich_su_trang_thai_don_hangs`
  ADD CONSTRAINT `lich_su_trang_thai_don_hangs_ibfk_1` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hangs` (`id`),
  ADD CONSTRAINT `lich_su_trang_thai_don_hangs_ibfk_2` FOREIGN KEY (`nguoi_cap_nhat_id`) REFERENCES `tai_khoans` (`id`);

--
-- Constraints for table `san_phams`
--
ALTER TABLE `san_phams`
  ADD CONSTRAINT `san_phams_ibfk_1` FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_mucs` (`id`),
  ADD CONSTRAINT `san_phams_ibfk_2` FOREIGN KEY (`bo_suu_tap_id`) REFERENCES `bo_suu_taps` (`id`);

--
-- Constraints for table `tai_khoans`
--
ALTER TABLE `tai_khoans`
  ADD CONSTRAINT `tai_khoans_ibfk_1` FOREIGN KEY (`chuc_vu_id`) REFERENCES `chuc_vus` (`id`);

--
-- Constraints for table `yeu_cau_doi_tras`
--
ALTER TABLE `yeu_cau_doi_tras`
  ADD CONSTRAINT `yeu_cau_doi_tras_ibfk_1` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hangs` (`id`),
  ADD CONSTRAINT `yeu_cau_doi_tras_ibfk_2` FOREIGN KEY (`chi_tiet_don_hang_id`) REFERENCES `chi_tiet_don_hangs` (`id`),
  ADD CONSTRAINT `yeu_cau_doi_tras_ibfk_3` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoans` (`id`);

--
-- Constraints for table `yeu_thichs`
--
ALTER TABLE `yeu_thichs`
  ADD CONSTRAINT `yeu_thichs_ibfk_1` FOREIGN KEY (`tai_khoan_id`) REFERENCES `tai_khoans` (`id`),
  ADD CONSTRAINT `yeu_thichs_ibfk_2` FOREIGN KEY (`san_pham_id`) REFERENCES `san_phams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
