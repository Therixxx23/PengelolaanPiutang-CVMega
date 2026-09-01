<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_pimpinan_can_view_user_index(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Manajemen User');
    }

    public function test_bagian_administrasi_cannot_view_user_index(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(403);
    }

    public function test_pimpinan_can_store_new_user(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->post('/users', [
            'name' => 'Budi Sales',
            'email' => 'budi@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'sales',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'sales',
            'is_active' => true,
        ]);
    }

    public function test_store_with_duplicate_email_fails(): void
    {
        $existing = User::factory()->create(['email' => 'dup@example.com']);
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->post('/users', [
            'name' => 'Duplikat',
            'email' => $existing->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'sales',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_with_short_password_fails(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->post('/users', [
            'name' => 'Budi Sales',
            'email' => 'budi@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'sales',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_store_with_password_lacking_number_fails(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->post('/users', [
            'name' => 'Budi Sales',
            'email' => 'budi@example.com',
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
            'role' => 'sales',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_with_password_lacking_uppercase_fails(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->post('/users', [
            'name' => 'Budi Sales',
            'email' => 'budi@example.com',
            'password' => 'abc12345',
            'password_confirmation' => 'abc12345',
            'role' => 'sales',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_pimpinan_cannot_deactivate_self(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();

        $response = $this->actingAs($pimpinan)->delete('/users/'.$pimpinan->id);

        $response->assertStatus(403);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->nonAktif()->create([
            'email' => 'nonaktif@example.com',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
