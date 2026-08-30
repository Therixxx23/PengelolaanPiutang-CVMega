<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiplahColumnsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'bagian_administrasi']);
    }

    public function test_pelanggan_index_menampilkan_kolom_siplah(): void
    {
        $pelanggan = Pelanggan::factory()->create([
            'kode_pelanggan' => 'PLG-001',
            'nama_lembaga' => 'SMA N 1 Bandung',
            'status_lembaga' => 'NEGERI',
            'kabupaten' => 'Bandung',
        ]);

        $this->actingAs($this->admin())
            ->get(route('pelanggan.index'))
            ->assertOk()
            ->assertSee('PLG-001')
            ->assertSee('SMA N 1 Bandung')
            ->assertSee('Bandung')
            ->assertSee('NEGERI');
    }

    public function test_pelanggan_index_filter_kabupaten(): void
    {
        Pelanggan::factory()->create(['nama_pelanggan' => 'SD Maju', 'kabupaten' => 'Bandung']);
        Pelanggan::factory()->create(['nama_pelanggan' => 'SD Jaya', 'kabupaten' => 'Bekasi']);

        $this->actingAs($this->admin())
            ->get(route('pelanggan.index', ['kabupaten' => 'Bandung']))
            ->assertOk()
            ->assertSee('SD Maju')
            ->assertDontSee('SD Jaya');
    }

    public function test_pelanggan_index_filter_status_lembaga(): void
    {
        Pelanggan::factory()->create(['nama_pelanggan' => 'SD Negeri A', 'status_lembaga' => 'NEGERI']);
        Pelanggan::factory()->create(['nama_pelanggan' => 'SD Swasta B', 'status_lembaga' => 'SWASTA']);

        $this->actingAs($this->admin())
            ->get(route('pelanggan.index', ['status_lembaga' => 'SWASTA']))
            ->assertOk()
            ->assertSee('SD Swasta B')
            ->assertDontSee('SD Negeri A');
    }

    public function test_tagihan_index_menampilkan_kolom_siplah(): void
    {
        $pelanggan = Pelanggan::factory()->create([
            'nama_lembaga' => 'SMPN 1 Depok',
            'kabupaten' => 'Depok',
        ]);

        $tagihan = Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_sj' => 'SJ-2026-001',
            'sumber_dana' => 'BOS',
            'nama_sales' => 'Rusdi',
            'status_penagihan' => 'sedang_ditagih',
        ]);

        $this->actingAs($this->admin())
            ->get(route('tagihan.index'))
            ->assertOk()
            ->assertSee($tagihan->no_invoice)
            ->assertSee('SJ-2026-001')
            ->assertSee('SMPN 1 Depok')
            ->assertSee('Rusdi');
    }

    public function test_tagihan_index_filter_sumber_dana(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        $bos = Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'sumber_dana' => 'BOS']);
        $bop = Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'sumber_dana' => 'BOP']);

        $this->actingAs($this->admin())
            ->get(route('tagihan.index', ['sumber_dana' => 'BOS']))
            ->assertOk()
            ->assertSee($bos->no_invoice)
            ->assertDontSee($bop->no_invoice);
    }

    public function test_tagihan_index_filter_sales(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        $a = Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'nama_sales' => 'Agus']);
        $b = Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'nama_sales' => 'Budi']);

        $this->actingAs($this->admin())
            ->get(route('tagihan.index', ['sales' => 'Budi']))
            ->assertOk()
            ->assertSee($b->no_invoice)
            ->assertDontSee($a->no_invoice);
    }

    public function test_tagihan_tanpa_sumber_dana_tetap_tampil_tanpa_error(): void
    {
        Tagihan::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('tagihan.index'))
            ->assertOk();
    }

    public function test_umur_piutang_menampilkan_kolom_siplah(): void
    {
        $pelanggan = Pelanggan::factory()->create([
            'nama_lembaga' => 'MIN 1 Bogor',
            'kabupaten' => 'Bogor',
        ]);

        $tagihan = Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'status' => 'belum_lunas',
            'approval_status' => 'aktif',
            'no_sj' => 'SJ-UMUR-1',
            'sumber_dana' => 'BOP',
            'nama_sales' => 'Siti',
        ]);

        $this->actingAs($this->admin())
            ->get(route('laporan.umur-piutang'))
            ->assertOk()
            ->assertSee($tagihan->no_invoice)
            ->assertSee('SJ-UMUR-1')
            ->assertSee('MIN 1 Bogor')
            ->assertSee('Bogor')
            ->assertSee('Siti');
    }

    public function test_riwayat_pembayaran_menampilkan_kolom_siplah(): void
    {
        $pelanggan = Pelanggan::factory()->create([
            'nama_lembaga' => 'SMA Swasta Harapan',
        ]);

        $tagihan = Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_sj' => 'SJ-PAY-2',
            'sumber_dana' => 'BOS',
        ]);

        $pembayaran = Pembayaran::factory()->create([
            'id_tagihan' => $tagihan->id_tagihan,
        ]);

        $this->actingAs($this->admin())
            ->get(route('riwayat-pembayaran'))
            ->assertOk()
            ->assertSee($pembayaran->tanggal_bayar->format('d/m/Y'))
            ->assertSee('SJ-PAY-2')
            ->assertSee('SMA Swasta Harapan');
    }

    public function test_rekapitulasi_menampilkan_kolom_siplah(): void
    {
        $pelanggan = Pelanggan::factory()->create([
            'nama_lembaga' => 'SMA N 1 Cirebon',
            'status_lembaga' => 'NEGERI',
            'kabupaten' => 'Cirebon',
        ]);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'sumber_dana' => 'BOS',
            'status' => 'belum_lunas',
            'approval_status' => 'aktif',
        ]);

        $this->actingAs($this->admin())
            ->get(route('laporan.rekapitulasi'))
            ->assertOk()
            ->assertSee('SMA N 1 Cirebon')
            ->assertSee('Cirebon')
            ->assertSee('NEGERI');
    }

    public function test_rekapitulasi_filter_kabupaten(): void
    {
        $a = Pelanggan::factory()->create(['nama_pelanggan' => 'A Kab Bandung', 'kabupaten' => 'Bandung']);
        $b = Pelanggan::factory()->create(['nama_pelanggan' => 'B Kab Bekasi', 'kabupaten' => 'Bekasi']);
        Tagihan::factory()->create(['id_pelanggan' => $a->id_pelanggan]);
        Tagihan::factory()->create(['id_pelanggan' => $b->id_pelanggan]);

        $this->actingAs($this->admin())
            ->get(route('laporan.rekapitulasi', ['kabupaten' => 'Bandung']))
            ->assertOk()
            ->assertSee('A Kab Bandung')
            ->assertDontSee('B Kab Bekasi');
    }
}
