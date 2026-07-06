<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelangganControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_index(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        Pelanggan::factory(3)->create();

        $response = $this->actingAs($admin)->get(route('pelanggan.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_pelanggan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Maju Jaya',
            'wilayah' => 'Jakarta Pusat',
            'alamat' => 'Jl. Merdeka No. 1',
            'no_telepon' => '021-1234567',
            'batas_kredit' => 50000000,
        ]);

        $response->assertRedirect(route('pelanggan.index'));
        $this->assertDatabaseHas('pelanggan', ['nama_pelanggan' => 'PT Maju Jaya']);
    }

    public function test_admin_can_update_pelanggan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($admin)->put(route('pelanggan.update', $pelanggan), [
            'nama_pelanggan' => 'PT Baru Lagi',
            'wilayah' => 'Jakarta Selatan',
            'alamat' => $pelanggan->alamat,
            'no_telepon' => $pelanggan->no_telepon,
            'batas_kredit' => $pelanggan->batas_kredit,
        ]);

        $response->assertRedirect(route('pelanggan.index'));
        $this->assertDatabaseHas('pelanggan', ['nama_pelanggan' => 'PT Baru Lagi']);
    }

    public function test_admin_can_delete_pelanggan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($admin)->delete(route('pelanggan.destroy', $pelanggan));
        $response->assertRedirect(route('pelanggan.index'));
        $this->assertDatabaseMissing('pelanggan', ['id_pelanggan' => $pelanggan->id_pelanggan]);
    }

    public function test_manajemen_cannot_view_index(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->get(route('pelanggan.index'));
        $response->assertStatus(403);
    }

    public function test_manajemen_cannot_create_pelanggan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);

        $response = $this->actingAs($man)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Nakal',
        ]);

        $response->assertStatus(403);
    }

    public function test_manajemen_cannot_update_pelanggan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($man)->put(route('pelanggan.update', $pelanggan), [
            'nama_pelanggan' => 'PT Nakal',
        ]);

        $response->assertStatus(403);
    }

    public function test_manajemen_cannot_delete_pelanggan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($man)->delete(route('pelanggan.destroy', $pelanggan));
        $response->assertStatus(403);
    }

    public function test_pimpinan_cannot_create_pelanggan(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Nakal',
        ]);

        $response->assertStatus(403);
    }

    public function test_pimpinan_cannot_update_pelanggan(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($pimpinan)->put(route('pelanggan.update', $pelanggan), [
            'nama_pelanggan' => 'PT Nakal',
        ]);

        $response->assertStatus(403);
    }

    public function test_pimpinan_cannot_delete_pelanggan(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($pimpinan)->delete(route('pelanggan.destroy', $pelanggan));
        $response->assertStatus(403);
    }

    public function test_pimpinan_cannot_view_index(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->get(route('pelanggan.index'));
        $response->assertStatus(403);
    }
}
