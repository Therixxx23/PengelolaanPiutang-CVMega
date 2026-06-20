<?php

namespace Database\Factories;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelanggan>
 */
class PelangganFactory extends Factory
{
    protected $model = Pelanggan::class;

    public function definition(): array
    {
        $wilayah = fake()->randomElement([
            'Jakarta Pusat', 'Jakarta Utara', 'Jakarta Barat',
            'Jakarta Selatan', 'Jakarta Timur', 'Bogor',
            'Depok', 'Tangerang', 'Bekasi',
        ]);

        return [
            'nama_pelanggan' => fake()->company(),
            'alamat' => fake()->address(),
            'wilayah' => $wilayah,
            'no_telepon' => fake()->phoneNumber(),
            'batas_kredit' => fake()->randomFloat(2, 10_000_000, 200_000_000),
        ];
    }
}
