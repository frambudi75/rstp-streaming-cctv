# System Architecture & Technical Design

## 1. High-Level Architecture

Sistem ini memisahkan tugas I/O intensif (transcoding video streaming dan komputasi gambar frame-diff) dari tugas aplikasi web standar (autentikasi, CRUD database, dan antarmuka pengguna).

```mermaid
flowchart TD
    subgraph Edge["Perangkat Kamera (Edge)"]
        CAM1["CCTV 1 (RTSP H.264)"]
        CAM2["CCTV 2 (RTSP H.264)"]
        CAMN["CCTV N (RTSP H.264)"]
    end

    subgraph StreamServer["Media & Streaming Service (Node.js)"]
        RTSP_IN["RTSP Client / Ingest Engine"]
        FFMPEG["FFmpeg Remuxer / Transcoder Engine"]
        WS_SRV["WebSocket / WebRTC Signaling Server"]
        MOTION["Motion Detection Worker (Frame Differencing)"]
        SNAP_GEN["Snapshot Storage Writer"]
    end

    subgraph WebServer["Web & API Server (PHP 8.x - XAMPP / Docker)"]
        ROUTER["Router & Request Dispatcher"]
        AUTH_MOD["Auth & Session Controller (RBAC)"]
        CAM_MOD["CCTV Controller (AES-256 Crypto)"]
        LOG_MOD["Motion & System Log Controller"]
    end

    subgraph DataStore["Data & File Persistence"]
        DB[(MySQL 8.x Database)]
        DISK[("Local Disk Storage: /storage/captures/")]
    end

    subgraph Client["Client Browser (Desktop & Mobile)"]
        PLAYER["HTML5 Canvas / Video Element"]
        PANZOOM["Digital Pan & Zoom Controller"]
        GRID_UI["Responsive Grid Manager (1x1, 2x2, 3x3)"]
    end

    CAM1 & CAM2 & CAMN -->|RTSP Port 554| RTSP_IN
    RTSP_IN --> FFMPEG
    RTSP_IN --> MOTION
    MOTION -->|Trigger Motion| SNAP_GEN
    SNAP_GEN -->|Save JPG File| DISK
    SNAP_GEN -->|Insert Event Record| DB

    FFMPEG -->|Transmuxed Stream| WS_SRV
    WS_SRV -->|Low-Latency Video Stream| PLAYER
    
    PLAYER <--> PANZOOM
    PANZOOM <--> GRID_UI

    GRID_UI <-->|HTTP REST API / Auth| WebServer
    WebServer <--> DB
```

---

## 2. Video Streaming Pipeline (RTSP to Web)

### 2.1. Mengapa Browser Tidak Mendukung RTSP?
Browser modern (Chrome, Safari, Firefox, Edge) hanya mendukung pemutaran video HTML5 dengan protokol transport **HTTP/HTTPS** atau **WebSocket / WebRTC**, dan format container seperti **MP4 (fMP4), WebM, HLS (`.m3u8`), atau raw WebRTC RTP**. 

Protokol RTSP berjalan di atas transport TCP/UDP raw dengan protokol kontrol khusus (DESCRIBE, SETUP, PLAY) yang tidak memiliki API browser native.

### 2.2. Pipeline Konversi Terpilih
Sistem mengadopsi pendekatan **Transmuxing (Bukan Re-encoding Penuh)** bilamana memungkinkan:
1. Jika kamera CCTV sudah memancarkan video codec **H.264 (AVC)**:
   - FFmpeg hanya melakukan *remuxing container* (`-c:v copy`), mengubah paket RTSP RTP langsung menjadi fMP4 atau raw WebRTC packet.
   - **Beban CPU sangat rendah ($< 3\%$ per stream)** karena tidak melakukan decode/encode ulang video bit-by-bit.
2. Jika kamera memancarkan codec lain (misal H.265 / HEVC yang tidak kompatibel di semua browser):
   - FFmpeg melakukan transcoding ringan ke H.264 baseline profile.

### 2.3. On-Demand Stream Management
Untuk menghemat pemakaian memori dan bandwidth:
- Stream FFmpeg **tidak aktif permanen** untuk semua CCTV.
- Ketika Observer membuka halaman CCTV, client mengirim sinyal `subscribe(camera_id)` via WebSocket.
- Node.js memeriksa apakah proses FFmpeg untuk kamera tersebut sudah berjalan. Jika belum, Node.js menyalakan *worker process* FFmpeg.
- Ketika semua viewer keluar dari halaman (viewer count = 0), timer 30 detik dimulai. Jika tidak ada viewer baru yang masuk, proses FFmpeg dimatikan (*graceful stop*).

---

## 3. Motion Detection Processing Pipeline

```mermaid
sequenceDiagram
    autonumber
    participant Cam as Kamera CCTV
    participant Node as Node.js Motion Worker
    participant Disk as Local Storage
    participant DB as MySQL Database
    participant Client as Observer Browser

    Cam->>Node: RTSP Video Stream
    Note over Node: Ekstraksi 1 frame per detik (Low-Res Grayscale)
    Note over Node: Komputasi Perbedaan Pixel (Frame N vs Frame N-1)
    
    alt Perubahan Pixel > Threshold (Gerakan Terdeteksi)
        Note over Node: Cek Cooldown (Minimal 10 detik sejak event terakhir)
        Node->>Disk: Simpan snapshot gambar: /storage/captures/{cam_id}/{timestamp}.jpg
        Node->>DB: INSERT INTO motion_events (camera_id, snapshot_path, confidence_score)
        Node-->>Client: WebSocket Broadcast: event_motion_detected
        Client->>Client: Tampilkan badge notifikasi & bunyi alert (opsional)
    else Tidak Ada Perbedaan Berarti
        Note over Node: Frame di-discard (tidak disimpan)
    end
```

---

## 4. Sequence Diagram: Autentikasi & Live Streaming

```mermaid
sequenceDiagram
    autonumber
    actor User as Observer / Admin
    participant Browser as Web Browser
    participant PHP as PHP Backend
    participant DB as MySQL
    participant Node as Node.js Streamer
    participant Camera as Kamera CCTV

    User->>Browser: Akses Halaman Login & Submit Form
    Browser->>PHP: POST /login (username, password)
    PHP->>DB: SELECT * FROM users WHERE username = ?
    DB-->>PHP: Data user & password_hash
    PHP->>PHP: password_verify(password, hash)
    
    alt Kredensial Valid
        PHP->>PHP: Inisialisasi Session ($_SESSION['user_id'], role)
        PHP-->>Browser: Redirect ke /dashboard
    else Kredensial Salah
        PHP-->>Browser: Tampilkan Alert Error Login
    end

    User->>Browser: Buka Grid Monitoring CCTV
    Browser->>PHP: GET /api/cameras (Daftar CCTV Aktif)
    PHP->>DB: SELECT id, name, ip_address, status FROM cameras
    DB-->>PHP: Daftar kamera
    PHP-->>Browser: Return JSON daftar kamera

    Browser->>Node: WebSocket Connect & Subscribe(cam_id)
    Node->>PHP: Internal API: Minta RTSP URL Decrypted (Token Protected)
    PHP->>DB: Ambil encrypted_password & iv
    PHP->>PHP: Dekripsi AES-256 password CCTV
    PHP-->>Node: Return RTSP Connection URL
    Node->>Camera: RTSP Handshake & Pull Stream
    Camera-->>Node: RTSP Stream
    Node->>Node: Transmux ke Web-friendly stream
    Node-->>Browser: Kirim Video Data Chunks (Live Play)
    Browser->>Browser: Render video di HTML5 Canvas/Video Player
```

---

## 5. Deployment Topology Comparison

### 5.1. Lingkungan Pengembangan Lokal (XAMPP + Node.js)
* **Apache & PHP 8.x:** Berjalan di `http://localhost/rstp` (Port 80 / 443).
* **MySQL Database:** Berjalan di `localhost:3306`.
* **Node.js Stream Service:** Dijalankan via terminal PowerShell/CMD di port `9999` (`node streamer/server.js`).
* **Storage Path:** Direct local file system `d:\xampp\htdocs\rstp\storage\captures\`.

### 5.2. Lingkungan Target Produksi (Docker Compose)
* **Container `web`:** Nginx + PHP 8.1 FPM.
* **Container `streamer`:** Node.js v18 + FFmpeg pre-installed.
* **Container `db`:** MySQL 8.0 dengan persistent docker volume.
* **Shared Volume:** Volume bersama untuk `/storage/captures` yang dapat diakses oleh container `streamer` (untuk menulis snapshot) dan container `web` (untuk melayani file gambar ke browser).
