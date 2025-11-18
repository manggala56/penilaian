<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Participant;
use App\Models\Setting;
use App\Models\UploadHistory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WelcomeController extends Controller
{
    public function index()
    {
        try {
            $currentCompetition = Competition::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            // Jika tidak ada kompetisi aktif, ambil kategori default
            if (!$currentCompetition) {
                $categories = Category::where('is_active', true)->get();
            } else {
                $categories = $currentCompetition->activeCategories;
            }

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

        } catch (\Exception $e) {
            Log::error('WelcomeController index error: ' . $e->getMessage());
            // Fallback dengan data kosong jika ada error
            $categories = collect();
            $settings = [
                'primary_color' => '#3b82f6',
                'secondary_color' => '#1e40af',
                'contact_phone' => '081335109003',
                'contact_email' => 'info@nganjukkab.go.id',
                'contact_person' => 'YULI',
                'competition_theme' => 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing',
                'registration_location' => 'di Bidang Litbang Bappeda Kab. Nganjuk (pada jam kerja)',
            ];

            return view('welcome', compact('categories', 'settings'));
        }
    }

    public function store(Request $request)
    {
        // Validasi
        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email',
            'phone'                 => 'required|string|max:20',
            'institution'           => 'nullable|string|max:255',
            'category'              => 'required|exists:categories,id',
            'innovation_title'      => 'required|string|max:255',
            'innovation_description'=> 'required|string|min:25|max:2000',
            'documents'             => 'required|array|min:1|max:5',
            'documents.*'           => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar sebagai peserta.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'category.required' => 'Kategori lomba wajib dipilih.',
            'innovation_title.required' => 'Judul inovasi wajib diisi.',
            'innovation_description.required' => 'Deskripsi inovasi wajib diisi.',
            'innovation_description.min' => 'Deskripsi inovasi minimal 50 karakter.',
            'documents.required' => 'Minimal 1 file pendukung harus diupload.',
            'documents.min' => 'Minimal 1 file pendukung harus diupload.',
            'documents.max' => 'Maksimal 5 file yang dapat diupload.',
            'documents.*.mimes' => 'File harus berformat: PDF, DOC, DOCX, JPG, JPEG, PNG, atau ZIP.',
            'documents.*.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat kesalahan pada form.',
                'errors'  => $validator->errors()
            ], 422);
        }
        if (Participant::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Email ini sudah terdaftar sebagai peserta. Gunakan email lain.'
            ], 422);
        }
        // Cek IP limit (1x dalam 24 jam)
        $ipAddress = $request->ip();
        $ipCount = UploadHistory::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($ipCount >= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Maaf, Anda hanya boleh mendaftar 1 kali dalam 24 jam dari perangkat/jaringan ini.'
            ], 429);
        }


        try {
            DB::beginTransaction();

            $documentPaths = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '_' . \Str::slug($originalName) . '.' . $extension;

                    $path = $file->storeAs('participant-documents', $filename, 'public');

                    $documentPaths[] = $path;
                }
            }

            $participant = Participant::create([
                'category_id'            => $request->category,
                'name'                   => $request->name,
                'email'                  => $request->email,
                'phone'                  => $request->phone,
                'institution'            => $request->institution,
                'innovation_title'       => $request->innovation_title,
                'innovation_description' => $request->innovation_description,
                'documents'              => $documentPaths,
                'is_approved'            => false,
                'registration_date'      => now(),
            ]);

            UploadHistory::create([
                'participant_id'       => $participant->id,
                'email'                => $request->email,
                'ip_address'           => $ipAddress,
                'user_agent'           => $request->header('User-Agent'),
                'uploaded_files_count' => count($documentPaths),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil! Terima kasih telah mendaftar. Kami akan menghubungi Anda melalui email.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti atau hubungi contact person.'
            ], 500);
        }
    }
    /**
     * Method untuk menampilkan halaman sukses (optional)
     */
    public function success()
    {
        return view('registration-success');
    }

    /**
     * Method untuk download file (optional)
     */
    public function downloadGuide()
    {
        $filePath = storage_path('app/public/registration-guide.pdf');

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File panduan tidak ditemukan.');
        }

        return response()->download($filePath, 'Panduan-Pendaftaran-Lomba-Inovasi-Nganjuk-2024.pdf');
    }

    /**
     * Method untuk mendapatkan data peserta berdasarkan email (API)
     */
    public function checkEmail(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return response()->json(['error' => 'Email parameter required'], 400);
        }

        $exists = Participant::where('email', $email)->exists();

        return response()->json([
            'email' => $email,
            'registered' => $exists
        ]);
    }
}
