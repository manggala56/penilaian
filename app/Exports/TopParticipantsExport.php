<?php

namespace App\Exports;

use App\Models\Participant;
use App\Models\Competition;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TopParticipantsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $stageId;
    protected $rankCounter = 0;
    protected $currentCategory = null;

    public function __construct($stageId)
    {
        $this->stageId = $stageId;
    }

    public function query()
    {
        $stageId = $this->stageId;

        if (!$stageId) {
            return Participant::query()->whereNull('id');
        }

        return Participant::query()
            ->whereHas('evaluations', function ($query) use ($stageId) {
                $query->where('competition_stage_id', $stageId);
            })
            ->with(['category'])
            ->withAvg(['evaluations' => function ($query) use ($stageId) {
                $query->where('competition_stage_id', $stageId);
            }], 'final_score')
            ->orderBy('category_id')
            ->orderByDesc('evaluations_avg_final_score');
    }

    public function map($participant): array
    {
        // Calculate rank dynamically based on category
        if ($this->currentCategory !== $participant->category_id) {
            $this->currentCategory = $participant->category_id;
            $this->rankCounter = 0;
        }
        
        // We can't easily get the exact "real_rank" logic from the widget in a single query pass 
        // without re-querying for every row or pre-calculating. 
        // For export, a simple counter per category sorted by score is usually sufficient and faster.
        // However, to be 100% consistent with the widget's "dense rank" logic (handling ties), 
        // we might need to replicate that logic. 
        // Let's stick to the widget's logic: count people with higher scores in the same category.
        
        $myScore = $participant->evaluations_avg_final_score;
        $higherRankCount = Participant::query()
            ->where('category_id', $participant->category_id)
            ->whereHas('evaluations', fn($q) => $q->where('competition_stage_id', $this->stageId))
            ->withAvg(['evaluations' => fn($q) => $q->where('competition_stage_id', $this->stageId)], 'final_score')
            ->having('evaluations_avg_final_score', '>', $myScore)
            ->count();
            
        $rank = '#' . ($higherRankCount + 1);

        return [
            $rank,
            $participant->name,
            $participant->innovation_title,
            $participant->institution,
            $participant->category->name,
            number_format($participant->evaluations_avg_final_score, 2),
            $participant->created_at->format('d-m-Y'),
        ];
    }

    public function headings(): array
    {
        return [
            'Peringkat',
            'Nama Peserta',
            'Judul Inovasi',
            'Institusi',
            'Kategori',
            'Nilai Akhir',
            'Tanggal Daftar',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
