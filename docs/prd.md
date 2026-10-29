# Product Requirements Document (PRD)

## Project Name: RTSP Web CCTV Monitoring System
- **Status:** Approved / In Development
- **Owner / Author:** Project Team
- **Target Platform:** Web Application (Responsive: Desktop & Mobile)
- **Deployment Roadmap:** Phase 1 (XAMPP: PHP + MySQL + Node.js) $\rightarrow$ Phase 2 (Docker Compose)

---

## 1. Executive Summary & Objective

Sistem ini dirancang untuk menyediakan platform pemantauan CCTV berbasis web secara *real-time* (seperti pemantauan lalu lintas jalan raya, fasilitas umum, atau gedung perkantoran). 

Tantangan utama kamera CCTV modern adalah penggunaan protokol **RTSP (Real-Time Streaming Protocol)** yang tidak didukung secara *native* oleh browser modern (HTML5). Proyek ini menyelesaikan masalah tersebut dengan menyediakan **RTSP-to-Web streaming gateway** menggunakan Node.js dan stack PHP/MySQL, memungkinkan pengguna memantau live video langsung di browser manapun tanpa instalasi plugin/software pihak ketiga.

Fitur utama mencakup kontrol akses multi-role (Administrator & Observer), live streaming multi-grid, digital zoom interaktif, deteksi gerakan (*motion detection*) otomatis dengan penyimpanan snapshot ke database, serta migrasi yang siap dikemas ke Docker.

---

## 2. Target Users & Role-Based Access Control (RBAC)

| Role | Deskripsi | Hak Akses Utama |
| :--- | :--- | :--- |
| **Administrator** | Penanggung jawab teknis & operasional sistem | - Akses penuh ke seluruh fitur sistem.<br>- Manajemen akun pengguna (buat/edit/hapus Observer & Admin).<br>- Manajemen perangkat CCTV (tambah, edit nama, IP, port, path, kredensial, hapus).<br>- Konfigurasi parameter deteksi gerakan & sensitivitas.<br>- Mengakses dan mengekspor seluruh log aktivitas & log deteksi. |
| **Observer** | Petugas piket, pemantau lapangan, atau supervisor | - Melihat *live stream* CCTV (grid 1x1, 2x2, 3x3, 4x4).<br>- Menggunakan fitur *Digital Zoom* dan *Fullscreen*.<br>- Melihat galeri event dan riwayat hasil *motion detection*.<br>- Filter dan pencarian kamera berdasarkan nama/lokasi jalan raya.<br>- *Read-only* (tidak dapat mengubah konfigurasi kamera maupun akun). |

---

## 3. User Stories

1. **Sebagai Administrator**, saya ingin mendaftarkan kamera CCTV baru dengan memasukkan nama jalan/lokasi, IP address, port, path RTSP, username, dan password sehingga kamera tersebut terdaftar di sistem.
2. **Sebagai Administrator**, saya ingin mengedit atau menghapus data CCTV jika terjadi perubahan jaringan atau penggantian unit kamera.
3. **Sebagai Observer**, saya ingin membuka dashboard di browser saya dan langsung melihat live stream beberapa CCTV jalan raya secara bersamaan dalam format grid tanpa perlu menginstal plugin tambahan.
4. **Sebagai Observer**, saya ingin melakukan zoom digital pada plat nomor kendaraan atau area jalan tertentu untuk memeriksa detail kejadian secara lebih jelas.
5. **Sebagai Pengguna (Admin & Observer)**, saya ingin sistem mendeteksi pergerakan (*motion*) di area tertentu, mengambil snapshot gambar, dan mencatat waktunya ke database agar kami bisa meninjau kembali insiden di kemudian hari.

---

## 4. Functional Requirements (Kebutuhan Fungsional)

### 4.1. Modul Autentikasi & Akun
- **FR-1.1:** Halaman login aman dengan validasi username/email dan password (ter-enkripsi `bcrypt`).
- **FR-1.2:** Session management berbasis cookie/token aman dengan auto-logout setelah masa inaktif.
- **FR-1.3:** Manajemen pengguna oleh Administrator (Role Administrator vs Observer).

### 4.2. Manajemen Perangkat CCTV (CRUD)
- **FR-2.1:** **Tambah CCTV:** Form input mencakup Nama Kamera, Lokasi Jalan/Titik Pantau, Protokol (`rtsp://`), IP Address, Port (default: 554), RTSP Path (misal: `/live/ch0`), Username, Password, dan Status Aktif.
- **FR-2.2:** **Edit CCTV:** Kemampuan memperbarui konfigurasi jaringan dan kredensial kamera.
- **FR-2.3:** **Hapus CCTV:** Hapus perangkat (disertai konfirmasi keamanan) atau nonaktifkan sementara (*soft delete*).
- **FR-2.4:** **Keamanan Kredensial:** Password kamera CCTV **harus dienkripsi dua arah (AES-256)** di database MySQL agar tidak tersimpan sebagai teks biasa (*plain text*).
- **FR-2.5:** **Status Connection Check:** Indikator visual real-time status kamera (Online / Offline / Error Stream).

### 4.3. Video Streaming Gateway & Player
- **FR-3.1:** Konversi RTSP ke protokol browser-friendly (WebRTC / WHEP untuk ultra-low latency $< 1$ detik, atau fallback HLS / WebSocket-MSE).
- **FR-3.2:** Tampilan **Multi-View Grid Layout**:
  - Pilihan tata letak: 1x1 (Single focus), 2x2 (4 kamera), 3x3 (9 kamera), atau tampilan kustom.
- **FR-3.3:** Kontrol player per-kamera: Fullscreen mode, snapshot manual, tombol reload stream, dan audio mute/unmute.
- **FR-3.4:** Responsif untuk berbagai resolusi layar: Desktop, Tablet, dan Smartphone.

### 4.4. Fitur Digital Zoom & Pan
- **FR-4.1:** Fitur **Client-side Digital Zoom** pada frame video (bisa di-zoom hingga 4x–8x) menggunakan wheel mouse atau pinch-to-zoom di perangkat layar sentuh.
- **FR-4.2:** Fitur **Panning (Geser)** ketika sedang dalam keadaan zoomed-in untuk melihat sudut lain dari frame CCTV.
- **FR-4.3:** Tombol *Reset Zoom* untuk mengembalikan rasio tampilan ke ukuran normal (1x).

### 4.5. Fitur Motion Detection (Deteksi Gerakan)
- **FR-5.1:** Server-side worker (Node.js / FFmpeg) menganalisis stream frame per detik (FPS yang dapat disesuaikan) untuk mendeteksi perubahan pixel/gerakan.
- **FR-5.2:** Pengaturan sensitivitas gerakan (Low, Medium, High) per kamera oleh Administrator.
- **FR-5.3:** Ketika gerakan terdeteksi di atas batas ambang (*threshold*):
  - Sistem otomatis menangkap snapshot gambar (*JPG/PNG*).
  - Snapshot disimpan ke folder direktori storage server (misal: `/storage/captures/YYYY-MM-DD/`).
  - Metadata disimpan ke database MySQL: ID Kamera, Timestamp kejadian, Path file snapshot, Nilai confidence gerakan, dan Status terbaca.

### 4.6. Galeri Event & Log Riwayat
- **FR-6.1:** Halaman riwayat event deteksi gerakan yang dapat diakses oleh Admin dan Observer.
- **FR-6.2:** Fitur filter riwayat berdasarkan: Nama Kamera, Rentang Tanggal & Waktu.
- **FR-6.3:** Modal popup untuk melihat preview snapshot resolusi penuh dan opsi unduh (*download*).
- **FR-6.4:** Fitur penandaan *"Tandai Sudah Dilihat"* pada notifikasi gerakan baru.

---

## 5. Non-Functional Requirements (Kebutuhan Non-Fungsional)

1. **Performa & Latency:**
   - Latency streaming diharapkan $< 1.5$ detik untuk WebRTC pada jaringan lokal/LAN.
   - Streaming on-demand: Stream transcoding hanya berjalan ketika ada Observer/Admin yang sedang membuka halaman CCTV untuk menghemat resource CPU/RAM.
2. **Kompatibilitas:**
   - Dapat diakses dari browser modern: Google Chrome, Mozilla Firefox, Microsoft Edge, dan Apple Safari (tanpa plug-in khusus).
3. **Keamanan:**
   - Proteksi terhadap SQL Injection (Prepared Statements / PDO).
   - Enkripsi password akun pengguna (Bcrypt / Argon2id).
   - Enkripsi kredensial kamera CCTV (AES-256).
   - Proteksi Cross-Site Scripting (XSS) dan CSRF.
4. **Reliabilitas:**
   - Mekanisme *auto-reconnect* pada browser player jika koneksi stream RTSP sempat terputus.

---

## 6. Arsitektur & Teknologi

```
+-------------------------------------------------------------+
|                     Client Web Browser                      |
| (HTML5, Tailwind/Bootstrap, JS Player: WebRTC / Canvas Zoom)|
+-------------------------------------------------------------+
                               |
               +---------------+---------------+
               | HTTP / REST API               | WebSocket / WebRTC
               v                               v
+-------------------------------+   +-------------------------+
|     Web Backend (PHP 8.x)     |   |   Streaming & Worker    |
| - Authentication & RBAC       |   |      (Node.js / Go)     |
| - CCTV CRUD & Config          |   | - RTSP Relay / MediaMTX |
| - Motion Logs API             |   | - FFmpeg Stream Bridge  |
| - Static Asset Serving        |   | - Motion Detector Worker|
+-------------------------------+   +-------------------------+
               |                               |
               |                               | Capture Snapshots
               v                               v
+-------------------------------+   +-------------------------+
|     Database (MySQL 8.x)      |   |   Disk Storage Path     |
| - Users, Cameras, Motion Logs |   |   /storage/captures/    |
+-------------------------------+   +-------------------------+
```

### Stack Detail
* **Frontend:** HTML5, CSS3 (Tailwind CSS / modern UI), Vanilla JavaScript / React, library Panzoom (untuk digital zoom).
* **Backend Web & REST API:** PHP 8.x (Native MVC atau Micro-framework) berjalan di Apache XAMPP.
* **Database:** MySQL 8.x / MariaDB (XAMPP).
* **Streaming & Media Gateway:** Node.js (Express, `ws`, `node-media-server` / FFmpeg child processes) atau integrasi MediaMTX / go2rtc untuk konversi RTSP $\rightarrow$ WebRTC / HLS.
* **Packaging Target:** Docker & Docker Compose (`docker-compose.yml` dengan multi-container: Web PHP/Apache, MySQL, Node.js Streamer).

---

## 7. Skema Data Entitas (High-Level)

1. **`users`**
   - `id`, `username`, `email`, `password_hash`, `role` (`admin` / `observer`), `created_at`, `updated_at`.
2. **`cameras`**
   - `id`, `name`, `location`, `ip_address`, `port`, `rtsp_path`, `username`, `encrypted_password`, `status` (`online` / `offline`), `motion_detection_enabled`, `created_at`, `updated_at`.
3. **`motion_events`**
   - `id`, `camera_id` (FK), `snapshot_path`, `detected_at`, `confidence_score`, `is_viewed`, `notes`.
4. **`system_logs`**
   - `id`, `user_id` (FK), `action`, `ip_address`, `created_at`.

---

## 8. Roadmap Pengembangan

| Fase | Target Deliverable | Lingkungan |
| :--- | :--- | :--- |
| **Fase 1: Fondasi & Auth** | Setup DB MySQL, Auth PHP (Admin & Observer), CRUD CCTV dengan enkripsi kredensial kamera. | XAMPP |
| **Fase 2: Streaming Gateway** | Setup Node.js RTSP bridge, web player live view, layout grid (1x1, 2x2, 3x3), dan fitur Digital Zoom. | XAMPP + Node.js |
| **Fase 3: Motion Detection** | Worker deteksi gerakan via FFmpeg/Node.js, auto-capture snapshot ke disk, logging ke DB, galeri riwayat event. | XAMPP + Node.js |
| **Fase 4: Dockerization & Hardening** | Pembuatan `Dockerfile` & `docker-compose.yml`, uji coba multi-container, optimasi resource stream, dan audit keamanan. | Docker |
