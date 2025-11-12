<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Setting;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        $currentCompetition = Competition::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        $categories = $currentCompetition ? $currentCompetition->activeCategories : collect();

        // Ambil pengaturan dari database
        $settings = [
            'primary_color' => Setting::getValue('primary_color', '#3b82f6'),
            'secondary_color' => Setting::getValue('secondary_color', '#1e40af'),
            'contact_phone' => Setting::getValue('contact_phone', '081335109003'),
            'contact_email' => Setting::getValue('contact_email', 'info@nganjukkab.go.id'),
            'contact_person' => Setting::getValue('contact_person', 'YULI'),
            'competition_theme' => Setting::getValue('competition_theme', 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing'),
            'registration_location' => Setting::getValue('registration_location', 'di Bidang Litbang Bappeda Kab. Nganjuk (pada jam kerja)'),
        ];

        return view('welcome', compact('categories', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:participants,email',
            'phone' => 'required|string|max:20',
            'category' => 'required|exists:categories,id',
            'innovation_title' => 'required|string|max:255',
            'innovation_description' => 'required|string',
            'documents' => 'required|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png,zip|max:10240',
        ]);

        // Cek apakah email sudah terdaftar
        $existingParticipant = \App\Models\Participant::where('email', $request->email)->first();
        if ($existingParticipant) {
            return back()->with('error', 'Email ini sudah terdaftar. Setiap email hanya dapat mendaftar sekali.');
        }

        $participant = \App\Models\Participant::create([
            'category_id' => $request->category,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'institution' => $request->institution,
            'innovation_title' => $request->innovation_title,
            'innovation_description' => $request->innovation_description,
            'is_approved' => false,
        ]);

        // Upload dokumen
        $documentPaths = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $path = $document->store('participant-documents', 'public');
                $documentPaths[] = $path;
            }
            $participant->update(['documents' => $documentPaths]);
        }

        return back()->with('success', 'Pendaftaran berhasil! Terima kasih telah mendaftar.');
    }
}
