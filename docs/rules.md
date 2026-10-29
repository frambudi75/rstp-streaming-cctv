# Coding & Operational Rules

Aturan baku pengembangan (*developer guidelines*) dan standar kualitas kode untuk proyek **RTSP Web CCTV Monitoring System**.

---

## 1. PHP Coding Standards (Backend)

1. **Standar Kode:** Mengikuti standar **PSR-12 (Extended Coding Style)**.
2. **Database Queries:**
   - Wajib menggunakan **PDO Prepared Statements** dengan parameter binding.
   - Dilarang keras melakukan konkatenasi variabel string langsung ke dalam klausa SQL.
3. **Penanganan Error & Logging:**
   - Gunakan blok `try-catch` untuk operasi I/O, database, dan pemanggilan layanan eksternal.
   - Jangan pernah menampilkan pesan error SQL mentah (`PDOException::getMessage()`) ke browser pengguna produksi. Log pesan detail ke file internal dan kembalikan pesan ramah pengguna.
4. **Keamanan Input & Output:**
   - Sanitasi semua data yang masuk via `$_POST`, `$_GET`, atau payload JSON.
   - Escape seluruh output HTML menggunakan `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.

---

## 2. JavaScript & Node.js Standards

1. **Modern ECMAScript:** Gunakan sintaks ES6+ (`const`/`let`, arrow functions, destructuring).
2. **Asynchronous Handling:** Gunakan `async / await` dibandingkan callback hell, selalu bungkus dalam blok `try / catch`.
3. **Child Process & Stream Safety:**
   - Setiap proses FFmpeg yang di-spawn (`child_process.spawn`) wajib memiliki event listener `error`, `exit`, dan `stderr` untuk mencegah proses *zombie* atau kebocoran memori (*memory leak*).
   - Selalu bersihkan resource proses FFmpeg saat koneksi WebSocket client terputus (*disconnect*).
4. **WebSocket Handling:**
   - Terapkan mekanisme heartbeat (ping/pong) untuk mendeteksi koneksi *stale* (putus tanpa sinyal close).

---

## 3. Git & Repository Hygiene

1. **File yang Dilarang Masuk Git (`.gitignore`):**
   - File konfigurasi rahasia: `.env`
   - Direktori dependensi: `node_modules/`, `vendor/`
   - File media dinamis: `storage/captures/*` (kecuali file `.gitkeep`)
   - Log aplikasi: `*.log`
2. **Format Pesan Commit (Conventional Commits):**
   - `feat: [deskripsi fitur baru]`
   - `fix: [perbaikan bug]`
   - `docs: [pembaruan dokumentasi]`
   - `refactor: [perapihan struktur kode tanpa mengubah fungsi]`
   - `chore: [update dependensi / konfigurasi build]`

---

## 4. Operational Safety

- Jangan pernah mematikan proteksi CSRF demi kemudahan testing.
- Password RTSP kamera wajib melalui fungsi enkripsi `AES-256-CBC` sebelum disimpan ke database.

Aturan baku pengembangan (*developer guidelines*) dan standar kualitas kode untuk proyek **RTSP Web CCTV Monitoring System**.

---

## 1. PHP Coding Standards (Backend)

1. **Standar Kode:** Mengikuti standar **PSR-12 (Extended Coding Style)**.
2. **Database Queries:**
   - Wajib menggunakan **PDO Prepared Statements** dengan parameter binding.
   - Dilarang keras melakukan konkatenasi variabel string langsung ke dalam klausa SQL.
3. **Penanganan Error & Logging:**
   - Gunakan blok `try-catch` untuk operasi I/O, database, dan pemanggilan layanan eksternal.
   - Jangan pernah menampilkan pesan error SQL mentah (`PDOException::getMessage()`) ke browser pengguna produksi. Log pesan detail ke file internal dan kembalikan pesan ramah pengguna.
4. **Keamanan Input & Output:**
   - Sanitasi semua data yang masuk via `$_POST`, `$_GET`, atau payload JSON.
   - Escape seluruh output HTML menggunakan `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.

---

## 2. JavaScript & Node.js Standards

1. **Modern ECMAScript:** Gunakan sintaks ES6+ (`const`/`let`, arrow functions, destructuring).
2. **Asynchronous Handling:** Gunakan `async / await` dibandingkan callback hell, selalu bungkus dalam blok `try / catch`.
3. **Child Process & Stream Safety:**
   - Setiap proses FFmpeg yang di-spawn (`child_process.spawn`) wajib memiliki event listener `error`, `exit`, dan `stderr` untuk mencegah proses *zombie* atau kebocoran memori (*memory leak*).
   - Selalu bersihkan resource proses FFmpeg saat koneksi WebSocket client terputus (*disconnect*).
4. **WebSocket Handling:**
   - Terapkan mekanisme heartbeat (ping/pong) untuk mendeteksi koneksi *stale* (putus tanpa sinyal close).

---

## 3. Git & Repository Hygiene

1. **File yang Dilarang Masuk Git (`.gitignore`):**
   - File konfigurasi rahasia: `.env`
   - Direktori dependensi: `node_modules/`, `vendor/`
   - File media dinamis: `storage/captures/*` (kecuali file `.gitkeep`)
   - Log aplikasi: `*.log`
2. **Format Pesan Commit (Conventional Commits):**
   - `feat: [deskripsi fitur baru]`
   - `fix: [perbaikan bug]`
   - `docs: [pembaruan dokumentasi]`
   - `refactor: [perapihan struktur kode tanpa mengubah fungsi]`
   - `chore: [update dependensi / konfigurasi build]`

---

## 4. Operational Safety

- Jangan pernah mematikan proteksi CSRF demi kemudahan testing.
- Password RTSP kamera wajib melalui fungsi enkripsi `AES-256-CBC` sebelum disimpan ke database.
