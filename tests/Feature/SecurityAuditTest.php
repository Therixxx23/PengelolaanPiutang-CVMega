<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
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

    private function tagihanPayload(int $pelangganId, float $total, array $extra = []): array
    {
        return array_merge([
            'id_pelanggan' => $pelangganId,
            'tanggal_tagihan' => now()->format('Y-m-d'),
            'tanggal_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'total_tagihan' => $total,
        ], $extra);
    }

    public function test_store_pembayaran_menolak_metode_tidak_valid(): void
    {
        $admin = $this->admin();
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 50000,
            'metode_bayar' => 'paypal',
        ]);

        $response->assertSessionHasErrors('metode_bayar');
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_store_pembayaran_menolak_tanggal_bayar_masa_depan(): void
    {
        $admin = $this->admin();
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->addDays(1)->format('Y-m-d'),
            'jumlah_bayar' => 50000,
            'metode_bayar' => 'tunai',
        ]);

        $response->assertSessionHasErrors('tanggal_bayar');
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_store_pembayaran_menolak_jumlah_melebihi_sisa(): void
    {
        $admin = $this->admin();
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $response = $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 100001,
            'metode_bayar' => 'transfer',
        ]);

        $response->assertSessionHasErrors('jumlah_bayar');
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_store_pelanggan_menolak_duplikat_nama(): void
    {
        $admin = $this->admin();
        Pelanggan::factory()->create(['nama_pelanggan' => 'PT Sumber Rejeki']);

        $response = $this->actingAs($admin)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Sumber Rejeki',
            'wilayah' => 'Jakarta Pusat',
            'no_telepon' => '081234567890',
            'batas_kredit' => 10000000,
        ]);

        $response->assertSessionHasErrors('nama_pelanggan');
    }

    public function test_store_pelanggan_menolak_nomor_telepon_tidak_valid(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Cipta Karya',
            'wilayah' => 'Bekasi',
            'no_telepon' => 'abc-def-ghi',
            'batas_kredit' => 10000000,
        ]);

        $response->assertSessionHasErrors('no_telepon');
    }

    public function test_store_pelanggan_menolak_batas_kredit_negatif(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('pelanggan.store'), [
            'nama_pelanggan' => 'PT Cipta Karya',
            'wilayah' => 'Bekasi',
            'no_telepon' => '081234567890',
            'batas_kredit' => -1000,
        ]);

        $response->assertSessionHasErrors('batas_kredit');
    }

    public function test_mass_assignment_tidak_menghargai_field_tersembunyi(): void
    {
        $admin = $this->admin();
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 1_000_000_000]);

        $response = $this->actingAs($admin)->post(route('tagihan.store'), $this->tagihanPayload(
            $pelanggan->id_pelanggan,
            100000,
            [
                'status' => 'lunas',
                'approved_by' => 999,
                'approved_at' => now()->format('Y-m-d H:i:s'),
                'approval_status' => 'menunggu_persetujuan',
            ]
        ));

        $response->assertStatus(302);

        $tagihan = Tagihan::first();
        $this->assertSame('belum_lunas', $tagihan->status);
        $this->assertNull($tagihan->approved_by);
        $this->assertNull($tagihan->approved_at);
        $this->assertSame($pelanggan->id_pelanggan, $tagihan->id_pelanggan);
    }

    public function test_store_tagihan_mencatat_log_aktivitas(): void
    {
        $admin = $this->admin();
        $pelanggan = Pelanggan::factory()->create(['batas_kredit' => 1_000_000_000]);

        $this->actingAs($admin)->post(
            route('tagihan.store'),
            $this->tagihanPayload($pelanggan->id_pelanggan, 100000)
        );

        $tagihan = Tagihan::first();

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $admin->id,
            'aksi' => 'buat_tagihan',
            'model_type' => 'Tagihan',
            'model_id' => $tagihan->id_tagihan,
        ]);
    }

    public function test_pembayaran_mencatat_log_aktivitas(): void
    {
        $admin = $this->admin();
        $tagihan = Tagihan::factory()->create(['total_tagihan' => 100000]);

        $this->actingAs($admin)->post(route('tagihan.bayar', $tagihan), [
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 100000,
            'metode_bayar' => 'transfer',
        ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $admin->id,
            'aksi' => 'catat_pembayaran',
            'model_type' => 'Pembayaran',
        ]);
    }

    public function test_setujui_tagihan_mencatat_log_aktivitas(): void
    {
        $pimpinan = $this->pimpinan();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $this->actingAs($pimpinan)->post(route('approval.setujui', $tagihan));

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $pimpinan->id,
            'aksi' => 'setujui_tagihan',
            'model_type' => 'Tagihan',
            'model_id' => $tagihan->id_tagihan,
        ]);
    }

    public function test_tolak_tagihan_mencatat_log_aktivitas(): void
    {
        $pimpinan = $this->pimpinan();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create();

        $this->actingAs($pimpinan)->post(route('approval.tolak', $tagihan), [
            'approval_note' => 'Limit kredit pelanggan tidak mencukupi.',
        ]);

        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $pimpinan->id,
            'aksi' => 'tolak_tagihan',
            'model_type' => 'Tagihan',
            'model_id' => $tagihan->id_tagihan,
        ]);
    }

    public function test_dashboard_pimpinan_menampilkan_data_persetujuan(): void
    {
        $pimpinan = $this->pimpinan();
        Tagihan::factory()->menungguPersetujuan()->count(2)->create();

        $response = $this->actingAs($pimpinan)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Menunggu Persetujuan');
        $response->assertSee('Disetujui Hari Ini');
        $response->assertSee('Nilai Menunggu Approval');
        $response->assertSee('Top 5 Piutang Terbesar');
    }

    public function test_dashboard_keuangan_menampilkan_data_piutang(): void
    {
        $keuangan = $this->keuangan();
        Tagihan::factory()->lancar()->create();

        $response = $this->actingAs($keuangan)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Top 5 Piutang Terbesar');
        $response->assertSee('Kondisi Piutang Saat Ini');
        $response->assertSee('Total Belum Lunas');
    }

    public function test_dashboard_administrasi_menampilkan_tagihan_terbaru_dan_ditolak(): void
    {
        $admin = $this->admin();
        Tagihan::factory()->create();
        Tagihan::factory()->ditolak()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tagihan Terbaru');
        $response->assertSee('Jatuh Tempo Minggu Ini');
        $response->assertSee('Tagihan Ditolak Pimpinan');
    }

    public function test_pimpinan_bisa_mengakses_log_aktivitas(): void
    {
        $pimpinan = $this->pimpinan();

        $response = $this->actingAs($pimpinan)->get(route('log-aktivitas'));

        $response->assertOk();
        $response->assertSee('Log Aktivitas');
    }

    public function test_administrasi_tidak_bisa_mengakses_log_aktivitas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('log-aktivitas'))->assertForbidden();
    }

    public function test_security_headers_dipasang(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_rate_limit_429_pada_attempt_keenam(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', [
                'email' => 'penyerang@example.com',
                'password' => 'salah',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'email' => 'penyerang@example.com',
            'password' => 'salah',
        ])->assertStatus(429);
    }

    public function test_pelanggan_suggest_rate_limit_429_setelah_30_request(): void
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 30; $i++) {
            $this->actingAs($admin)->get(route('pelanggan.suggest'))->assertOk();
        }

        $this->actingAs($admin)->get(route('pelanggan.suggest'))->assertStatus(429);
    }
}
