<?php

namespace Tests\Feature;

use App\Models\Tagihan;
use App\Models\User;
use App\Services\ImportSiplahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function buildXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siplah').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    protected function xlsxUpload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'siplah-test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    protected function validRows(): array
    {
        return [
            ['No Invoice', 'Nama Pelanggan', 'Tanggal Faktur', 'Total', 'Status'],
            ['INV/TEST/000001', 'SMA Test A', '01/07/2026', 1000000, 'Lunas'],
            ['INV/TEST/000002', 'SMA Test B', '02/07/2026', 2000000, 'Belum Lunas'],
        ];
    }

    /**
     * Meniru struktur export SIPLAH asli: baris judul di atas baris header,
     * dan satu faktur (NOFAK) terdiri dari banyak baris item.
     */
    protected function siplahStyleRows(): array
    {
        $header = [
            'NOSRTJLN', 'NOFAK', 'PERIODETGL', 'KODSAL', 'NAMSAL',
            'NAMLAND', 'NAMLEM', 'STATUS', 'KODEBARANG', 'NAMABARANG',
            'SATUAN', 'HARGA JUAL', 'BRUTOPEN (QTY)', 'NETTOPENJ (RP)', 'SUMB_DANA',
        ];

        return [
            ['FAKTUR KEMBALI DENGAN DETAIL BARANG'],
            $header,
            // Faktur 1: 2 item
            ['SJ/001', 'FKT/2026/07/0001', '31/07/2026', 'SL-1', 'Rusdi', 'SMA A', 'SMA Negeri A', 'NEGERI', 'BK-1', 'Buku MTK', 'PCS', 50000, 2, 100000, 'BOS'],
            ['SJ/001', 'FKT/2026/07/0001', '31/07/2026', 'SL-1', 'Rusdi', 'SMA A', 'SMA Negeri A', 'NEGERI', 'BK-2', 'Buku BSI', 'PCS', 25000, 4, 100000, 'BOS'],
            // Faktur 2: 1 item
            ['SJ/002', 'FKT/2026/07/0002', '31/07/2026', 'SL-2', 'Dedi', 'SMP B', 'SMP Negeri B', 'NEGERI', 'BK-3', 'Alat Tulis', 'SET', 30000, 5, 150000, 'BOP'],
        ];
    }

    public function test_administrasi_can_import(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $this->assertTrue($admin->can('import', Tagihan::class));
    }

    public function test_pimpinan_can_import(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();
        $this->assertTrue($pimpinan->can('import', Tagihan::class));
    }

    public function test_sales_cannot_import(): void
    {
        $sales = User::factory()->sales()->create();
        $this->assertFalse($sales->can('import', Tagihan::class));
    }

    public function test_keuangan_cannot_import(): void
    {
        $keuangan = User::factory()->bagianKeuangan()->create();
        $this->assertFalse($keuangan->can('import', Tagihan::class));
    }

    public function test_import_page_requires_import_permission(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $this->actingAs($admin)->get(route('import.index'))->assertOk();

        $sales = User::factory()->sales()->create();
        $this->actingAs($sales)->get(route('import.index'))->assertForbidden();
    }

    public function test_preview_rejects_non_excel_file(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $file = UploadedFile::fake()->create('data.txt', 100, 'text/plain');

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_preview_accepts_valid_file(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk()
            ->assertSee('INV/TEST/000001');
    }

    public function test_store_imports_new_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('import.store'))
            ->assertRedirect(route('import.index'));

        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'INV/TEST/000001']);
        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'INV/TEST/000002']);
    }

    public function test_store_skips_duplicate_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();
        $this->actingAs($admin)->post(route('import.store'))->assertRedirect();

        // run again -> duplicate skip, no crash
        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();
        $this->actingAs($admin)->post(route('import.store'))->assertRedirect();

        $this->assertSame(2, Tagihan::whereIn('no_invoice', ['INV/TEST/000001', 'INV/TEST/000002'])->count());
    }

    public function test_store_requires_active_session_file(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $this->actingAs($admin)
            ->post(route('import.store'))
            ->assertRedirect(route('import.index'));
    }

    public function test_sales_cannot_access_preview_and_store(): void
    {
        $sales = User::factory()->sales()->create();
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($sales)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertForbidden();

        $this->actingAs($sales)
            ->post(route('import.store'))
            ->assertForbidden();
    }

    public function test_preview_shows_confirm_and_batal_buttons(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk()
            ->assertSee('Konfirmasi &amp; Import', false)
            ->assertSee('Batal', false);
    }

    public function test_cancel_clears_session_and_redirects(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();

        $this->assertTrue(session()->has('import.file_path'));

        $this->actingAs($admin)
            ->post(route('import.cancel'))
            ->assertRedirect(route('import.index'));

        $this->assertFalse(session()->has('import.file_path'));
    }

    public function test_sales_cannot_cancel(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)
            ->post(route('import.cancel'))
            ->assertForbidden();
    }

    public function test_siplah_style_preview_detects_header_and_groups_items(): void
    {
        $path = $this->buildXlsx($this->siplahStyleRows());

        $preview = app(ImportSiplahService::class)->preview($path);

        $this->assertTrue($preview['success']);
        $this->assertSame(3, $preview['summary']['total_baris']);
        $this->assertSame(2, $preview['ringkasan']['totalFaktur']);
        $this->assertSame(2, $preview['ringkasan']['fakturBaru']);

        $first = $preview['rows']->firstWhere('no_invoice', 'FKT/2026/07/0001');
        $this->assertSame(2, $first['jumlah_item']);
        $this->assertSame('SMA Negeri A', $first['nama_lembaga']);
        $this->assertSame('BOS', $first['sumber_dana']);
    }

    public function test_siplah_style_store_creates_invoice_with_multiple_items(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->siplahStyleRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('import.store'))
            ->assertRedirect(route('import.index'));

        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'FKT/2026/07/0001']);
        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'FKT/2026/07/0002']);

        $tagihan = Tagihan::where('no_invoice', 'FKT/2026/07/0001')->firstOrFail();
        $this->assertSame(2, $tagihan->items()->count());

        $tagihan2 = Tagihan::where('no_invoice', 'FKT/2026/07/0002')->firstOrFail();
        $this->assertSame(1, $tagihan2->items()->count());
    }
}
