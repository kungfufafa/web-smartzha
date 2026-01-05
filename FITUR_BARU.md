# Fitur Baru (di luar core README)

Dokumen ini menjelaskan fitur tambahan yang sudah ada di implementasi aplikasi, namun **belum tercantum** pada daftar “MENU FITUR” di README.

> Catatan: Aplikasi ini berbasis CodeIgniter 3. Banyak fitur baru membutuhkan tabel DB tambahan pada folder `assets/app/db/`.

---

## Ringkasan

Fitur tambahan utama:

1. **Pembayaran/Tagihan** (Admin + Siswa + Orang Tua)
2. **Role Orang Tua** (portal orang tua, switch anak, lihat nilai/absensi/tagihan)
3. **Role Tendik** (portal tendik, panel absensi)
4. **Absensi Module V2** (shift, GPS/QR, bypass, audit, laporan)
5. **Pengajuan Izin/Cuti + Izin Keluar Siswa**
6. **API untuk Mobile App**
7. **LuckySheet** (spreadsheet UI untuk guru/admin)

---

## 1) Pembayaran / Tagihan

### Tujuan
Mengelola tagihan siswa dan proses upload bukti pembayaran serta verifikasi oleh admin.

### Akses Role
- Admin: kelola konfigurasi, jenis tagihan, data tagihan, verifikasi, riwayat, laporan.
- Siswa: lihat tagihan sendiri, upload bukti pembayaran, lihat riwayat.
- Orang Tua: lihat tagihan anak, lakukan pembayaran (upload bukti), riwayat.

### Menu (Admin)
Sidebar admin menampilkan bagian **PEMBAYARAN**.

### Lokasi Implementasi
- Controller admin: `application/controllers/Pembayaran.php`
- Controller siswa: `application/controllers/Tagihanku.php`
- Model: `application/models/Pembayaran_model.php`
- View admin: `application/views/pembayaran/`
- View siswa: `application/views/pembayaran/siswa/`
- View orang tua: `application/views/members/orangtua/tagihan/`
- JS pendukung (jika ada): `assets/app/js/**/pembayaran/` atau folder terkait

### Upload & Penyimpanan Bukti
- Folder upload: `uploads/pembayaran/`
- Tipe file: JPG/JPEG/PNG/PDF
- Max size default (lihat konstanta model): `Pembayaran_model::MAX_UPLOAD_SIZE_KB`

---

## 2) Role + Portal Orang Tua

### Tujuan
Memberikan akses orang tua untuk:
- memilih anak (jika lebih dari satu),
- melihat nilai hasil,
- melihat kehadiran,
- melihat & membayar tagihan.

### Lokasi Implementasi
- Portal orang tua: `application/controllers/Orangtua.php`
- Template & halaman: `application/views/members/orangtua/`
- User management (admin): `application/controllers/Userorangtua.php`
- View user orang tua (admin): `application/views/users/orangtua/`
- Model: `application/models/Orangtua_model.php`

### DB
- Skema/tabel: `assets/app/db/orangtua.sql`

---

## 3) Role + Portal Tendik

### Tujuan
Memberikan akses tendik untuk panel tertentu (utamanya absensi).

### Lokasi Implementasi
- Portal tendik: `application/controllers/Tendik.php`
- Template & halaman: `application/views/members/tendik/`
- Sidebar tendik: `application/views/members/tendik/templates/sidebar.php`
- User management (admin): `application/controllers/Usertendik.php`
- View user tendik (admin): `application/views/users/tendik/`
- Model: `application/models/Tendik_model.php`

### DB
- Skema/tabel: `assets/app/db/tendik.sql`

---

## 4) Absensi Module V2 (Shift, GPS/QR, dll)

### Tujuan
Absensi untuk user (guru/siswa/tendik/admin) dengan fitur:
- shift & lintas hari,
- validasi metode absensi (GPS/QR/manual),
- bypass/approval,
- audit log,
- laporan & konfigurasi.

### Lokasi Implementasi
- Controller utama: `application/controllers/Absensi.php`
- Model: `application/models/Absensi_model.php`
- Manager/admin tambahan: `application/controllers/Absensimanager.php` (jika dipakai)
- View admin/guru/siswa/tendik: `application/views/absensi/`
- Helper: `application/helpers/absen_helper.php` (atau helper serupa)

### DB
- Skema/tabel: `assets/app/db/absensi.sql`

---

## 5) Pengajuan Izin/Cuti + Izin Keluar Siswa

### Tujuan
- Guru/user mengajukan izin/sakit/cuti/dinas/lembur.
- Admin melakukan approval.
- Fitur tambahan: **IzinKeluar** untuk siswa (pulang lebih awal) dan tersinkron ke log absensi.

### Lokasi Implementasi
- Controller: `application/controllers/Pengajuan.php`
- Model: `application/models/Pengajuan_model.php`
- View: `application/views/absensi/pengajuan/`

---

## 6) API untuk Mobile App

### Tujuan
Menyediakan endpoint JSON untuk klien mobile (tercantum di komentar controller).

### Lokasi Implementasi
- Controller: `application/controllers/Api.php`

> Catatan: Endpoint cukup besar; dokumentasi endpoint sebaiknya dipisah ke dokumen khusus bila dibutuhkan.

---

## 7) LuckySheet

### Tujuan
Integrasi UI spreadsheet (LuckySheet) untuk kebutuhan tertentu (mis. input/rekap).

### Lokasi Implementasi
- Controller: `application/controllers/Luckysheet.php`
- View: `application/views/members/guru/luckyview.php`
- Asset plugin: `assets/plugins/luckysheet/`

---
