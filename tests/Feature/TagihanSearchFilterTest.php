<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'bagian_administrasi']);
    }

    public function test_search_by_no_invoice(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        $target = Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/07/000042',
        ]);
        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/07/000099',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['search' => '000042']));

        $response->assertOk();
        $response->assertSee('INV/2026/07/000042');
        $response->assertDontSee('INV/2026/07/000099');
    }

    public function test_search_by_pelanggan_name(): void
    {
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Barrows Inc']);
        $other = Pelanggan::factory()->create(['nama_pelanggan' => 'Zeta Corp']);

        Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);
        Tagihan::factory()->create(['id_pelanggan' => $other->id_pelanggan]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['search' => 'Barrows']));

        $response->assertOk();
        $response->assertSee('Barrows Inc');
        $response->assertDontSee('Zeta Corp');
    }

    public function test_filter_by_belum_lunas(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'status' => 'belum_lunas']);
        Tagihan::factory()->lunas()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['status' => 'belum_lunas']));

        $response->assertOk();
        $response->assertSee('1 dari');
    }

    public function test_filter_by_lunas(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'status' => 'belum_lunas']);
        Tagihan::factory()->lunas()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['status' => 'lunas']));

        $response->assertOk();
        $response->assertSee('1 dari');
    }

    public function test_combined_search_and_status_filter(): void
    {
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Barrows Inc']);
        $other = Pelanggan::factory()->create(['nama_pelanggan' => 'Zeta Corp']);

        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/07/000010',
            'status' => 'belum_lunas',
        ]);
        Tagihan::factory()->lunas()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/07/000011',
        ]);
        Tagihan::factory()->create([
            'id_pelanggan' => $other->id_pelanggan,
            'no_invoice' => 'INV/2026/07/000012',
            'status' => 'belum_lunas',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', [
            'search' => 'Barrows',
            'status' => 'belum_lunas',
        ]));

        $response->assertOk();
        $response->assertSee('INV/2026/07/000010');
        $response->assertDontSee('INV/2026/07/000011');
        $response->assertDontSee('INV/2026/07/000012');
    }

    public function test_search_no_results_shows_empty_message(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['search' => 'XYZNONEXISTENT']));

        $response->assertOk();
        $response->assertSee('Tidak ada tagihan yang cocok dengan pencarian ini');
        $response->assertSee('Reset pencarian');
    }

    public function test_pagination_preserves_search_and_status(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        Tagihan::factory()->count(15)->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'status' => 'belum_lunas',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', [
            'search' => '',
            'status' => 'belum_lunas',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('status=belum_lunas');
    }

    public function test_info_line_shows_total_without_filter(): void
    {
        Tagihan::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('tagihan.index'));

        $response->assertOk();
        $response->assertSee('3 tagihan');
        $response->assertDontSee('dari');
    }

    public function test_info_line_shows_filtered_count(): void
    {
        $pelanggan = Pelanggan::factory()->create();
        Tagihan::factory()->count(2)->create(['id_pelanggan' => $pelanggan->id_pelanggan, 'status' => 'belum_lunas']);
        Tagihan::factory()->lunas()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['status' => 'belum_lunas']));

        $response->assertOk();
        $response->assertSee('dari');
        $response->assertSee('Belum Lunas');
    }

    public function test_search_is_case_insensitive(): void
    {
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Barrows Inc']);
        Tagihan::factory()->create(['id_pelanggan' => $pelanggan->id_pelanggan]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.index', ['search' => 'barrows']));

        $response->assertOk();
        $response->assertSee('Barrows Inc');
    }

    public function test_suggest_returns_matching_invoices_and_pelanggan(): void
    {
        $pelanggan = Pelanggan::factory()->create(['nama_pelanggan' => 'Barrows Inc']);
        Tagihan::factory()->create([
            'id_pelanggan' => $pelanggan->id_pelanggan,
            'no_invoice' => 'INV/2026/07/000042',
        ]);

        $response = $this->actingAs($this->admin)->get(route('tagihan.suggest', ['q' => 'INV']));

        $response->assertOk();
        $response->assertJsonFragment(['type' => 'invoice', 'label' => 'INV/2026/07/000042']);

        $response2 = $this->actingAs($this->admin)->get(route('tagihan.suggest', ['q' => 'Bar']));
        $response2->assertOk();
        $response2->assertJsonFragment(['type' => 'pelanggan', 'label' => 'Barrows Inc']);
    }

    public function test_suggest_returns_empty_for_short_query(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tagihan.suggest', ['q' => 'a']));

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_suggest_returns_empty_for_no_match(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tagihan.suggest', ['q' => 'ZZZZZ']));

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_suggest_requires_login(): void
    {
        $response = $this->get(route('tagihan.suggest', ['q' => 'INV']));

        $response->assertRedirect();
    }

    public function test_suggest_returns_max_8_items(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            Tagihan::factory()->create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'no_invoice' => 'INV/2026/07/0000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('tagihan.suggest', ['q' => 'INV']));

        $response->assertOk();
        $this->assertLessThanOrEqual(8, count($response->json()));
    }
}
