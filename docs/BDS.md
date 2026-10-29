# Backend Design Specification (BDS)

Spesifikasi rancangan backend untuk arsitektur hibrida **PHP 8.x** (Web, Auth, REST API, Database) dan **Node.js** (Streaming Media Gateway & Motion Worker).

---

## 1. PHP Application Structure & Pattern

Backend PHP dirancang menggunakan pola **Lightweight MVC (Model-View-Controller)** tanpa framework berat agar ringan, cepat dijalankan di XAMPP, dan minim jejak memori (*low memory footprint*).

### 1.1. Core Routing & Dispatching
- File `index.php` bertindak sebagai Front Controller yang menangkap request URL:
  - Routing halaman: `/login`, `/dashboard`, `/cameras`, `/users`, `/motion-events`.
  - Routing API: `/api/cameras`, `/api/motion-events`, `/api/login`, `/api/logout`.
- Middleware:
  - `AuthMiddleware`: Memastikan user memiliki session aktif.
  - `AdminMiddleware`: Memastikan `$_SESSION['user_role'] === 'admin'`.
  - `CsrfMiddleware`: Memvalidasi `X-CSRF-Token` pada request mutasi (POST/PUT/DELETE).

### 1.2. Database Abstraction Layer (PDO)
- Menggunakan Singleton pattern pada `config/Database.php` untuk mengelola koneksi PDO tunggal per-request:
  ```php
  class Database {
      private static ?PDO $instance = null;
      public static function getConnection(): PDO {
          if (self::$instance === null) {
              $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
              self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                  PDO::ATTR_EMULATE_PREPARES   => false,
              ]);
          }
          return self::$instance;
      }
  }
  ```

---

## 2. Controller & Business Logic Design

1. **`AuthController`:**
   - Menangani autentikasi, verifikasi password bcrypt, regenerasi ID sesi, dan pencatatan audit log login.
2. **`CameraController`:**
   - Menangani CRUD data CCTV.
   - Enkripsi password kamera menggunakan `AES-256-CBC` sebelum masuk ke database.
   - Mengembalikan daftar kamera ke frontend dengan IP & password disembunyikan (*stream masking*).
3. **`MotionController`:**
   - Melayani pagination dan filter data riwayat motion detection.
   - Menyediakan endpoint untuk memperbarui status `is_viewed = 1`.
4. **`InternalApiController`:**
   - Melayani endpoint khusus untuk Node.js Streamer yang dilindungi oleh header `X-Internal-Token`.

---

## 3. Node.js Stream Gateway Architecture

Node.js bertindak sebagai microservice media streaming:
1. **`StreamManager`:**
   - Mengelola koleksi child process FFmpeg aktif.
   - Memetakan `camera_id` ke instance proses FFmpeg dan daftar client WebSocket yang sedang subscribe.
   - On-demand cleanup: Menghentikan proses FFmpeg setelah 30 detik tanpa viewer.
2. **`MotionDetector`:**
   - Menggunakan FFmpeg sub-process untuk mengekstrak 1 frame/detik resolusi rendah.
   - Menganalisis perbedaan buffer raw frame (grayscale pixel difference).
   - Menyimpan gambar snapshot ke direktori bersama `storage/captures/` dan mengirim request HTTP webhook ke PHP/MySQL untuk logging.
