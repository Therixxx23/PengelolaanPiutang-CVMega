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
        'kode_pelanggan' => ['kode pelanggan', 'id pelanggan', 'kodlan'],
        'nama_pelanggan' => ['nama pelanggan', 'nama institusi', 'nama', 'namland', 'namlan'],
        'kode_lembaga' => ['kode lembaga', 'kode sekolah', 'npsn', 'kodelem'],
        'nama_lembaga' => ['nama lembaga', 'nama sekolah', 'nama institusi', 'namlem', 'namland'],
        'status_lembaga' => ['status lembaga', 'status sekolah', 'status'],
        'provinsi' => ['provinsi'],
        'kabupaten' => ['kabupaten', 'kota/kabupaten', 'kota'],
        'kecamatan' => ['kecamatan'],
        'desa' => ['desa', 'kelurahan'],
        'no_invoice' => ['no invoice', 'no faktur', 'no_faktur', 'nomor faktur', 'nomor invoice', 'invoice', 'faktur', 'nofak'],
        'no_sj' => ['no sj', 'no surat jalan', 'no_sj', 'nomor surat jalan', 'nosrtjln'],
        'tanggal_tagihan' => ['tanggal faktur', 'tanggal tagihan', 'tanggal invoice', 'tanggal', 'tgl faktur', 'tgl', 'periodetgl', 'tglfak'],
        'tanggal_jatuh_tempo' => ['tanggal jatuh tempo', 'jatuh tempo', 'due date', 'tgl jatuh tempo', 'tgl tempo', 'tanggal tempo'],
        'total_tagihan' => ['total', 'total tagihan', 'total faktur', 'nilai total', 'jumlah total', 'grand total'],
        'kode_barang' => ['kode barang', 'kode produk', 'kodebarang'],
        'nama_barang' => ['nama barang', 'nama produk', 'uraian', 'deskripsi barang', 'namabarang'],
        'kelas' => ['kelas', 'satuan pendidikan'],
        'spesifikasi' => ['spesifikasi'],
        'satuan' => ['satuan', 'uom'],
        'jenis_barang' => ['jenis barang'],
        'kategori' => ['kategori'],
        'sub_kategori' => ['sub kategori', 'subkategori'],
        'kode_supplier' => ['kode supplier', 'kodsupplier'],
        'nama_supplier' => ['nama supplier', 'namsupplier'],
        'harga_jual' => ['harga jual', 'harga satuan', 'harga'],
        'qty_bruto' => ['qty bruto', 'qty', 'jumlah barang', 'kuantitas', 'jumlah', 'brutopen (qty)'],
        'nilai_bruto' => ['nilai bruto', 'total bruto', 'brutopenj (rp)'],
        'persen_diskon' => ['% diskon', 'persen diskon', 'diskon %', 'persen_diskon', 'potongan (%)', 'dispenj (%)'],
        'nilai_diskon' => ['nilai diskon', 'diskon', 'total diskon', 'dispenj (rp)'],
        'nilai_netto' => ['nilai netto', 'total netto', 'netto', 'nettopenj'],
        'qty_retur' => ['qty retur', 'jumlah retur', 'rtrpenj (qty)'],
        'nilai_retur' => ['nilai retur', 'retur', 'rtrpenj (rp)'],
        'qty_netto' => ['qty netto', 'netpenj (qty)'],
        'netto_penj' => ['netto penj', 'netto penjualan', 'penjualan netto', 'nettopenj (rp)'],
        'kode_sales' => ['kode sales', 'kode salesman', 'id sales', 'kodsal'],
        'nama_sales' => ['nama sales', 'nama salesman', 'namsal'],
        'sumber_dana' => ['sumber dana', 'sumber pembayaran', 'sumb_dana'],
        'status_tagihan' => ['status faktur', 'status pembayaran', 'status tagihan'],
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
     * Mencari baris header pada file. File SIPLAH asli sering memiliki
     * satu baris judul di atas baris header. Baris header dikenali dari
     * kehadiran salah satu alias kolom `no_invoice` (mis. NOFAK).
     *
     * @return int|null index baris header, atau null bila tidak ditemukan
     */
    protected function findHeaderRowIndex(Collection $rows): ?int
    {
        $aliases = array_map(fn ($a) => mb_strtolower(trim((string) $a)), static::COLUMN_MAP['no_invoice']);

        foreach ($rows as $index => $row) {
            $rowArr = array_map(fn ($v) => mb_strtolower(trim((string) ($v ?? ''))), array_values($row));
            foreach ($aliases as $alias) {
                if (in_array($alias, $rowArr, true)) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Membaca file dan menemukan baris header serta indeks kolomnya.
     *
     * @return array{header_index: int, header_index_map: array<string,int>, rows: Collection}
     */
    protected function resolveFile(string $filePath): array
    {
        $rows = $this->readRows($filePath);

        if ($rows->isEmpty()) {
            return ['header_index' => -1, 'header_index_map' => [], 'rows' => $rows];
        }

        $headerIndex = $this->findHeaderRowIndex($rows);

        if ($headerIndex === null) {
            return ['header_index' => -1, 'header_index_map' => [], 'rows' => $rows];
        }

        $headerIndexMap = $this->buildHeaderIndex($rows[$headerIndex]);

        return [
            'header_index' => $headerIndex,
            'header_index_map' => $headerIndexMap,
            'rows' => $rows,
        ];
    }

    /**
     * Memetakan baris data (setelah header) ke associative array.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function mappedDataRows(array $headerIndexMap, Collection $rows, int $headerIndex): Collection
    {
        return $rows->slice($headerIndex + 1)
            ->map(fn ($row) => $this->mapRow($headerIndexMap, $row))
            ->filter(function ($data) {
                return $this->cleanText($data['no_invoice'] ?? null) !== null;
            })
            ->values();
    }

    /**
     * Mengelompokkan baris (satu baris = satu item) berdasarkan NOFAK,
     * menggabungkan item barang pada setiap faktur.
     *
     * @return Collection<int, array{invoice: array<string,mixed>, items: array<int,array<string,mixed>>, jumlah_item: int}>
     */
    protected function groupByInvoice(Collection $dataRows): Collection
    {
        $groups = [];

        foreach ($dataRows as $data) {
            $noInvoice = $this->cleanText($data['no_invoice'] ?? null) ?? '';

            if (! isset($groups[$noInvoice])) {
                $groups[$noInvoice] = [
                    'invoice' => $data,
                    'items' => [],
                ];
            }

            $item = $this->extractItemData($data);
            if ($item !== null) {
                $groups[$noInvoice]['items'][] = $item;
            }
        }

        return collect(array_values($groups))
            ->map(fn ($g) => [
                'invoice' => $g['invoice'],
                'items' => $g['items'],
                'jumlah_item' => count($g['items']),
            ]);
    }

    /**
     * Menjalankan impor file dan mengembalikan ringkasan hasil.
     *
     * @return array{alokasi: array<mixed>, success: bool, message: string}
     */
    public function import(string $filePath, ?User $actor = null): array
    {
        // Temukan baris header secara otomatis (file SIPLAH sering punya baris judul di atas).
        $resolved = $this->resolveFile($filePath);

        if ($resolved['header_index'] < 0) {
            return $this->fail('Kolom NOFAK (nomor faktur) tidak ditemukan di file ini. Pastikan file adalah export SIPLAH yang benar.', [
                'total_baris' => $resolved['rows']->count(),
            ]);
        }

        $headerIndexMap = $resolved['header_index_map'];
        $dataRows = $this->mappedDataRows($headerIndexMap, $resolved['rows'], $resolved['header_index']);

        if ($dataRows->isEmpty()) {
            return $this->fail('File kosong atau tidak memiliki baris data.');
        }

        // Satu baris = satu item barang; banyak baris boleh ber-NOFAK sama → satukan jadi satu faktur.
        $fakturs = $this->groupByInvoice($dataRows);

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
            foreach ($fakturs as $grup) {
                $data = $grup['invoice'];
                $items = collect($grup['items']);

                $noInvoice = $this->cleanText($data['no_invoice'] ?? null);
                $tanggalTagihan = $this->parseTanggal($data['tanggal_tagihan'] ?? null);
                $jatuhTempo = $this->parseTanggal($data['tanggal_jatuh_tempo'] ?? null);

                if ($tanggalTagihan === null) {
                    $barisGagal++;

                    continue;
                }

                // --- Skip faktur yang sudah ada (duplikat) ---
                if ($noInvoice !== null && Tagihan::where('no_invoice', $noInvoice)->exists()) {
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

                // Semua item pada faktur ini (bisa > 1 baris data).
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
        $resolved = $this->resolveFile($filePath);

        if ($resolved['header_index'] < 0) {
            return [
                'success' => false,
                'message' => 'Kolom NOFAK (nomor faktur) tidak ditemukan di file ini. Pastikan file adalah export SIPLAH yang benar.',
                'header' => [],
                'rows' => collect(),
                'summary' => [],
                'ringkasan' => [],
            ];
        }

        $headerIndexMap = $resolved['header_index_map'];
        $dataRows = $this->mappedDataRows($headerIndexMap, $resolved['rows'], $resolved['header_index']);
        $header = array_keys($headerIndexMap);

        if ($dataRows->isEmpty()) {
            return [
                'success' => false,
                'message' => 'File kosong atau tidak memiliki baris data.',
                'header' => $header,
                'rows' => collect(),
                'summary' => ['total_baris' => 0, 'kolom_terdeteksi' => count($header)],
                'ringkasan' => [
                    'totalFaktur' => 0,
                    'fakturBaru' => 0,
                    'fakturSkip' => 0,
                    'pelangganBaru' => 0,
                ],
            ];
        }

        $fakturs = $this->groupByInvoice($dataRows);

        $totalFaktur = $fakturs->count();
        $fakturBaru = 0;
        $fakturSkip = 0;
        $pelangganBaru = 0;

        $previewRows = $fakturs->take(25)->map(function ($grup) use (&$fakturBaru, &$fakturSkip, &$pelangganBaru) {
            $data = $grup['invoice'];

            $noInvoice = $this->cleanText($data['no_invoice'] ?? null) ?? '-';
            $sudahAda = $noInvoice !== '-' && Tagihan::where('no_invoice', $noInvoice)->exists();
            if ($sudahAda) {
                $fakturSkip++;
            } else {
                $fakturBaru++;
            }

            $kodePelanggan = $this->cleanText($data['kode_pelanggan'] ?? null);
            $namaPelanggan = $this->cleanText($data['nama_pelanggan']
                ?? $data['nama_lembaga']
                ?? 'Pelanggan Tanpa Nama');
            $isPelangganBaru = ! $this->hasMatchingPelanggan($data, $kodePelanggan, $namaPelanggan);
            if ($isPelangganBaru) {
                $pelangganBaru++;
            }

            $totalTagihan = $this->toMoney($data['total_tagihan'] ?? null) ?? 0;
            if ($totalTagihan === 0 && count($grup['items'])) {
                $totalTagihan = round(collect($grup['items'])->sum('netto_penj'), 2);
            }

            $tanggal = $this->parseTanggal($data['tanggal_tagihan'] ?? null);

            return [
                'no_invoice' => $noInvoice,
                'tanggal' => $tanggal ? date('d/m/Y', strtotime($tanggal)) : '-',
                'nama_sales' => $this->cleanText($data['nama_sales'] ?? null),
                'nama_pelanggan' => $namaPelanggan,
                'nama_lembaga' => $this->cleanText($data['nama_lembaga'] ?? null),
                'sumber_dana' => $this->cleanText($data['sumber_dana'] ?? null),
                'total_tagihan' => $totalTagihan,
                'jumlah_item' => $grup['jumlah_item'],
                'sudah_ada' => $sudahAda,
                'pelanggan_baru' => $isPelangganBaru,
            ];
        });

        $summary = [
            'total_baris' => $dataRows->count(),
            'kolom_terdeteksi' => count($header),
        ];

        $ringkasan = [
            'totalFaktur' => $totalFaktur,
            'fakturBaru' => $fakturBaru,
            'fakturSkip' => $fakturSkip,
            'pelangganBaru' => $pelangganBaru,
        ];

        return [
            'success' => true,
            'message' => 'File berhasil dibaca.',
            'header' => $header,
            'rows' => $previewRows,
            'summary' => $summary,
            'ringkasan' => $ringkasan,
        ];
    }

    /**
     * Mengecek apakah pelanggan dengan kode/nama+wilayah yang sama sudah terdaftar.
     */
    protected function hasMatchingPelanggan(array $data, ?string $kode, string $nama): bool
    {
        $kabupaten = $this->cleanText($data['kabupaten'] ?? null);
        $kecamatan = $this->cleanText($data['kecamatan'] ?? null);
        $wilayah = trim(trim((string) ($kabupaten ?? '')).' '.trim((string) ($kecamatan ?? '')));

        $query = Pelanggan::query();
        if ($kode !== null && (clone $query)->where('kode_pelanggan', $kode)->exists()) {
            return true;
        }

        $query->where('nama_pelanggan', $nama)
            ->when($wilayah !== '', fn ($q) => $q->where('wilayah', $wilayah));

        return $query->exists();
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
        $item = $this->extractItemData($data);

        return $item === null ? collect() : collect([$item]);
    }

    protected function extractItemData(array $data): ?array
    {
        $namaBarang = $this->cleanText($data['nama_barang'] ?? null);
        $hargaJual = $this->toMoney($data['harga_jual'] ?? null);
        $qtyBruto = $this->toInt($data['qty_bruto'] ?? null);

        // Hanya buat item bila ada nama barang atau nilai terkait.
        if ($namaBarang === null && $hargaJual === null && $qtyBruto === null) {
            return null;
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

        return [
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
        ];
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
