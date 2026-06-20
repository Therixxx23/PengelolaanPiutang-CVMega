<?php

namespace Database\Factories;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
{
    protected $model = Pembayaran::class;

    public function definition(): array
    {
        return [
            'id_tagihan' => Tagihan::factory(),
            'tanggal_bayar' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'jumlah_bayar' => fake()->randomFloat(2, 100_000, 10_000_000),
            'metode_bayar' => fake()->randomElement(['tunai', 'transfer', 'giro']),
            'keterangan' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function tunai(): static
    {
        return $this->state(fn (array $attributes) => [
            'metode_bayar' => 'tunai',
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'metode_bayar' => 'transfer',
        ]);
    }
}
