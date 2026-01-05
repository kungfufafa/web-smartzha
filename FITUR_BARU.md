# Fitur Baru (di luar core README)

Dokumen ini menjelaskan modul/fitur tambahan yang **sudah terimplementasi di kode** namun belum tercantum pada daftar “MENU FITUR” di README.
Target dokumen ini adalah teknis dan _traceable_ (bisa ditelusuri ke kode + DB), bukan sekadar deskripsi produk.

> Stack: CodeIgniter 3 + Ion Auth (role/permission via `groups` & `users_groups`).

---

## Prasyarat Teknis

1. **Migrasi DB tambahan**
	 - Semua skema ada di folder `assets/app/db/`.
	 - Jalankan SQL sesuai modul:
		 - Pembayaran/Tagihan: `assets/app/db/pembayaran.sql`
		 - Orang Tua: `assets/app/db/orangtua.sql`
		 - Tendik: `assets/app/db/tendik.sql`
		 - Absensi V2 + Pengajuan: `assets/app/db/absensi.sql`
	 - `absensi.sql` bersifat “idempotent-ish” (buat tabel yang belum ada + patch ALTER + seed/upsert data).

2. **Folder upload**
	 - Pastikan folder berikut writable oleh web server:
		 - `uploads/pembayaran/qris/`
		 - `uploads/pembayaran/bukti/` (subfolder otomatis per tahun/bulan: `YYYY/MM/`)
		 - `uploads/absensi/` (subfolder otomatis per tahun/bulan: `YYYY/MM/`)

3. **Role/Izin akses (Ion Auth groups)**
	 - Admin: `ion_auth->is_admin()`
	 - Guru/Siswa/Tendik/Orangtua: `ion_auth->in_group('<nama_group>')`

---

## Ringkasan Modul

1. **Pembayaran/Tagihan** (Admin + Siswa + Orang Tua)
2. **Portal Orang Tua** (switch anak + nilai/absensi/tagihan)
3. **Portal Tendik** (dashboard tendik + integrasi Absensi)
4. **Absensi Module V2** (shift, lintas hari, GPS/QR, bypass, audit, laporan)
5. **Pengajuan Izin/Cuti/Lembur + Izin Keluar** (sinkron ke log absensi)
6. **API untuk Mobile App** (JSON endpoint untuk Flutter)
7. **LuckySheet** (UI spreadsheet untuk guru/admin)

---

## 1) Pembayaran / Tagihan

### Tujuan
Mengelola tagihan siswa (invoice) dan proses upload bukti bayar, termasuk verifikasi admin + audit trail.

### Lokasi Implementasi
- Admin: `application/controllers/Pembayaran.php`
- Siswa: `application/controllers/Tagihanku.php`
- Orang Tua: `application/controllers/Orangtua.php` (bagian tagihan)
- Model: `application/models/Pembayaran_model.php`
- View:
	- Admin: `application/views/pembayaran/`
	- Siswa: `application/views/pembayaran/siswa/`
	- Orang Tua: `application/views/members/orangtua/tagihan/`

### Skema DB
Sumber: `assets/app/db/pembayaran.sql`

Tabel utama:
- `pembayaran_config` — konfigurasi QRIS statis + rekening + instruksi.
- `pembayaran_jenis` — master jenis tagihan (unik per `kode_jenis`).
- `pembayaran_tagihan` — invoice per siswa.
- `pembayaran_transaksi` — bukti bayar + status verifikasi.
- `pembayaran_log` — audit log aksi penting.

Status penting (as implemented):
- `pembayaran_tagihan.status`: `belum_bayar | menunggu_verifikasi | lunas | ditolak | expired`
- `pembayaran_transaksi.status`: `pending | verified | rejected | cancelled`

Kunci/format:
- `pembayaran_tagihan.kode_tagihan`: `TG-YYYYMM-XXXXX` (dibuat oleh `Pembayaran_model::generateKodeTagihan()` / batch generator).
- `pembayaran_transaksi.kode_transaksi`: `TRX-YYYYMMDD-XXXXX`.

### Aturan Bisnis & Validasi
1. **Jenis tagihan bulanan (recurring)**
	 - Jika `pembayaran_jenis.is_recurring = 1`, maka input `bulan` dan `tahun` wajib.
	 - Sistem mencegah duplikasi per siswa+jenis+bulan+tahun+periode (`checkTagihanExists`).

2. **Upload bukti bayar (Siswa/Orang Tua)**
	 - Allowed types: `jpg|jpeg|png|pdf` (`Pembayaran_model::ALLOWED_UPLOAD_TYPES`).
	 - Max size: `2048 KB` (`Pembayaran_model::MAX_UPLOAD_SIZE_KB`).
	 - Path simpan:
		 - Bukti bayar: `uploads/pembayaran/bukti/YYYY/MM/<encrypted_name>`
		 - QRIS image (admin config): `uploads/pembayaran/qris/<encrypted_name>`
	 - Deteksi bukti duplikat:
		 - Sistem menghitung `SHA256` file (`bukti_bayar_hash`) dan menolak jika hash sudah dipakai pada transaksi lain yang statusnya bukan `rejected`.

3. **Batas reject**
	 - `Pembayaran_model::MAX_REJECT_ATTEMPTS = 3`.
	 - Jika transaksi ditolak 3x, status transaksi menjadi `cancelled` dan siswa tidak bisa upload ulang untuk tagihan tersebut.

### Alur Proses (End-to-End)
1. **Admin membuat tagihan**
	 - Admin memilih `id_jenis`, periode aktif (`id_tp`, `id_smt`), siswa target.
	 - Sistem membuat baris pada `pembayaran_tagihan` dengan status awal `belum_bayar`.

2. **Siswa/Orang Tua upload bukti bayar**
	 - Controller (`Tagihanku::uploadBukti()` / `Orangtua::uploadBukti()`) melakukan upload file, menghitung hash, lalu memanggil `Pembayaran_model::processUploadBukti()`.
	 - Di model:
		 - Validasi kepemilikan tagihan (tagihan harus milik siswa bersangkutan).
		 - Validasi status tagihan (hanya `belum_bayar` atau `ditolak`).
		 - Validasi max reject attempt.
		 - Cek duplikasi hash.
		 - Insert `pembayaran_transaksi` status `pending` dan set tagihan menjadi `menunggu_verifikasi`.
		 - Catat `pembayaran_log` action `upload_bukti`.

3. **Admin verifikasi**
	 - Approve:
		 - Lock row transaksi (`SELECT ... FOR UPDATE`).
		 - Set transaksi: `verified`, isi `verified_by`, `verified_at`, `catatan_admin`.
		 - Set tagihan: `lunas`.
		 - Catat log `verify_approve`.
	 - Reject:
		 - Lock row transaksi.
		 - Wajib isi alasan (`catatan_admin`).
		 - Increment `reject_count`.
		 - Status transaksi: `rejected` atau `cancelled` jika sudah 3x.
		 - Status tagihan: `ditolak`.
		 - Catat log `verify_reject`.

### Endpoint/Route Teknis (CI3)
Admin (`Pembayaran`):
- `GET /pembayaran` (dashboard)
- `GET /pembayaran/config` + `POST /pembayaran/saveConfig`
- `GET /pembayaran/jenis` + `POST /pembayaran/dataJenis` + `POST /pembayaran/saveJenis` + `POST /pembayaran/deleteJenis`
- `GET /pembayaran/tagihan` + `POST /pembayaran/dataTagihan` + `GET /pembayaran/createTagihan` + `POST /pembayaran/createTagihanProcess`
- `GET /pembayaran/verifikasi` + `POST /pembayaran/dataVerifikasi` + `POST /pembayaran/approve` + `POST /pembayaran/reject`
- `GET /pembayaran/riwayat` + `GET /pembayaran/laporan` (+ endpoint JSON laporan)

Siswa (`Tagihanku`):
- `GET /tagihanku`
- `GET /tagihanku/bayar/{id_tagihan}`
- `POST /tagihanku/uploadBukti`
- `GET /tagihanku/riwayat`

Orang Tua (`Orangtua`):
- `GET /orangtua/tagihan`
- `GET /orangtua/bayar/{id_tagihan}`
- `POST /orangtua/uploadBukti`
- `GET /orangtua/riwayat`

---

## 2) Role + Portal Orang Tua

### Tujuan
Memberikan portal orang tua untuk melihat data anak (nilai hasil, kehadiran, tagihan) dengan kontrol akses berbasis relasi parent→siswa.

### Lokasi Implementasi
- Portal: `application/controllers/Orangtua.php`
- Model: `application/models/Orangtua_model.php`
- View: `application/views/members/orangtua/`
- Admin user management:
	- Controller: `application/controllers/Userorangtua.php`
	- View: `application/views/users/orangtua/`

### Skema DB
Sumber: `assets/app/db/orangtua.sql`

Komponen:
- `groups.name = 'orangtua'` (dibuat/di-upsert oleh SQL).
- `master_orangtua` — profil orang tua (relasi opsional ke `users`).
- `parent_siswa` — mapping orang tua (`users.id`) ke `master_siswa.id_siswa` + `relasi` (`ayah|ibu|wali`).
- Patch tambahan: `master_siswa.id_user_orangtua` (opsional/legacy linkage).

### Kontrol Akses (as implemented)
- Hanya user dengan group `orangtua` yang dapat mengakses (`Orangtua::__construct`).
- Semua akses ke data anak divalidasi via `Orangtua_model::isParentOfSiswa($user_id, $id_siswa)`.

### Mekanisme “Switch Anak”
- Anak aktif disimpan di session: `selected_anak_id`.
- Route: `GET /orangtua/switchAnak/{id_siswa}`.
- Hanya bisa memilih siswa yang ada di `parent_siswa` milik user.

### Fitur yang Ditampilkan
- Nilai hasil: `GET /orangtua/hasil` (menggunakan `Cbt_model` + `Rapor_model` sesuai implementasi).
- Kehadiran: `GET /orangtua/kehadiran` (rekap bulanan dari modul akademik yang sudah ada).
- Tagihan + bayar: `GET /orangtua/tagihan` dan upload bukti menggunakan proses yang sama dengan siswa (`Pembayaran_model::processUploadBukti()`).

---

## 3) Role + Portal Tendik

### Tujuan
Memberikan portal khusus tenaga kependidikan (tendik) dan integrasi tampilan untuk modul Absensi.

### Lokasi Implementasi
- Portal: `application/controllers/Tendik.php`
- Model: `application/models/Tendik_model.php`
- View: `application/views/members/tendik/`
- Sidebar: `application/views/members/tendik/templates/sidebar.php`

### Skema DB
Sumber: `assets/app/db/tendik.sql`

Komponen:
- `groups.name = 'tendik'` (di-upsert).
- `master_tendik` — profil tendik, termasuk `tipe_tendik` (TU, PUSTAKAWAN, SATPAM, dll) dan relasi opsional ke `users`.

---

## 4) Absensi Module V2 (Shift, GPS/QR, Bypass, Audit)

### Tujuan
Absensi untuk multi role (guru/siswa/tendik/admin) yang mendukung shift lintas hari, metode GPS/QR/manual, bypass, audit trail, dan reporting.

### Lokasi Implementasi
- Controller utama: `application/controllers/Absensi.php`
- Controller legacy (kompatibilitas menu lama): `application/controllers/Absensimanager.php` (deprecated, rute lama masih ada)
- Model: `application/models/Absensi_model.php`, `application/models/Shift_model.php`
- View: `application/views/absensi/`
- Upload foto absensi: `uploads/absensi/YYYY/MM/`

### Skema DB
Sumber: `assets/app/db/absensi.sql`

Tabel inti (ringkas):
- Shift & jadwal:
	- `master_shift` (jam masuk/pulang, lintas hari, toleransi)
	- `pegawai_shift` (fixed assignment)
	- `shift_jadwal` (override harian)
- Konfigurasi:
	- `absensi_config` (global)
	- `absensi_group_config` (per group/kode_tipe)
- Lokasi:
	- `absensi_lokasi` (lat/long + radius)
- Log absensi:
	- `absensi_logs` (checkin/checkout, metode, koordinat, foto, status)
- QR:
	- `absensi_qr_token` (token + masa berlaku)
- Bypass:
	- `absensi_bypass_request` (approval bypass radius/metode)
- Pengajuan:
	- `absensi_pengajuan` + `master_jenis_izin`
- Audit:
	- `absensi_audit_log` (JSON before/after + ip/user-agent)

### Resolusi Shift (priority)
Sesuai `Shift_model::getUserShift()`:
1. `shift_jadwal` (override spesifik tanggal)
2. `pegawai_shift` (fixed assignment terbaru berdasarkan `tgl_efektif`)
3. `absensi_group_config.id_shift_default` (fallback per group)

### Konfigurasi Default (contoh key penting)
Di-seed oleh `absensi.sql`:
- `enable_gps`, `enable_qr`, `enable_manual`
- `require_photo_checkin`, `require_photo_checkout`
- `allow_bypass_request`, `bypass_auto_approve`
- `qr_validity_minutes`, `qr_refresh_interval`
- `default_radius_meter`, `max_bypass_per_month`
- `late_threshold_minutes`, `working_days`, `timezone`

### Mekanisme Check-in/Check-out (as implemented)
Route utama untuk user non-admin: `GET /absensi/checkin`.

Validasi penting:
- Sistem mencegah double check-in jika masih ada log yang belum check-out (`Absensi_model::getOpenLog()`), termasuk kasus shift lintas hari.
- Jika user sudah tercatat status `Izin/Sakit/Cuti/Dinas Luar` pada hari itu, check-in ditolak.
- Metode absensi (GPS/QR/Manual) divalidasi oleh `Absensi_model::validateAttendanceMethod()` dengan mempertimbangkan config user.

Upload foto (base64):
- Diproses di `Absensi::handlePhotoUpload()`.
- Validasi: prefix base64 `data:image/...`, ukuran min 1KB, max 5MB, validasi MIME + dimensi.
- Output: path relatif `uploads/absensi/YYYY/MM/<type>_<user>_<timestamp>.<ext>`.

Audit:
- Aksi check-in/out dan perubahan penting dicatat via `Absensi_model::logAudit()` ke `absensi_audit_log`.

### Endpoint/Route Teknis (CI3)
User:
- `GET /absensi` (admin diarahkan ke dashboard; non-admin ke halaman check-in)
- `GET /absensi/checkin`
- `POST /absensi/doCheckin`
- `POST /absensi/doCheckout`
- `GET /absensi/riwayat?bulan=MM&tahun=YYYY`
- `GET /absensi/jadwal`

Admin (contoh, tidak exhaustif):
- `GET /absensi/dashboard_admin`
- `GET /absensi/config` (pengaturan global/group/lokasi sesuai implementasi)
- Rute legacy: `GET /absensimanager/...` (lihat catatan di controller: deprecated)

---

## 5) Pengajuan Izin/Cuti + Izin Keluar Siswa

### Tujuan
Workflow pengajuan izin/sakit/cuti/dinas/lembur dan sinkronisasi status ke `absensi_logs` setelah disetujui. Termasuk “Izin Keluar” (pulang lebih awal) yang mengisi jam pulang.

### Lokasi Implementasi
- Controller: `application/controllers/Pengajuan.php`
- Model: `application/models/Pengajuan_model.php`
- View: `application/views/absensi/pengajuan/`

### Skema DB
Sumber: `assets/app/db/absensi.sql`

Tipe pengajuan (`absensi_pengajuan.tipe_pengajuan`):
- `Izin | Sakit | Cuti | Dinas | Lembur | Koreksi | IzinKeluar`

Status pengajuan (`absensi_pengajuan.status`):
- `Pending | Disetujui | Ditolak | Dibatalkan`

Sinkronisasi:
- Saat status berubah menjadi `Disetujui`, model memanggil `Pengajuan_model::syncToAbsensiLogs()`.
- Untuk pengajuan harian/rentang, model melakukan upsert ke `absensi_logs` untuk setiap tanggal (tanpa menimpa jika sudah ada `jam_masuk`).
- Untuk `IzinKeluar`, model mengisi `absensi_logs.jam_pulang`, `pulang_awal_menit`, dan mengaitkan `id_pengajuan`.

Catatan teknis penting:
- Form “Izin Keluar” mengambil daftar siswa hadir dari `Absensi_model::getOpenAttendanceUsers()` yang berbasis `users.id`. Di controller, parameter POST bernama `id_siswa`, namun nilainya dipakai sebagai `id_user` pada tabel absensi (as implemented).

### Endpoint/Route Teknis
- `GET /pengajuan` (daftar pengajuan user)
- `POST /pengajuan/create` (buat pengajuan)
- `GET /pengajuan/manage` (admin, daftar pending)
- `POST /pengajuan/approve` (admin, setujui/tolak)
- `GET /pengajuan/formIzinKeluar` (admin/guru)
- `POST /pengajuan/izinKeluar` (admin/guru)

---

## 6) API untuk Mobile App

### Tujuan
Menyediakan endpoint JSON untuk Flutter Mobile App.

### Lokasi Implementasi
- Controller: `application/controllers/Api.php`

### Pola Autentikasi (as implemented)
- Menggunakan Ion Auth session (bukan token stateless):
	- `Api::login()` melakukan `ion_auth->login(...)`.
	- Endpoint lain umumnya memanggil `check_login()`.
- Pembatasan role:
	- Login via mobile dibatasi untuk group `siswa`.
- Validasi klien:
	- Ada pemeriksaan _user-agent_ (`SIMS-ALZ-...`) / fallback bukan “unknown”.

> Catatan: Karena `Api.php` sangat besar dan berisi banyak endpoint (CBT, jadwal, dll), dokumentasi endpoint sebaiknya dipisah per domain bila diperlukan.

---

## 7) LuckySheet

### Tujuan
Integrasi UI spreadsheet (LuckySheet) untuk kebutuhan input/rekap berbasis web.

### Lokasi Implementasi
- Controller: `application/controllers/Luckysheet.php`
- View: `application/views/members/guru/luckyview.php`
- Asset plugin: `assets/plugins/luckysheet/`

### Kontrol Akses
- Hanya Admin atau Guru (`ion_auth->is_admin()` atau `in_group('guru')`).

---
