# API Documentation

Sistem menyediakan dua kelompok antarmuka API:
1. **Public/Session REST API (PHP Backend):** Digunakan oleh client web frontend (terautentikasi session).
2. **Internal Service API (PHP $\leftrightarrow$ Node.js):** Digunakan untuk komunikasi inter-service antara streaming gateway dan backend web.
3. **Streaming WebSocket API (Node.js Gateway):** Digunakan oleh browser untuk menerima live video dan notifikasi motion.

---

## 1. Authentication & Session Endpoints

### 1.1. User Login
- **Endpoint:** `POST /api/login`
- **Request Body (JSON):**
  ```json
  {
    "username": "admin",
    "password": "mypassword123"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Login berhasil",
    "user": {
      "id": 1,
      "username": "admin",
      "role": "admin"
    }
  }
  ```

### 1.2. User Logout
- **Endpoint:** `POST /api/logout`
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Sesi telah diakhiri"
  }
  ```

---

## 2. Camera Management Endpoints

### 2.1. List All Active Cameras
- **Endpoint:** `GET /api/cameras`
- **Access:** Administrator & Observer
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "name": "CCTV 01 - Simpang Sudirman",
        "location": "Jl. Jend. Sudirman KM 2",
        "status": "online",
        "motion_enabled": true,
        "stream_url": "ws://localhost:9999/stream?id=1"
      }
    ]
  }
  ```
  *(Catatan: IP address riil, port, dan password tidak diekspos ke frontend)*.

### 2.2. Get Camera Detail (Admin Only)
- **Endpoint:** `GET /api/cameras/{id}`
- **Access:** Administrator
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "name": "CCTV 01 - Simpang Sudirman",
      "location": "Jl. Jend. Sudirman KM 2",
      "ip_address": "192.168.1.101",
      "port": 554,
      "rtsp_path": "/live/ch0",
      "username": "admin",
      "status": "online",
      "motion_enabled": true,
      "motion_sensitivity": "medium"
    }
  }
  ```

### 2.3. Create New Camera
- **Endpoint:** `POST /api/cameras`
- **Access:** Administrator
- **Request Body:**
  ```json
  {
    "name": "CCTV 05 - Tol Slipi",
    "location": "Gerbang Tol Slipi 1",
    "ip_address": "192.168.1.105",
    "port": 554,
    "rtsp_path": "/h264Preview_01_main",
    "username": "admin",
    "password": "CameraPassword123",
    "motion_enabled": true,
    "motion_sensitivity": "medium"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "success": true,
    "message": "Kamera CCTV berhasil ditambahkan",
    "camera_id": 5
  }
  ```

### 2.4. Update Camera Configuration
- **Endpoint:** `PUT /api/cameras/{id}`
- **Access:** Administrator
- **Request Body:** Sama seperti Create (field `password` bersifat opsional; jika kosong, password lama dipertahankan).

### 2.5. Delete Camera
- **Endpoint:** `DELETE /api/cameras/{id}`
- **Access:** Administrator
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Kamera berhasil dihapus"
  }
  ```

---

## 3. Motion Detection & Event Endpoints

### 3.1. List Motion Events
- **Endpoint:** `GET /api/motion-events`
- **Query Parameters:**
  - `camera_id` (opsional): Filter per ID kamera
  - `start_date` (opsional): Format `YYYY-MM-DD`
  - `end_date` (opsional): Format `YYYY-MM-DD`
  - `page` (default: 1), `limit` (default: 20)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1024,
        "camera_id": 1,
        "camera_name": "CCTV 01 - Simpang Sudirman",
        "snapshot_url": "/storage/captures/1/2026-10-29/11-30-00_a8f9.jpg",
        "confidence_score": 84.50,
        "is_viewed": false,
        "detected_at": "2026-10-29 11:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_records": 92
    }
  }
  ```

### 3.2. Mark Motion Event as Viewed
- **Endpoint:** `PATCH /api/motion-events/{id}/viewed`
- **Access:** Administrator & Observer
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "message": "Event telah ditandai sudah dilihat"
  }
  ```

---

## 4. Internal Service Inter-Communication API

Digunakan khusus oleh Node.js Streamer untuk menarik kredensial RTSP yang sudah didekripsi.

- **Endpoint:** `GET /internal/cameras/{id}/stream-target`
- **Header Keamanan:** `X-Internal-Token: [SECRET_INTERNAL_KEY]`
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "rtsp_url": "rtsp://admin:CameraPassword123@192.168.1.101:554/live/ch0",
    "motion_enabled": true,
    "motion_sensitivity": "medium"
  }
  ```

---

## 5. Streaming WebSocket Protocol (Node.js Gateway)

- **WebSocket URL:** `ws://localhost:9999/stream?id={camera_id}`
- **Protokol Pesan:**
  1. **Subscribe:** Saat koneksi terbuka, client mengirim:
     ```json
     { "type": "subscribe", "cameraId": 1 }
     ```
  2. **Video Stream Packets:** Server mengirim data binary stream (fMP4 chunks atau MPEG-TS packets) secara kontinu.
  3. **Motion Alert Broadcast:** Jika worker mendeteksi gerakan, server mem-broadcast pesan JSON ke semua client yang sedang memantau:
     ```json
     {
       "type": "motion_alert",
       "cameraId": 1,
       "timestamp": "2026-10-29T11:35:10Z",
       "snapshotUrl": "/storage/captures/1/..."
     }
     ```
