# Technical Requirements Document (TRD)

## Project: RTSP Web CCTV Monitoring System
- **Architecture Type:** Hybrid Monolith + Streaming Microservice (PHP/MySQL + Node.js)
- **Runtime Environment:** 
  - Development: Local XAMPP (Apache, PHP 8.1+, MySQL 8.0/MariaDB) + Node.js (v18+)
  - Production/Target: Containerized Docker (Docker Compose)
- **Status:** Approved / Specification Ready

---

## 1. System Architecture Overview

Sistem menggunakan pendekatan arsitektur terpisah antara **Web & Business Logic Service (PHP)** dan **Media Streaming & Detection Service (Node.js)** untuk memastikan performa tinggi dan isolasi beban kerja I/O intensif:

```
[ CCTV / IP Cameras (RTSP) ]
         |
         | RTSP (TCP/UDP, Port 554)
         v
+-------------------------------------------------------------+
|               Node.js Media Gateway Service                 |
|  - RTSP Ingestion & Connection Pool                         |
|  - FFmpeg Transcoder (RTSP -> WebRTC / WebSocket-MSE / HLS) |
|  - Motion Detector Worker (Pixel Frame-Diff Engine)         |
|  - Snapshot Generator (Local Storage Writer)                |
+-------------------------------------------------------------+
         |                                |
         | WebRTC / WebSocket Stream      | HTTP Webhook / REST Call
         v                                v
[ Client Web Browser ] <---------> [ PHP 8.x Web Application ]
  - HTML5 Video Player               - Session & RBAC Auth
  - Panzoom Digital Zoom             - CCTV CRUD & AES-256 Crypto
  - Grid View (1x1, 2x2, 3x3)        - Motion Event History API
  - Event Notification Popups        - MySQL Database Connector
                                          |
                                          v
                                 [ MySQL Database ]
                                   - Users, Cameras, Logs
```

---

## 2. Technology Stack & Dependencies

### 2.1. Backend Web Application (PHP)
- **Language:** PHP 8.1 or higher
- **Web Server:** Apache (via XAMPP) / Nginx (via Docker)
- **Database Driver:** PHP Data Objects (PDO) with prepared statements
- **Security & Crypto:** `openssl_encrypt` / `openssl_decrypt` (AES-256-CBC) untuk kredensial kamera, `password_hash` (`PASSWORD_BCRYPT` / `ARGON2ID`) untuk password user.
- **Data Serialization:** JSON RESTful Endpoints.

### 2.2. Media Streaming & Motion Engine (Node.js)
- **Runtime:** Node.js v18.x LTS or higher
- **Core Packages:**
  - `express`: REST API internal & static media server.
  - `ws`: WebSocket server untuk signaling WebRTC atau direct video frame relay.
  - `fluent-ffmpeg` / raw `child_process.spawn('ffmpeg')`: Hardware-accelerated / software RTSP remuxing & snapshot extraction.
  - `node-cron` / interval timers: Health check pings ke kamera RTSP.
  - `mysql2/promise` atau Axios REST bridge ke PHP backend: Pencatatan event motion detection secara asinkron.

### 2.3. Frontend Client
- **HTML5 & CSS3:** Responsive Grid UI (Flexbox / CSS Grid), Tailwind CSS atau modern utility CSS.
- **Video Renderer:**
  - Opsi A (Ultra-low latency $< 1$s): WebRTC WHEP / MSE (Media Source Extensions) via WebSocket.
  - Opsi B (Universal Fallback): JSMpeg / HLS.js.
- **Interactivity:**
  - `panzoom` library (atau Native HTML5 Canvas 2D Transform) untuk fluid digital zoom & drag panning.
  - Fetch API untuk AJAX polling / Server-Sent Events (SSE) notifikasi motion detection.

### 2.4. Database (MySQL 8.0+ / MariaDB 10.4+)
- **Engine:** InnoDB (Full ACID compliance, Foreign Keys).
- **Charset:** `utf8mb4` with collation `utf8mb4_unicode_ci`.

---

## 3. Detailed Component Specifications

### 3.1. Authentication & Security Engine
1. **User Authentication:**
   - Session-based auth dengan `HttpOnly`, `SameSite=Lax`, dan `Secure` flag.
   - Brute-force throttling: Max 5 percobaan login gagal per IP/username dalam 15 menit.
2. **Kredensial Kamera CCTV:**
   - Password RTSP disimpan dalam format ciphertext hex/base64 di database.
   - Menggunakan algoritma `AES-256-CBC` dengan `APP_KEY` dan `IV` acak 16-byte yang disimpan per-record atau di server environment.

### 3.2. RTSP Ingestion & Conversion Pipeline
1. **RTSP URL Construction:**
   ```
   rtsp://[username]:[password]@[ip_address]:[port][path]
   Contoh: rtsp://admin:SecretPass123@192.168.1.108:554/live/ch0
   ```
2. **On-Demand Streaming:**
   - Stream FFmpeg tidak dijalankan terus-menerus untuk semua kamera guna mencegah overload CPU/RAM.
   - Stream hanya aktif ketika ada minimal 1 client (Observer/Admin) yang terhubung ke stream ID tersebut.
   - Timeout inaktivitas: Jika tidak ada viewer selama 30 detik, proses FFmpeg untuk kamera tersebut ditutup secara otomatis (*graceful shutdown*).

### 3.3. Motion Detection Worker Engine
1. **Metode Deteksi:**
   - Background worker mengambil sampling 1–2 frame per detik dari sub-stream RTSP resolusi rendah (misal: 640x360).
   - Membandingkan perbedaan pixel grayscale antar frame berturut-turut (*Frame Differencing*).
   - Jika persentase perbedaan pixel melebihi threshold sensitivitas (misal > 5% area frame), event di-trigger.
2. **Cooldown Period:**
   - Minimum 10 detik jeda antar snapshot untuk kamera yang sama agar tidak terjadi spam snapshot berturut-turut saat ada objek bergerak lama.
3. **Penyimpanan Snapshot:**
   - Folder: `storage/captures/{camera_id}/{YYYY-MM-DD}/{timestamp}_{hash}.jpg`
   - Record database: ID Kamera, Timestamp, Filepath relatif, Score gerakan.

---

## 4. Directory Structure (Proposed)

```text
rstp/
├── assets/                  # Frontend assets (CSS, JS, Icons)
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── panzoom.min.js
│       ├── player.js
│       └── app.js
├── config/                  # Configuration files
│   ├── database.php         # PDO Database connection
│   ├── config.php           # App keys, encryption secret, base URL
│   └── security.php         # CSRF & Sanitization helpers
├── controllers/             # PHP Controllers
│   ├── AuthController.php
│   ├── CameraController.php
│   ├── MotionController.php
│   └── UserController.php
├── models/                  # PHP Data Models
│   ├── User.php
│   ├── Camera.php
│   └── MotionEvent.php
├── views/                   # PHP Views / Templates
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   ├── index.php        # Multi-view grid (Observer & Admin)
│   │   └── motion_logs.php  # Motion event gallery
│   ├── admin/
│   │   ├── cameras.php      # CCTV CRUD management
│   │   └── users.php        # User management
│   └── layouts/
│       ├── header.php
│       └── footer.php
├── storage/                 # Storage for captures
│   └── captures/
├── streamer/                # Node.js Streaming Microservice
│   ├── package.json
│   ├── server.js            # Main entry point (Express + WebSocket)
│   ├── streamManager.js     # On-demand FFmpeg process manager
│   ├── motionDetector.js    # Frame diff worker
│   └── config.js
├── database/                # Database Migrations & Seeds
│   ├── schema.sql
│   └── seeders.sql
├── docs/                    # System Documentation
│   ├── prd.md
│   ├── TRD.md
│   ├── database.md
│   ├── architecture.md
│   ├── ui-ux.md
│   └── SECURITY.md
├── docker/                  # Dockerization configuration
│   ├── Dockerfile.php
│   ├── Dockerfile.node
│   └── nginx.conf
├── docker-compose.yml
├── .env.example
├── index.php                # Front controller / router
└── README.md
```

---

## 5. Non-Functional & Performance Targets

| Parameter | Target Spesifikasi |
| :--- | :--- |
| **Max Concurrent Streams** | 9-16 grid streams simultan per browser instance (resolusi disesuaikan ke sub-stream) |
| **Latency Stream** | $< 1.0$ detik (WebRTC) atau $< 2.5$ detik (WebSocket-MSE) |
| **Max CPU Consumption** | $< 60\%$ CPU utilization pada 4-8 kamera aktif (dengan optimasi hardware acceleration / copy codec) |
| **Snapshot Compression** | JPEG Quality 80% (rata-rata ukuran per file 80–150 KB) |
| **Retention Policy** | Auto-clean snapshot lebih lama dari 30 hari (configurable) |

## Project: RTSP Web CCTV Monitoring System
- **Architecture Type:** Hybrid Monolith + Streaming Microservice (PHP/MySQL + Node.js)
- **Runtime Environment:** 
  - Development: Local XAMPP (Apache, PHP 8.1+, MySQL 8.0/MariaDB) + Node.js (v18+)
  - Production/Target: Containerized Docker (Docker Compose)
- **Status:** Approved / Specification Ready

---

## 1. System Architecture Overview

Sistem menggunakan pendekatan arsitektur terpisah antara **Web & Business Logic Service (PHP)** dan **Media Streaming & Detection Service (Node.js)** untuk memastikan performa tinggi dan isolasi beban kerja I/O intensif:

```
[ CCTV / IP Cameras (RTSP) ]
         |
         | RTSP (TCP/UDP, Port 554)
         v
+-------------------------------------------------------------+
|               Node.js Media Gateway Service                 |
|  - RTSP Ingestion & Connection Pool                         |
|  - FFmpeg Transcoder (RTSP -> WebRTC / WebSocket-MSE / HLS) |
|  - Motion Detector Worker (Pixel Frame-Diff Engine)         |
|  - Snapshot Generator (Local Storage Writer)                |
+-------------------------------------------------------------+
         |                                |
         | WebRTC / WebSocket Stream      | HTTP Webhook / REST Call
         v                                v
[ Client Web Browser ] <---------> [ PHP 8.x Web Application ]
  - HTML5 Video Player               - Session & RBAC Auth
  - Panzoom Digital Zoom             - CCTV CRUD & AES-256 Crypto
  - Grid View (1x1, 2x2, 3x3)        - Motion Event History API
  - Event Notification Popups        - MySQL Database Connector
                                          |
                                          v
                                 [ MySQL Database ]
                                   - Users, Cameras, Logs
```

---

## 2. Technology Stack & Dependencies

### 2.1. Backend Web Application (PHP)
- **Language:** PHP 8.1 or higher
- **Web Server:** Apache (via XAMPP) / Nginx (via Docker)
- **Database Driver:** PHP Data Objects (PDO) with prepared statements
- **Security & Crypto:** `openssl_encrypt` / `openssl_decrypt` (AES-256-CBC) untuk kredensial kamera, `password_hash` (`PASSWORD_BCRYPT` / `ARGON2ID`) untuk password user.
- **Data Serialization:** JSON RESTful Endpoints.

### 2.2. Media Streaming & Motion Engine (Node.js)
- **Runtime:** Node.js v18.x LTS or higher
- **Core Packages:**
  - `express`: REST API internal & static media server.
  - `ws`: WebSocket server untuk signaling WebRTC atau direct video frame relay.
  - `fluent-ffmpeg` / raw `child_process.spawn('ffmpeg')`: Hardware-accelerated / software RTSP remuxing & snapshot extraction.
  - `node-cron` / interval timers: Health check pings ke kamera RTSP.
  - `mysql2/promise` atau Axios REST bridge ke PHP backend: Pencatatan event motion detection secara asinkron.

### 2.3. Frontend Client
- **HTML5 & CSS3:** Responsive Grid UI (Flexbox / CSS Grid), Tailwind CSS atau modern utility CSS.
- **Video Renderer:**
  - Opsi A (Ultra-low latency $< 1$s): WebRTC WHEP / MSE (Media Source Extensions) via WebSocket.
  - Opsi B (Universal Fallback): JSMpeg / HLS.js.
- **Interactivity:**
  - `panzoom` library (atau Native HTML5 Canvas 2D Transform) untuk fluid digital zoom & drag panning.
  - Fetch API untuk AJAX polling / Server-Sent Events (SSE) notifikasi motion detection.

### 2.4. Database (MySQL 8.0+ / MariaDB 10.4+)
- **Engine:** InnoDB (Full ACID compliance, Foreign Keys).
- **Charset:** `utf8mb4` with collation `utf8mb4_unicode_ci`.

---

## 3. Detailed Component Specifications

### 3.1. Authentication & Security Engine
1. **User Authentication:**
   - Session-based auth dengan `HttpOnly`, `SameSite=Lax`, dan `Secure` flag.
   - Brute-force throttling: Max 5 percobaan login gagal per IP/username dalam 15 menit.
2. **Kredensial Kamera CCTV:**
   - Password RTSP disimpan dalam format ciphertext hex/base64 di database.
   - Menggunakan algoritma `AES-256-CBC` dengan `APP_KEY` dan `IV` acak 16-byte yang disimpan per-record atau di server environment.

### 3.2. RTSP Ingestion & Conversion Pipeline
1. **RTSP URL Construction:**
   ```
   rtsp://[username]:[password]@[ip_address]:[port][path]
   Contoh: rtsp://admin:SecretPass123@192.168.1.108:554/live/ch0
   ```
2. **On-Demand Streaming:**
   - Stream FFmpeg tidak dijalankan terus-menerus untuk semua kamera guna mencegah overload CPU/RAM.
   - Stream hanya aktif ketika ada minimal 1 client (Observer/Admin) yang terhubung ke stream ID tersebut.
   - Timeout inaktivitas: Jika tidak ada viewer selama 30 detik, proses FFmpeg untuk kamera tersebut ditutup secara otomatis (*graceful shutdown*).

### 3.3. Motion Detection Worker Engine
1. **Metode Deteksi:**
   - Background worker mengambil sampling 1–2 frame per detik dari sub-stream RTSP resolusi rendah (misal: 640x360).
   - Membandingkan perbedaan pixel grayscale antar frame berturut-turut (*Frame Differencing*).
   - Jika persentase perbedaan pixel melebihi threshold sensitivitas (misal > 5% area frame), event di-trigger.
2. **Cooldown Period:**
   - Minimum 10 detik jeda antar snapshot untuk kamera yang sama agar tidak terjadi spam snapshot berturut-turut saat ada objek bergerak lama.
3. **Penyimpanan Snapshot:**
   - Folder: `storage/captures/{camera_id}/{YYYY-MM-DD}/{timestamp}_{hash}.jpg`
   - Record database: ID Kamera, Timestamp, Filepath relatif, Score gerakan.

---

## 4. Directory Structure (Proposed)

```text
rstp/
├── assets/                  # Frontend assets (CSS, JS, Icons)
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── panzoom.min.js
│       ├── player.js
│       └── app.js
├── config/                  # Configuration files
│   ├── database.php         # PDO Database connection
│   ├── config.php           # App keys, encryption secret, base URL
│   └── security.php         # CSRF & Sanitization helpers
├── controllers/             # PHP Controllers
│   ├── AuthController.php
│   ├── CameraController.php
│   ├── MotionController.php
│   └── UserController.php
├── models/                  # PHP Data Models
│   ├── User.php
│   ├── Camera.php
│   └── MotionEvent.php
├── views/                   # PHP Views / Templates
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   ├── index.php        # Multi-view grid (Observer & Admin)
│   │   └── motion_logs.php  # Motion event gallery
│   ├── admin/
│   │   ├── cameras.php      # CCTV CRUD management
│   │   └── users.php        # User management
│   └── layouts/
│       ├── header.php
│       └── footer.php
├── storage/                 # Storage for captures
│   └── captures/
├── streamer/                # Node.js Streaming Microservice
│   ├── package.json
│   ├── server.js            # Main entry point (Express + WebSocket)
│   ├── streamManager.js     # On-demand FFmpeg process manager
│   ├── motionDetector.js    # Frame diff worker
│   └── config.js
├── database/                # Database Migrations & Seeds
│   ├── schema.sql
│   └── seeders.sql
├── docs/                    # System Documentation
│   ├── prd.md
│   ├── TRD.md
│   ├── database.md
│   ├── architecture.md
│   ├── ui-ux.md
│   └── SECURITY.md
├── docker/                  # Dockerization configuration
│   ├── Dockerfile.php
│   ├── Dockerfile.node
│   └── nginx.conf
├── docker-compose.yml
├── .env.example
├── index.php                # Front controller / router
└── README.md
```

---

## 5. Non-Functional & Performance Targets

| Parameter | Target Spesifikasi |
| :--- | :--- |
| **Max Concurrent Streams** | 9-16 grid streams simultan per browser instance (resolusi disesuaikan ke sub-stream) |
| **Latency Stream** | $< 1.0$ detik (WebRTC) atau $< 2.5$ detik (WebSocket-MSE) |
| **Max CPU Consumption** | $< 60\%$ CPU utilization pada 4-8 kamera aktif (dengan optimasi hardware acceleration / copy codec) |
| **Snapshot Compression** | JPEG Quality 80% (rata-rata ukuran per file 80–150 KB) |
| **Retention Policy** | Auto-clean snapshot lebih lama dari 30 hari (configurable) |
