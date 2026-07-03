<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_pelanggan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $this->assertTrue($admin->can('create', Pelanggan::class));
    }

    public function test_manajemen_cannot_create_pelanggan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $this->assertFalse($man->can('create', Pelanggan::class));
    }

    public function test_manajemen_can_view_pelanggan(): void
    {
        $man = User::factory()->create(['role' => 'bagian_keuangan']);
        $pelanggan = Pelanggan::factory()->create();
        $this->assertTrue($man->can('view', $pelanggan));
        $this->assertTrue($man->can('viewAny', Pelanggan::class));
    }
}
