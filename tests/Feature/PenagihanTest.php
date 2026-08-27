<?php

namespace Tests\Feature;

use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenagihanTest extends TestCase
{
    use RefreshDatabase;

    protected function sales(): User
    {
        return User::factory()->sales()->create();
    }

    protected function assignedTagihanFor(User $user): Tagihan
    {
        return Tagihan::factory()->create([
            'assigned_sales_id' => $user->id,
            'approval_status' => 'aktif',
            'status' => 'belum_lunas',
            'status_penagihan' => 'belum_ditagih',
        ]);
    }

    public function test_sales_can_update_status_of_assigned_tagihan(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $this->actingAs($sales)
            ->from(route('tagihan.show-sales', $tagihan))
            ->patch(route('tagihan.update-status', $tagihan), [
                'status_penagihan' => 'sedang_ditagih',
                'catatan' => 'Kunjungan pertama, menunggu konfirmasi kepala sekolah.',
            ])
            ->assertRedirect(route('tagihan.show-sales', $tagihan));

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_penagihan' => 'sedang_ditagih',
            'catatan_penagihan_terakhir' => 'Kunjungan pertama, menunggu konfirmasi kepala sekolah.',
        ]);

        $this->assertDatabaseHas('catatan_penagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'user_id' => $sales->id,
            'status_penagihan' => 'sedang_ditagih',
            'catatan' => 'Kunjungan pertama, menunggu konfirmasi kepala sekolah.',
        ]);
    }

    public function test_sales_cannot_update_status_of_non_assigned_tagihan(): void
    {
        $sales = $this->sales();
        $otherSales = $this->sales();
        $tagihan = $this->assignedTagihanFor($otherSales);

        $this->actingAs($sales)
            ->patch(route('tagihan.update-status', $tagihan), [
                'status_penagihan' => 'sudah_ditagih',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('catatan_penagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
        ]);
    }

    public function test_sales_cannot_view_non_assigned_tagihan_page(): void
    {
        $sales = $this->sales();
        $otherSales = $this->sales();
        $tagihan = $this->assignedTagihanFor($otherSales);

        $this->actingAs($sales)
            ->get(route('tagihan.show-sales', $tagihan))
            ->assertForbidden();
    }

    public function test_sales_can_view_assigned_tagihan_page(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $this->actingAs($sales)
            ->get(route('tagihan.show-sales', $tagihan))
            ->assertOk()
            ->assertSee($tagihan->no_invoice)
            ->assertSee('Update Status Penagihan');
    }

    public function test_administrasi_can_update_status(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $tagihan = Tagihan::factory()->create(['approval_status' => 'aktif']);

        $this->actingAs($admin)
            ->from(route('tagihan.show', $tagihan))
            ->patch(route('tagihan.update-status', $tagihan), [
                'status_penagihan' => 'janji_bayar',
                'catatan' => 'Janji bayar minggu depan.',
            ])
            ->assertRedirect(route('tagihan.show', $tagihan));

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status_penagihan' => 'janji_bayar',
        ]);

        $this->assertDatabaseHas('catatan_penagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'user_id' => $admin->id,
            'status_penagihan' => 'janji_bayar',
        ]);
    }

    public function test_status_update_requires_valid_status(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $this->actingAs($sales)
            ->patch(route('tagihan.update-status', $tagihan), [
                'status_penagihan' => 'invalid_status',
            ])
            ->assertSessionHasErrors('status_penagihan');

        $this->assertDatabaseMissing('catatan_penagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
        ]);
    }

    public function test_admin_can_assign_sales_to_tagihan(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $sales = $this->sales();
        $tagihan = Tagihan::factory()->create();

        $this->actingAs($admin)
            ->from(route('tagihan.show', $tagihan))
            ->patch(route('tagihan.assign-sales', $tagihan), [
                'sales_id' => $sales->id,
            ])
            ->assertRedirect(route('tagihan.show', $tagihan));

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'assigned_sales_id' => $sales->id,
        ]);
    }

    public function test_sales_cannot_assign_sales(): void
    {
        $sales = $this->sales();
        $tagihan = Tagihan::factory()->create();

        $this->actingAs($sales)
            ->patch(route('tagihan.assign-sales', $tagihan), [
                'sales_id' => $sales->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'assigned_sales_id' => null,
        ]);
    }

    public function test_sales_dashboard_shows_assigned_tagihan(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $this->actingAs($sales)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tagihan yang Perlu Ditagih')
            ->assertSee($tagihan->no_invoice);
    }

    public function test_pimpinan_dashboard_shows_sales_monitoring(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $tagihan->update(['status_penagihan' => 'sedang_ditagih']);

        $this->actingAs($pimpinan)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monitoring Tim Sales')
            ->assertSee($sales->name);
    }

    public function test_sales_cannot_update_status_of_tagihan_not_active(): void
    {
        $sales = $this->sales();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create([
            'assigned_sales_id' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->patch(route('tagihan.update-status', $tagihan), [
                'status_penagihan' => 'sudah_ditagih',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('catatan_penagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
        ]);
    }
}
