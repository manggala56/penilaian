<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParticipantResource\Pages;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use App\Imports\ParticipantsImport;
use App\Exports\ParticipantsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Database\Eloquent\Model;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Competition;
use Illuminate\Support\HtmlString;
use App\Models\Setting;
use App\Models\User;
use App\Models\Juri;
use Filament\Support\Enums\Alignment;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Peserta';

    protected static ?string $navigationGroup = 'Manajemen Peserta';
    protected static ?int $navigationSort = 6 ;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Peserta')
                    ->schema([
                        Forms\Components\Select::make('competition_id')
                            ->label('Lomba')
                            ->options(Competition::all()->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('category_id', null))
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                if ($record && $record->category) {
                                    $component->state($record->category->competition_id);
                                }
                            }),
                            Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                    $competitionId = $get('competition_id');
                                    if ($competitionId) {
                                        $query->where('competition_id', $competitionId);
                                    }
                                }
                            )
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule) {
                                return $rule->whereNull('deleted_at');
                            })
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('institution')
                            ->label('Institusi')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('innovation_title')
                            ->label('Judul Inovasi')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('innovation_description')
                            ->label('Deskripsi Inovasi')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('documents')
                            ->label('Dokumen Pendukung')
                            ->multiple()
                            ->preserveFilenames()
                            ->disk('public')
                            ->directory('participant-documents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'application/zip'
                            ]),
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Disetujui')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                // Gunakan Tables\Actions\Action untuk header actions
                Tables\Actions\Action::make('download_template')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->visible(fn () => Auth::user()->role !== 'juri')
                    ->action(fn () => Excel::download(new ParticipantsTemplateExport, 'template_peserta.xlsx')),

                Tables\Actions\Action::make('import')
                    ->label('Impor Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('success')
                    ->form([
                        FileUpload::make('attachment')
                            ->label('Upload Template')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                    ])
                    ->action(function (array $data) {
                        try {
                            Excel::import(new ParticipantsImport, $data['attachment']);

                            Notification::make()
                                ->title('Impor Berhasil')
                                ->body('Data peserta telah berhasil diimpor.')
                                ->success()
                                ->send();
                        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                            $failures = $e->failures();
                            $errorMessages = [];

                            foreach ($failures as $failure) {
                                $errorMessages[] = "Baris " . $failure->row() . ": " . implode(", ", $failure->errors());
                            }

                            Notification::make()
                                ->title('Impor Gagal: Terdapat Kesalahan Validasi')
                                ->body(implode("\n", $errorMessages))
                                ->danger()
                                ->persistent()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Impor Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn () => Auth::user()->role !== 'juri'),
            ])
            ->recordUrl(null)
            ->recordAction('checkStatus')
            ->defaultSort('category.name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('category.competition.name')
                    ->label('Lomba')
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    Tables\Columns\IconColumn::make('is_fully_evaluated')
                    ->label('Status Juri')
                    ->boolean()
                    ->state(function (Participant $record) {
                        $totalEligible = Juri::query()
                        ->active() // Hanya juri aktif
                        ->canJudgeCategory($record->category_id)
                        ->count();
                    if ($totalEligible === 0) return false;
                    return $record->evaluations_count == $totalEligible;
                })
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->color(fn (bool $state) => $state ? 'success' : 'warning')
                    ->tooltip(function (Participant $record) {
                        $categoryId = $record->category_id;
                        $totalEligible = Juri::active()->canJudgeCategory($categoryId)->count();
                        return "Dinilai oleh {$record->evaluations_count} dari {$totalEligible} Juri yang bertugas";
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('innovation_title')
                    ->label('Judul Inovasi')
                    ->searchable()
                    ->limit(30)
                    ->visibleFrom('sm'),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai Akhir')
                    ->numeric(2)
                    ->sortable(query: function ($query, string $direction) {
                        $query->orderByRaw('(
                            SELECT AVG(final_score)
                            FROM evaluations
                            WHERE participant_id = participants.id
                        ) ' . $direction);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('is_approved')
                    ->label('Status Persetujuan')
                    ->options([
                        true => 'Disetujui',
                        false => 'Belum Disetujui',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('checkStatus')
                ->label('Cek Status')
                ->icon('heroicon-o-list-bullet')
                ->color('secondary')
                ->modalHeading(fn (Participant $record) => "Rangkuman Penilaian – {$record->name}")
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function (Participant $record) {
                    $juris = \App\Models\Juri::with('user')
                        ->active()
                        ->canJudgeCategory($record->category_id)
                        ->get();
                    $evaluatedUserIds = $record->evaluations()->pluck('user_id')->toArray();

                    $html = '<div class="overflow-hidden border border-gray-200 rounded-lg dark:border-white/10">';
                    $html .= '<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">';
                    $html .= '<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-white/5 dark:text-gray-200"><tr><th class="px-4 py-3 font-medium">Nama Juri</th><th class="px-4 py-3 font-medium">Status</th></tr></thead>';
                    $html .= '<tbody class="divide-y divide-gray-200 dark:divide-white/5">';

                    if ($juris->isEmpty()) {
                        $html .= '<tr><td colspan="2" class="px-4 py-3 text-center text-gray-500 italic">Belum ada juri yang ditugaskan untuk kategori ini.</td></tr>';
                    }

                    foreach ($juris as $juri) {
                        $hasRated = in_array($juri->user_id, $evaluatedUserIds);

                        $statusBadge = $hasRated
                            ? '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 rounded-md ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">Sudah Menilai</span>'
                            : '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-gray-50 rounded-md ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">Belum Menilai</span>';

                        $bgRow = $hasRated ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-white/5';
                        $juriName = $juri->user->name ?? 'Juri Tanpa Nama';

                        $html .= "<tr class='{$bgRow} transition hover:bg-gray-50 dark:hover:bg-white/5'>";
                        $html .= "<td class='px-4 py-3 font-medium text-gray-900 dark:text-white'>{$juriName}</td>";
                        $html .= "<td class='px-4 py-3'>{$statusBadge}</td>";
                        $html .= '</tr>';
                    }

                    $html .= '</tbody></table></div>';

                    return new HtmlString($html);
                }),
                Tables\Actions\Action::make('viewPdf')
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
                Tables\Actions\Action::make('evaluate')
                    ->label('Nilai')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn () => Auth::user()->role == 'juri')
                    ->url(fn (Participant $record) => \App\Filament\Resources\EvaluationResource::getUrl('create', [
                        'participant_id' => $record->id
                    ]))
                    ->hidden(function (Participant $record): bool {
                        return $record->evaluations_count > 0;
                    }),
                    Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Participant $record) => static::downloadDocuments($record))
                    ->color('success'),
                Tables\Actions\EditAction::make()
                ->visible(fn () => Auth::user()->role !== 'juri'),
                Tables\Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()->role !== 'juri'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListParticipants::route('/'),
            'create' => Pages\CreateParticipant::route('/create'),
            'edit' => Pages\EditParticipant::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return Auth::user()->role !== 'juri';
    }
    public static function canCreate(): bool
    {
        return Auth::user()->role !== 'juri';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->role !== 'juri';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->role !== 'juri';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->role !== 'juri';
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['category.competition']);
        $user = Auth::user();
        if ($user && $user->role !== 'admin') {
            $activeCompetitions = Competition::where('is_active', true)
                                            ->with('activeStage', 'categories')
                                            ->get();
            if ($activeCompetitions->isEmpty()) {
                return $query->whereNull('id');
            }
            $query->where(function (Builder $participantQuery) use ($activeCompetitions) {
                $hasActiveStages = false;

                foreach ($activeCompetitions as $competition) {
                    if ($competition->activeStage) {
                        $hasActiveStages = true;
                        $stageOrder = $competition->activeStage->stage_order;
                        $categoryIds = $competition->categories->pluck('id');

                        if ($categoryIds->isNotEmpty()) {
                            $participantQuery->orWhere(function (Builder $q) use ($categoryIds, $stageOrder) {
                                $q->whereIn('category_id', $categoryIds)
                                ->where('current_stage_order', $stageOrder);
                            });
                        }
                    }
                }

                if (!$hasActiveStages) {
                    $participantQuery->whereNull('id');
                }
            });
        }
        $query->withCount('evaluations');
        $query->withAvg('evaluations', 'final_score');
        $query->orderBy('evaluations_count', 'asc');
        $query->orderBy('evaluations_avg_final_score', 'desc');
        return $query;
    }

    public static function getSoftDeletingScope(): ?string
    {
        if (Auth::user()->role === 'superadmin') {
            return \Illuminate\Database\Eloquent\SoftDeletingScope::class;
        }
        return null;
    }
}
