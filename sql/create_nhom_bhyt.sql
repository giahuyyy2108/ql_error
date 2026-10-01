CREATE TABLE IF NOT EXISTS `nhom_BHYT` (
    `id` VARCHAR(10) NOT NULL,
    `ten` VARCHAR(255) NOT NULL,
    `mota` TEXT NOT NULL,
    `dien` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_nhom_bhyt_dien` (`dien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `nhom_BHYT` (`id`, `ten`, `mota`, `dien`) VALUES
(
    'DN',
    'Người lao động tại doanh nghiệp',
    'Người lao động làm việc trong các doanh nghiệp thành lập, hoạt động theo Luật Doanh nghiệp (https://thuvienphapluat.vn/van-ban/Doanh-nghiep/Luat-Doanh-nghiep-so-59-2020-QH14-427301.aspx) và Luật Đầu tư (https://thuvienphapluat.vn/van-ban/Doanh-nghiep/Luat-Dau-tu-so-61-2020-QH14-321051.aspx).',
    4
),
(
    'HX',
    'Người lao động tại hợp tác xã',
    'Người lao động làm việc trong các hợp tác xã, liên hiệp hợp tác xã thành lập và hoạt động theo Luật Hợp tác xã (https://thuvienphapluat.vn/van-ban/Doanh-nghiep/Luat-Hop-tac-xa-499239.aspx).',
    4
),
(
    'CH',
    'Người lao động tại cơ quan, tổ chức',
    'Người lao động làm việc trong các cơ quan nhà nước, đơn vị sự nghiệp, lực lượng vũ trang, tổ chức chính trị, tổ chức chính trị - xã hội, tổ chức chính trị - xã hội - nghề nghiệp, tổ chức xã hội - nghề nghiệp và tổ chức xã hội khác.',
    4
),
(
    'HT',
    'Người hưởng lương hưu, trợ cấp mất sức',
    'Người hưởng lương hưu, trợ cấp mất sức lao động hàng tháng.',
    3
),
(
    'TB',
    'Người hưởng trợ cấp tai nạn lao động, bệnh nghề nghiệp',
    'Người đang hưởng trợ cấp bảo hiểm xã hội hàng tháng do bị tai nạn lao động, bệnh nghề nghiệp.',
    4
),
(
    'NO',
    'Người nghỉ việc hưởng chế độ ốm đau dài ngày',
    'Người lao động nghỉ việc đang hưởng chế độ ốm đau theo quy định của pháp luật về bảo hiểm xã hội do mắc bệnh thuộc danh mục bệnh cần chữa trị dài ngày theo quy định của Bộ trưởng Bộ Y tế.',
    4
),
(
    'QN',
    'Quân nhân và người làm công tác cơ yếu',
    'Sỹ quan, quân nhân chuyên nghiệp, hạ sỹ quan, binh sỹ Quân đội nhân dân Việt Nam đang tại ngũ; người làm công tác cơ yếu hưởng lương như đối với quân nhân đang công tác tại Ban Cơ yếu Chính phủ; học viên cơ yếu hưởng sinh hoạt phí từ ngân sách Nhà nước theo chế độ, chính sách như đối với học viên Quân đội.',
    5
),
(
    'CA',
    'Công an nhân dân',
    'Sỹ quan, hạ sỹ quan nghiệp vụ và sỹ quan, hạ sỹ quan chuyên môn kỹ thuật, hạ sỹ quan, chiến sỹ nghĩa vụ đang công tác trong lực lượng công an nhân dân, học viên công an nhân dân hưởng sinh hoạt phí từ ngân sách Nhà nước.',
    5
),
(
    'CN',
    'Người thuộc hộ gia đình cận nghèo',
    'Người thuộc hộ gia đình cận nghèo.',
    3
),
(
    'HS',
    'Học sinh',
    'Học sinh đang theo học tại các cơ sở giáo dục và đào tạo thuộc hệ thống giáo dục quốc dân.',
    4
),
(
    'SV',
    'Sinh viên',
    'Sinh viên đang theo học tại các cơ sở giáo dục và đào tạo, cơ sở dạy nghề thuộc hệ thống giáo dục quốc dân.',
    4
),
(
    'GB',
    'Hộ làm nông, lâm, ngư nghiệp và diêm nghiệp',
    'Người thuộc hộ gia đình làm nông nghiệp, lâm nghiệp, ngư nghiệp và diêm nghiệp có mức sống trung bình theo quy định của pháp luật.',
    4
),
(
    'GD',
    'Người tham gia BHYT theo hộ gia đình',
    'Người tham gia BHYT theo hộ gia đình gồm những người thuộc hộ gia đình, trừ đối tượng quy định tại các điểm a, b, c, d nêu trên.',
    4
)
ON DUPLICATE KEY UPDATE
    `ten` = VALUES(`ten`),
    `mota` = VALUES(`mota`),
    `dien` = VALUES(`dien`);
