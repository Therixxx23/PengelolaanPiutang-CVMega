<?php

namespace Tests\Feature;

use App\Models\PembayaranBukti;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PembayaranBuktiTest extends TestCase
{
    use RefreshDatabase;

    protected function sales(): User
    {
        return User::factory()->sales()->create();
    }

    protected function keuangan(): User
    {
        return User::factory()->bagianKeuangan()->create();
    }

    protected function assignedTagihanFor(User $user, float $total = 100000): Tagihan
    {
        return Tagihan::factory()->create([
            'assigned_sales_id' => $user->id,
            'approval_status' => 'aktif',
            'status' => 'belum_lunas',
            'total_tagihan' => $total,
        ]);
    }

    protected function uploadPayload(Tagihan $tagihan, float $nominal = 50000): array
    {
        return [
            'tagihan_id' => $tagihan->id_tagihan,
            'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            'nominal_dibayar' => $nominal,
            'tanggal_bayar' => now()->format('Y-m-d'),
        ];
    }

    public function test_sales_can_upload_payment_proof_for_assigned_tagihan(): void
    {
        Storage::fake('public');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan))
            ->assertRedirect(route('pembayaran-bukti.index'));

        $this->assertDatabaseHas('pembayaran_bukti', [
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'nominal_dibayar' => 50000,
            'status' => 'pending',
        ]);

        Storage::disk('public')->assertExists(
            PembayaranBukti::first()->file_path
        );
    }

    public function test_sales_cannot_upload_for_tagihan_not_their_responsibility(): void
    {
        $sales = $this->sales();
        $other = $this->sales();
        $tagihan = $this->assignedTagihanFor($other);

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan))
            ->assertSessionHasErrors('tagihan_id');

        $this->assertDatabaseCount('pembayaran_bukti', 0);
    }

    public function test_sales_cannot_upload_overpayment(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales, 100000);

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan, 150000))
            ->assertSessionHasErrors('nominal_dibayar');

        $this->assertDatabaseCount('pembayaran_bukti', 0);
    }

    public function test_sales_cannot_upload_for_tagihan_not_active(): void
    {
        $sales = $this->sales();
        $tagihan = Tagihan::factory()->menungguPersetujuan()->create([
            'assigned_sales_id' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan))
            ->assertSessionHasErrors('tagihan_id');

        $this->assertDatabaseCount('pembayaran_bukti', 0);
    }

    public function test_keuangan_can_approve_pending_proof_and_records_payment(): void
    {
        $sales = $this->sales();
        $keuangan = $this->keuangan();
        $tagihan = $this->assignedTagihanFor($sales, 100000);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'nominal_dibayar' => 100000,
            'status' => 'pending',
        ]);

        $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.setujui', $bukti))
            ->assertRedirect();

        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'approved',
            'validated_by' => $keuangan->id,
        ]);
        $this->assertDatabaseHas('pembayaran', [
            'id_tagihan' => $tagihan->id_tagihan,
            'jumlah_bayar' => 100000,
        ]);
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'lunas',
        ]);
    }

    public function test_approving_partial_payment_leaves_invoice_belum_lunas(): void
    {
        $sales = $this->sales();
        $keuangan = $this->keuangan();
        $tagihan = $this->assignedTagihanFor($sales, 100000);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'nominal_dibayar' => 40000,
            'status' => 'pending',
        ]);

        $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.setujui', $bukti));

        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_keuangan_can_reject_pending_proof_with_note(): void
    {
        $sales = $this->sales();
        $keuangan = $this->keuangan();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'status' => 'pending',
        ]);

        $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.tolak', $bukti), [
                'catatan_reject' => 'Bukti tidak terbaca.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'rejected',
            'catatan_reject' => 'Bukti tidak terbaca.',
            'validated_by' => $keuangan->id,
        ]);
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_reject_requires_note(): void
    {
        $sales = $this->sales();
        $keuangan = $this->keuangan();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'status' => 'pending',
        ]);

        $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.tolak', $bukti), ['catatan_reject' => ''])
            ->assertSessionHasErrors('catatan_reject');

        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'pending',
        ]);
    }

    public function test_overpayment_at_approval_time_auto_rejects(): void
    {
        $sales = $this->sales();
        $keuangan = $this->keuangan();
        $tagihan = $this->assignedTagihanFor($sales, 100000);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'nominal_dibayar' => 100000,
            'status' => 'pending',
        ]);

        $tagihan->pembayaran()->create([
            'tanggal_bayar' => now()->format('Y-m-d'),
            'jumlah_bayar' => 70000,
            'metode_bayar' => 'transfer',
        ]);

        $response = $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.setujui', $bukti));

        $response->assertSessionHasErrors('nominal');

        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'rejected',
        ]);
    }

    public function test_sales_cannot_approve_proof(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'status' => 'pending',
        ]);

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.setujui', $bukti))
            ->assertForbidden();

        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'pending',
        ]);
    }

    public function test_administrasi_cannot_approve_proof(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('pembayaran-bukti.setujui', $bukti))
            ->assertForbidden();

        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'pending',
        ]);
    }

    public function test_keuangan_cannot_upload_proof(): void
    {
        $keuangan = $this->keuangan();
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $this->actingAs($keuangan)
            ->get(route('pembayaran-bukti.create'))
            ->assertForbidden();

        $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan))
            ->assertForbidden();

        $this->assertDatabaseCount('pembayaran_bukti', 0);
    }

    public function test_sales_index_only_shows_own_proofs(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        PembayaranBukti::factory()->count(2)->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
        ]);

        $other = $this->sales();
        $otherTagihan = $this->assignedTagihanFor($other);
        PembayaranBukti::factory()->create([
            'tagihan_id' => $otherTagihan->id_tagihan,
            'sales_id' => $other->id,
        ]);

        $this->actingAs($sales)
            ->get(route('pembayaran-bukti.index'))
            ->assertOk()
            ->assertSee($tagihan->no_invoice);

        $this->assertSame(2, PembayaranBukti::where('sales_id', $sales->id)->count());
    }

    public function test_keuangan_can_view_all_proofs(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        PembayaranBukti::factory()->count(3)->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
        ]);

        $this->actingAs($this->keuangan())
            ->get(route('pembayaran-bukti.index'))
            ->assertOk();
    }
}
