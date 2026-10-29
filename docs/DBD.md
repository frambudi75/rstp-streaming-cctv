# Database Design Document (DBD)

Dokumen ini mendokumentasikan spesifikasi perancangan database relasional MySQL/MariaDB untuk sistem pemantauan CCTV berbasis web. Detail skema lengkap dan script SQL DDL tersedia pada [docs/database.md](file:///d:/xampp/htdocs/rstp/docs/database.md).

---

## 1. Normalisasi Data

Struktur database dirancang memenuhi kaidah **Third Normal Form (3NF)**:
1. **1NF (First Normal Form):** Tidak ada grup berulang atau kolom multivalue. Nilai atomik untuk semua atribut (nama, IP, port, status).
2. **2NF (Second Normal Form):** Semua atribut non-kunci bergantung sepenuhnya pada *Primary Key* (PK). Tabel `cameras` memisahkan identitas kamera dari `motion_events`.
3. **3NF (Third Normal Form):** Tidak ada ketergantungan transitif. Kredensial kamera dan konfigurasi jaringan berada dalam satu entitas yang relevan, sedangkan data audit dipisahkan ke `system_logs`.

---

## 2. Strategi Indexing & Optimasi Query

Untuk menjaga performa query tetap di bawah 50ms bahkan ketika tabel `motion_events` mencapai ratusan ribu record:

1. **Composite Index pada `motion_events`:**
   - `INDEX (camera_id, detected_at)`: Mengoptimalkan pencarian riwayat deteksi berdasarkan kamera tertentu dalam rentang tanggal tertentu.
   - `INDEX (is_viewed)`: Mempercepat perhitungan jumlah badge notifikasi yang belum dibaca (`SELECT COUNT(*) FROM motion_events WHERE is_viewed = 0`).
2. **Status Index pada `cameras`:**
   - `INDEX (status)`: Mempercepat query pemantauan kamera aktif saat pengguna membuka dashboard.

---

## 3. Strategi Retensi Data & Pembersihan Otomatis (Purging)

File snapshot gambar dan record `motion_events` dapat tumbuh pesat. Sistem menerapkan kebijakan retensi 30 hari:
- **Scheduler Maintenance:**
  Dijalankan berkala melalui Cron (Linux/Docker) atau Windows Task Scheduler:
  ```sql
  -- Hapus record motion_events yang lebih lama dari 30 hari
  DELETE FROM motion_events WHERE detected_at < NOW() - INTERVAL 30 DAY;
  ```
- **File System Cleaner:** Skrip pembersih file menghapus direktori folder tanggal yang sudah kedaluwarsa di `storage/captures/`.
