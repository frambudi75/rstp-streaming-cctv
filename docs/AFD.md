# Architecture & Flow Diagram (AFD)

Dokumen ini mendeskripsikan diagram alur data, siklus hidup koneksi kamera (*camera lifecycle*), dan alur interaksi pengguna di dalam aplikasi **RTSP Web CCTV Monitoring System**.

---

## 1. User Journey & Navigation Flow

```mermaid
flowchart TD
    START([Pengguna Mengakses Website]) --> LOGIN{Sudah Login?}
    
    LOGIN -- Tidak --> LOGIN_PAGE[Halaman Login]
    LOGIN_PAGE --> SUBMIT_LOGIN[Kirim Kredensial via POST /api/login]
    SUBMIT_LOGIN --> AUTH_CHECK{Kredensial Valid?}
    AUTH_CHECK -- Gagal --> LOGIN_ERR[Tampilkan Pesan Error & Catat Log]
    LOGIN_ERR --> LOGIN_PAGE
    AUTH_CHECK -- Berhasil --> INIT_SESS[Buat Session & Regenerate ID]
    
    LOGIN -- Ya --> CHECK_ROLE{Periksa Role}
    INIT_SESS --> CHECK_ROLE

    CHECK_ROLE -- Observer --> OBS_DASH[Dashboard Observer: Grid Monitoring CCTV]
    CHECK_ROLE -- Administrator --> ADM_DASH[Dashboard Admin: Grid + Menu Manajemen]

    OBS_DASH --> VIEW_GRID[Lihat Live Stream Grid 1x1, 2x2, 3x3]
    OBS_DASH --> ZOOM_PAN[Interaksi Digital Zoom & Panning]
    OBS_DASH --> MOTION_GALLERY[Galeri Riwayat Deteksi Gerakan]

    ADM_DASH --> VIEW_GRID
    ADM_DASH --> CAM_MGT[Manajemen CCTV: Tambah / Edit / Hapus Kamera]
    ADM_DASH --> USER_MGT[Manajemen Pengguna: Tambah / Edit / Nonaktifkan Akun]
    ADM_DASH --> SYS_LOGS[Audit Trail & Log Sistem]
```

---

## 2. Camera Stream Lifecycle Flow

```mermaid
stateDiagram-v2
    [*] --> Unsubscribed : Inisialisasi Sistem
    
    Unsubscribed --> Connecting : User Membuka Grid (WebSocket Subscribe)
    Connecting --> Streaming : FFmpeg Terhubung ke RTSP & Paket Mengalir
    Connecting --> Offline : Gagal Handshake RTSP / Timeout 10s
    
    Streaming --> Streaming : Meneruskan Video ke Browser Client
    Streaming --> DetectionTriggered : Frame Diff Melebihi Threshold
    DetectionTriggered --> Streaming : Simpan Snapshot & Broadcast Alert
    
    Streaming --> Disconnecting : Semua Viewer Meninggalkan Grid
    Disconnecting --> Unsubscribed : Inactivity Timer (30s) Berakhir & FFmpeg Ditutup
    
    Offline --> Connecting : Auto-Retry Tiap 15s atau Tombol Reload Manual
```

---

## 3. Motion Detection & Alert Sequence

```mermaid
sequenceDiagram
    autonumber
    participant C as CCTV RTSP
    participant N as Node.js Motion Engine
    participant D as Disk Storage
    participant S as MySQL DB
    participant B as Browser Client

    C->>N: Aliran Paket Video RTSP
    loop Sampling Tiap 1 Detik
        N->>N: Ambil 1 Frame (Grayscale 320x180)
        N->>N: Bandingkan dengan Frame Sebelumnya
        alt Delta > Sensitivity Threshold
            N->>D: Simpan Snapshot: /storage/captures/{cam_id}/{timestamp}.jpg
            N->>S: INSERT INTO motion_events (camera_id, snapshot_path, confidence_score)
            N-->>B: WebSocket Broadcast: { type: "motion_alert", cameraId, timestamp }
            B->>B: Highlight Border Kamera (Pulsing Amber) & Putar Sound Alert
        end
    end
```
