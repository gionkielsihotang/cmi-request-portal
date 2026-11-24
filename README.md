# Portal Request Publikasi & Riset — Biro CMI BEM FILKOM UB

Sistem Informasi Manajemen dan Pemrosesan Permohonan Publikasi Media Sosial terintegrasi antara Kementerian/Biro di lingkungan **BEM FILKOM Universitas Brawijaya** dengan **Biro Center of Media and Information (CMI)**.

Proyek ini dikembangkan menggunakan arsitektur berorientasi objek berpola **BCE (*Boundary - Control - Entity*)** berbasis **Native PHP & MySQL** sesuai spesifikasi dokumen *Software Design Description* (SDD) mata kuliah Rekayasa Sistem Informasi (RSI).

---

## 📌 Daftar Isi
- [Latar Belakang & Tujuan](#-latar-belakang--tujuan)
- [Peran Pengguna (Actors)](#-peran-pengguna-actors)
- [Fitur Utama & Aturan Bisnis (SOP CMI)](#-fitur-utama--aturan-bisnis-sop-cmi)
- [Arsitektur Sistem (Pola BCE)](#-arsitektur-sistem-pola-bce)
- [Struktur Direktori](#-struktur-direktori)
- [Skema Basis Data](#-skema-basis-data)
- [Panduan Instalasi & Menjalankan (Laragon / XAMPP)](#-panduan-instalasi--menjalankan-laragon--xampp)
- [Kredensial Akun Demo](#-kredensial-akun-demo)
- [Kontributor](#-kontributor)

---

## 🎯 Latar Belakang & Tujuan

Sebelum sistem ini diimplementasikan, pengajuan materi publikasi (Instagram Feeds, Reels, dan Story) dari kementerian internal BEM FILKOM ke Biro CMI sering mengalami bentrok jadwal, ketidaklengkapan materi, pengajuan mendadak yang melanggar SOP H-min, serta respon revisi desain yang berlarut-larut.

Sistem ini hadir untuk:
1. **Menstandarisasi SOP Publikasi**: Mengunci jadwal minimal H-3 untuk konten *Story* dan H-7 untuk konten *Feeds/Reels*.
2. **Menghindari Tabrakan Jadwal**: Sistem kalender dengan slot waktu tetap yang otomatis mengunci slot terpesan (*no double booking*).
3. **Meningkatkan Efisiensi Revisi**: Memberikan batas waktu verifikasi 1 jam untuk pengaju dengan mekanisme persetujuan otomatis (*Auto-Approve*) atau sanksi penalti pengurangan poin kementerian jika terlambat.
4. **Transparansi & Akuntabilitas**: Jejak audit (*audit log*) tindakan staf CMI dan notifikasi sistem otomatis.

---

## 👥 Peran Pengguna (Actors)

| Peran | Deskripsi Hak Akses & Tugas |
| :--- | :--- |
| **Pengaju Internal**<br>*(Kementerian / Biro)* | • Melihat ketersediaan slot tanggal & jam pada kalender.<br>• Mengisi form permohonan publikasi & mengunggah berkas acuan.<br>• Pratinjau permohonan dengan batas waktu toleransi 180 detik.<br>• Memantau status antrean publikasi.<br>• Memverifikasi hasil desain final CMI dalam batas waktu 1 jam (*Approve* / *Revisi*). |
| **Admin Biro CMI**<br>*(Staf & Desainer)* | • Memantau dasbor antrean seluruh permohonan masuk.<br>• Meninjau kepatuhan materi & berkas acuan pemohon.<br>• Menyetujui request untuk mulai diproses (*Status: Diproses*).<br>• Menolak request disertai alasan wajib (*Status: Ditolak*).<br>• Mencatat catatan koreksi kelengkapan materi awal.<br>• Mengunggah berkas hasil karya desain final dan memicu timer verifikasi 1 jam. |

---

## ⚙️ Fitur Utama & Aturan Bisnis (SOP CMI)

1. **Kalender Booking Slot Terjadwal**:
   - Memiliki 4 slot waktu publikasi per hari: **09:00**, **13:00**, **15:00/16:00**, dan **19:00 WIB**.
   - Hari Minggu dinonaktifkan (tutup operasional CMI).
   - Slot yang berstatus selain *Ditolak* otomatis terkunci (*booked*).
2. **Validasi Kategori Konten Berdasarkan Hari (H-min)**:
   - Pengajuan berjarak **H-3 s.d. H-6**: Hanya diizinkan untuk jenis konten **Story**. Opsi Feeds dan Reels otomatis dikunci.
   - Pengajuan berjarak **≥ H-7**: Seluruh jenis konten (**Feeds, Reels, Story**) bebas dipilih.
3. **Pratinjau Request 180 Detik (3 Menit)**:
   - Sebelum submit, pengaju diberikan waktu 180 detik via modal dialog untuk memeriksa ulang rincian form. Bila waktu habis, modal tertutup otomatis demi konsistensi data.
4. **Verifikasi Desain 1 Jam & Auto-Approve**:
   - Setelah CMI mengunggah karya desain, pengaju wajib merespons dalam waktu 3600 detik (1 jam).
   - Jika waktu habis tanpa respon revisi, sistem dapat melakukan persetujuan otomatis (*Auto-Approve* $\rightarrow$ status `Fix`).
5. **Revisi Berpenalti Keterlambatan**:
   - Revisi yang dikirimkan dalam batas toleransi 1 jam diterima secara normal.
   - Revisi yang dikirimkan setelah lewat 1 jam mewajibkan konfirmasi **penalti pengurangan 5 poin kementerian** yang dicatat permanen di database.

---

## 🏛️ Arsitektur Sistem (Pola BCE)

Proyek ini mengadopsi pola perancangan **Boundary - Control - Entity** (BCE):

```
┌─────────────────────────────────────────────────────────────┐
│ 1. BOUNDARY LAYER (Antarmuka Pengguna & Dialog)            │
│ Folder: boundaries/  &  views/                              │
│ • FormRequestInternal, DialogPreviewRequest, DialogPenalti  │
│ • HalamanStatusPengajuan, HalamanDetailRequest, dll.        │
└──────────────────────────────┬──────────────────────────────┘
                               │ memanggil
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. CONTROL LAYER (Alur Logika & Aturan Bisnis)              │
│ Folder: controllers/                                        │
│ • pengajuan/  : ControlRequestInternal, ControlPreviewRequest│
│ • peninjauan/ : ControlPeninjauanRequest, PemrosesanRequest │
│ • desain/     : ControlPengirimanDesain, VerifikasiDesain   │
└──────────────────────────────┬──────────────────────────────┘
                               │ memanipulasi & membaca
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. ENTITY LAYER (Struktur Data & Operasi Basis Data)        │
│ Folder: entities/                                           │
│ • Pengajuan, BerkasLampiran, CatatanRevisi, RiwayatLog      │
│ • PenaltiKementerian, Notifikasi, PengajuInternal, CMI      │
└─────────────────────────────────────────────────────────────┘
```

---

## 📂 Struktur Direktori

```
cmi-request-portal/
├── assets/
│   ├── css/              # Stylesheet modular (global.css, components.css, portal.css, modal.css)
│   ├── js/               # Skrip interaktivitas (countdown.js, pengaju.js, admin.js)
│   ├── img/              # Aset visual (logo cmi.jpeg)
│   └── uploads/          # Direktori berkas fisik
│       ├── referensi/    # Berkas acuan/lampiran dari pengaju (PDF, JPG, PNG)
│       └── hasil_desain/ # Berkas hasil desain final dari staf CMI
├── boundaries/           # Kelas Boundary implementasi SDD (UI / Dialog Abstraction)
├── config/
│   ├── database.php      # Alias konfigurasi database
│   └── koneksi.php       # Inisialisasi koneksi MySQLi, PDO, dan Session Handler
├── controllers/          # Kelas Control pemrosesan logika bisnis
│   ├── pengajuan/        # Pengendali permohonan & pratinjau pengaju
│   ├── peninjauan/       # Pengendali review, proses, & penolakan CMI
│   └── desain/           # Pengendali unggah karya, verifikasi, & revisi
├── database/
│   └── schema_dan_seed.sql # Skrip DDL 9 tabel relasional & DML data dummy awal
├── entities/             # Kelas Entity pemodelan basis data (PDO-based)
├── views/                # Lembar tampilan web fisik (PHP Native SSR)
│   ├── admin/            # Antarmuka Admin CMI (dashboard, review, unggah, tolak)
│   └── pengaju/          # Antarmuka Pengaju (status, kalender, form, verifikasi, revisi)
├── index.php             # Halaman pembuka / Role Switcher
└── README.md             # Dokumentasi proyek
```

---

## 🗄️ Skema Basis Data

Terdapat 9 tabel relasional dalam database `cmi_portal`:

1. **`KEMENTERIAN`** : Master data 10 kementerian/biro BEM FILKOM UB (`kementerianId`, `asalKementerian`).
2. **`PENGAJU_INTERNAL`** : Data mahasiswa pemohon (`idPengaju`, `nim`, `nama`, `email`, `totalPoinPenalti`).
3. **`CMI`** : Data staf/desainer pengelola publikasi (`idCMI`, `namaAdmin`, `accountAdmin`).
4. **`PENGAJUAN`** : Transaksi inti permohonan publikasi (`idPengajuan`, `judulRequest`, `targetTanggal`, `targetJam`, `statusPengajuan`, dll).
5. **`BERKAS_LAMPIRAN`** : Metadata berkas fisik referensi maupun hasil desain (`idBerkas`, `kategoriBerkas`, `pathPenyimpanan`).
6. **`CATATAN_REVISI`** : Umpan balik koreksi desain dari pengaju (`idRevisi`, `teksRevisi`, `waktuRevisi`).
7. **`PENALTI_KEMENTERIAN`** : Riwayat sanksi keterlambatan verifikasi respon pengaju (`poinPenalti = 5`).
8. **`RIWAYAT_LOG`** : Catatan jejak audit aktivitas staf CMI (`idLog`, `idCMI`, `aksi`, `waktu`).
9. **`NOTIFIKASI`** : Riwayat pesan pemberitahuan sistem ke email pengaju (`idNotifikasi`, `isiPesan`).

---

## 🚀 Panduan Instalasi & Menjalankan (Laragon / XAMPP)

### Prasyarat
- **PHP**: Versi 7.4, 8.0, 8.1, atau 8.2+ (dengan ekstensi `mysqli` dan `pdo_mysql` aktif).
- **Web Server & Database**: Laragon (direkomendasikan) atau XAMPP.
- **Browser**: Google Chrome, Mozilla Firefox, atau Microsoft Edge.

### Langkah-Langkah:
1. **Pindahkan Folder Proyek**:
   Letakkan folder proyek ini ke dalam direktori root web server Anda:
   - Jika menggunakan **Laragon**: `C:\laragon\www\cmi-request-portal`
   - Jika menggunakan **XAMPP**: `C:\xampp\htdocs\cmi-request-portal`

2. **Jalankan Layanan Server**:
   Buka Laragon / XAMPP Control Panel, lalu klik **Start All** (Apache & MySQL).

3. **Inisialisasi Database**:
   - *Cara Otomatis*: Sistem pada `config/koneksi.php` telah diprogram untuk otomatis membuat database `cmi_portal` dan mengeksekusi seed data saat aplikasi dibuka pertama kali.
   - *Cara Manual (Alternatif)*:
     1. Buka HeidiSQL (bawaan Laragon) atau phpMyAdmin (`http://localhost/phpmyadmin`).
     2. Buat database baru bernama `cmi_portal`.
     3. Impor file `database/schema_dan_seed.sql` ke dalam database `cmi_portal`.

4. **Buka Aplikasi di Browser**:
   Buka peramban web dan akses alamat:
   ```
   http://localhost/cmi-request-portal/
   ```

---

## 🧪 Pengujian Otomatis (Unit Testing)

Tersedia berkas *automated test suite* untuk menguji method paling kompleks `ControlRequestInternal::kirimDataRequest()` mencakup 13 skenario kasus uji (Alur Normal & Alur Alternatif):

- **Menjalankan via Browser**:
  Akses URL: `http://localhost/cmi-request-portal/tests/test_kirim_data_request.php`
- **Menjalankan via Terminal CLI**:
  ```bash
  php tests/test_kirim_data_request.php
  ```

---

## 🔑 Kredensial Akun Demo

Sistem menyediakan mekanisme *Role Switcher* instan pada halaman pembuka (`index.php`):

| Role Akun | Nama Akun Demo | Keterangan / Asal Instansi | Akses Masuk |
| :--- | :--- | :--- | :--- |
| **Pengaju Internal** | Gion Yehezkiel Sihotang | Kementerian Human Capital (BEM FILKOM UB) | Klik tombol *"Masuk sebagai Pengaju"* |
| **Admin CMI** | Adinda Staf CMI | Biro Center of Media and Information (CMI) | Klik tombol *"Masuk sebagai Admin CMI"* |

---


