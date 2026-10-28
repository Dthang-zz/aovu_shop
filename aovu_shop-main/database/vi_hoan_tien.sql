-- =====================================================
-- Tạo bảng Ví hoàn tiền và Giao dịch ví
-- Chạy trực tiếp trên phpMyAdmin hoặc MySQL CLI
-- =====================================================

-- Bảng ví hoàn tiền (mỗi khách hàng 1 ví)
CREATE TABLE `vi_hoan_tiens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tai_khoan_id` int unsigned NOT NULL,
  `so_du` decimal(15,0) NOT NULL DEFAULT '0',
  `trang_thai` tinyint NOT NULL DEFAULT '1' COMMENT '1=Hoạt động, 0=Đã khóa',
  `ngay_tao` datetime DEFAULT NULL,
  `ngay_cap_nhat` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vi_hoan_tiens_tai_khoan_id_unique` (`tai_khoan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lịch sử giao dịch ví
CREATE TABLE `giao_dich_vis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vi_id` bigint unsigned NOT NULL,
  `loai_giao_dich` tinyint NOT NULL COMMENT '1=Hoàn tiền vào ví, 2=Sử dụng từ ví, 3=Điều chỉnh thủ công',
  `so_tien` decimal(15,0) NOT NULL,
  `so_du_truoc` decimal(15,0) NOT NULL DEFAULT '0',
  `so_du_sau` decimal(15,0) NOT NULL DEFAULT '0',
  `don_hang_id` int unsigned DEFAULT NULL,
  `yeu_cau_doi_tra_id` int unsigned DEFAULT NULL,
  `ghi_chu` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nguoi_thuc_hien_id` int unsigned DEFAULT NULL COMMENT 'Admin thực hiện',
  `ngay_tao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `giao_dich_vis_vi_id_foreign` (`vi_id`),
  CONSTRAINT `giao_dich_vis_vi_id_foreign` FOREIGN KEY (`vi_id`) REFERENCES `vi_hoan_tiens` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Dữ liệu mẫu (tuỳ chọn - xoá phần này nếu không cần)
-- =====================================================

-- Giả sử đã có tài khoản với id = 1, 2, 3 trong bảng tai_khoans
-- Bỏ comment các dòng INSERT bên dưới nếu muốn test

-- INSERT INTO `vi_hoan_tiens` (`tai_khoan_id`, `so_du`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
-- (1, 250000, 1, '2026-10-20 10:00:00', '2026-10-28 08:30:00'),
-- (2, 500000, 1, '2026-10-22 14:00:00', '2026-10-27 16:45:00'),
-- (3, 0, 0, '2026-10-25 09:00:00', '2026-10-26 11:00:00');

-- INSERT INTO `giao_dich_vis` (`vi_id`, `loai_giao_dich`, `so_tien`, `so_du_truoc`, `so_du_sau`, `don_hang_id`, `ghi_chu`, `ngay_tao`) VALUES
-- (1, 1, 300000, 0, 300000, NULL, 'Hoàn tiền đơn hàng bị huỷ', '2026-10-20 10:00:00'),
-- (1, 2, -50000, 300000, 250000, NULL, 'Sử dụng thanh toán đơn mới', '2026-10-28 08:30:00'),
-- (2, 1, 500000, 0, 500000, NULL, 'Hoàn tiền yêu cầu trả hàng #DT-000001', '2026-10-22 14:00:00');
