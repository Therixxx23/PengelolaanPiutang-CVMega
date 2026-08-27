<?php

namespace Tests\Feature;

use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function buildXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siplah').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    protected function xlsxUpload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'siplah-test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    protected function validRows(): array
    {
        return [
            ['No Invoice', 'Nama Pelanggan', 'Tanggal Faktur', 'Total', 'Status'],
            ['INV/TEST/000001', 'SMA Test A', '01/07/2026', 1000000, 'Lunas'],
            ['INV/TEST/000002', 'SMA Test B', '02/07/2026', 2000000, 'Belum Lunas'],
        ];
    }

    public function test_administrasi_can_import(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $this->assertTrue($admin->can('import', Tagihan::class));
    }

    public function test_pimpinan_can_import(): void
    {
        $pimpinan = User::factory()->pimpinan()->create();
        $this->assertTrue($pimpinan->can('import', Tagihan::class));
    }

    public function test_sales_cannot_import(): void
    {
        $sales = User::factory()->sales()->create();
        $this->assertFalse($sales->can('import', Tagihan::class));
    }

    public function test_keuangan_cannot_import(): void
    {
        $keuangan = User::factory()->bagianKeuangan()->create();
        $this->assertFalse($keuangan->can('import', Tagihan::class));
    }

    public function test_import_page_requires_import_permission(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $this->actingAs($admin)->get(route('import.index'))->assertOk();

        $sales = User::factory()->sales()->create();
        $this->actingAs($sales)->get(route('import.index'))->assertForbidden();
    }

    public function test_preview_rejects_non_excel_file(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $file = UploadedFile::fake()->create('data.txt', 100, 'text/plain');

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_preview_accepts_valid_file(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk()
            ->assertSee('INV/TEST/000001');
    }

    public function test_store_imports_new_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('import.store'))
            ->assertRedirect(route('import.index'));

        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'INV/TEST/000001']);
        $this->assertDatabaseHas('tagihan', ['no_invoice' => 'INV/TEST/000002']);
    }

    public function test_store_skips_duplicate_invoices(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();
        $this->actingAs($admin)->post(route('import.store'))->assertRedirect();

        // run again -> duplicate skip, no crash
        $this->actingAs($admin)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertOk();
        $this->actingAs($admin)->post(route('import.store'))->assertRedirect();

        $this->assertSame(2, Tagihan::whereIn('no_invoice', ['INV/TEST/000001', 'INV/TEST/000002'])->count());
    }

    public function test_store_requires_active_session_file(): void
    {
        $admin = User::factory()->create(['role' => 'bagian_administrasi']);

        $this->actingAs($admin)
            ->post(route('import.store'))
            ->assertRedirect(route('import.index'));
    }

    public function test_sales_cannot_access_preview_and_store(): void
    {
        $sales = User::factory()->sales()->create();
        $path = $this->buildXlsx($this->validRows());

        $this->actingAs($sales)
            ->post(route('import.preview'), ['file' => $this->xlsxUpload($path)])
            ->assertForbidden();

        $this->actingAs($sales)
            ->post(route('import.store'))
            ->assertForbidden();
    }
}
