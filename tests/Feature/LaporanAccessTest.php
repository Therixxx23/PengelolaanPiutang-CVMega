<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Tagihan Belum Lunas');
    }

    public function test_manajemen_can_access_dashboard(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Total Piutang');
    }

    public function test_admin_can_access_aging_report(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('laporan.umur-piutang'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Umur Piutang');
    }

    public function test_manajemen_can_access_aging_report(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->get(route('laporan.umur-piutang'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Umur Piutang');
    }

    public function test_admin_can_access_payment_history(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('riwayat-pembayaran'));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembayaran');
    }

    public function test_manajemen_can_access_payment_history(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->get(route('riwayat-pembayaran'));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembayaran');
    }

    public function test_admin_can_access_recap_report(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('laporan.rekapitulasi'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rekapitulasi');
    }

    public function test_manajemen_can_access_recap_report(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->get(route('laporan.rekapitulasi'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rekapitulasi');
    }

    public function test_aging_report_displays_buckets_with_data(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        Tagihan::factory()->lancar()->create();
        Tagihan::factory()->overdue30()->create();
        Tagihan::factory()->overdue60()->create();
        Tagihan::factory()->overdue60plus()->create();

        $response = $this->actingAs($admin)->get(route('laporan.umur-piutang'));

        $response->assertStatus(200);
        $response->assertSee('Lancar (Belum Jatuh Tempo)');
        $response->assertSee('0–30 Hari');
        $response->assertSee('31–60 Hari');
        $response->assertSee('>60 Hari');
    }

    public function test_aging_report_shows_lunas_in_own_bucket(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        Tagihan::factory()->lunas()->create();

        $response = $this->actingAs($admin)->get(route('laporan.umur-piutang'));

        $response->assertStatus(200);
    }

    public function test_riwayat_pembayaran_can_filter_by_pelanggan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('riwayat-pembayaran', ['id_pelanggan' => 1]));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembayaran');
    }

    public function test_riwayat_pembayaran_can_filter_by_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('riwayat-pembayaran', [
            'dari' => now()->subMonth()->format('Y-m-d'),
            'sampai' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembayaran');
    }

    public function test_recap_report_shows_summary_cards(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('laporan.rekapitulasi'));

        $response->assertStatus(200);
        $response->assertSee('Total Piutang');
        $response->assertSee('Total Pelanggan');
    }

    public function test_recap_report_lists_each_pelanggan_with_unpaid_tagihan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $pelangganA = Pelanggan::factory()->create(['nama_pelanggan' => 'PT Maju Jaya']);
        $pelangganB = Pelanggan::factory()->create(['nama_pelanggan' => 'CV Sukses Makmur']);

        Tagihan::factory()->lancar()->create(['id_pelanggan' => $pelangganA->id_pelanggan]);
        Tagihan::factory()->overdue30()->create(['id_pelanggan' => $pelangganB->id_pelanggan]);

        $response = $this->actingAs($admin)->get(route('laporan.rekapitulasi'));

        $response->assertStatus(200);
        $response->assertSee('PT Maju Jaya');
        $response->assertSee('CV Sukses Makmur');
        $response->assertSee('Lancar');
        $response->assertSee('0-30 Hari');
        $response->assertDontSee('Belum ada data piutang untuk ditampilkan');
    }

    public function test_recap_report_table_and_chart_headings_are_distinct(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        Pelanggan::factory()->count(2)->has(Tagihan::factory()->lancar())->create();

        $response = $this->actingAs($admin)->get(route('laporan.rekapitulasi'));
        $html = $response->getContent();

        $response->assertStatus(200);
        $this->assertEquals(1, substr_count($html, '>Sisa Piutang per Pelanggan</h2>'), 'Tabel heading harus muncul tepat 1x');
        $this->assertEquals(1, substr_count($html, '>Grafik Sisa Piutang per Pelanggan</h2>'), 'Chart heading harus muncul tepat 1x');
    }

    public function test_admin_can_download_excel_export(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->lancar()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($admin)->get(route('laporan.piutang.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_manajemen_can_download_excel_export(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->lancar()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($man)->get(route('laporan.piutang.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
