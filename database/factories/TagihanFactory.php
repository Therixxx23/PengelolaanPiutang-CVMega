<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tagihan>
 */
class TagihanFactory extends Factory
{
    protected $model = Tagihan::class;

    private static int $invoiceCounter = 0;

    public function definition(): array
    {
        static::$invoiceCounter++;

        $tanggalTagihan = fake()->dateTimeBetween('-6 months', 'now');
        $tanggalJatuhTempo = (clone $tanggalTagihan)->modify('+30 days');

        return [
            'id_pelanggan' => Pelanggan::factory(),
            'no_invoice' => 'INV/' . now()->format('Y/m/') . str_pad((string) static::$invoiceCounter, 6, '0', STR_PAD_LEFT),
            'tanggal_tagihan' => $tanggalTagihan->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'),
            'total_tagihan' => fake()->randomFloat(2, 500_000, 50_000_000),
            'status' => 'belum_lunas',
        ];
    }

    public function lunas(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lunas',
        ]);
    }

    public function lancar(): static
    {
        $tanggalTagihan = fake()->dateTimeBetween('-15 days', 'now');
        $tanggalJatuhTempo = (clone $tanggalTagihan)->modify('+30 days');

        return $this->state(fn (array $attributes) => [
            'tanggal_tagihan' => $tanggalTagihan->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);
    }

    public function overdue30(): static
    {
        $daysOverdue = fake()->numberBetween(1, 30);
        $jatuhTempo = now()->subDays($daysOverdue);
        $tagihan = (clone $jatuhTempo)->subDays(30);

        return $this->state(fn (array $attributes) => [
            'tanggal_tagihan' => $tagihan->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $jatuhTempo->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);
    }

    public function overdue60(): static
    {
        $daysOverdue = fake()->numberBetween(31, 60);
        $jatuhTempo = now()->subDays($daysOverdue);
        $tagihan = (clone $jatuhTempo)->subDays(30);

        return $this->state(fn (array $attributes) => [
            'tanggal_tagihan' => $tagihan->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $jatuhTempo->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);
    }

    public function overdue60plus(): static
    {
        $daysOverdue = fake()->numberBetween(61, 120);
        $jatuhTempo = now()->subDays($daysOverdue);
        $tagihan = (clone $jatuhTempo)->subDays(30);

        return $this->state(fn (array $attributes) => [
            'tanggal_tagihan' => $tagihan->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $jatuhTempo->format('Y-m-d'),
            'status' => 'belum_lunas',
        ]);
    }
}
