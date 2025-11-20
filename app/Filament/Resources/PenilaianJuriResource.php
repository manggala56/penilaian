<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource;
use App\Filament\Resources\PenilaianJuriResource\Pages;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\HtmlString;

class PenilaianJuriResource extends Resource
{
    protected static ?string $model = Participant::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Penilaian'; // Ini label menu
    protected static ?string $modelLabel = 'Peserta';
    protected static ?string $pluralModelLabel = 'Daftar Peserta untuk Dinilai';
    protected static ?int $navigationSort = 1;
    public static function canViewAny(): bool
    {
        return Auth::user()->role === 'juri';
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $juriId = Auth::id();

        $activeCompetitions = Competition::where('is_active', true)
                                        ->with('activeStage', 'categories')
                                        ->get();

        if ($activeCompetitions->isEmpty()) {
            return Participant::query()->whereRaw('1 = 0');
        }

        $participantQuery = Participant::query()
            ->with([
                'category.competition.activeStage',
                'evaluations' => fn ($query) => $query
                                    ->where('user_id', $juriId)
                                    ->with('scores.aspect'), // <-- INI YANG PENTING
            ]);

        $participantQuery->where(function ($query) use ($activeCompetitions) {
            foreach ($activeCompetitions as $competition) {
                if ($competition->activeStage) {
                    $stageOrder = $competition->activeStage->stage_order;
                    $categoryIds = $competition->categories->pluck('id');

                    $query->orWhere(function ($q) use ($categoryIds, $stageOrder) {
                        $q->whereIn('category_id', $categoryIds)
                        ->where('current_stage_order', $stageOrder);
                    });
                }
            }
        });

        return $participantQuery;
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('innovation_title')
                ->description(function (Participant $record): string {
                    return $record->innovation_description ?? 'Tidak ada deskripsi';
                })
                    ->label('Judul Inovasi')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai Final')
                    ->getStateUsing(function (Participant $record): ?string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        if (!$activeStageId) return null;

                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation ? number_format($evaluation->final_score, 2) : null;
                    })
                    ->default('-')
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state === '-' ? 'gray' : 'primary'),

                Tables\Columns\IconColumn::make('status_penilaian')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(function (Participant $record): bool {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        if (!$activeStageId) return false;

                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation !== null;
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                ->label('Downloadsss')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (Participant $record) => static::downloadDocuments($record))
                ->color('success'),
                Action::make('viewPdf')
    ->label('View PDF')
    ->icon('heroicon-o-eye')
    ->color('info')
    ->visible(fn (Participant $record) => !empty($record->documents))
    ->modalHeading(fn (Participant $record) => "Preview Dokumen – {$record->name}")
    ->modalSubmitAction(false)
    ->modalCancelActionLabel('Tutup')
    ->modalWidth('4xl')
    ->form(function (Participant $record) {
        if (empty($record->documents)) {
            return [
                Forms\Components\Placeholder::make('no_documents')
                    ->content('Tidak ada dokumen untuk ditampilkan.')
                    ->columnSpanFull(),
            ];
        }

        $pdf = collect($record->documents)->first(fn ($doc) => strtolower(pathinfo($doc, PATHINFO_EXTENSION)) === 'pdf');
        $fileToShow = $pdf ?? $record->documents[0];

        $filePath = $fileToShow;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Pastikan file ada
        if (!Storage::disk('public')->exists($filePath)) {
            return [
                Forms\Components\Placeholder::make('missing')
                    ->content("File '{$filePath}' tidak ditemukan.")
                    ->columnSpanFull(),
            ];
        }

        $fileUrl = Storage::disk('public')->url($filePath);

        // Escape URL untuk keamanan
        $escapedUrl = htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8');

        if ($extension === 'pdf') {
            $html = <<<HTML
                <div style="border:1px solid #e5e7eb; border-radius:0.5rem; overflow:hidden;">
                    <iframe
                        src="{$escapedUrl}#toolbar=0&navpanes=0&scrollbar=1"
                        width="100%"
                        height="600"
                        style="border:none; display:block;"
                        title="Preview PDF"
                    ></iframe>
                </div>
            HTML;
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
            $html = <<<HTML
                <div style="text-align:center;">
                    <img
                        src="{$escapedUrl}"
                        alt="Preview Dokumen"
                        style="max-width:100%; max-height:600px; border-radius:0.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.1);"
                    />
                </div>
            HTML;
        } else {
            $html = <<<HTML
                <p class="text-gray-600">File dengan ekstensi <code>.{$extension}</code> tidak dapat dipreview.</p>
                <a href="{$escapedUrl}" target="_blank" class="mt-2 inline-flex items-center px-3 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-8-5l-4 5m0 0l4 5m-4-5h10" />
                    </svg>
                    Buka di Tab Baru
                </a>
            HTML;
        }

        return [
            Forms\Components\Placeholder::make('preview')
                ->label('Preview')
                ->content(new HtmlString($html))
                ->columnSpanFull(),
        ];
    }),
                Action::make('viewEvaluationDetails')
                    ->label('Detail')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('gray')
                    ->modalHeading(fn (Participant $record) => "Detail Penilaian - {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('lg')
                    ->form(function (Participant $record) {
                        $activeStageId = $record->category?->competition?->active_stage_id;

                        if (!$activeStageId) {
                            return [
                                Forms\Components\Placeholder::make('no_stage')
                                    ->content('Tidak ada tahap kompetisi aktif')
                                    ->columnSpanFull(),
                            ];
                        }

                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        if (!$evaluation) {
                            return [
                                Forms\Components\Placeholder::make('no_evaluation')
                                    ->content('Belum ada penilaian untuk peserta ini')
                                    ->columnSpanFull(),
                            ];
                        }

                        return [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('participant_name')
                                        ->label('Nama Peserta')
                                        ->content($record->name),

                                    Forms\Components\Placeholder::make('category')
                                        ->label('Kategori')
                                        ->content($record->category?->name ?? '-'),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Detail Nilai Per Aspek')
                                ->schema(
                                    $evaluation->scores->map(function ($score) {
                                        return Forms\Components\Placeholder::make("score_{$score->id}")
                                            ->label($score->aspect?->name ?? 'Aspek Dihapus')
                                            ->content(number_format($score->score, 2))
                                            ->extraAttributes(['class' => 'border-l-4 border-primary-500 pl-3']);
                                    })->toArray()
                                )
                                ->columns(2)
                                ->compact(),

                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('final_score')
                                        ->label('Nilai Final')
                                        ->content(number_format($evaluation->final_score, 2))
                                        ->extraAttributes([
                                            'class' => 'text-2xl font-bold text-primary-600 text-center'
                                        ]),
                                ])
                                ->compact(),
                        ];
                    })
                    ->visible(function (Participant $record) {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        if (!$activeStageId) return false;

                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation !== null;
                    }),


                Action::make('evaluate')
                    ->label(function (Participant $record): string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation ? 'Edit Nilai' : 'Beri Nilai';
                    })
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->button()
                    ->url(function (Participant $record): string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        if ($evaluation) {
                            return EvaluationResource::getUrl('edit', ['record' => $evaluation->id]);
                        } else {
                            return EvaluationResource::getUrl('create', [
                                'participant_id' => $record->id,
                            ]);
                        }
                    }),
            ])
            ->bulkActions([]);
    }
    public static function downloadDocuments(Participant $participant)
    {
        if (empty($participant->documents)) {
            return;
        }

        if (count($participant->documents) === 1) {
            return Storage::disk('public')->download($participant->documents[0]);
        }

        $zip = new \ZipArchive();
        $zipFileName = 'documents-' . $participant->id . '.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($participant->documents as $document) {
                if (Storage::disk('public')->exists($document)) {
                    $zip->addFile(
                        Storage::disk('public')->path($document),
                        basename($document)
                    );
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenilaianJuris::route('/'),
            // Kita tidak pakai halaman create/edit resource INI
        ];
    }

}
