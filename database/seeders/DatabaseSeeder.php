<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Bagian Administrasi',
            'email' => 'admin@example.com',
            'role' => 'bagian_administrasi',
        ]);

        User::factory()->bagianKeuangan()->create([
            'name' => 'Bagian Keuangan',
            'email' => 'manajemen@example.com',
            'role' => 'bagian_keuangan',
        ]);

        $pelangganList = Pelanggan::factory(8)->create();

        foreach ($pelangganList as $pelanggan) {
            $this->createTagihanForPelanggan($pelanggan);
        }
    }

    private function createTagihanForPelanggan(Pelanggan $pelanggan): void
    {
        $invoiceCount = rand(2, 5);
        $sequence = [];

        // Always create at least one of each bucket type across all customers
        // Distribute across customers: some get specific profiles

        for ($i = 0; $i < $invoiceCount; $i++) {
            $roll = rand(1, 100);

            if ($roll <= 25) {
                $sequence[] = 'lunas';
            } elseif ($roll <= 45) {
                $sequence[] = 'lancar';
            } elseif ($roll <= 65) {
                $sequence[] = 'overdue30';
            } elseif ($roll <= 80) {
                $sequence[] = 'overdue60';
            } else {
                $sequence[] = 'overdue60plus';
            }
        }

        foreach ($sequence as $bucket) {
            $tagihan = Tagihan::factory()->{$bucket}()->create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'no_invoice' => 'INV/'.now()->format('Y/m/').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            ]);

            if ($bucket === 'lunas') {
                // Full payment on the invoice date or shortly after
                $paymentDate = (clone $tagihan->tanggal_tagihan)->addDays(rand(1, 25));
                Pembayaran::factory()->create([
                    'id_tagihan' => $tagihan->id_tagihan,
                    'tanggal_bayar' => $paymentDate->format('Y-m-d'),
                    'jumlah_bayar' => $tagihan->total_tagihan,
                ]);
            } elseif ($bucket === 'lancar' || $bucket === 'overdue30') {
                // 30% chance of partial payment
                if (rand(1, 100) <= 30) {
                    $partialAmount = round($tagihan->total_tagihan * rand(10, 60) / 100, 2);
                    Pembayaran::factory()->create([
                        'id_tagihan' => $tagihan->id_tagihan,
                        'tanggal_bayar' => (clone $tagihan->tanggal_tagihan)->addDays(rand(1, 10))->format('Y-m-d'),
                        'jumlah_bayar' => $partialAmount,
                    ]);
                }
            }
            // overdue60 and overdue60plus: no payments at all
        }
    }
}
