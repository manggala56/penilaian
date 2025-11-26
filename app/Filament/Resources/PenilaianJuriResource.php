<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource;
use App\Filament\Resources\PenilaianJuriResource\Pages;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\Evaluation;
use App\Models\Juri;
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
use App\Models\Setting;
use Filament\Support\Enums\Alignment;

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

        $juriProfile =  Juri::where('user_id', $juriId)->with('categories')->first();

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
                                    ->with('scores.aspect'),
            ]);
            if ($juriProfile && !$juriProfile->can_judge_all_categories) {
                $allowedCategoryIds = $juriProfile->categories->pluck('id')->toArray();
                $participantQuery->whereIn('category_id', $allowedCategoryIds);
            }
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
        ->header(function () {
            $status = Setting::getJudgingStatus();

            if ($status === 'not_started') {
                $start = Setting::first()->judging_start->format('d M Y H:i');
                return view('filament.components.alert-warning', [
                    'title' => 'Penilaian Belum Dimulai',
                    'message' => "Fitur penilaian akan dibuka pada: {$start}"
                ]);
            }

            if ($status === 'ended') {
                return view('filament.components.alert-danger', [
                    'title' => 'Penilaian Berakhir',
                    'message' => 'Sesi penilaian telah ditutup. Anda tidak dapat mengubah atau menambah nilai lagi.'
                ]);
            }

            return null;
        })
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
                    ->sortable()
                    ->searchable(),

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

            ])
            ->description(function() {
                $status = Setting::getJudgingStatus();
                if ($status === 'not_started') {
                    return new HtmlString('<div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50" role="alert"><span class="font-medium">Penilaian Belum Dimulai!</span> Harap tunggu jadwal yang ditentukan.</div>');
                }
                if ($status === 'ended') {
                    return new HtmlString('<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert"><span class="font-medium">Penilaian Berakhir!</span> Sesi penilaian telah ditutup.</div>');
                }
                return null;
            })
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
