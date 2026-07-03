<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembayaranFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_full_payment(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 100000,
            'metode_bayar' => 'transfer',
        ]);

        $response->assertRedirect(route('tagihan.show', $tagihan));
        $this->assertDatabaseHas('pembayaran', [
            'id_tagihan' => $tagihan->id_tagihan,
            'jumlah_bayar' => 100000,
        ]);
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'lunas',
        ]);
    }

    public function test_admin_can_record_partial_payment(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 40000,
            'metode_bayar' => 'tunai',
        ]);

        $response->assertRedirect(route('tagihan.show', $tagihan));
        $this->assertDatabaseHas('pembayaran', ['jumlah_bayar' => 40000]);
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_partial_payment_then_remaining_completes_invoice(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 40000,
            'metode_bayar' => 'tunai',
        ]);

        $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 60000,
            'metode_bayar' => 'transfer',
        ]);

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'lunas',
        ]);
        $this->assertEquals(2, $tagihan->refresh()->pembayaran()->count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 150000,
            'metode_bayar' => 'transfer',
        ]);

        $response->assertSessionHasErrors('jumlah_bayar');
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_manajemen_cannot_record_payment(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $tagihan = Tagihan::factory()->create();

        $response = $this->actingAs($man)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 50000,
            'metode_bayar' => 'tunai',
        ]);

        $response->assertStatus(403);
    }

    public function test_payment_history_visible_on_show_page(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 200000]);

        Pembayaran::factory()->count(2)->create([
            'id_tagihan' => $tagihan->id_tagihan,
        ]);

        $response = $this->actingAs($admin)->get(route('tagihan.show', $tagihan));
        $response->assertStatus(200);
        $response->assertSee('Riwayat Pembayaran');
    }
}
