# Troubleshooting & Diagnostics Guide

Panduan diagnostik cepat untuk mendeteksi dan menyelesaikan kendala teknis pada sistem monitoring CCTV berbasis RTSP.

---

## 1. Masalah Koneksi Kamera RTSP

### Gejala: Kamera berstatus "Offline" atau Player menampilkan "Connection Failed"

| Kemungkinan Penyebab | Cara Diagnostik | Solusi |
| :--- | :--- | :--- |
| **IP / Port 554 Tidak Terjangkau** | Jalankan di terminal PowerShell:<br>`Test-NetConnection -ComputerName 192.168.1.101 -Port 554` | Pastikan kamera menyala dan satu subnet dengan server. Periksa kabel LAN / Switch PoE. |
| **RTSP Path Salah** | Tiap brand kamera memiliki path berbeda (Dahua: `/cam/realmonitor?channel=1&subtype=0`, Hikvision: `/Streaming/Channels/101`, Generic: `/live/ch0`). | Buka aplikasi **VLC Media Player**, pilih `Media -> Open Network Stream`, masukkan URL RTSP manual untuk mengetes apakah stream valid. |
| **Kredensial Username / Password Salah** | Streamer log: `401 Unauthorized` | Periksa kembali akun kamera. Pastikan akun tidak terkunci akibat salah password berulang kali. |

---

## 2. Masalah Node.js & FFmpeg Streaming Gateway

### 2.1. Error: `ffmpeg is not recognized as an internal or external command`
* **Penyebab:** FFmpeg belum terinstal atau path foldernya belum didaftarkan ke System Environment Variable `PATH` di Windows.
* **Solusi:**
  1. Unduh FFmpeg build Windows dari `https://www.gyan.dev/ffmpeg/builds/`.
  2. Ekstrak ke `C:\ffmpeg`.
  3. Masukkan `C:\ffmpeg\bin` ke dalam variable `PATH`.
  4. Restart terminal PowerShell dan uji dengan `ffmpeg -version`.

### 2.2. Error: `WebSocket connection to 'ws://localhost:9999/...' failed`
* **Penyebab:** Node.js streaming service belum dijalankan, atau port 9999 terblokir oleh Windows Firewall.
* **Solusi:**
  1. Pastikan service Node.js berjalan: `cd streamer && node server.js`.
  2. Tambahkan Inbound Rule di Windows Firewall untuk Port `9999` (TCP).

### 2.3. Video Patah-patah atau Lag Tinggi (> 3 Detik)
* **Penyebab:** Stream mengambil resolusi Main-Stream (4K/1080p bitrate tinggi) atau CPU server kewalahan melakukan re-encoding.
* **Solusi:**
  1. Gunakan **Sub-Stream (Resolusi 640x360 atau 720p)** pada URL RTSP untuk keperluan grid monitoring (misal Hikvision channel `102` alih-alih `101`).
  2. Pastikan parameter FFmpeg menggunakan `-c:v copy` jika kamera sudah ber-codec H.264.

---

## 3. Masalah Motion Detection

### 3.1. False Alarms (Terlalu Banyak Notifikasi Gerakan Palsu)
* **Penyebab:** Lampu sorot mobil malam hari, perubahan bayangan awan, atau ranting pohon goyang.
* **Solusi:**
  1. Turunkan sensitivitas ke `low` pada menu konfigurasi kamera.
  2. Terapkan batas area deteksi (Region of Interest / ROI) agar frame atas (langit/pohon) diabaikan.
  3. Naikkan durasi jeda *cooldown* (default: 10 detik).

### 3.2. Snapshot Gambar Tidak Tersimpan di Disk
* **Penyebab:** Direktori `storage/captures/` belum ada atau terkendala izin hak akses (*file permission*).
* **Solusi:**
  1. Pastikan folder `storage/captures` telah dibuat.
  2. Di lingkungan Linux/Docker, jalankan: `chmod -R 775 storage/captures` dan `chown -R www-data:www-data storage/captures`.

---

## 4. Masalah PHP & Database (XAMPP)

### 4.1. Error: `SQLSTATE[HY000] [1049] Unknown database 'rstp_cctv_db'`
* **Solusi:** Database belum dibuat. Buka phpMyAdmin dan jalankan skrip pembuatan database dari [docs/database.md](file:///d:/xampp/htdocs/rstp/docs/database.md).

### 4.2. Error: `Call to undefined function openssl_encrypt()`
* **Solusi:** Ekstensi OpenSSL di PHP belum aktif. Buka file `php.ini` di folder XAMPP (`C:\xampp\php\php.ini`), cari baris `;extension=openssl` dan hapus tanda titik koma (`;`), kemudian restart Apache di XAMPP.
