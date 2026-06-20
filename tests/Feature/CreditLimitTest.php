<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelanggan_below_limit_no_warning(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 100_000_000]);

        $response = $this->actingAs($admin)->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/06/000001',
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 50_000_000,
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionMissing('warning');
        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'INV/2026/06/000001']);
    }

    public function test_pelanggan_exactly_at_limit_no_warning(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 100_000_000]);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'total_tagihan' => 60_000_000,
            'status' => 'belum_lunas',
        ]);

        $response = $this->actingAs($admin)->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/06/000002',
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 40_000_000,
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionMissing('warning');
    }

    public function test_new_tagihan_exceeding_credit_limit_shows_warning(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 100_000_000]);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'total_tagihan' => 80_000_000,
            'status' => 'belum_lunas',
        ]);

        $response = $this->actingAs($admin)->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/06/000003',
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 50_000_000,
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'INV/2026/06/000003']);
    }

    public function test_credit_limit_warning_contains_amount_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 100_000_000]);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'total_tagihan' => 90_000_000,
            'status' => 'belum_lunas',
        ]);

        $response = $this->actingAs($admin)->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/06/000004',
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 30_000_000,
        ]);

        $warning = session('warning');
        $this->assertStringContainsString('melebihi batas kredit', $warning);
        $this->assertStringContainsString('Rp 20.000.000', $warning);
        $this->assertStringContainsString('Rp 10.000.000', $warning);
    }

    public function test_pelanggan_without_credit_limit_no_warning(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 0]);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'total_tagihan' => 500_000_000,
            'status' => 'belum_lunas',
        ]);

        $response = $this->actingAs($admin)->post(route('tagihan.store'), [
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/06/000005',
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 500_000_000,
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionMissing('warning');
    }

    public function test_cek_batas_kredit_on_model(): void
    {
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 100_000_000]);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'total_tagihan' => 70_000_000,
            'status' => 'belum_lunas',
        ]);

        $result = $pelanggan->cekBatasKredit(40_000_000);

        $this->assertTrue($result['exceeded']);
        $this->assertEquals(110_000_000, $result['total_baru']);
        $this->assertEquals(100_000_000, $result['batas_kredit']);
        $this->assertEquals(30_000_000, $result['sisa_limit']);
        $this->assertEquals(10_000_000, $result['kelebihan']);

        $result2 = $pelanggan->cekBatasKredit(20_000_000);

        $this->assertFalse($result2['exceeded']);
        $this->assertEquals(90_000_000, $result2['total_baru']);
    }
}
