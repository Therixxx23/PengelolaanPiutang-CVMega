# SECURITY_AUDIT.md — Laporan Audit Keamanan (Go-Live Prep)

> Dibuat sebagai bagian dari Fase 2 — Security Hardening untuk Go-Live.
> Status tiap item: **done** (sudah dikerjakan/dipastikan aman), **skip** (dilewati
> dengan alasan), atau **butuh-keputusan** (perlu tindakan manual di luar kode).

---

## 2.1 Validasi File Upload — done

| Item | Status | Catatan |
|---|---|---|
| Whitelist MIME untuk bukti bayar | done | `StorePembayaranBuktiRequest`: `mimes:jpg,jpeg,png,pdf`, `max:5120` (5 MB) |
| Cek real MIME (bukan hanya extension) | done | Laravel `mimes` rule memeriksa MIME sebenarnya dari file, bukan nama file |
| Nama file acak | done | Disimpan via `$file->store('bukti-bayar', 'local')` → digenerate hash acak, bukan nama asli |
| Simpan di storage privat (bukan public) | done | Disk `local` (`storage/app/private`), bukan `public` |
| Serve via route terproteksi | done | Route `pembayaran-bukti.download` dengan `authorize('view')` — sales lain/tamu tidak bisa akses |
| Folder upload tidak bisa dieksekusi script | done | File disimpan di storage (di luar `public/`), tidak ditaruh dengan PHP execution |
| Upload import SIPLAH | done | `ImportController::preview`: `mimes:xlsx,csv`, `max:2048` (2 MB), disimpan di `local` |

## 2.2 Audit Middleware & Authorization — done (tidak ditemukan celah)

Semua route (kecuali `/login`, asset publik, `/dashboard` dengan `auth+verified`) dibungkus
`Route::middleware('auth')`. Setiap aksi tulis/lihat di controller memanggil `authorize()`
(lihat daftar di bawah), bukan hanya disembunyikan di sidebar.

Controller yang melakukan `$this->authorize(...)` per aksi:
- `PelangganController`: viewAny/create/view/update/delete
- `TagihanController`: viewAny/create/view/update/delete + `bayar` (create Pembayaran),
  `updateStatus` (updatePenagihan), `assignSales` (update), `viewSales`, `pdf`
- `UserController`: viewAny/create/update/delete + abort 403 nonaktifkan diri sendiri
- `ImportController`: `import` di semua 4 aksi
- `ApprovalController`: viewApproval + approve
- `LaporanUmurPiutangController` / `LaporanRekapitulasiController` / `RiwayatPembayaranController`
  / `LaporanImportSiplahController`: `viewLaporan`
- `LogAktivitasController`: viewAny
- `PembayaranBuktiController`: viewAny/create/view/approve/reject/delete + download

Semua Policy didaftarkan eksplisit di `AppServiceProvider::boot()`.

## 2.3 Environment & Config Production — done (sebagian butuh SSH/cert manual)

| Item | Status | Catatan |
|---|---|---|
| `.env` di `.gitignore` | done | Ada di `.gitignore` (`.env`, `.env.backup`, `.env.production`) |
| `.env` tidak pernah ke-commit | done | `git log --all --full-history -- .env` = kosong (tidak pernah ada) |
| `APP_DEBUG=false` saat production | done | Guard runtime di `AppServiceProvider::boot()` → abort 500 jika production + debug true |
| `APP_ENV=production` | butuh-keputusan | Di-set manual saat deploy; `.env.example` sudah berisi panduan |
| `SESSION_SECURE_COOKIE` | done (default) | `config/session.php` memakai `env('SESSION_SECURE_COOKIE')`; di aktifkan manual di `.env` production + HTTPS |
| `same_site` cookie | done | Default `lax` di config/session.php |
| Throttle login | done | Rate limiter `login` (5/menit per email, 10/menit per IP) di AppServiceProvider |
| Throttle suggest/info | done | `throttle:30,1` di route `pelanggan.suggest`, `pelanggan.info`, `tagihan.suggest` |
| Force HTTPS production | done | `URL::forceScheme('https')` di AppServiceProvider saat production |

## 2.4 Input Validation Audit — done

- `ImportSiplahService` + `ImportController`: file divalidasi (`mimes:xlsx,csv|max:2048`),
  disimpan sementara di storage `local`, session key `import.file_path` menyimpan path
  internal (bukan input user).
- **Formula injection (XLSX/CSV)**: diremediasi di Fase 2 dengan helper baru
  `app/Support/SpreadsheetSafeString.php` — semua sel string pada ketiga export
  (`TagihanBelumLunasExport`, `RekapitulasiExport`, `LaporanSipLahExport`) yang diawali
  karakter formula (`= + - @`) di-escape dengan apostrof.
- Semua create/update memakai `FormRequest` (rule ketat, `$fillable` di model sudah benar):
  - `StoreUserRequest` / `UpdateUserRequest`: `role` divalidasi ke enum, `is_active` boolean,
    password kompleks (min 8, huruf+angka).
  - `StorePelangganRequest` / `UpdatePelangganRequest`, `StoreTagihanRequest`,
    `StorePembayaranBuktiRequest`: nomor dibatasi, tanggal tidak boleh masa depan, dsb.

## 2.5 HTTPS & Headers — done

- Secara **code**: `SecurityHeaders` middleware global sudah mengirim:
  `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`,
  `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`,
  `Permissions-Policy` (camera/mic/geolocation/FLoC off), dan `Strict-Transport-Security`
  (HSTS) khusus environment production.
- `URL::forceScheme('https')` hanya aktif di production.
- **butuh-keputusan**: instalasi SSL certificate + konfigurasi nginx/apache untuk HTTPS
  (di luar scope kode). Tanpa SSL, HSTS dan `SESSION_SECURE_COOKIE=true` tidak aktif maksimal.

## 2.6 Laporan Akhir

Checklist lengkap di atas. Route yang authorization-nya diperiksa/diperbaiki:
- Tidak ada route yang ternyata hanya dilindungi UI; semua aksi controller sudah memakai policy.
- Fase 2 commit `35e0b1c` — hardening: bukti bayar privat + download route authorized,
  guard APP_DEBUG production, header keamanan lanjutan, password kompleks, `.env.example`.
- Fase 2 commit selanjutnya (setelah audit ini) — force HTTPS production + formula-injection
  escape di export.

## Rekomendasi yang butuh keputusan manual

1. **SSL certificate + konfigurasi web server** (nginx/apache) — prasyarat agar
   `SESSION_SECURE_COOKIE` + HSTS efektif. Di luar scope kode.
2. **WAF / rate-limit tambahan di level server** (mis. fail2ban, Cloudflare) — opsional,
   mitigasi bertingkat di atas throttle aplikasi.
3. **Kebijakan password & 2FA** — sudah ada kompleksitas; 2FA di luar scope v1.