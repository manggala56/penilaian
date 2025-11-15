<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Participant;
use App\Models\Aspect;
use App\Models\EvaluationScore; // Sesuaikan nama model jika berbeda
use Illuminate\Database\Eloquent\Model;

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    public function mount(): void
    {
        parent::mount();

        $participantId = request()->query('participant_id');

        // Jika ada participant_id dari query string
        if ($participantId) {
            $participant = Participant::with('category')->find($participantId);

            if ($participant && $participant->category_id) {
                // Isi form dengan data peserta dan kategorinya
                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_id' => $participant->category_id,
                    'category_name' => $participant->category->name, // Karena field ini disabled dan dehydrated(false)
                    'evaluation_date' => now(),
                    // 'scores' tidak diisi di sini untuk Create, biarkan afterStateUpdated yang menanganinya
                ]);
            } else {
                // Jika participant tidak ditemukan atau tidak punya kategori, mungkin arahkan ke index
                // Atau tampilkan error
                // $this->redirect($this->getResource()::getUrl('index'));
                // Atau tampilkan notifikasi error
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan user_id (juri yang login) diisi
        $data['user_id'] = Auth::id();

        // Data 'scores' yang disimpan seharusnya berasal dari relationship repeater
        // Jika 'scores' tidak muncul di data, bisa jadi ada masalah dengan relationship atau afterStateUpdated
        // Kita biarkan 'scores' diproses oleh Filament melalui relationship
        // unset($data['category_name']); // Karena dehydrated(false), field ini ikut terkirim, hapus jika tidak perlu disimpan
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Arahkan ke halaman daftar penilaian setelah berhasil membuat
        return $this->getResource()::getUrl('index');
    }
}
