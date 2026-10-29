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


---

## 6. Spesifikasi Sistem Skala Besar (100+ CCTV Streaming Engine)

Bagian ini mendefinisikan rancangan kapasitas, beban komputasi, dan spesifikasi infrastruktur untuk menjalankan pemantauan lebih dari **100 kamera CCTV** secara stabil tanpa lag (*zero-lag*).

### 6.1. Kalkulasi Beban Jaringan & Komputasi (Mathematical Modeling)

Menjalankan 100+ CCTV memerlukan pemahaman terhadap dua jalur lalu lintas data: **Ingest (Kamera ke Server)** dan **Egress (Server ke Browser Operator)**.

#### 1. Jalur Ingest (RTSP dari 100 Kamera ke Server)
| Kategori Stream | Resolusi & Frame Rate | Bitrate Rata-Rata | Total Bandwidth Ingest (100 CCTV) |
| :--- | :--- | :--- | :--- |
| **Main Stream** (1080p FHD) | 1920x1080 @ 25fps (H.264) | 3.5 Mbps | **350 Mbps** (Konstan) |
| **Sub Stream** (360p SD) | 640x360 @ 15fps (H.264) | 400 kbps | **40 Mbps** (Konstan) |

> [!IMPORTANT]
> **Aturan Wajib:** Jangan pernah menarik Main Stream untuk tampilan Multi-Grid (2x2, 3x3, 4x4). Selalu gunakan **Sub-Stream** untuk pemantauan grid. Main Stream hanya ditarik ketika operator membuka kamera dalam mode layar penuh (1x1).

#### 2. Jalur Egress (Server ke Browser Operator)
- Setiap operator yang memantau grid 16 kamera (4x4) menggunakan sub-stream membutuhkan:  
  $$16 \times 400\text{ kbps} = 6.4\text{ Mbps per workstation}$$
- Jika terdapat 5 workstation operator yang memantau secara bersamaan:  
  $$5 \times 6.4\text{ Mbps} = 32\text{ Mbps}$$

---

### 6.2. Spesifikasi Hardware Server (Pusat Kendali / TMC)

#### Spesifikasi Minimal (Kapasitas s/d 100 Kamera, On-Demand Streaming)
*Direkomendasikan untuk sistem dengan 1–3 workstation operator aktif bersamaan.*

- **Prosesor (CPU):** Intel Core i7 Generasi 10+ / AMD Ryzen 7 3700X / Intel Xeon E-2288G (Minimal 8 Core, 16 Thread @ 3.2 GHz).
- **Memori (RAM):** 16 GB DDR4 (3200 MHz).
- **Penyimpanan Primer:** 512 GB NVMe PCIe M.2 SSD (Untuk OS, Docker, Redis Cache, dan database MySQL).
- **Penyimpanan Log/Capture:** 2 TB Enterprise HDD Surveillance (WD Purple / Seagate SkyHawk) untuk direktori `/storage/captures/`.
- **Kartu Jaringan (NIC):** 1x Gigabit Ethernet (1000BASE-T) Dedicated Full-Duplex.
- **Sistem Operasi:** Ubuntu Server 22.04 LTS / 24.04 LTS x86_64 atau Windows Server 2022.

#### Spesifikasi Rekomendasi (Kapasitas 100–300 Kamera, Operasi 24/7 Multi-Operator)
*Direkomendasikan untuk instalasi Command Center berskala enterprise dengan puluhan monitor aktif.*

- **Prosesor (CPU):** AMD EPYC 7302P (16 Core, 32 Thread) atau Intel Xeon Silver 4314 (16 Core, 32 Thread).
- **Akselerator Transcoding (GPU):** NVIDIA T4 (16 GB) / RTX A2000 (12 GB) dengan dukungan **NVENC & NVDEC** (untuk akselerasi hardware decoding H.264/H.265).
- **Memori (RAM):** 32 GB – 64 GB DDR4/DDR5 ECC Registered.
- **Penyimpanan Primer:** 1 TB NVMe SSD Enterprise (Kioxia / Samsung PM9A3) dalam konfigurasi RAID 1.
- **Penyimpanan Rekaman & Capture:** 4 TB – 8 TB SAS/SATA Enterprise 7200 RPM RAID 10.
- **Kartu Jaringan (NIC):** 2x 10 Gbps SFP+ (LACP Link Aggregation / Bonding).
- **Power Supply:** Redundant PSU (1+1 Hot-swappable 550W+).

---

### 6.3. Spesifikasi Client Workstation (PC Operator Pemantau)

Komputer operator yang bertugas menampilkan grid CCTV di browser harus memiliki kemampuan decode video hardware agar CPU tidak 100%:

- **Prosesor:** Minimal Intel Core i5 Gen 11 / AMD Ryzen 5 4000 series (4 Core, 8 Thread @ 2.8 GHz+).
- **RAM:** 8 GB – 16 GB DDR4.
- **Kartu Grafis (GPU):** Intel Iris Xe Graphics / NVIDIA GeForce GTX 1650 atau lebih tinggi (Hardware Video Decode Acceleration wajib aktif di browser).
- **Layar / Display:** Dual-Monitor Full HD (1920x1080) atau 4K Ultra HD.
- **Browser:** Google Chrome (v115+), Microsoft Edge (v115+), atau Brave dengan opsi *"Use graphics acceleration when available"* diaktifkan.

---

### 6.4. Tujuh Pilar Arsitektur Anti-Lag (Zero-Lag Blueprint)

```mermaid
flowchart LR
    subgraph Cameras["100+ CCTV (Edge)"]
        direction TB
        C1["CCTV 01 (Main + Sub)"]
        C2["CCTV 02 (Main + Sub)"]
        CN["CCTV 100 (Main + Sub)"]
    end

    subgraph ServerCore["Streaming Engine & Cache"]
        direction TB
        LAZY["On-Demand Ingest (Active Viewers Only)"]
        COPY["Zero-Transcode Passthrough (-c:v copy)"]
        REDIS[("Redis Cache: State & Metadata")]
        MYSQL[("MySQL 8.0: Compound Indexed")]
    end

    subgraph ClientOpt["Browser Operator"]
        direction TB
        VIRT["DOM Virtualization (Max 16 Active)"]
        HWACC["GPU Hardware Decoded Canvas"]
    end

    Cameras -->|Sub-Stream (400 kbps)| LAZY
    LAZY --> COPY
    COPY --> REDIS
    COPY -->|WebSocket fMP4| VIRT
    VIRT --> HWACC
    ServerCore <--> MYSQL
```

1. **On-Demand Lazy Ingestion:**  
   Proses FFmpeg tidak berjalan secara statis untuk 100 kamera. FFmpeg hanya dipanggil ketika ada minimal 1 user yang membuka kamera tersebut (`subscribers > 0`). Ketika tab browser ditutup atau filter gedung dipindahkan, worker otomatis dihentikan setelah batas toleransi 30 detik.
2. **Dual-Stream Passthrough:**  
   Simpan dua URL RTSP di profil kamera: URL Main Stream (1080p) dan URL Sub-Stream (360p/D1). Tampilan Live Grid secara otomatis memilih Sub-Stream sehingga konsumsi memori dan bandwidth turun hingga 88%.
3. **Zero-Transcoding Mode (`-c:v copy`):**  
   Hindari komputasi decode-encode ulang pada CPU server. Karena kamera IP modern telah memancarkan format H.264 standar, paket NAL unit langsung diremux ke container fMP4 / WebRTC dengan pemakaian CPU $< 0.8\%$ per stream.
4. **DOM Virtualization di Frontend:**  
   Browser modern mengalami degradasi render bila terdapat lebih dari 16 elemen video/canvas aktif di satu tab (keterbatasan WebGL contexts). Frontend membatasi grid maksimal 16 kamera aktif (4x4) per tampilan halaman, dilengkapi sistem paginasi instan.
5. **In-Memory State & Caching (Redis):**  
   Metadata kamera, query daftar gedung/lantai, dan penghitung event notifikasi tidak menyentuh hard disk melainkan dilayani oleh Redis Cache dalam waktu $< 1$ milidetik.
6. **Compound Indexing MySQL:**  
   Query daftar kamera per gedung/lantai menggunakan index komposit `(group_name, floor_subgroup)` dengan waktu eksekusi query stabil di bawah 2 milidetik meski tabel menampung ribuan rekaman.
7. **Migrasi ke Media Gateway Terdedikasi (MediaMTX / go2rtc):**  
   Jika kebutuhan berkembang menjadi lebih dari 200 stream simultan atau ratusan operator publik, Node.js gateway dapat ditransisikan ke engine binary C++/Go (*MediaMTX*) sebagai layer transmisi WebRTC berlatensi sub-detik.
