<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use App\Models\Juri;

class CustomLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $response = parent::authenticate();
            
            $user = auth()->user();
            
            if ($user->role === 'juri') {
                $juri = Juri::where('user_id', $user->id)->first();
                
                if ($juri && !$juri->is_active) {
                    auth()->logout();
                    
                    throw ValidationException::withMessages([
                        'data.email' => __('Akun Anda telah dibatasi. Silakan hubungi administrator.'),
                    ]);
                }
            }
            
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
