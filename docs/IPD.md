# Implementation & Deployment Plan (IPD)

Rencana tahapan implementasi teknis dan strategi deployment untuk **RTSP Web CCTV Monitoring System**.

---

## 1. Work Breakdown Structure (WBS)

```
RTSP Web CCTV System
├── Milestone 1: Fondasi Backend & Database (Hari 1 - 2)
│   ├── Setup Skema Database MySQL & Seeder Initial User
│   ├── Modul Autentikasi PHP (Login, Session, RBAC: Admin vs Observer)
│   ├── Modul Kriptografi (Enkripsi AES-256 untuk Kredensial Kamera)
│   └── CRUD Manajemen Kamera CCTV (Admin Panel)
│
├── Milestone 2: Media Streaming Gateway (Hari 3 - 4)
│   ├── Setup Microservice Node.js & WebSocket Server
│   ├── Integrasi FFmpeg Transmuxer (RTSP -> Web-friendly Stream)
│   ├── On-demand Stream Manager (Auto-start & Auto-kill proses FFmpeg)
│   └── Client Web Player (Integrasi Canvas / WebRTC Player)
│
├── Milestone 3: Fitur Interaktif & Motion Detection (Hari 5 - 6)
│   ├── Implementasi Multi-View Grid (1x1, 2x2, 3x3)
│   ├── Integrasi Digital Zoom & Pan (Library Panzoom)
│   ├── Worker Background Deteksi Gerakan (Frame Differencing)
│   ├── Auto-capture Snapshot & Penyimpanan ke Disk & DB
│   └── Halaman Galeri Riwayat Motion Events & Notifikasi Real-time
│
└── Milestone 4: Dockerization & Testing (Hari 7)
    ├── Pembuatan Dockerfile (PHP-Apache & Node-FFmpeg)
    ├── Konfigurasi docker-compose.yml & Shared Volumes
    ├── Uji Ketahanan Latensi & Uji Beban Multi-Stream
    └── Audit Keamanan & Finalisasi Dokumentasi
```

---

## 2. Kriteria Penerimaan (Acceptance Criteria)

- [x] Dokumen spesifikasi teknis lengkap ([docs/prd.md](file:///d:/xampp/htdocs/rstp/docs/prd.md), [docs/TRD.md](file:///d:/xampp/htdocs/rstp/docs/TRD.md), [docs/database.md](file:///d:/xampp/htdocs/rstp/docs/database.md), dll).
- [ ] Administrator dapat login dan mengelola daftar CCTV dengan aman.
- [ ] Password kamera tersimpan dalam bentuk terenkripsi di database MySQL.
- [ ] Observer dapat melihat video streaming CCTV di browser tanpa menginstal plugin apapun.
- [ ] Fitur digital zoom bekerja lancar dengan scroll mouse dan touch gesture.
- [ ] Gerakan di depan kamera memicu penyimpanan snapshot dan tercatat di database.
- [ ] Sistem dapat dijalankan baik di XAMPP lokal maupun menggunakan Docker Compose.
