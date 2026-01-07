# Fitur Baru (di luar core README)

Dokumen ini menjelaskan modul/fitur tambahan yang **sudah terimplementasi di kode** namun belum tercantum pada daftar "MENU FITUR" di README.
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
		 - Presensi + Pengajuan: `assets/app/db/presensi.sql`
	 - `presensi.sql` bersifat "idempotent-ish" (buat tabel yang belum ada + seed/upsert data).

2. **Folder upload**
	 - Pastikan folder berikut writable oleh web server:
		 - `uploads/pembayaran/qris/`
		 - `uploads/pembayaran/bukti/` (subfolder otomatis per tahun/bulan: `YYYY/MM/`)
		 - `uploads/presensi/` (subfolder otomatis per tahun/bulan: `YYYY/MM/`)

3. **Role/Izin akses (Ion Auth groups)**
	 - Admin: `ion_auth->is_admin()`
	 - Guru/Siswa/Tendik/Orangtua: `ion_auth->in_group('<nama_group>')`

---

## Ringkasan Modul

| No | Modul | Aktor | Deskripsi Singkat |
|----|-------|-------|-------------------|
| 1 | Pembayaran/Tagihan | Admin + Siswa + Orang Tua | Invoice & verifikasi bukti bayar |
| 2 | Portal Orang Tua | Orang Tua | Switch anak + nilai/absensi/tagihan |
| 3 | Portal Tendik | Tendik | Dashboard + integrasi Presensi |
| 4 | Presensi | Multi-role | Shift, GPS/QR, bypass, audit |
| 5 | Pengajuan Izin/Cuti | Multi-role | Workflow izin + sinkron log |
| 6 | API Mobile App | Siswa | JSON endpoint untuk Flutter |
| 7 | LuckySheet | Admin/Guru | UI spreadsheet input nilai |

---

## 1) Pembayaran / Tagihan

### Tujuan
Mengelola tagihan siswa (invoice) dan proses upload bukti bayar, termasuk verifikasi admin + audit trail.

### Lokasi Implementasi

#### Controllers
| File | Deskripsi |
|------|-----------|
| `application/controllers/Pembayaran.php` | Admin: CRUD tagihan, verifikasi, laporan |
| `application/controllers/Tagihanku.php` | Siswa: lihat & bayar tagihan |
| `application/controllers/Orangtua.php` | Orang Tua: lihat & bayar tagihan anak |

#### Models
| File | Deskripsi |
|------|-----------|
| `application/models/Pembayaran_model.php` | Business logic pembayaran |

#### Views
| Path | Deskripsi |
|------|-----------|
| `application/views/pembayaran/dashboard.php` | Dashboard admin |
| `application/views/pembayaran/config.php` | Konfigurasi QRIS/rekening |
| `application/views/pembayaran/jenis/data.php` | Master jenis tagihan |
| `application/views/pembayaran/tagihan/data.php` | Daftar tagihan |
| `application/views/pembayaran/tagihan/add.php` | Form tambah tagihan |
| `application/views/pembayaran/verifikasi/data.php` | Daftar perlu verifikasi |
| `application/views/pembayaran/verifikasi/detail.php` | Detail transaksi |
| `application/views/pembayaran/verifikasi/riwayat.php` | Riwayat verifikasi |
| `application/views/pembayaran/laporan/index.php` | Laporan pembayaran |
| `application/views/pembayaran/siswa/tagihan.php` | Tagihan siswa |
| `application/views/pembayaran/siswa/bayar.php` | Form bayar siswa |
| `application/views/pembayaran/siswa/riwayat.php` | Riwayat siswa |
| `application/views/pembayaran/siswa/detail_transaksi.php` | Detail transaksi siswa |
| `application/views/members/orangtua/tagihan/data.php` | Tagihan orangtua |
| `application/views/members/orangtua/tagihan/bayar.php` | Form bayar orangtua |
| `application/views/members/orangtua/tagihan/riwayat.php` | Riwayat orangtua |
| `application/views/members/orangtua/tagihan/detail_transaksi.php` | Detail transaksi orangtua |

### Skema DB
Sumber: `assets/app/db/pembayaran.sql`

**Tabel utama:**
| Tabel | Deskripsi |
|-------|-----------|
| `pembayaran_config` | Konfigurasi QRIS statis + rekening + instruksi |
| `pembayaran_jenis` | Master jenis tagihan (unik per `kode_jenis`) |
| `pembayaran_tagihan` | Invoice per siswa |
| `pembayaran_transaksi` | Bukti bayar + status verifikasi |
| `pembayaran_log` | Audit log aksi penting |

**Views (SQL):**
| View | Deskripsi |
|------|-----------|
| `v_pembayaran_tagihan` | Tagihan + info siswa + kelas |
| `v_pembayaran_transaksi` | Transaksi + info lengkap |

**Status penting:**
- `pembayaran_tagihan.status`: `belum_bayar | menunggu_verifikasi | lunas | ditolak | expired`
- `pembayaran_transaksi.status`: `pending | verified | rejected | cancelled`

**Format kode:**
- `pembayaran_tagihan.kode_tagihan`: `TG-YYYYMM-XXXXX` (auto-generated via trigger)
- `pembayaran_transaksi.kode_transaksi`: `TRX-YYYYMMDD-XXXXX` (auto-generated via trigger)

### Endpoint/Route Teknis

**Admin (`Pembayaran`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/pembayaran` | Dashboard |
| GET | `/pembayaran/config` | Halaman config |
| POST | `/pembayaran/saveConfig` | Simpan config |
| GET | `/pembayaran/jenis` | Master jenis |
| POST | `/pembayaran/dataJenis` | Data jenis (AJAX) |
| POST | `/pembayaran/saveJenis` | Simpan jenis |
| GET | `/pembayaran/getJenis/{id}` | Get jenis by ID |
| POST | `/pembayaran/deleteJenis` | Hapus jenis |
| GET | `/pembayaran/tagihan` | Daftar tagihan |
| POST | `/pembayaran/dataTagihan` | Data tagihan (AJAX) |
| GET | `/pembayaran/createTagihan` | Form buat tagihan |
| POST | `/pembayaran/createTagihanProcess` | Proses buat tagihan |
| GET | `/pembayaran/getTagihan/{id}` | Get tagihan by ID |
| POST | `/pembayaran/updateTagihan` | Update tagihan |
| POST | `/pembayaran/deleteTagihan` | Hapus tagihan |
| GET | `/pembayaran/getSiswaByKelas/{id}` | Get siswa per kelas |
| GET | `/pembayaran/verifikasi` | Daftar verifikasi |
| POST | `/pembayaran/dataVerifikasi` | Data verifikasi (AJAX) |
| GET | `/pembayaran/detailTransaksi/{id}` | Detail transaksi |
| POST | `/pembayaran/approve` | Approve transaksi |
| POST | `/pembayaran/reject` | Reject transaksi |
| GET | `/pembayaran/riwayat` | Riwayat verifikasi |
| POST | `/pembayaran/dataRiwayat` | Data riwayat (AJAX) |
| GET | `/pembayaran/laporan` | Halaman laporan |
| GET | `/pembayaran/laporanHarian` | Laporan harian |
| GET | `/pembayaran/laporanTunggakan` | Laporan tunggakan |

**Siswa (`Tagihanku`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/tagihanku` | Daftar tagihan |
| GET | `/tagihanku/bayar/{id}` | Form bayar |
| GET | `/tagihanku/qris/{id}` | Halaman QRIS |
| POST | `/tagihanku/uploadBukti` | Upload bukti |
| GET | `/tagihanku/riwayat` | Riwayat |
| GET | `/tagihanku/detailTransaksi/{id}` | Detail transaksi |

**Orang Tua (`Orangtua`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/orangtua/tagihan` | Daftar tagihan anak |
| GET | `/orangtua/bayar/{id}` | Form bayar |
| GET | `/orangtua/qris/{id}` | Halaman QRIS |
| POST | `/orangtua/uploadBukti` | Upload bukti |
| GET | `/orangtua/riwayat` | Riwayat |
| GET | `/orangtua/detailTransaksi/{id}` | Detail transaksi |

---

## 2) Role + Portal Orang Tua

### Tujuan
Memberikan portal orang tua untuk melihat data anak (nilai hasil, kehadiran, tagihan) dengan kontrol akses berbasis relasi parent-siswa.

### Lokasi Implementasi

#### Controllers
| File | Deskripsi |
|------|-----------|
| `application/controllers/Orangtua.php` | Portal utama orangtua |
| `application/controllers/Dataorangtua.php` | Admin: CRUD master data orangtua |
| `application/controllers/Userorangtua.php` | Admin: user management orangtua |

#### Models
| File | Deskripsi |
|------|-----------|
| `application/models/Orangtua_model.php` | Business logic orangtua |

#### Views
| Path | Deskripsi |
|------|-----------|
| `application/views/members/orangtua/dashboard.php` | Dashboard orangtua |
| `application/views/members/orangtua/no_anak.php` | Halaman jika belum ada anak |
| `application/views/members/orangtua/nilai/data.php` | Nilai anak |
| `application/views/members/orangtua/absensi/data.php` | Kehadiran anak |
| `application/views/members/orangtua/tagihan/*` | Tagihan (lihat bagian Pembayaran) |
| `application/views/members/orangtua/templates/header.php` | Template header |
| `application/views/members/orangtua/templates/footer.php` | Template footer |
| `application/views/users/orangtua/data.php` | Admin: data user orangtua |
| `application/views/users/orangtua/dashboard.php` | Admin: dashboard user orangtua |

#### Assets
| Path | Deskripsi |
|------|-----------|
| `assets/app/js/master/orangtua/data.js` | JS master data orangtua |
| `assets/app/js/users/orangtua/data.js` | JS user orangtua |

### Skema DB
Sumber: `assets/app/db/orangtua.sql`

**Tabel:**
| Tabel | Deskripsi |
|-------|-----------|
| `groups` | Insert group `orangtua` |
| `master_orangtua` | Profil orangtua (NIK, nama, HP, email, alamat, dll) |
| `parent_siswa` | Mapping orangtua (`users.id`) ke siswa + relasi (`ayah\|ibu\|wali`) |
| `master_siswa.id_user_orangtua` | Patch: legacy linkage (opsional) |

### Kontrol Akses
- Hanya user dengan group `orangtua` yang dapat mengakses (`Orangtua::__construct`).
- Semua akses ke data anak divalidasi via `Orangtua_model::isParentOfSiswa($user_id, $id_siswa)`.

### Mekanisme "Switch Anak"
- Anak aktif disimpan di session: `selected_anak_id`.
- Route: `GET /orangtua/switchAnak/{id_siswa}`.
- Hanya bisa memilih siswa yang ada di `parent_siswa` milik user.

### Endpoint/Route Teknis

**Portal Orangtua:**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/orangtua` | Dashboard |
| GET | `/orangtua/switchAnak/{id}` | Ganti anak aktif |
| GET | `/orangtua/hasil` | Nilai hasil anak |
| GET | `/orangtua/kehadiran` | Kehadiran anak |
| GET | `/orangtua/tagihan` | Tagihan anak |

**Admin - Master Data (`Dataorangtua`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/dataorangtua` | Halaman data |
| POST | `/dataorangtua/data` | Data (AJAX) |
| GET | `/dataorangtua/add` | Form tambah |
| GET | `/dataorangtua/edit/{id}` | Form edit |
| POST | `/dataorangtua/create` | Simpan baru |
| POST | `/dataorangtua/update` | Simpan update |
| POST | `/dataorangtua/delete` | Hapus |

**Admin - User Management (`Userorangtua`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/userorangtua` | Halaman index |
| POST | `/userorangtua/data` | Data (AJAX) |
| GET | `/userorangtua/activate/{id}` | Aktifkan user |
| GET | `/userorangtua/deactivate/{id}` | Nonaktifkan user |
| POST | `/userorangtua/reset_login` | Reset login |
| POST | `/userorangtua/aktifkanSemua` | Aktifkan semua |
| POST | `/userorangtua/nonaktifkanSemua` | Nonaktifkan semua |

---

## 3) Role + Portal Tendik

### Tujuan
Memberikan portal khusus tenaga kependidikan (tendik) dan integrasi tampilan untuk modul Presensi.

### Lokasi Implementasi

#### Controllers
| File | Deskripsi |
|------|-----------|
| `application/controllers/Tendik.php` | Portal utama tendik |
| `application/controllers/Datatendik.php` | Admin: CRUD master data tendik |
| `application/controllers/Usertendik.php` | Admin: user management tendik |

#### Models
| File | Deskripsi |
|------|-----------|
| `application/models/Tendik_model.php` | Business logic tendik |

#### Views
| Path | Deskripsi |
|------|-----------|
| `application/views/members/tendik/dashboard.php` | Dashboard tendik |
| `application/views/members/tendik/profil.php` | Profil tendik |
| `application/views/members/tendik/jadwal.php` | Jadwal kerja |
| `application/views/members/tendik/riwayat.php` | Riwayat presensi |
| `application/views/members/tendik/pengajuan.php` | Pengajuan izin |
| `application/views/members/tendik/bypass_request.php` | Request bypass |
| `application/views/members/tendik/templates/sidebar.php` | Sidebar |
| `application/views/members/tendik/templates/navbar.php` | Navbar |
| `application/views/members/tendik/templates/header.php` | Header |
| `application/views/members/tendik/templates/header_topnav.php` | Header topnav |
| `application/views/members/tendik/templates/footer.php` | Footer |
| `application/views/members/tendik/templates/footer_topnav.php` | Footer topnav |
| `application/views/users/tendik/index.php` | Admin: index user tendik |
| `application/views/users/tendik/data.php` | Admin: data user tendik |
| `application/views/users/tendik/dashboard.php` | Admin: dashboard user tendik |

### Skema DB
Sumber: `assets/app/db/tendik.sql`

**Tabel:**
| Tabel | Deskripsi |
|-------|-----------|
| `groups` | Insert group `tendik` |
| `master_tendik` | Profil tendik (NIP, nama, tipe, jabatan, status kepegawaian, dll) |

**Enum `tipe_tendik`:**
`TU | PUSTAKAWAN | LABORAN | SATPAM | KEBERSIHAN | PENJAGA | TEKNISI | DRIVER | LAINNYA`

**Enum `status_kepegawaian`:**
`PNS | PPPK | Honorer | Kontrak`

### Endpoint/Route Teknis

**Portal Tendik:**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/tendik` | Dashboard |
| GET | `/tendik/absensi` | Redirect ke presensi |
| GET | `/tendik/presensi` | Halaman presensi |
| GET | `/tendik/bypass_request` | Form bypass request |
| GET | `/tendik/riwayat` | Riwayat presensi |
| GET | `/tendik/jadwal` | Jadwal kerja |
| GET | `/tendik/pengajuan` | Pengajuan izin |
| GET | `/tendik/profil` | Profil |
| POST | `/tendik/change_password` | Ganti password |

**Admin - Master Data (`Datatendik`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/datatendik` | Halaman data |
| POST | `/datatendik/data` | Data (AJAX) |
| GET | `/datatendik/add` | Form tambah |
| GET | `/datatendik/edit/{id}` | Form edit |
| POST | `/datatendik/create` | Simpan baru |
| POST | `/datatendik/update` | Simpan update |
| POST | `/datatendik/delete` | Hapus |

**Admin - User Management (`Usertendik`):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/usertendik` | Halaman index |
| POST | `/usertendik/data` | Data (AJAX) |
| GET | `/usertendik/activate/{id}` | Aktifkan user |
| GET | `/usertendik/deactivate/{id}` | Nonaktifkan user |
| POST | `/usertendik/reset_login` | Reset login |
| POST | `/usertendik/aktifkanSemua` | Aktifkan semua |
| POST | `/usertendik/nonaktifkanSemua` | Nonaktifkan semua |

---

## 4) Presensi (Shift, GPS/QR, Bypass, Audit)

### Tujuan
Sistem presensi untuk multi role (guru/siswa/tendik/admin) yang mendukung shift lintas hari, metode GPS/QR/manual, bypass, audit trail, dan reporting.

### Lokasi Implementasi

#### Controllers
| File | Deskripsi |
|------|-----------|
| `application/controllers/Presensi.php` | Controller utama presensi |
| `application/controllers/Pengajuan.php` | Pengajuan izin/cuti/lembur |

#### Models
| File | Deskripsi |
|------|-----------|
| `application/models/Presensi_model.php` | Business logic presensi |
| `application/models/Shift_model.php` | Manajemen shift |
| `application/models/Pengajuan_model.php` | Business logic pengajuan |

#### Views
| Path | Deskripsi |
|------|-----------|
| `application/views/presensi/checkin.php` | Halaman check-in |
| `application/views/presensi/history.php` | Riwayat presensi |
| `application/views/presensi/jadwal.php` | Jadwal user |
| `application/views/presensi/dashboard_admin.php` | Dashboard admin |
| `application/views/presensi/shift_management.php` | Manajemen shift |
| `application/views/presensi/location_management.php` | Manajemen lokasi |
| `application/views/presensi/hari_libur.php` | Manajemen hari libur |
| `application/views/presensi/group_config.php` | Konfigurasi per group |
| `application/views/presensi/global_config.php` | Konfigurasi global |
| `application/views/presensi/jadwal_kerja.php` | Jadwal kerja |
| `application/views/presensi/list_qr_tokens.php` | Daftar QR token |
| `application/views/presensi/bypass_request.php` | Form bypass request |
| `application/views/presensi/pengajuan/index.php` | Daftar pengajuan user |
| `application/views/presensi/pengajuan/manage.php` | Manajemen pengajuan (admin) |

### Skema DB
Sumber: `assets/app/db/presensi.sql`

#### Tabel Master Data
| Tabel | Deskripsi |
|-------|-----------|
| `presensi_shift` | Definisi shift (jam masuk/pulang, toleransi, lintas hari) |
| `presensi_lokasi` | Lokasi kantor (lat/long + radius geofencing) |
| `presensi_jenis_izin` | Jenis izin (sakit, cuti, dinas, dll) |
| `presensi_hari_libur` | Hari libur (nasional/akademik/kantor) |

#### Tabel Konfigurasi
| Tabel | Deskripsi |
|-------|-----------|
| `presensi_config_global` | Konfigurasi sistem global |
| `presensi_config_group` | Konfigurasi per group (guru/tendik/siswa) |
| `presensi_config_user` | Override per user (opsional) |

#### Tabel Jadwal Kerja
| Tabel | Deskripsi |
|-------|-----------|
| `presensi_jadwal_kerja` | Jadwal mingguan per group |
| `presensi_jadwal_tendik` | Jadwal per tipe tendik |
| `presensi_jadwal_user` | Override jadwal per user |
| `presensi_jadwal_override` | Override tanggal spesifik |

#### Tabel Transaksi
| Tabel | Deskripsi |
|-------|-----------|
| `presensi_logs` | Log presensi utama (checkin/checkout) |
| `presensi_qr_token` | Token QR untuk presensi |
| `presensi_bypass` | Request bypass lokasi/metode |
| `presensi_pengajuan` | Pengajuan izin/cuti/lembur |
| `presensi_audit_log` | Audit trail semua aksi |

#### Konfigurasi Global (seed default)
| Key | Deskripsi |
|-----|-----------|
| `max_bypass_per_month` | Maksimal bypass per bulan |
| `bypass_auto_approve` | Auto-approve bypass |
| `qr_validity_minutes` | Durasi validitas QR |
| `qr_refresh_interval` | Interval refresh QR |
| `enable_overtime` | Aktifkan tracking lembur |
| `overtime_require_approval` | Lembur perlu approval |
| `min_overtime_minutes` | Minimal menit untuk lembur |
| `auto_alpha_enabled` | Auto-mark Alpha |
| `auto_alpha_time` | Waktu proses auto-alpha |
| `timezone` | Timezone sistem |

### Endpoint/Route Teknis

**User (Presensi):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/presensi` | Index (redirect sesuai role) |
| GET | `/presensi/checkin` | Halaman check-in |
| POST | `/presensi/do_checkin` | Proses check-in |
| POST | `/presensi/do_checkout` | Proses check-out |
| GET | `/presensi/bypass_request` | Form bypass |
| POST | `/presensi/do_bypass_request` | Submit bypass |
| GET | `/presensi/history` | Riwayat presensi |
| GET | `/presensi/jadwal` | Jadwal kerja |

**Admin (Presensi):**
| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/presensi/dashboard_admin` | Dashboard admin |
| GET | `/presensi/shift_management` | Manajemen shift |
| POST | `/presensi/save_shift` | Simpan shift |
| POST | `/presensi/delete_shift` | Hapus shift |
| GET | `/presensi/kode_shift_check/{kode}` | Cek kode shift |
| GET | `/presensi/location_management` | Manajemen lokasi |
| POST | `/presensi/save_location` | Simpan lokasi |
| POST | `/presensi/delete_location` | Hapus lokasi |
| GET | `/presensi/kode_lokasi_check/{kode}` | Cek kode lokasi |
| GET | `/presensi/hari_libur` | Manajemen libur |
| POST | `/presensi/save_hari_libur` | Simpan libur |
| POST | `/presensi/delete_hari_libur` | Hapus libur |
| GET | `/presensi/group_config` | Konfigurasi group |
| POST | `/presensi/save_group_config` | Simpan config group |
| GET | `/presensi/delete_group_config/{id}` | Hapus config group |
| GET | `/presensi/jadwal_kerja` | Jadwal kerja |
| POST | `/presensi/save_jadwal_kerja` | Simpan jadwal kerja |
| POST | `/presensi/delete_jadwal_kerja` | Hapus jadwal kerja |
| POST | `/presensi/save_jadwal_tendik` | Simpan jadwal tendik |
| POST | `/presensi/delete_jadwal_tendik` | Hapus jadwal tendik |
| GET | `/presensi/search_users` | Cari users (AJAX) |
| GET | `/presensi/get_jadwal_user` | Get jadwal user |
| POST | `/presensi/save_jadwal_user_weekly` | Simpan jadwal user |
| POST | `/presensi/clear_jadwal_user_weekly` | Hapus jadwal user |
| POST | `/presensi/generate_qr_token` | Generate QR token |
| GET | `/presensi/list_qr_tokens` | Daftar QR token |
| GET | `/presensi/global_config` | Konfigurasi global |
| POST | `/presensi/save_global_config` | Simpan config global |

---

## 5) Pengajuan Izin/Cuti + Izin Keluar

### Tujuan
Workflow pengajuan izin/sakit/cuti/dinas/lembur dan sinkronisasi status ke `presensi_logs` setelah disetujui. Termasuk "Izin Keluar" (pulang lebih awal) yang mengisi jam pulang.

### Lokasi Implementasi

#### Controllers
| File | Deskripsi |
|------|-----------|
| `application/controllers/Pengajuan.php` | Controller pengajuan |

#### Models
| File | Deskripsi |
|------|-----------|
| `application/models/Pengajuan_model.php` | Business logic pengajuan |

#### Views
| Path | Deskripsi |
|------|-----------|
| `application/views/presensi/pengajuan/index.php` | Daftar pengajuan user |
| `application/views/presensi/pengajuan/manage.php` | Manajemen pengajuan (admin) |

### Skema DB
Sumber: `assets/app/db/presensi.sql`

**Tipe pengajuan (`presensi_pengajuan.tipe_pengajuan`):**
`Izin | Sakit | Cuti | Dinas | Lembur | Koreksi | IzinKeluar`

**Status pengajuan (`presensi_pengajuan.status`):**
`Pending | Disetujui | Ditolak | Dibatalkan`

**Sinkronisasi:**
- Saat status berubah menjadi `Disetujui`, model memanggil `Pengajuan_model::syncToPresensiLogs()`.
- Untuk pengajuan harian/rentang, model melakukan upsert ke `presensi_logs` untuk setiap tanggal.
- Untuk `IzinKeluar`, model mengisi `presensi_logs.jam_pulang`, `pulang_awal_menit`.

### Endpoint/Route Teknis

| Method | Route | Deskripsi |
|--------|-------|-----------|
| GET | `/pengajuan` | Daftar pengajuan user |
| POST | `/pengajuan/create` | Buat pengajuan baru |
| GET | `/pengajuan/manage` | Daftar pending (admin) |
| POST | `/pengajuan/approve` | Approve/reject pengajuan |
| GET | `/pengajuan/formIzinKeluar` | Form izin keluar |
| POST | `/pengajuan/izinKeluar` | Submit izin keluar |

---

## 6) API untuk Mobile App

### Tujuan
Menyediakan endpoint JSON untuk Flutter Mobile App.

### Lokasi Implementasi

| File | Deskripsi |
|------|-----------|
| `application/controllers/Api.php` | Controller API |

### Pola Autentikasi
- Menggunakan Ion Auth session (bukan token stateless):
	- `Api::login()` melakukan `ion_auth->login(...)`.
	- Endpoint lain umumnya memanggil `check_login()`.
- Pembatasan role:
	- Login via mobile dibatasi untuk group `siswa`.
- Validasi klien:
	- Ada pemeriksaan _user-agent_ (`SIMS-ALZ-...`) / fallback bukan "unknown".

> Catatan: Karena `Api.php` sangat besar dan berisi banyak endpoint (CBT, jadwal, absensi, dll), dokumentasi endpoint sebaiknya dipisah per domain bila diperlukan.

---

## 7) LuckySheet

### Tujuan
Integrasi UI spreadsheet (LuckySheet) untuk kebutuhan input/rekap berbasis web.

### Lokasi Implementasi

| File | Deskripsi |
|------|-----------|
| `application/controllers/Luckysheet.php` | Controller LuckySheet |
| `application/views/members/guru/luckyview.php` | View spreadsheet |
| `assets/plugins/luckysheet/` | Plugin assets |

### Kontrol Akses
- Hanya Admin atau Guru (`ion_auth->is_admin()` atau `in_group('guru')`).

---

## Inventaris File Lengkap

### Per Fitur

#### Pembayaran
```
Controllers:
  application/controllers/Pembayaran.php
  application/controllers/Tagihanku.php

Models:
  application/models/Pembayaran_model.php

Views:
  application/views/pembayaran/dashboard.php
  application/views/pembayaran/config.php
  application/views/pembayaran/jenis/data.php
  application/views/pembayaran/tagihan/data.php
  application/views/pembayaran/tagihan/add.php
  application/views/pembayaran/verifikasi/data.php
  application/views/pembayaran/verifikasi/detail.php
  application/views/pembayaran/verifikasi/riwayat.php
  application/views/pembayaran/laporan/index.php
  application/views/pembayaran/siswa/tagihan.php
  application/views/pembayaran/siswa/bayar.php
  application/views/pembayaran/siswa/riwayat.php
  application/views/pembayaran/siswa/detail_transaksi.php

SQL:
  assets/app/db/pembayaran.sql
```

#### Orangtua
```
Controllers:
  application/controllers/Orangtua.php
  application/controllers/Dataorangtua.php
  application/controllers/Userorangtua.php

Models:
  application/models/Orangtua_model.php

Views:
  application/views/members/orangtua/dashboard.php
  application/views/members/orangtua/no_anak.php
  application/views/members/orangtua/nilai/data.php
  application/views/members/orangtua/absensi/data.php
  application/views/members/orangtua/tagihan/data.php
  application/views/members/orangtua/tagihan/bayar.php
  application/views/members/orangtua/tagihan/riwayat.php
  application/views/members/orangtua/tagihan/detail_transaksi.php
  application/views/members/orangtua/templates/header.php
  application/views/members/orangtua/templates/footer.php
  application/views/users/orangtua/data.php
  application/views/users/orangtua/dashboard.php

Assets:
  assets/app/js/master/orangtua/data.js
  assets/app/js/users/orangtua/data.js

SQL:
  assets/app/db/orangtua.sql
```

#### Tendik
```
Controllers:
  application/controllers/Tendik.php
  application/controllers/Datatendik.php
  application/controllers/Usertendik.php

Models:
  application/models/Tendik_model.php

Views:
  application/views/members/tendik/dashboard.php
  application/views/members/tendik/profil.php
  application/views/members/tendik/jadwal.php
  application/views/members/tendik/riwayat.php
  application/views/members/tendik/pengajuan.php
  application/views/members/tendik/bypass_request.php
  application/views/members/tendik/templates/sidebar.php
  application/views/members/tendik/templates/navbar.php
  application/views/members/tendik/templates/header.php
  application/views/members/tendik/templates/header_topnav.php
  application/views/members/tendik/templates/footer.php
  application/views/members/tendik/templates/footer_topnav.php
  application/views/users/tendik/index.php
  application/views/users/tendik/data.php
  application/views/users/tendik/dashboard.php

SQL:
  assets/app/db/tendik.sql
```

#### Presensi
```
Controllers:
  application/controllers/Presensi.php
  application/controllers/Pengajuan.php

Models:
  application/models/Presensi_model.php
  application/models/Shift_model.php
  application/models/Pengajuan_model.php

Views:
  application/views/presensi/checkin.php
  application/views/presensi/history.php
  application/views/presensi/jadwal.php
  application/views/presensi/dashboard_admin.php
  application/views/presensi/shift_management.php
  application/views/presensi/location_management.php
  application/views/presensi/hari_libur.php
  application/views/presensi/group_config.php
  application/views/presensi/global_config.php
  application/views/presensi/jadwal_kerja.php
  application/views/presensi/list_qr_tokens.php
  application/views/presensi/bypass_request.php
  application/views/presensi/pengajuan/index.php
  application/views/presensi/pengajuan/manage.php

SQL:
  assets/app/db/presensi.sql
```

---

*Terakhir diperbarui: Januari 2026*