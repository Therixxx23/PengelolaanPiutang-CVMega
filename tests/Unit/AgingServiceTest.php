<?php

namespace Tests\Unit;

use App\Models\Tagihan;
use App\Services\PiutangAgingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PiutangAgingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PiutangAgingService::class);
    }

    public function test_lancar_when_not_yet_due(): void
    {
        $tagihan = Tagihan::factory()->lancar()->create();

        $this->assertLessThanOrEqual(0, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('lancar', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_lancar_when_due_today(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(0, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('lancar', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_0_30_at_day_1(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDay()->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(1, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('0-30', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_0_30_at_day_30(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDays(30)->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(30, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('0-30', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_31_60_at_day_31(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDays(31)->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(31, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('31-60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_31_60_at_day_60(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDays(60)->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(60, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('31-60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_over_60_at_day_61(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDays(61)->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(61, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('>60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_over_60_at_day_120(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDays(120)->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);

        $this->assertEquals(120, $this->service->getDaysOverdue($tagihan));
        $this->assertEquals('>60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_lunas_invoice_has_zero_days_overdue(): void
    {
        $tagihan = Tagihan::factory()->create([
            'tanggal_jatuh_tempo' => now()->subDays(45)->format('Y-m-d'),
            'status' => 'lunas',
        ]);

        $this->assertEquals(0, $this->service->getDaysOverdue($tagihan));
    }

    public function test_get_bucket_summary_returns_all_four_buckets(): void
    {
        Tagihan::factory()->lancar()->create(['total_tagihan' => 100000]);
        Tagihan::factory()->overdue30()->create(['total_tagihan' => 200000]);
        Tagihan::factory()->overdue60()->create(['total_tagihan' => 300000]);
        Tagihan::factory()->overdue60plus()->create(['total_tagihan' => 400000]);

        $summary = $this->service->getBucketSummary();

        $this->assertArrayHasKey('lancar', $summary);
        $this->assertArrayHasKey('0-30', $summary);
        $this->assertArrayHasKey('31-60', $summary);
        $this->assertArrayHasKey('>60', $summary);
    }

    public function test_get_total_piutang_returns_sum_of_unpaid(): void
    {
        Tagihan::factory()->create(['total_tagihan' => 500000, 'status' => 'belum_lunas']);
        Tagihan::factory()->create(['total_tagihan' => 300000, 'status' => 'lunas']);

        $total = $this->service->getTotalPiutang();

        $this->assertEquals(500000, $total);
    }
}
