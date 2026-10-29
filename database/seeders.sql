USE `rstp_cctv_db`;

-- Default Users
-- admin: admin123
-- observer: observer123
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`) VALUES
('admin', 'admin@cctv.local', '$2y$10$H/1FmlW9DESnulC3twXa2ejA3blKF6hE3PxGg/PqKs9BaUb8R6cMS', 'admin', 1),
('observer', 'observer@cctv.local', '$2y$10$cuXiN.kfm8O.uZ8ns51TFOAcQkWeJpf6DD9RiHGx9yFCTtQVGf4Pm', 'observer', 1)
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`), `updated_at` = NOW();

-- Default sample cameras (road monitoring)
INSERT INTO `cameras` (`name`, `location`, `ip_address`, `port`, `rtsp_path`, `username`, `status`, `motion_enabled`) VALUES
('CCTV 01 - Simpang Sudirman', 'Jl. Jend. Sudirman KM 2', '192.168.1.101', 554, '/live/ch0', 'admin', 'online', 1),
('CCTV 02 - Flyover Thamrin', 'Flyover MH Thamrin Arah Utara', '192.168.1.102', 554, '/live/ch0', 'admin', 'online', 1),
('CCTV 03 - Bundaran HI Timur', 'Pos Bundaran HI Sisi Timur', '192.168.1.103', 554, '/live/ch0', 'admin', 'offline', 1),
('CCTV 04 - Simpang Kuningan', 'Jl. HR Rasuna Said', '192.168.1.104', 554, '/live/ch0', 'admin', 'online', 0)
ON DUPLICATE KEY UPDATE `updated_at` = NOW();
