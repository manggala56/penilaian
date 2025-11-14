<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ParticipantsTemplateExport implements WithHeadings, ShouldAutoSize, WithEvents
{
    /**
    * @return array
    */
    public function headings(): array
    {
        // === INI BAGIAN YANG DIUBAH ===
        return [
            // Kolom Wajib
            'kategori_id (Wajib*)',
            'nama_inovasi (Wajib*)',

            // Kolom Opsional
            'nama_inisiator (Opsional)',
            'email (Opsional)',
            'telepon (Opsional)',
            'deskripsi_inovasi (Opsional)',
            'nama_akun (Opsional)',
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Pastikan range style (A1:G1) sesuai jumlah header (7 kolom)
                $event->sheet->getDelegate()->getStyle('A1:G1')->getFont()->setBold(true);
            },
        ];
    }
}
