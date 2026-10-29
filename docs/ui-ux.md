# UI/UX & Frontend Design Specification

## 1. Design Philosophy: NOC & Traffic Command Center

Aplikasi pemantauan CCTV (terutama pemantauan lalu lintas jalan raya) memerlukan antarmuka yang dirancang untuk **Network Operations Center (NOC)** dengan prinsip utama:
1. **Dark Theme Utama:** Mengurangi kelelahan mata (*eye-strain*) bagi operator yang memantau layar selama berjam-jam (24/7).
2. **Minimalis & Video-First:** Ruang layar dimaksimalkan untuk area pemutaran video; elemen navigasi dibuat ringkas (*compact*).
3. **Status High-Contrast:** Indikator visual instan untuk status kamera (Online, Offline) dan peringatan gerakan (*motion alert*) yang mencolok.

---

## 2. Color Palette & Typography

| Komponen / Token | Nilai Warna (Hex / Tailwind) | Kegunaan |
| :--- | :--- | :--- |
| **Background Body** | `#0f172a` (`slate-900`) | Latar belakang utama aplikasi |
| **Card / Surface** | `#1e293b` (`slate-800`) | Kartu player kamera, panel, modal |
| **Borders & Dividers** | `#334155` (`slate-700`) | Garis pembatas antar frame kamera |
| **Accent / Primary** | `#3b82f6` (`blue-500`) | Tombol aksi utama, tab aktif |
| **Status: Online** | `#10b981` (`emerald-500`) | Titik indikator kamera aktif |
| **Status: Offline / Error** | `#ef4444` (`red-500`) | Titik indikator kamera mati / putus |
| **Status: Motion Alert** | `#f59e0b` (`amber-500`) | Border berkedip saat gerakan terdeteksi |
| **Typography** | Inter, Roboto, atau System Sans-Serif | Font yang bersih dan mudah dibaca pada resolusi rendah |

---

## 3. Layout Structure & Wireframe

### 3.1. Main Layout Wireframe

```
+---------------------------------------------------------------------------------------+
|  [Logo] CCTV Traffic Monitor   | Grid: [1x1] [2x2] [3x3] [F] | [!] Bell (3) | [Admin v]|
+---------------------------------------------------------------------------------------+
| SIDEBAR     |  MONITORING GRID AREA                                                   |
|             |  +--------------------------------+  +--------------------------------+ |
| [x] Live    |  | CCTV 01 - Simpang Sudirman [G] |  | CCTV 02 - Flyover Thamrin  [G] | |
| [ ] Motion  |  | [=== VIDEO STREAM 1 ==========]|  | [=== VIDEO STREAM 2 ==========]| |
|     Events  |  |                                |  |                                | |
| [ ] Cameras |  | [Zoom: + - R] [Snap] [Full]    |  | [Zoom: + - R] [Snap] [Full]    | |
|     (Admin) |  +--------------------------------+  +--------------------------------+ |
| [ ] Users   |  +--------------------------------+  +--------------------------------+ |
|     (Admin) |  | CCTV 03 - Bundaran HI      [R] |  | CCTV 04 - Simpang Kuningan [G] | |
| [ ] Logs    |  | [ (X) Offline / Reconnect... ] |  | [=== VIDEO STREAM 4 ==========]| |
|             |  |                                |  |                                | |
|             |  | [Retry Connection]             |  | [Zoom: + - R] [Snap] [Full]    | |
|             |  +--------------------------------+  +--------------------------------+ |
+---------------------------------------------------------------------------------------+
```

---

## 4. Video Player Component Specifications

Setiap slot kamera dalam grid dibungkus dalam komponen kartu independen yang memiliki:

### 4.1. Header Bar Kartu Kamera
- **Nama Kamera & Lokasi Jalan:** Teks ringkas (misal: "CCTV 01 - Sudirman").
- **Status Indicator Dot:**
  - Hijau: Stream aktif (menampilkan badge FPS & Bitrate jika tersedia).
  - Merah: Stream offline (menampilkan tombol `Retry`).
  - Kuning/Oranye: Terjadi deteksi gerakan (border kartu menyala *pulsing amber* selama 5 detik).

### 4.2. Video Area & Interaktivitas Digital Zoom
- Area pemutar video membungkus elemen `<canvas>` atau `<video>` yang terhubung ke instance library **Panzoom**:
  - **Scroll Wheel Up / Down:** Zoom-in (hingga 5x) dan Zoom-out (hingga 1x).
  - **Click & Drag:** Menggeser bidang pandang (*pan*) ketika posisi video dalam keadaan ter-zoom.
  - **Pinch Gesture:** Zoom di perangkat tablet dan smartphone.
  - **Double Click:** Reset posisi zoom kembali ke 1x.

### 4.3. Overlay Floating Control Bar (Muncul Saat Hover)
- **Tombol Zoom In (`+`)** & **Zoom Out (`-`)**
- **Tombol Reset Zoom (`1:1`)**
- **Tombol Snapshot Manual (`Kamera Icon`):** Mengambil tangkapan layar instan langsung dari frame video saat itu dan otomatis mendownloadnya.
- **Tombol Fullscreen Single Camera (`Maximize Icon`):** Membuka kamera tersebut memenuhi layar penuh (*Single Focus Mode*).

---

## 5. Grid View Configuration & Responsiveness

| Mode Grid | Penggunaan | Resolusi Layar yang Disarankan |
| :--- | :--- | :--- |
| **Grid 1x1 (Single)** | Pemantauan mendetail 1 titik jalan raya, penyelidikan plat nomor / insiden. | Semua perangkat (Desktop, Tablet, Mobile) |
| **Grid 2x2 (4 Kamera)** | Standar pemantauan umum simpang 4 jalan raya. | Desktop ($\ge 1024\text{px}$) & Tablet Landscape |
| **Grid 3x3 (9 Kamera)** | Pemantauan koridor jalan panjang atau area kota. | Desktop Wide ($\ge 1440\text{px}$) |
| **Mobile Layout** | Otomatis berganti menjadi 1 kolom vertikal (*swipeable* atau *accordion*). | Layar $< 768\text{px}$ |

---

## 6. Motion Event Gallery UI

Halaman khusus untuk meninjau rekaman tangkapan gerakan:
1. **Filter Header:**
   - Dropdown pemilihan kamera CCTV.
   - Date range picker (Hari ini, Kemarin, 7 Hari Terakhir, Custom).
   - Filter status ("Semua", "Belum Dilihat", "Sudah Dilihat").
2. **Timeline Grid:**
   - Ditampilkan dalam bentuk kartu thumbnail snapshot dengan cap waktu (*timestamp* detail hingga detik), nama kamera, dan persentase *confidence score*.
   - Badge "BARU" untuk snapshot yang belum dilihat oleh operator.
3. **Detail Modal View:**
   - Ketika thumbnail diklik, modal resolusi penuh terbuka dengan tombol:
     - `Download High-Res Snapshot`.
     - `Tandai Sudah Diperiksa`.
     - `Buka Live Stream Kamera Ini`.
