<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'bagian_administrasi']);
    }

    private function keuangan(): User
    {
        return User::factory()->bagianKeuangan()->create();
    }

    private function pimpinan(): User
    {
        return User::factory()->pimpinan()->create();
    }

    private function payload(int $pelangganId, float $total, string $noInvoice = 'INV/2026/08/000001'): array
    {
        return [
            'id_pelanggan' => $pelangganId,
            'no_invoice' => $noInvoice,
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => $total,
        ];
    }

    public function test_tagihan_dibawah_threshold_langsung_aktif(): void
    {
        $admin = $this->admin();
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 1_000_000_000]);

        $this->actingAs($admin)
            ->post(route('tagihan.store'), $this->payload($pelanggan->id_pelanggan, 50_000_000))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tagihan', [
            'no_invoice' => 'INV/2026/08/000001',
            'approval_status' => 'aktif',
        ]);
    }

    public function test_tagihan_diatas_threshold_menunggu_persetujuan(): void
    {
        $admin = $this->admin();
        $pelanggan = Pelanggan::factory()->create();

        $this->actingAs($admin)
            ->post(route('tagihan.store'), $this->payload($pelanggan->id_pelanggan, 150_000_000))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('tagihan', [
            'no_invoice' => 'INV/2026/08/000001',
            'approval_status' => 'menunggu_persetujuan',
        ]);
    }

    public function test_pimpinan_bisa_melihat_halaman_approval(): void
    {
        $pimpinan = $this->pimpinan();

        $this->actingAs($pimpinan)->get(route('approval.index'))->assertOk();
    }

    public function test_administrasi_tidak_bisa_melihat_halaman_approval(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('approval.index'))->assertForbidden();
    }

    public function test_keuangan_tidak_bisa_melihat_halaman_approval(): void
    {
        $keuangan = $this->keuangan();

        $this->actingAs($keuangan)->get(route('approval.index'))->assertForbidden();
    }

    public function test_pimpinan_bisa_menyetujui_tagihan(): void
    {
        $pimpinan = $this->pimpinan();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $this->actingAs($pimpinan)
            ->post(route('approval.setujui', $tagihan))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'approval_status' => 'aktif',
            'approved_by' => $pimpinan->id,
        ]);
    }

    public function test_tolak_tanpa_alasan_menghasilkan_validation_error(): void
    {
        $pimpinan = $this->pimpinan();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $this->actingAs($pimpinan)
            ->post(route('approval.tolak', $tagihan), [])
            ->assertSessionHasErrors('approval_note');

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'approval_status' => 'menunggu_persetujuan',
        ]);
    }

    public function test_pimpinan_bisa_menolak_tagihan_dengan_alasan(): void
    {
        $pimpinan = $this->pimpinan();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $this->actingAs($pimpinan)
            ->post(route('approval.tolak', $tagihan), [
                'approval_note' => 'Pelanggan masih memiliki tunggakan yang belum diselesaikan',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'approval_status' => 'ditolak',
            'approved_by' => $pimpinan->id,
        ]);
    }

    public function test_administrasi_tidak_bisa_menyetujui(): void
    {
        $admin = $this->admin();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $this->actingAs($admin)
            ->post(route('approval.setujui', $tagihan))
            ->assertForbidden();
    }

    public function test_pembayaran_tagihan_menunggu_mengembalikan_error(): void
    {
        $admin = $this->admin();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 10_000_000,
            'metode_bayar' => 'tunai',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('pembayaran', 0);
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'approval_status' => 'menunggu_persetujuan',
            'status' => 'belum_lunas',
        ]);
    }
}
