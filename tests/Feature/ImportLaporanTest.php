<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\TagihanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function importedTagihan(array $overrides = []): Tagihan
    {
        $pelanggan = Pelanggan::factory()->create([
            'kabupaten' => 'Bandung',
            'nama_lembaga' => 'SMA Negeri 1 Bandung',
        ]);

        $tagihan = Tagihan::factory()->create(array_merge([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tanggal_tagihan' => now()->subDays(2),
            'tanggal_jatuh_tempo' => now()->addMonths(1),
            'no_sj' => 'SJ/TEST/001',
            'nama_sales' => 'Rusdi Permana',
            'kode_sales' => 'SL-001',
            'sumber_dana' => 'BOS',
            'status' => 'belum_lunas',
            'approval_status' => 'aktif',
        ], $overrides));

        TagihanItem::create([
            'id_tagihan' => $tagihan->id_tagihan,
            'kode_barang' => 'BK-001',
            'nama_barang' => 'Buku Matematika',
            'kelas' => 'Kelas 10',
            'nama_supplier' => 'PT Gramedia',
            'harga_jual' => 50000,
            'qty_bruto' => 10,
            'persen_diskon' => 10,
            'netto_penj' => 450000,
            'qty_netto' => 10,
        ]);

        return $tagihan->load('items', 'pelanggan');
    }

    public function test_import_page_accessible_for_administrasi(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $this->actingAs($admin)->get(route('import.index'))->assertOk();
    }

    public function test_import_page_forbidden_for_sales(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get(route('import.index'))->assertForbidden();
    }

    public function test_report_accessible_for_keuangan(): void
    {
        $keuangan = User::factory()->bagianKeuangan()->create();
        $this->importedTagihan();

        $this->actingAs($keuangan)
            ->get(route('laporan.import-siplah'))
            ->assertOk();
    }

    public function test_report_forbidden_for_sales(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)
            ->get(route('laporan.import-siplah'))
            ->assertForbidden();
    }

    public function test_report_shows_only_tagihan_with_items(): void
    {
        $keuangan = User::factory()->bagianKeuangan()->create();
        $imported = $this->importedTagihan();

        $withoutItems = Tagihan::factory()->create([
            'tanggal_tagihan' => now()->subDays(2),
            'approval_status' => 'aktif',
        ]);

        $response = $this->actingAs($keuangan)
            ->get(route('laporan.import-siplah', ['periode' => 'semua']))
            ->assertOk();

        $response->assertSee($imported->no_invoice);
        $response->assertDontSee($withoutItems->no_invoice);
    }

    public function test_report_with_region_filter(): void
    {
        $keuangan = User::factory()->bagianKeuangan()->create();
        $imported = $this->importedTagihan(['sumber_dana' => 'BOP', 'nama_sales' => 'Dedi Kurnia']);

        $response = $this->actingAs($keuangan)
            ->get(route('laporan.import-siplah', [
                'periode' => 'semua',
                'wilayah' => 'Bandung',
                'sumber_dana' => 'BOP',
                'sales' => 'Dedi Kurnia',
            ]))
            ->assertOk();

        $response->assertSee($imported->no_invoice);
    }

    public function test_export_returns_xlsx(): void
    {
        $keuangan = User::factory()->bagianKeuangan()->create();
        $this->importedTagihan();

        $this->actingAs($keuangan)
            ->get(route('laporan.import-siplah.export'))
            ->assertDownload();
    }

    public function test_export_forbidden_for_sales(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)
            ->get(route('laporan.import-siplah.export'))
            ->assertForbidden();
    }
}
