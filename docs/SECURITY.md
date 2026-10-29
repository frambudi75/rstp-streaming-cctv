# Security Architecture & Policies

Dokumen ini menjabarkan standar keamanan sistem, kebijakan enkripsi data, dan perlindungan privasi pada infrastruktur pemantauan CCTV berbasis web.

---

## 1. Perlindungan Kredensial Kamera CCTV (AES-256)

### 1.1. Masalah Utama
Kamera CCTV di lapangan sering kali menggunakan password sensitif. Menyimpan password RTSP dalam format *plain text* di database adalah celah keamanan fatal jika database terakses oleh pihak tidak berwenang.

### 1.2. Implementasi Enkripsi Dua Arah
- Algoritma: **AES-256-CBC (Cipher Block Chaining)**
- Kunci Enkripsi (`APP_ENCRYPTION_KEY`): String acak 256-bit (32 bytes) yang disimpan di file konfigurasi `.env` (di luar root web publik).
- Initialization Vector (`IV`): Dihasilkan secara acak menggunakan `random_bytes(16)` per-kamera dan disimpan berdampingan di kolom `iv_vector` database.

#### Contoh Implementasi PHP:
```php
function encryptCctvPassword(string $password, string $key): array {
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
    return [
        'encrypted' => base64_encode($ciphertext),
        'iv'        => base64_encode($iv)
    ];
}

function decryptCctvPassword(string $encrypted, string $ivBase64, string $key): string {
    $iv = base64_decode($ivBase64);
    $ciphertext = base64_decode($encrypted);
    return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
}
```

---

## 2. Penyembunyian RTSP URL dari Client (Stream Masking)

> [!IMPORTANT]
> **Client Browser TIDAK PERNAH menerima RTSP URL asli, IP kamera asli, maupun kredensial kamera!**

- **Arsitektur Zero-Exposure:**
  1. Frontend hanya meminta stream berdasarkan ID kamera anonim: `ws://localhost:9999/stream?id=1` bersama dengan cookie session pengguna yang valid.
  2. Node.js Streaming Gateway memvalidasi session pengguna ke backend PHP melalui internal loopback (`127.0.0.1`).
  3. Node.js mengambil URL RTSP secara internal dan menghubungkannya langsung ke kamera di jaringan CCTV terisolasi.
  4. Pengguna di browser hanya melihat aliran paket data WebRTC / WebSocket canvas.

---

## 3. Otentikasi & Manajemen Sesi Pengguna

1. **Password Hashing:**
   - Password akun pengguna wajib di-hash menggunakan `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`.
2. **Session Security:**
   - Sesi PHP dikonfigurasi dengan:
     ```php
     ini_set('session.cookie_httponly', 1);
     ini_set('session.cookie_samesite', 'Lax');
     ini_set('session.use_strict_mode', 1);
     ```
   - Regenerasi ID sesi (`session_regenerate_id(true)`) dilakukan setiap kali pengguna berhasil login untuk mencegah serangan *Session Fixation*.
3. **Proteksi Brute-Force:**
   - Maksimal 5 kali kegagalan login berturut-turut untuk kombinasi username/IP yang sama.
   - Setelah 5 kali gagal, akun atau IP akan diblokir sementara selama 15 menit.

---

## 4. Proteksi Input & Anti-Eksploitasi

1. **SQL Injection:**
   - Seluruh query database wajib menggunakan PDO Prepared Statements dengan parameter binding (`:param` atau `?`). Penggabungan string langsung ke query dilarang keras.
2. **Cross-Site Scripting (XSS):**
   - Seluruh output dinamis yang dicetak ke tampilan HTML wajib di-escape menggunakan `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF):**
   - Setiap form pengubahan data (tambah/edit/hapus CCTV, ubah user) wajib menyertakan CSRF Token unik yang divalidasi di controller.
4. **Keamanan File Storage Snapshot:**
   - File snapshot yang dihasilkan oleh worker motion detector hanya diizinkan berekstensi `.jpg` atau `.png`.
   - Direktori `storage/captures/` dilindungi agar tidak dapat mengeksekusi file skrip (PHP/CGI) dengan menempatkan file `.htaccess`:
     ```apache
     <FilesMatch "\.(php|php5|phtml|cgi|pl|sh)$">
         Deny from all
     </FilesMatch>
     ```

---

## 5. Audit Trail & Akses Log

Setiap aksi perubahan kritis yang dilakukan oleh Administrator dicatat ke dalam tabel `system_logs`:
- **Penambahan Kamera Baru:** Mencatat nama kamera, IP, dan admin yang menambahkan.
- **Perubahan Konfigurasi Kamera:** Mencatat field yang diubah.
- **Penghapusan Kamera:** Mencatat ID dan nama kamera yang dihapus.
- **Upaya Login Gagal:** Mencatat IP address dan timestamp.
