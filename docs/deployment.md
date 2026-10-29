# Production & Local Deployment Guide

Panduan lengkap instalasi, konfigurasi, dan migrasi sistem dari lingkungan lokal **XAMPP (Windows)** menuju lingkungan kontainer **Docker Compose**.

---

## 1. Setup Lingkungan Lokal (XAMPP + Node.js)

### 1.1. Prasyarat Sistem
- **XAMPP for Windows** dengan PHP 8.1+ dan MySQL / MariaDB aktif.
- **Node.js** v18.x LTS atau lebih baru (`https://nodejs.org`).
- **FFmpeg** terpasang di sistem Windows dan path binari-nya terdaftar di environment variable `PATH`.
  - Verifikasi di terminal PowerShell: `ffmpeg -version`

### 1.2. Langkah Setup Database (MySQL XAMPP)
1. Buka XAMPP Control Panel, pastikan modul **Apache** dan **MySQL** berstatus `Running`.
2. Buka browser ke `http://localhost/phpmyadmin`.
3. Buka tab **SQL**, salin dan jalankan skrip DDL & Seeder dari [docs/database.md](file:///d:/xampp/htdocs/rstp/docs/database.md):
   ```sql
   CREATE DATABASE IF NOT EXISTS `rstp_cctv_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   -- (Jalankan seluruh isi DDL dan Seeder dari database.md)
   ```

### 1.3. Konfigurasi Backend PHP
1. Salin file contoh konfigurasi `.env.example` menjadi `.env` di folder root project:
   ```ini
   APP_ENV=local
   APP_URL=http://localhost/rstp
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=rstp_cctv_db
   DB_USER=root
   DB_PASS=
   APP_ENCRYPTION_KEY=your_32_characters_random_secret_key!
   INTERNAL_SERVICE_TOKEN=secret_stream_token_12345
   STREAMER_WS_PORT=9999
   ```

### 1.4. Menjalankan Node.js Streaming Gateway
1. Buka terminal PowerShell di folder `streamer/`:
   ```powershell
   cd d:\xampp\htdocs\rstp\streamer
   npm install
   node server.js
   ```
2. Terminal akan menampilkan:
   ```text
   [Streamer] WebSocket Server running on port 9999
   [Streamer] Ready to bridge RTSP streams
   ```

---

## 2. Setup Lingkungan Produksi (Docker Compose)

Untuk deployment di server Ubuntu / Linux atau Docker Desktop:

### 2.1. File `docker-compose.yml` (Target)
```yaml
version: '3.8'

services:
  # Service 1: MySQL Database
  db:
    image: mysql:8.0
    container_name: cctv_db
    restart: always
    environment:
      MYSQL_DATABASE: rstp_cctv_db
      MYSQL_ROOT_PASSWORD: root_secure_password
      MYSQL_USER: cctv_user
      MYSQL_PASSWORD: cctv_user_password
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - cctv_net

  # Service 2: Web & PHP Backend
  web:
    build:
      context: .
      dockerfile: docker/Dockerfile.php
    container_name: cctv_web
    restart: always
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
      - capture_storage:/var/www/html/storage/captures
    depends_on:
      - db
    networks:
      - cctv_net

  # Service 3: Node.js Streaming & FFmpeg Gateway
  streamer:
    build:
      context: ./streamer
      dockerfile: Dockerfile
    container_name: cctv_streamer
    restart: always
    ports:
      - "9999:9999"
    volumes:
      - capture_storage:/app/storage/captures
    depends_on:
      - web
    networks:
      - cctv_net

volumes:
  db_data:
  capture_storage:

networks:
  cctv_net:
    driver: bridge
```

### 2.2. Perintah Menjalankan Docker
```bash
# Build dan jalankan seluruh container
docker compose up -d --build

# Periksa status container
docker compose ps

# Memeriksa log streaming gateway
docker compose logs -f streamer
```


---

## 3. Optimasi Server & Kernel OS untuk Skala 100+ CCTV

Saat melayani 100+ stream RTSP bersamaan, sistem operasi Linux host memerlukan penyesuaian parameter jaringan dan socket descriptor agar tidak mengalami bottleneck *Too many open files* atau *socket packet drop*.

### 3.1. Penyesuaian Kernel Linux (`/etc/sysctl.conf`)

Tambahkan parameter berikut ke file `/etc/sysctl.conf` pada server host:

```ini
# Meningkatkan batas antrean socket koneksi baru
net.core.somaxconn = 65535

# Memperbesar buffer penerima dan pengirim jaringan (Penting untuk video UDP/TCP)
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 87380 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216

# Memperbesar buffer backlog interface jaringan
net.core.netdev_max_backlog = 10000

# Mengoptimalkan daur ulang koneksi TCP TIME_WAIT
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15

# Menaikkan batas sistem file descriptors
fs.file-max = 2097152
```

Terapkan perubahan dengan menjalankan:
```bash
sudo sysctl -p
```

### 3.2. Penyesuaian Batas File Descriptor (`/etc/security/limits.conf`)

Pastikan proses Docker dan streamer dapat membuka ribuan socket file:

```ini
* soft nofile 65535
* hard nofile 65535
* soft nproc 65535
* hard nproc 65535
```

---

## 4. Alokasi Resource Kontainer Docker (100+ CCTV)

Untuk memastikan stabilitas sistem di lingkungan Docker produksi, alokasikan resource limit pada `docker-compose.yml`:

```yaml
services:
  db:
    deploy:
      resources:
        limits:
          cpus: '2.0'
          memory: 4096M
        reservations:
          memory: 2048M

  redis:
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M

  web:
    deploy:
      resources:
        limits:
          cpus: '2.0'
          memory: 2048M

  streamer:
    deploy:
      resources:
        limits:
          cpus: '8.0'
          memory: 8192M
        reservations:
          cpus: '2.0'
          memory: 2048M
```
