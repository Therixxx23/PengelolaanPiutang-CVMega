<?php

namespace App\Services;

use App\Models\ImportLog;
use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\TagihanItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class ImportSiplahService
{
    /**
     * Alias pencocokan kolom dari file Excel SIPLAH ke kolom database.
     * Kunci adalah nama kolom di database, nilai adalah daftar kemungkinan
     * nama header pada file (case-insensitive).
     */
    protected const COLUMN_MAP = [
        'kode_pelanggan' => ['kode pelanggan', 'kode pelanggan', 'id pelanggan'],
        'nama_pelanggan' => ['nama pelanggan', 'nama institusi', 'nama'],
        'kode_lembaga' => ['kode lembaga', 'kode sekolah', 'npsn'],
        'nama_lembaga' => ['nama lembaga', 'nama sekolah', 'nama institusi'],
        'status_lembaga' => ['status lembaga', 'status sekolah'],
        'provinsi' => ['provinsi'],
        'kabupaten' => ['kabupaten', 'kota/kabupaten', 'kota'],
        'kecamatan' => ['kecamatan'],
        'desa' => ['desa', 'kelurahan'],
        'no_invoice' => ['no invoice', 'no faktur', 'no_faktur', 'nomor faktur', 'nomor invoice', 'invoice', 'faktur'],
        'no_sj' => ['no sj', 'no surat jalan', 'no_sj', 'nomor surat jalan'],
        'tanggal_tagihan' => ['tanggal faktur', 'tanggal tagihan', 'tanggal invoice', 'tanggal', 'tgl faktur', 'tgl'],
        'tanggal_jatuh_tempo' => ['tanggal jatuh tempo', 'jatuh tempo', 'due date', 'tgl jatuh tempo', 'tgl tempo', 'tanggal tempo'],
        'total_tagihan' => ['total', 'total tagihan', 'total faktur', 'nilai total', 'jumlah total', 'grand total'],
        'kode_barang' => ['kode barang', 'kode produk'],
        'nama_barang' => ['nama barang', 'nama produk', 'uraian', 'deskripsi barang'],
        'kelas' => ['kelas', 'satuan pendidikan'],
        'spesifikasi' => ['spesifikasi'],
        'satuan' => ['satuan', 'uom'],
        'jenis_barang' => ['jenis barang'],
        'kategori' => ['kategori'],
        'sub_kategori' => ['sub kategori', 'subkategori'],
        'kode_supplier' => ['kode supplier'],
        'nama_supplier' => ['nama supplier'],
        'harga_jual' => ['harga jual', 'harga satuan', 'harga'],
        'qty_bruto' => ['qty bruto', 'qty', 'jumlah barang', 'kuantitas', 'jumlah'],
        'nilai_bruto' => ['nilai bruto', 'total bruto'],
        'persen_diskon' => ['% diskon', 'persen diskon', 'diskon %', 'persen_diskon', 'potongan (%)'],
        'nilai_diskon' => ['nilai diskon', 'diskon', 'total diskon'],
        'nilai_netto' => ['nilai netto', 'total netto', 'netto'],
        'qty_retur' => ['qty retur', 'jumlah retur'],
        'nilai_retur' => ['nilai retur', 'retur'],
        'qty_netto' => ['qty netto'],
        'netto_penj' => ['netto penj', 'netto penjualan', 'penjualan netto'],
        'kode_sales' => ['kode sales', 'kode salesman', 'id sales'],
        'nama_sales' => ['nama sales', 'nama salesman'],
        'sumber_dana' => ['sumber dana', 'sumber pembayaran'],
        'status_tagihan' => ['status', 'status faktur', 'status pembayaran', 'status tagihan'],
    ];

    public function __construct(
        protected InvoiceNumberService $invoiceNumberService,
    ) {}

    /**
     * Membaca semua baris pada sheet pertama file Excel/CSV.
     *
     * @return Collection<int, array<int, string|float|int|null>>
     */
    protected function readRows(string $filePath): Collection
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $reader = $extension === 'csv'
            ? new CsvReader
            : new XlsxReader;

        $rows = [];
        try {
            $reader->open($filePath);
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();
                    if (Arr::every($values, fn ($v) => $v === null || $v === '')) {
                        continue;
                    }
                    $rows[] = array_values($values);
                }
                // hanya baca sheet pertama
                break;
            }
            $reader->close();
        } catch (\Throwable $e) {
            Log::error('Gagal membaca file SIPLAH: '.$e->getMessage());
            throw new \RuntimeException('File tidak dapat dibaca. Pastikan formatnya .xlsx atau .csv.');
        }

        return collect($rows);
    }

    /**
     * Memetakan satu baris data ke array ber-keysi kolom database
     * berdasarkan header yang terdeteksi.
     */
    protected function mapRow(array $headerIndex, array $row): array
    {
        $mapped = [];
        foreach ($headerIndex as $field => $index) {
            $mapped[$field] = $row[$index] ?? null;
        }

        return $mapped;
    }

    /**
     * Menghasilkan indeks kolom dari baris header.
     *
     * @return array<string, int>
     */
    protected function buildHeaderIndex(array $headerRow): array
    {
        $index = [];
        foreach ($headerRow as $i => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $normalized = mb_strtolower(trim((string) $value));
            if ($normalized === '') {
                continue;
            }
            foreach (static::COLUMN_MAP as $field => $aliases) {
                foreach ($aliases as $alias) {
                    if ($normalized === mb_strtolower($alias)) {
                        $index[$field] = $i;
                        break 2;
                    }
                }
            }
        }

        return $index;
    }

    /**
     * Menjalankan impor file dan mengembalikan ringkasan hasil.
     *
     * @return array{alokasi: array<mixed>, success: bool, message: string}
     */
    public function import(string $filePath, ?User $actor = null): array
    {
        $rows = $this->readRows($filePath);

        if ($rows->isEmpty()) {
            return $this->fail('File kosong atau tidak memiliki baris data.');
        }

        // Baris pertama dianggap header.
        $headerRow = $rows->first();
        $headerIndex = $this->buildHeaderIndex($headerRow);
        $dataRows = $rows->slice(1);

        $totalBaris = count($dataRows);
        $fakturBaru = 0;
        $fakturSkip = 0;
        $pelangganBaru = 0;
        $fakturProses = 0;
        $fakturLunas = 0;
        $fakturSementara = 0;
        $barisGagal = 0;

        DB::beginTransaction();
        try {
            foreach ($dataRows as $row) {
                $data = $this->mapRow($headerIndex, $row);

                $noInvoiceValue = $this->cleanText($data['no_invoice'] ?? null);

                // Faktur tanpa nomor / tanpa tanggal dianggap baris tidak valid.
                if ($noInvoiceValue === null && ! isset($data['no_invoice'])) {
                    $barisGagal++;

                    continue;
                }

                $noInvoice = $noInvoiceValue
                    ?? $this->invoiceNumberService->generate();

                $tanggalTagihan = $this->parseTanggal($data['tanggal_tagihan'] ?? null);
                $jatuhTempo = $this->parseTanggal($data['tanggal_jatuh_tempo'] ?? null);

                if ($tanggalTagihan === null) {
                    $barisGagal++;

                    continue;
                }

                // --- Skip faktur yang sudah ada (duplikat) ---
                if (Tagihan::where('no_invoice', $noInvoice)->exists()) {
                    $fakturSkip++;

                    continue;
                }

                $fakturBaru++;
                $fakturProses++;

                // --- Pelanggan ---
                $kodePelanggan = $this->cleanText($data['kode_pelanggan'] ?? null);
                $namaPelanggan = $this->cleanText($data['nama_pelanggan']
                    ?? $data['nama_lembaga']
                    ?? 'Pelanggan Tanpa Nama');

                $pelanggan = $this->findOrCreatePelanggan($data, $kodePelanggan, $namaPelanggan);
                if ($pelanggan['baru']) {
                    $pelangganBaru++;
                }
                $pelangganId = $pelanggan['model']->id_pelanggan;

                // --- Item yang dikelompokkan untuk faktur ini ---
                $items = $this->extractItems($data);

                $totalTagihan = $this->toMoney($data['total_tagihan'] ?? null);
                if ($totalTagihan === null && $items->count()) {
                    $totalTagihan = round($items->sum('netto_penj'), 2);
                }
                $totalTagihan = $totalTagihan ?? 0;

                // Status tagihan berdasarkan kolom status pada file (bila ada),
                // selain itu default belum lunas.
                $statusFile = mb_strtolower($this->cleanText($data['status_tagihan'] ?? '') ?? '');
                $status = in_array($statusFile, ['lunas', 'paid', 'lunas,', 'ya'], true)
                    ? 'lunas'
                    : 'belum_lunas';

                $tagihan = Tagihan::create([
                    'id_pelanggan' => $pelangganId,
                    'no_invoice' => $noInvoice,
                    'no_sj' => $this->cleanText($data['no_sj'] ?? null),
                    'tanggal_tagihan' => $tanggalTagihan,
                    'tanggal_jatuh_tempo' => $jatuhTempo ?? $tanggalTagihan,
                    'total_tagihan' => $totalTagihan,
                    'status' => $status,
                    'kode_sales' => $this->cleanText($data['kode_sales'] ?? null),
                    'nama_sales' => $this->cleanText($data['nama_sales'] ?? null) ?: $this->resolveNamaSales($data),
                    'sumber_dana' => $this->cleanText($data['sumber_dana'] ?? null) ?: 'SIPLAH',
                ]);

                // Item barang (bila baris memuat data barang).
                foreach ($items as $item) {
                    TagihanItem::create([
                        'id_tagihan' => $tagihan->id_tagihan,
                        ...$item,
                    ]);
                }

                if ($status === 'lunas') {
                    $fakturLunas++;
                } else {
                    $fakturSementara++;
                }
            }

            if ($fakturBaru === 0) {
                DB::rollBack();

                return $this->fail('Tidak ada faktur baru yang diimpor. Semua nomor faktur sudah terdaftar atau baris tidak valid.', [
                    'total_baris' => $totalBaris,
                    'faktur_skip' => $fakturSkip,
                ]);
            }

            DB::commit();

            try {
                $this->logImport($filePath, $actor ? $actor->id : null, [
                    'total_baris' => $totalBaris,
                    'total_faktur' => $fakturProses,
                    'faktur_baru' => $fakturBaru,
                    'faktur_skip' => $fakturSkip,
                    'pelanggan_baru' => $pelangganBaru,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Gagal menulis log impor: '.$e->getMessage());
            }

            $message = sprintf(
                'Impor selesai: %d baris diproses, %d faktur baru dibuat (%d lunas, %d belum lunas), %d faktur dilewati (duplikat), %d pelanggan baru.',
                $totalBaris,
                $fakturBaru,
                $fakturLunas,
                $fakturSementara,
                $fakturSkip,
                $pelangganBaru
            );

            return [
                'success' => true,
                'message' => $message,
                'alokasi' => [
                    'total_baris' => $totalBaris,
                    'total_faktur' => $fakturProses,
                    'faktur_baru' => $fakturBaru,
                    'faktur_lunas' => $fakturLunas,
                    'faktur_sementara' => $fakturSementara,
                    'faktur_skip' => $fakturSkip,
                    'pelanggan_baru' => $pelangganBaru,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal impor SIPLAH: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->fail('Terjadi kesalahan saat memproses file: '.$e->getMessage());
        }
    }

    /**
     * Preview: membaca file dan mengembalikan ringkasan tanpa menyimpan ke DB.
     */
    public function preview(string $filePath): array
    {
        $rows = $this->readRows($filePath);

        if ($rows->isEmpty()) {
            return [
                'success' => false,
                'message' => 'File kosong atau tidak memiliki baris data.',
                'header' => [],
                'rows' => collect(),
                'summary' => [],
            ];
        }

        $headerRow = $rows->first();
        $headerIndex = $this->buildHeaderIndex($headerRow);
        $header = array_keys($headerIndex);

        $previewRows = $rows->slice(1, 25)->map(
            fn ($r) => $this->mapRow($headerIndex, $r)
        );

        $summary = [
            'total_baris' => $rows->count() - 1,
            'kolom_terdeteksi' => count($header),
        ];

        return [
            'success' => true,
            'message' => 'File berhasil dibaca.',
            'header' => $header,
            'rows' => $previewRows,
            'summary' => $summary,
        ];
    }

    protected function findOrCreatePelanggan(array $data, ?string $kode, string $nama): array
    {
        $kabupaten = $this->cleanText($data['kabupaten'] ?? null);
        $kecamatan = $this->cleanText($data['kecamatan'] ?? null);
        $wilayah = trim(trim((string) ($kabupaten ?? '')).' '.trim((string) ($kecamatan ?? '')));

        $query = Pelanggan::query();
        if ($kode !== null) {
            // cari berdasarkan kode pelanggan (bila terisi)
            $existing = (clone $query)->where('kode_pelanggan', $kode)->first();
            if ($existing) {
                return ['model' => $existing, 'baru' => false];
            }
            $query->where('nama_pelanggan', $nama);
        } else {
            $query->where('nama_pelanggan', $nama);
        }

        // fallback: satukan faktur pada pelanggan yang sama (nama + wilayah)
        $existing = (clone $query)
            ->when($wilayah !== '', fn ($q) => $q->where('wilayah', $wilayah))
            ->first();

        if ($existing) {
            return ['model' => $existing, 'baru' => false];
        }

        $pelanggan = Pelanggan::create([
            'kode_pelanggan' => $kode,
            'nama_pelanggan' => $nama,
            'kode_lembaga' => $this->cleanText($data['kode_lembaga'] ?? null),
            'nama_lembaga' => $this->cleanText($data['nama_lembaga'] ?? null),
            'status_lembaga' => $this->cleanText($data['status_lembaga'] ?? null),
            'provinsi' => $this->cleanText($data['provinsi'] ?? null),
            'kabupaten' => $kabupaten,
            'kecamatan' => $kecamatan,
            'desa' => $this->cleanText($data['desa'] ?? null),
            'wilayah' => $wilayah === '' ? null : $wilayah,
        ]);

        return ['model' => $pelanggan, 'baru' => true];
    }

    protected function extractItems(array $data): Collection
    {
        $namaBarang = $this->cleanText($data['nama_barang'] ?? null);
        $hargaJual = $this->toMoney($data['harga_jual'] ?? null);
        $qtyBruto = $this->toInt($data['qty_bruto'] ?? null);

        // Hanya buat item bila ada nama barang atau nilai terkait.
        if ($namaBarang === null && $hargaJual === null && $qtyBruto === null) {
            return collect();
        }

        $nilaiBruto = $this->round2($this->toMoney($data['nilai_bruto'] ?? null));
        $nilaiDiskon = $this->round2($this->toMoney($data['nilai_diskon'] ?? null));
        $nilaiNetto = $this->round2($this->toMoney($data['nilai_netto'] ?? null));
        $nilaiRetur = $this->round2($this->toMoney($data['nilai_retur'] ?? null));
        $nettoPenj = $this->round2($this->toMoney($data['netto_penj'] ?? null));

        if ($nilaiBruto === null && $qtyBruto !== null && $hargaJual !== null) {
            $nilaiBruto = $this->round2($qtyBruto * $hargaJual);
        }
        if ($nettoPenj === null && $nilaiNetto !== null) {
            $nettoPenj = $nilaiNetto;
        }
        if ($nilaiNetto === null && $nilaiBruto !== null && $nilaiDiskon !== null) {
            $nilaiNetto = $this->round2($nilaiBruto - $nilaiDiskon);
        }
        if ($nettoPenj === null && $nilaiBruto !== null && $nilaiRetur !== null) {
            $nettoPenj = $this->round2($nilaiBruto - $nilaiRetur);
        }

        return collect([
            [
                'kode_barang' => $this->cleanText($data['kode_barang'] ?? null),
                'nama_barang' => $namaBarang ?? '-',
                'kelas' => $this->cleanText($data['kelas'] ?? null),
                'spesifikasi' => $this->cleanText($data['spesifikasi'] ?? null),
                'satuan' => $this->cleanText($data['satuan'] ?? null),
                'jenis_barang' => $this->cleanText($data['jenis_barang'] ?? null),
                'kategori' => $this->cleanText($data['kategori'] ?? null),
                'sub_kategori' => $this->cleanText($data['sub_kategori'] ?? null),
                'kode_supplier' => $this->cleanText($data['kode_supplier'] ?? null),
                'nama_supplier' => $this->cleanText($data['nama_supplier'] ?? null),
                'harga_jual' => $hargaJual ?? 0,
                'qty_bruto' => $qtyBruto ?? 0,
                'nilai_bruto' => $nilaiBruto ?? 0,
                'persen_diskon' => $this->cleanText($data['persen_diskon'] ?? null) ?: null,
                'nilai_diskon' => $nilaiDiskon ?? 0,
                'nilai_netto' => $nilaiNetto ?? 0,
                'qty_retur' => $this->toInt($data['qty_retur'] ?? null) ?? 0,
                'nilai_retur' => $nilaiRetur ?? 0,
                'qty_netto' => $this->toInt($data['qty_netto'] ?? null) ?? 0,
                'netto_penj' => $nettoPenj ?? 0,
            ],
        ]);
    }

    protected function resolveNamaSales(array $data): ?string
    {
        return null;
    }

    protected function logImport(string $filePath, ?int $userId, array $stats): void
    {
        ImportLog::create([
            'user_id' => $userId,
            'nama_file' => basename($filePath),
            'total_baris' => $stats['total_baris'],
            'total_faktur' => $stats['total_faktur'],
            'faktur_baru' => $stats['faktur_baru'],
            'faktur_skip' => $stats['faktur_skip'],
            'pelanggan_baru' => $stats['pelanggan_baru'],
            'status' => 'sukses',
        ]);
    }

    protected function fail(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'message' => $message,
            'alokasi' => [],
        ], $extra);
    }

    protected function parseTanggal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel sering memberi DateTimeInterface
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && $value > 25569) {
            // serial date Excel
            $unix = (($value - 25569) * 86400);

            return date('Y-m-d', (int) $unix);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d', 'm/d/Y'] as $format) {
            $parsed = \DateTime::createFromFormat('!'.$format, $text);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        $ts = strtotime($text);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    protected function toMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);

        // menghapus "Rp", spasi, titik ribuan (sesuai format Indonesia)
        $cleaned = str_replace(['Rp', 'rp', 'Rp.', ' '], '', $text);
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);

        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        return null;
    }

    protected function toInt($value): ?int
    {
        $money = $this->toMoney($value);
        if ($money === null) {
            return null;
        }

        return (int) round($money);
    }

    protected function cleanText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    protected function round2($value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }
}
