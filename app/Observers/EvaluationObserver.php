<?php

namespace App\Observers;

use App\Models\Evaluation;
use App\Models\EvaluationHistory;
use Illuminate\Support\Facades\Auth;

class EvaluationObserver
{
    private function getAuthenticatedUser() {
        return Auth::check() ? Auth::user() : null;
    }

    public function created(Evaluation $evaluation)
    {
        $user = $this->getAuthenticatedUser();
        if ($user && $user->role === 'juri') {
            EvaluationHistory::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'evaluation_id' => $evaluation->id,
                'participant_name' => $evaluation->participant->name,
                'action' => 'dibuat',
                'details' => [
                    'new_final_score' => $evaluation->final_score,
                    'data' => $evaluation->toArray()
                ]
            ]);
        }
    }

    public function updated(Evaluation $evaluation)
    {
        $user = $this->getAuthenticatedUser();
        if ($user && $user->role === 'juri' && $evaluation->isDirty()) {

            $details = ['changes' => $evaluation->getChanges()];
            if ($evaluation->isDirty('final_score')) {
                $details['old_final_score'] = $evaluation->getOriginal('final_score');
                $details['new_final_score'] = $evaluation->final_score;
            }

            EvaluationHistory::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'evaluation_id' => $evaluation->id,
                'participant_name' => $evaluation->participant->name,
                'action' => 'diperbarui',
                'details' => $details
            ]);
        }
    }

    public function deleted(Evaluation $evaluation)
    {
        $user = $this->getAuthenticatedUser();
        if ($user && $user->role === 'juri') {
            EvaluationHistory::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'evaluation_id' => $evaluation->id,
                'participant_name' => $evaluation->participant->name,
                'action' => 'dihapus',
                'details' => [
                    'deleted_final_score' => $evaluation->final_score,
                    'deleted_data' => $evaluation->toArray()
                ]
            ]);
        }
    }
}
