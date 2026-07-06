<?php

namespace Tests\Unit;

use App\Models\Tagihan;
use App\Services\PiutangAgingService;
use Carbon\Carbon;
use Tests\TestCase;

class AgingServiceTest extends TestCase
{
    private PiutangAgingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-06-21'));
        $this->service = app(PiutangAgingService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function makeTagihan(array $overrides = []): Tagihan
    {
        $tagihan = new Tagihan;
        $tagihan->status = $overrides['status'] ?? 'belum_lunas';
        $tagihan->tanggal_jatuh_tempo = $overrides['tanggal_jatuh_tempo'] ?? now();
        $tagihan->total_tagihan = $overrides['total_tagihan'] ?? 0;

        return $tagihan;
    }

    public function test_lancar_when_due_today(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()]);

        $this->assertSame(0, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('lancar', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_lancar_when_due_tomorrow(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->addDay()]);

        $this->assertSame(0, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('lancar', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_0_30_when_1_day_overdue(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->subDay()]);

        $this->assertSame(1, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('0-30', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_0_30_when_30_days_overdue_boundary(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->subDays(30)]);

        $this->assertSame(30, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('0-30', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_31_60_when_31_days_overdue_boundary(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->subDays(31)]);

        $this->assertSame(31, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('31-60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_31_60_when_60_days_overdue_boundary(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->subDays(60)]);

        $this->assertSame(60, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('31-60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_over_60_when_61_days_overdue_boundary(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->subDays(61)]);

        $this->assertSame(61, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('>60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_bucket_over_60_when_90_days_overdue(): void
    {
        $tagihan = $this->makeTagihan(['tanggal_jatuh_tempo' => now()->subDays(90)]);

        $this->assertSame(90, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('>60', $this->service->getBucketForTagihan($tagihan));
    }

    public function test_lunas_invoice_returns_zero_days_overdue(): void
    {
        $tagihan = $this->makeTagihan([
            'status' => 'lunas',
            'tanggal_jatuh_tempo' => now()->subDays(45),
        ]);

        $this->assertSame(0, $this->service->getDaysOverdue($tagihan));
        $this->assertSame('lancar', $this->service->getBucketForTagihan($tagihan));
    }
}
