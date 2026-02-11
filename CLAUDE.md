# Sistem Manajemen RT-44 Sepinggan Baru

## Ringkasan Proyek
Aplikasi manajemen iuran & keuangan warga RT-44, Perumahan Sepinggan Baru, Balikpapan.
Mengelola data rumah, tagihan bulanan, pembayaran, pengeluaran, dan laporan keuangan.

**Tech Stack:** Laravel 12 + Inertia.js + Vue 3 + Tailwind CSS + MySQL

## Quick Start
```bash
composer install && npm install
cp .env.example .env  # DB: rt44_sepingganbaru
php artisan migrate:fresh --seed   # Seed data real dari Excel
npm run dev
php artisan serve
```
Login admin: `admin@rt44.com` / `password`

## Arsitektur & Struktur

### Models & Relasi
```
User (role: admin|warga)
 └── hasMany → House (owner_id)
                └── hasMany → Due (house_id) — tagihan per bulan
                               └── hasMany → Payment (due_id) — pembayaran

Expense — pengeluaran RT (standalone)
MonthlyBalance — saldo awal per bulan (standalone)
```

### Database Schema
| Tabel | Kolom Penting | Keterangan |
|-------|--------------|------------|
| users | name, email, role (admin/warga), phone_number, no_rumah | |
| houses | blok, nomor, status_huni (berpenghuni/kosong), resident_status (pemilik/kontrak), is_connected, meteran_count, owner_id | |
| dues | house_id, period (YYYY-MM-01), amount, status (unpaid/paid/overdue), due_date | Tagihan bulanan |
| payments | due_id, payer_id, recorded_by, amount_paid, amount_wajib, amount_sukarela, method (transfer/cash/manual), status (pending/verified/rejected), proof_path | |
| expenses | title, amount, category, date, proof_path | |
| monthly_balances | period, initial_balance, notes | Saldo awal tiap bulan |

### Aturan Bisnis Iuran (Pasal 5)
- **Berpenghuni:** Rp 160.000/bulan
- **Kosong:** Rp 110.000/bulan
- **2 Rumah Tersambung, 1 meteran:** Rp 135.000/rumah (total 270.000)
- **2 Rumah Tersambung, 2 meteran:** Rp 160.000/rumah (standar)
- Logika ada di `app/Services/DuesService.php`

### Pembayaran
- Pembayaran di-split: **amount_wajib** (porsi wajib) + **amount_sukarela** (kelebihan)
- Method: `transfer` (warga upload bukti), `manual` (admin input langsung), `cash`
- Status: `pending` → `verified` / `rejected`
- Due status jadi `paid` jika total wajib terverifikasi >= amount tagihan
- Input nominal **0** pada pembayaran manual = hapus payment manual & kembalikan status due

## Controllers & Routes

### Admin Routes (prefix: `/admin`)
| Route | Controller Method | Fungsi |
|-------|------------------|--------|
| GET /admin/dashboard | AdminController@index | Dashboard admin |
| GET /admin/tagihan | AdminController@tagihan | Daftar tagihan bulan ini |
| PATCH /admin/due/{due} | AdminController@updateDue | Edit nominal tagihan |
| GET /admin/calendar | AdminController@calendar | Kalender pembayaran semua rumah |
| GET /admin/calendar/export-pdf | AdminController@exportCalendarPdf | Export kalender ke PDF (landscape) |
| POST /admin/payment/{due} | AdminController@storePayment | Input pembayaran manual (0 = hapus) |
| POST /admin/tagihan/bulk-update | AdminController@bulkUpdateDues | Bulk update nominal per status huni |
| GET /admin/reminder/{house}/preview | AdminController@previewReminder | Preview pesan WA reminder (JSON) |
| POST /admin/reminder/{house} | AdminController@sendReminder | Kirim reminder WA (Fonnte) |
| GET /admin/warga | WargaController@index | Daftar warga & rumah |
| POST /admin/warga | WargaController@store | Tambah rumah + warga + 12 dues |
| PUT /admin/warga/{house} | WargaController@update | Edit data rumah/warga |
| DELETE /admin/warga/{house} | WargaController@destroy | Hapus rumah |
| POST /admin/warga/import | WargaController@import | Import CSV |
| GET /admin/expenses | ExpenseController@index | Daftar pengeluaran |
| POST /admin/expenses | ExpenseController@store | Tambah pengeluaran |
| DELETE /admin/expenses/{expense} | ExpenseController@destroy | Hapus pengeluaran |
| GET /admin/report | FinancialReportController@index | Laporan keuangan bulanan |
| POST /admin/report/initial-balance | FinancialReportController@updateInitialBalance | Set saldo awal |
| GET /admin/report/export-pdf | FinancialReportController@exportPdf | Export PDF |

### Warga Routes (auth)
| Route | Controller Method | Fungsi |
|-------|------------------|--------|
| GET /dashboard | DashboardController@index | Dashboard warga (rumah & tagihan) |
| GET /dashboard/calendar | DashboardController@calendar | Kalender personal |
| POST /dues/{due}/pay | PaymentController@store | Upload bukti bayar |
| GET /payments/{payment}/receipt | PaymentController@receipt | Download kwitansi |

## Frontend (Vue 3 + Inertia)

### Pages
```
resources/js/Pages/
├── Welcome.vue                    # Landing page
├── Dashboard.vue                  # Dashboard warga
├── Admin/
│   ├── Dashboard.vue              # Dashboard admin
│   ├── Calendar.vue               # Kalender pembayaran (houses x months grid) + search, year nav, PDF export, WA reminder preview
│   ├── TagihanDataWarga.vue       # Tabel tagihan bulanan + search, bulk update nominal per status huni
│   ├── Warga/Index.vue            # CRUD warga + import CSV + search
│   ├── Expenses/Index.vue         # Kelola pengeluaran
│   └── FinancialReport/Index.vue  # Laporan keuangan + PDF export
├── Warga/Calendar.vue             # Kalender personal warga
├── Auth/                          # Login, Register, dll (Breeze)
└── Profile/                       # Edit profil (Breeze)
```

### UI Components
- Berbasis **Reka-UI** + **Radix-Vue** (headless)
- Icons: **Lucide Vue Next**
- Styling utilities: `class-variance-authority`, `clsx`, `tailwind-merge`
- Komponen: Card, Table, Button, Badge, Dialog, Select, Input, dll di `resources/js/Components/ui/`

## Services

### DuesService (`app/Services/DuesService.php`)
- `calculate(House): int` — hitung nominal tagihan per aturan Pasal 5

### FonnteService (`app/Services/FonnteService.php`)
- `send(target, message): array` — kirim WA via Fonnte API
- Token di `.env`: `FONNTE_TOKEN`

## Artisan Commands
- `php artisan dues:generate` — Generate tagihan bulan ini untuk semua rumah (cek duplikat)

## Seeders
- `RealDataSeeder` — Data real 142 rumah dari "Data Perumahan.xlsx" + generate tagihan Jan-sekarang
- `DummyDataSeeder` — Data dummy lama (tidak aktif)
- Data Excel: kolom Rumah, Pemilik/Penghuni, Kontak, Status Huni, Status Warga

## PDF Export
- Package: `barryvdh/laravel-dompdf`

### Laporan Keuangan
- Template: `resources/views/reports/financial.blade.php`
- Isi: Ringkasan Saldo, Rincian Pemasukan (Wajib + Sukarela), Rincian Pengeluaran, TTD

### Kalender Iuran
- Template: `resources/views/reports/calendar.blade.php`
- Landscape A4, kolom: Rumah + Jan-Des (tanpa nama pemilik)
- Warna: hijau=lunas, kuning=sebagian, merah=belum bayar, abu=tidak ada tagihan
- Nominal format compact (160k, 110k)

## Catatan Penting
- Role check dilakukan di controller (bukan middleware), cek `auth()->user()->role`
- Dues period selalu tanggal 1 bulan (`YYYY-MM-01`), due_date tanggal 10
- Calendar view pakai grouping O(1) lookup: key `{house_id}-{month}`
- WargaController@store otomatis buat 12 dues sekaligus saat tambah rumah baru
- WargaController@update auto-adjust tagihan bulan berjalan jika status_huni berubah
- Import CSV format: Blok, Nomor, Nama, Email, Phone, StatusHuni, StatusResiden
- **Natural sort** untuk urutan rumah: `REGEXP_SUBSTR(blok, '^[A-Za-z]+')` + `CAST(REGEXP_SUBSTR(blok, '[0-9]+') AS UNSIGNED)` — dipakai di semua query (calendar, tagihan, warga)
- **Reminder WA** mencakup semua bulan yang belum lunas s.d. bulan berjalan, cutoff tanggal 5 (jika hari ini >= tgl 5, bulan ini termasuk). Sisa tagihan = amount - verified payments
- **Search** client-side di Calendar, Tagihan, dan Data Warga (computed filter, tanpa request ke server)
- **Bulk update tagihan** mengubah nominal semua dues bulan ini sekaligus berdasarkan status_huni (berpenghuni/kosong)
