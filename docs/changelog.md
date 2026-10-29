# Changelog

Semua perubahan dan pembaruan arsitektural proyek dicatat di sini.

## [1.0.0] - 2026-10-29

### Ditambahkan (Initial Specification Suite)
- **PRD ([docs/prd.md](file:///d:/xampp/htdocs/rstp/docs/prd.md)):** Perumusan kebutuhan produk lengkap (User Persona, Role Matrix Admin & Observer, Functional Requirements, Non-Functional Requirements, dan Roadmap).
- **TRD ([docs/TRD.md](file:///d:/xampp/htdocs/rstp/docs/TRD.md)):** Spesifikasi arsitektur teknis hibrida PHP 8.x + Node.js Streaming Gateway, on-demand streaming, dan struktur folder.
- **Database Architecture ([docs/database.md](file:///d:/xampp/htdocs/rstp/docs/database.md)):** Diagram ERD Mermaid, data dictionary detail, serta script SQL DDL & Seeder siap eksekusi.
- **System Architecture ([docs/architecture.md](file:///d:/xampp/htdocs/rstp/docs/architecture.md)):** Diagram alur data end-to-end, transmuxing FFmpeg, sequence diagram autentikasi dan live streaming.
- **UI/UX Design ([docs/ui-ux.md](file:///d:/xampp/htdocs/rstp/docs/ui-ux.md)):** Panduan desain tema gelap NOC Traffic Center, layout grid responsif (1x1, 2x2, 3x3), kontrol digital zoom, dan galeri motion alert.
- **Security Policies ([docs/SECURITY.md](file:///d:/xampp/htdocs/rstp/docs/SECURITY.md)):** Enkripsi dua arah AES-256 untuk kredensial kamera, hashing password akun Bcrypt, stream masking, CSRF & XSS protection.
- **API Documentation ([docs/api.md](file:///d:/xampp/htdocs/rstp/docs/api.md)):** Spesifikasi lengkap REST API untuk autentikasi, CRUD CCTV, motion log, inter-service bridge, dan WebSocket protocol.
- **Operational Rules ([docs/rules.md](file:///d:/xampp/htdocs/rstp/docs/rules.md)):** Standar PSR-12, aturan async Node.js, kebersihan Git, dan keamanan proses FFmpeg.
- **Troubleshooting Guide ([docs/TROUBLESHOOTING.md](file:///d:/xampp/htdocs/rstp/docs/TROUBLESHOOTING.md)):** Panduan pemecahan masalah koneksi RTSP, FFmpeg, WebSocket, dan database.
- **Deployment Guide ([docs/deployment.md](file:///d:/xampp/htdocs/rstp/docs/deployment.md)):** Panduan setup lokal XAMPP + Node.js serta konfigurasi target `docker-compose.yml`.
- **AFD ([docs/AFD.md](file:///d:/xampp/htdocs/rstp/docs/AFD.md)):** Alur navigasi sistem, diagram status siklus hidup kamera, dan urutan deteksi gerakan.
- **BDS ([docs/BDS.md](file:///d:/xampp/htdocs/rstp/docs/BDS.md)):** Desain arsitektur MVC PHP, middleware, koneksi database PDO, dan arsitektur stream manager.
- **DBD ([docs/DBD.md](file:///d:/xampp/htdocs/rstp/docs/DBD.md)):** Normalisasi data 3NF, strategi composite index, dan kebijakan retensi data 30 hari.
- **IPD ([docs/IPD.md](file:///d:/xampp/htdocs/rstp/docs/IPD.md)):** Work Breakdown Structure (WBS) 4 milestone dan kriteria penerimaan proyek.


## [1.1.0] - 2026-10-29

### Ditambahkan & Ditingkatkan (Enterprise 100+ CCTV Scale & Modern Stack)
- **Spesifikasi 100+ CCTV Streaming Engine ([docs/TRD.md](file:///d:/xampp/htdocs/rstp/docs/TRD.md#6-spesifikasi-sistem-skala-besar-100-cctv-streaming-engine)):** Ditambahkan kalkulasi beban bandwidth ingest (350 Mbps main vs 40 Mbps sub) & egress, spesifikasi hardware server minimal vs rekomendasi (CPU multi-core, ECC RAM, NVMe, Dual 10 Gbps SFP+), spesifikasi PC operator pemantau, dan 7 pilar arsitektur *Zero-Lag*.
- **Optimasi Server & Kernel Host ([docs/deployment.md](file:///d:/xampp/htdocs/rstp/docs/deployment.md#3-optimasi-server--kernel-os-untuk-skala-100-cctv)):** Dokumentasi tuning `sysctl.conf` Linux untuk buffer socket TCP/UDP, antrean `somaxconn`, batas `nofile` 65535, dan alokasi resource Docker.
- **Dukungan PHP 8.5 & Port 2020:** Konfigurasi Dockerfile berbasis `php:8.5-rc-apache` dan port web host `2020:80`.
- **Integrasi Redis Cache:** Implementasi `RedisCache` singleton untuk query metadata kamera, struktur grup/lantai, dan unread count log gerak dengan sistem invalidasi otomatis.
- **Compound Database Indexing:** Penambahan index komposit `idx_cameras_group_floor`, `idx_motion_status_date`, dan `idx_logs_user_date` untuk respon query $< 2\text{ms}$.
- **Perbaikan Dropdown High-Contrast:** Penambahan `<meta name="color-scheme" content="dark">` dan `:root { color-scheme: dark; }` untuk mengatasi bug native popup list di Chromium Windows.
