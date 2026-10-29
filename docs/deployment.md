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
