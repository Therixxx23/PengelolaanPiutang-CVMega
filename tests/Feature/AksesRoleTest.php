<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesRoleTest extends TestCase
{
    use RefreshDatabase;

    public function admin(): User
    {
        return User::factory()->create(['role' => 'bagian_administrasi']);
    }

    public function keuangan(): User
    {
        return User::factory()->bagianKeuangan()->create();
    }

    public function pimpinan(): User
    {
        return User::factory()->pimpinan()->create();
    }

    public function test_admin_get_pelanggan_index(): void
    {
        $response = $this->actingAs($this->admin())->get(route('pelanggan.index'));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_pelanggan_index(): void
    {
        $response = $this->actingAs($this->keuangan())->get(route('pelanggan.index'));
        $response->assertStatus(403);
    }

    public function test_pimpinan_get_pelanggan_index(): void
    {
        $response = $this->actingAs($this->pimpinan())->get(route('pelanggan.index'));
        $response->assertStatus(403);
    }

    public function test_admin_post_pelanggan(): void
    {
        $response = $this->actingAs($this->admin())->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Contoh',
        ]);
        $response->assertStatus(302);
    }

    public function test_keuangan_post_pelanggan(): void
    {
        $response = $this->actingAs($this->keuangan())->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Nakal',
        ]);
        $response->assertStatus(403);
    }

    public function test_pimpinan_post_pelanggan(): void
    {
        $response = $this->actingAs($this->pimpinan())->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Nakal',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_get_tagihan_index(): void
    {
        $response = $this->actingAs($this->admin())->get(route('tagihan.index'));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_tagihan_index(): void
    {
        $response = $this->actingAs($this->keuangan())->get(route('tagihan.index'));
        $response->assertStatus(403);
    }

    public function test_pimpinan_get_tagihan_index(): void
    {
        $response = $this->actingAs($this->pimpinan())->get(route('tagihan.index'));
        $response->assertStatus(403);
    }

    public function test_admin_post_tagihan(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 1000000,
        ]);
        $response->assertStatus(302);
    }

    public function test_keuangan_post_tagihan(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($this->keuangan())->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
        ]);
        $response->assertStatus(403);
    }

    public function test_pimpinan_post_tagihan(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($this->pimpinan())->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_get_umur_piutang(): void
    {
        $response = $this->actingAs($this->admin())->get(route('laporan.umur-piutang'));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_umur_piutang(): void
    {
        $response = $this->actingAs($this->keuangan())->get(route('laporan.umur-piutang'));
        $response->assertStatus(200);
    }

    public function test_pimpinan_get_umur_piutang(): void
    {
        $response = $this->actingAs($this->pimpinan())->get(route('laporan.umur-piutang'));
        $response->assertStatus(200);
    }

    public function test_admin_get_riwayat_pembayaran(): void
    {
        $response = $this->actingAs($this->admin())->get(route('riwayat-pembayaran'));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_riwayat_pembayaran(): void
    {
        $response = $this->actingAs($this->keuangan())->get(route('riwayat-pembayaran'));
        $response->assertStatus(200);
    }

    public function test_pimpinan_get_riwayat_pembayaran(): void
    {
        $response = $this->actingAs($this->pimpinan())->get(route('riwayat-pembayaran'));
        $response->assertStatus(200);
    }

    public function test_admin_get_rekapitulasi(): void
    {
        $response = $this->actingAs($this->admin())->get(route('laporan.rekapitulasi'));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_rekapitulasi(): void
    {
        $response = $this->actingAs($this->keuangan())->get(route('laporan.rekapitulasi'));
        $response->assertStatus(200);
    }

    public function test_pimpinan_get_rekapitulasi(): void
    {
        $response = $this->actingAs($this->pimpinan())->get(route('laporan.rekapitulasi'));
        $response->assertStatus(200);
    }

    public function test_admin_get_tagihan_pdf(): void
    {
        $tagihan = Tagihan::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('tagihan.pdf', $tagihan));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_tagihan_pdf(): void
    {
        $tagihan = Tagihan::factory()->create();

        $response = $this->actingAs($this->keuangan())->get(route('tagihan.pdf', $tagihan));
        $response->assertStatus(403);
    }

    public function test_pimpinan_get_tagihan_pdf(): void
    {
        $tagihan = Tagihan::factory()->create();

        $response = $this->actingAs($this->pimpinan())->get(route('tagihan.pdf', $tagihan));
        $response->assertStatus(403);
    }

    public function test_admin_get_export_excel(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->lancar()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->admin())->get(route('laporan.piutang.export'));
        $response->assertStatus(200);
    }

    public function test_keuangan_get_export_excel(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->lancar()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->keuangan())->get(route('laporan.piutang.export'));
        $response->assertStatus(200);
    }

    public function test_pimpinan_get_export_excel(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->lancar()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->pimpinan())->get(route('laporan.piutang.export'));
        $response->assertStatus(200);
    }
}
