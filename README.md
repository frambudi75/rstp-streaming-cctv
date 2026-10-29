# RTSP Web CCTV Monitoring System

Sistem pemantauan CCTV berbasis web dengan arsitektur hibrida (PHP + Node.js) yang mengonversi streaming RTSP menjadi video ramah browser tanpa plugin tambahan. Dirancang untuk Command Center pemantauan lalu lintas jalan raya dan keamanan fasilitas.

## Fitur Utama

- **Live Stream Multi-Grid:** Tata letak grid dinamis 1x1, 2x2, 3x3, dan 4x4 dengan opsi layar penuh (*fullscreen*).
- **Interactive Digital Zoom & Pan:** Fitur zoom digital (hingga 6x) dan geser posisi langsung di browser menggunakan scroll wheel atau sentuhan.
- **Deteksi Gerakan (Motion Detection):** Worker Node.js yang membandingkan perbedaan pixel (*frame differencing*), auto-capture snapshot ke disk, dan pencatatan event ke MySQL.
- **Multi-Role RBAC:**
  - **Administrator:** CRUD CCTV, manajemen pengguna, konfigurasi sensitivitas deteksi, audit logs.
  - **Observer:** Pemantauan live grid, digital zoom, dan galeri peninjauan deteksi gerakan.
- **Keamanan:** Enkripsi dua arah **AES-256-CBC** untuk password RTSP CCTV di database, password hashing Bcrypt untuk akun pengguna, dan *stream masking*.

---

## Panduan Instalasi Cepat (XAMPP Lokal)

### 1. Database MySQL
1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Buat database baru bernama `rstp_cctv_db`.
3. Import file `database/schema.sql` dan `database/seeders.sql`.

### 2. Konfigurasi Aplikasi
File `.env` sudah disiapkan. Pastikan konfigurasi database di `.env` sesuai dengan konfigurasi XAMPP Anda:
```ini
DB_HOST=127.0.0.1
DB_NAME=rstp_cctv_db
DB_USER=root
DB_PASS=
```

### 3. Jalankan Node.js Streamer
Buka terminal baru di folder `streamer/`:
```bash
cd streamer
npm install
node server.js
```

### 4. Akses Aplikasi
Buka browser dan kunjungi:
`http://localhost/rstp`

**Akun Bawaan (Default):**
- **Admin:** `admin` / `admin123`
- **Observer:** `observer` / `observer123`

---

## Deployment Menggunakan Docker

Jalankan satu perintah untuk menyalakan database, web PHP, dan streaming gateway:
```bash
docker compose up -d --build
```
Akses di: `http://localhost`

