<?php

namespace Database\Factories;

use App\Models\PembayaranBukti;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembayaranBukti>
 */
class PembayaranBuktiFactory extends Factory
{
    protected $model = PembayaranBukti::class;

    public function definition(): array
    {
        return [
            'tagihan_id' => Tagihan::factory(),
            'sales_id' => User::factory()->state(['role' => 'sales']),
            'file_path' => 'bukti-bayar/'.fake()->uuid().'.jpg',
            'nominal_dibayar' => fake()->randomFloat(2, 100_000, 10_000_000),
            'tanggal_bayar' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => 'pending',
            'catatan_reject' => null,
            'validated_by' => null,
            'validated_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'validated_by' => null,
            'validated_at' => null,
            'catatan_reject' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'validated_by' => User::factory()->state(['role' => 'bagian_keuangan']),
            'validated_at' => now(),
            'catatan_reject' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'validated_by' => User::factory()->state(['role' => 'bagian_keuangan']),
            'validated_at' => now(),
            'catatan_reject' => fake()->sentence(),
        ]);
    }
}
