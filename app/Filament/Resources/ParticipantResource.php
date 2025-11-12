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

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Peserta';

    protected static ?string $navigationGroup = 'Manajemen Peserta';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Peserta')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
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
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('innovation_title')
                    ->label('Judul Inovasi')
                    ->searchable()
                    ->limit(50),
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
                Tables\Actions\Action::make('evaluate')
                    ->label('Nilai')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn (Participant $record) => EvaluationResource::getUrl('create', ['participant_id' => $record->id])),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Participant $record) => static::downloadDocuments($record))
                    ->color('success'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

        // Untuk multiple files, buat zip
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
}
