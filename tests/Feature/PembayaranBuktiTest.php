<?php

namespace Tests\Feature;

use App\Models\PembayaranBukti;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
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
            'file' => UploadedFile::fake()->createWithContent(
                'bukti.pdf',
                "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"
            ),
            'nominal_dibayar' => $nominal,
            'tanggal_bayar' => now()->format('Y-m-d'),
        ];
    }

    public function test_upload_diminta_sales_dengan_file_extension_php_ditolak(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        $payload = $this->uploadPayload($tagihan);
        $payload['file'] = UploadedFile::fake()->createWithContent(
            'bukti.jpg',
            '<?php echo "nope"; ?>'
        );

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.store'), $payload)
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('pembayaran_bukti', [
            'tagihan_id' => $tagihan->id_tagihan,
        ]);
    }

    public function test_sales_can_upload_payment_proof_for_assigned_tagihan(): void
    {
        Storage::fake('local');

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

        Storage::disk('local')->assertExists(
            PembayaranBukti::first()->file_path
        );
    }

    public function test_upload_bukti_rate_limit_429_setelah_5_upload(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);

        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($sales)
                ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan, 5000));
        }

        $this->actingAs($sales)
            ->post(route('pembayaran-bukti.store'), $this->uploadPayload($tagihan, 5000))
            ->assertStatus(429);
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
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihan->id_tagihan,
            'status' => 'belum_lunas',
        ]);
    }

    public function test_approved_proof_cannot_be_approve_ulang_via_backend(): void
    {
        $sales = $this->sales();
        $keuangan = $this->keuangan();
        $tagihan = $this->assignedTagihanFor($sales, 100000);
        $bukti = PembayaranBukti::factory()->approved()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'nominal_dibayar' => 100000,
        ]);

        $this->actingAs($keuangan)
            ->post(route('pembayaran-bukti.setujui', $bukti))
            ->assertForbidden();

        $this->assertDatabaseCount('pembayaran', 0);
        $this->assertDatabaseHas('pembayaran_bukti', [
            'id' => $bukti->id,
            'status' => 'approved',
        ]);
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

    public function test_administrasi_melihat_status_readonly_untuk_bukti_approved(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $keuangan = $this->keuangan();
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->approved()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'validated_by' => $keuangan->id,
            'validated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('pembayaran-bukti.index'));

        $response->assertOk();
        $response->assertDontSee(route('pembayaran-bukti.setujui', $bukti));
        $response->assertDontSee(route('pembayaran-bukti.tolak', $bukti));
        $response->assertSee($keuangan->name, false);
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

    public function test_sales_can_download_own_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'file_path' => 'bukti-bayar/bukti.pdf',
        ]);
        Storage::disk('local')->put('bukti-bayar/bukti.pdf', 'fake-content');

        $response = $this->actingAs($sales)
            ->get(route('pembayaran-bukti.download', $bukti))
            ->assertOk();

        $this->assertStringContainsString(
            'inline',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertStringNotContainsString(
            'bukti-bayar/bukti.pdf',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_sales_cannot_download_other_sales_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $other = $this->sales();
        $tagihan = $this->assignedTagihanFor($other);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $other->id,
            'file_path' => 'bukti-bayar/bukti.pdf',
        ]);
        Storage::disk('local')->put('bukti-bayar/bukti.pdf', 'fake-content');

        $this->actingAs($sales)
            ->get(route('pembayaran-bukti.download', $bukti))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'file_path' => 'bukti-bayar/bukti.pdf',
        ]);

        $this->get(route('pembayaran-bukti.download', $bukti))
            ->assertRedirect(route('login'));
    }

    public function test_keuangan_can_download_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'file_path' => 'bukti-bayar/bukti.pdf',
        ]);
        Storage::disk('local')->put('bukti-bayar/bukti.pdf', 'fake-content');

        $this->actingAs($this->keuangan())
            ->get(route('pembayaran-bukti.download', $bukti))
            ->assertOk();
    }

    public function test_sales_can_delete_own_pending_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
            'status' => 'pending',
            'file_path' => 'bukti-bayar/bukti.pdf',
        ]);
        Storage::disk('local')->put('bukti-bayar/bukti.pdf', 'fake-content');

        $this->actingAs($sales)
            ->delete(route('pembayaran-bukti.destroy', $bukti))
            ->assertRedirect(route('pembayaran-bukti.index'));

        $this->assertDatabaseMissing('pembayaran_bukti', ['id' => $bukti->id]);
        Storage::disk('local')->assertMissing('bukti-bayar/bukti.pdf');
    }

    public function test_sales_cannot_delete_approved_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->approved()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->delete(route('pembayaran-bukti.destroy', $bukti))
            ->assertForbidden();

        $this->assertDatabaseHas('pembayaran_bukti', ['id' => $bukti->id]);
    }

    public function test_sales_cannot_delete_other_sales_pending_proof(): void
    {
        Storage::fake('local');

        $sales = $this->sales();
        $other = $this->sales();
        $tagihan = $this->assignedTagihanFor($other);
        $bukti = PembayaranBukti::factory()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $other->id,
            'status' => 'pending',
        ]);

        $this->actingAs($sales)
            ->delete(route('pembayaran-bukti.destroy', $bukti))
            ->assertForbidden();

        $this->assertDatabaseHas('pembayaran_bukti', ['id' => $bukti->id]);
    }

    public function test_approved_proof_cannot_be_edited_by_sales(): void
    {
        $sales = $this->sales();
        $tagihan = $this->assignedTagihanFor($sales);
        $bukti = PembayaranBukti::factory()->approved()->create([
            'tagihan_id' => $tagihan->id_tagihan,
            'sales_id' => $sales->id,
        ]);

        $this->assertFalse($this->app->make(Gate::class)
            ->forUser($sales)->allows('update', $bukti));
        $this->assertTrue($this->app->make(Gate::class)
            ->forUser($sales)->denies('delete', $bukti));
    }
}
