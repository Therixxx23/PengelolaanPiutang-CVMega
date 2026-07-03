<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanControllerTest extends TestCase
{
    use RefreshDatabase;

    private Pelanggan $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pelanggan = Pelanggan::factory()->create();
    }

    public function test_admin_can_view_index(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get(route('tagihan.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_tagihan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $noInvoice = app(InvoiceNumberService::class)->generate();

        $response = $this->actingAs($admin)->post(route('tagihan.store'), [
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'no_invoice' => $noInvoice,
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => 25000000,
        ]);

        $response->assertRedirect(route('tagihan.index'));
        $this->assertDatabaseHas('tagihan', ['no_invoice' => $noInvoice]);
    }

    public function test_admin_can_update_tagihan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['id_pelanggan' => $this->pelanggan->id_pelanggan]);

        $response = $this->actingAs($admin)->put(route('tagihan.update', $tagihan), [
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
            'no_invoice' => $tagihan->no_invoice,
            'tanggal_tagihan' => $tagihan->tanggal_tagihan->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $tagihan->tanggal_jatuh_tempo->format('Y-m-d'),
            'total_tagihan' => 30000000,
        ]);

        $response->assertRedirect(route('tagihan.index'));
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'total_tagihan' => 30000000,
        ]);
    }

    public function test_admin_can_delete_tagihan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['id_pelanggan' => $this->pelanggan->id_pelanggan]);

        $response = $this->actingAs($admin)->delete(route('tagihan.destroy', $tagihan));
        $response->assertRedirect(route('tagihan.index'));
        $this->assertDatabaseMissing('tagihan', ['id_tagihan' => $tagihan->id_tagihan]);
    }

    public function test_manajemen_can_view_index(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->get(route('tagihan.index'));
        $response->assertStatus(200);
    }

    public function test_manajemen_cannot_create_tagihan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->post(route('tagihan.store'), [
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
        ]);

        $response->assertStatus(403);
    }

    public function test_manajemen_cannot_update_tagihan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $tagihan = Tagihan::factory()->create(['id_pelanggan' => $this->pelanggan->id_pelanggan]);

        $response = $this->actingAs($man)->put(route('tagihan.update', $tagihan), [
            'id_pelanggan' => $this->pelanggan->id_pelanggan,
        ]);

        $response->assertStatus(403);
    }

    public function test_manajemen_cannot_delete_tagihan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $tagihan = Tagihan::factory()->create(['id_pelanggan' => $this->pelanggan->id_pelanggan]);

        $response = $this->actingAs($man)->delete(route('tagihan.destroy', $tagihan));
        $response->assertStatus(403);
    }
}
