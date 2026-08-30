<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriter;

class LaporanSipLahExport
{
    public function __construct(
        protected $tagihan,
    ) {}

    public function write(AbstractWriter $writer): void
    {
        $headerStyle = new Style(fontBold: true);

        $writer->getCurrentSheet()->setName('Ringkasan');

        $ringkasanHeaders = [
            'No. Faktur', 'No. SJ', 'Tanggal', 'Nama Pelanggan', 'Lembaga',
            'Kabupaten', 'Sales', 'Sumber Dana',
            'Jumlah Item', 'Total QTY', 'Total Nilai', 'Status Tagihan',
        ];
        $writer->addRow(Row::fromValuesWithStyle($ringkasanHeaders, $headerStyle));

        foreach ($this->tagihan as $t) {
            $writer->addRow(Row::fromValues([
                $t->no_invoice,
                $t->no_sj ?: '',
                $t->tanggal_tagihan?->format('Y-m-d'),
                $t->pelanggan?->nama_pelanggan,
                $t->pelanggan?->nama_lembaga ?: $t->pelanggan?->nama_pelanggan,
                $t->pelanggan?->kabupaten ?: '',
                $t->nama_sales ?: '',
                $t->sumber_dana ?: '',
                $t->items->count(),
                $t->items->sum('qty_netto'),
                (float) $t->total_tagihan,
                $t->status === 'lunas' ? 'Lunas' : 'Belum Lunas',
            ]));
        }

        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Detail Item');

        $detailHeaders = [
            'No. Faktur', 'Kode Barang', 'Nama Barang', 'Kelas', 'Supplier',
            'QTY', 'Harga Jual', 'Diskon (%)', 'Netto',
        ];
        $writer->addRow(Row::fromValuesWithStyle($detailHeaders, $headerStyle));

        foreach ($this->tagihan as $t) {
            foreach ($t->items as $item) {
                $writer->addRow(Row::fromValues([
                    $t->no_invoice,
                    $item->kode_barang ?: '',
                    $item->nama_barang,
                    $item->kelas ?: '',
                    $item->nama_supplier ?: '',
                    (int) $item->qty_netto,
                    (float) $item->harga_jual,
                    is_numeric($item->persen_diskon) ? (float) $item->persen_diskon : $item->persen_diskon,
                    (float) $item->netto_penj,
                ]));
            }
        }
    }
}
