<?php

namespace Tests\Unit;

use App\Models\Tagihan;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PembayaranServiceTest extends TestCase
{
    use RefreshDatabase;

    private PembayaranService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PembayaranService::class);
    }

    public function test_full_payment_flags_tagihan_as_lunas(): void
    {
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 500000, 'status' => 'belum_lunas']);

        $pembayaran = $this->service->catatPembayaran($tagihan, [
            'tanggal_bayar' => '2026-06-21',
            'jumlah_bayar' => 500000,
            'metode_bayar' => 'transfer',
        ]);

        $this->assertDatabaseHas('pembayaran', ['id_pembayaran' => $pembayaran->id_pembayaran]);
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'lunas',
        ]);
    }

    public function test_partial_payment_keeps_status_belum_lunas(): void
    {
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 500000, 'status' => 'belum_lunas']);

        $this->service->catatPembayaran($tagihan, [
            'tanggal_bayar' => '2026-06-21',
            'jumlah_bayar' => 200000,
            'metode_bayar' => 'tunai',
        ]);

        $this->assertDatabaseHas('pembayaran', ['jumlah_bayar' => 200000]);
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_two_partial_payments_complete_invoice(): void
    {
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 500000, 'status' => 'belum_lunas']);

        $this->service->catatPembayaran($tagihan, [
            'tanggal_bayar' => '2026-06-21',
            'jumlah_bayar' => 200000,
            'metode_bayar' => 'tunai',
        ]);

        $this->service->catatPembayaran($tagihan, [
            'tanggal_bayar' => '2026-06-28',
            'jumlah_bayar' => 300000,
            'metode_bayar' => 'transfer',
        ]);

        $this->assertEquals(2, $tagihan->refresh()->pembayaran()->count());
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'lunas',
        ]);
    }

    public function test_overpayment_is_rejected(): void
    {
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000, 'status' => 'belum_lunas']);

        $this->expectException(ValidationException::class);

        $this->service->catatPembayaran($tagihan, [
            'tanggal_bayar' => '2026-06-21',
            'jumlah_bayar' => 150000,
            'metode_bayar' => 'transfer',
        ]);
    }
}
