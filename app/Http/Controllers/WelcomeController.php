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
        // Validasi data input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:participants,email',
            'phone' => 'required|string|max:20',
            'institution' => 'nullable|string|max:255',
            'category' => 'required|exists:categories,id',
            'innovation_title' => 'required|string|max:255',
            'innovation_description' => 'required|string|min:50|max:2000',
            'documents' => 'required|array|min:1|max:5',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240', // 10MB
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar sebagai peserta.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'category.required' => 'Kategori lomba wajib dipilih.',
            'category.exists' => 'Kategori yang dipilih tidak valid.',
            'innovation_title.required' => 'Judul inovasi wajib diisi.',
            'innovation_description.required' => 'Deskripsi inovasi wajib diisi.',
            'innovation_description.min' => 'Deskripsi inovasi minimal 50 karakter.',
            'documents.required' => 'Minimal 1 file pendukung harus diupload.',
            'documents.min' => 'Minimal 1 file pendukung harus diupload.',
            'documents.max' => 'Maksimal 5 file yang dapat diupload.',
            'documents.*.mimes' => 'File harus berformat: PDF, DOC, DOCX, JPG, JPEG, PNG, atau ZIP.',
            'documents.*.max' => 'Ukuran file maksimal 10MB.',
        ]);

        // Cek jika validasi gagal
        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()->all(), 'email' => $request->email]);
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Terjadi kesalahan dalam pengisian form. Silakan periksa kembali.');
        }

        // Validasi IP address (maksimal 3 pendaftaran per IP)
        $ipAddress = $request->ip();
        $ipCount = UploadHistory::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($ipCount >= 3) {
            Log::warning('IP limit exceeded', ['ip' => $ipAddress, 'count' => $ipCount]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Maaf, Anda telah mencapai batas maksimal pendaftaran (3x dalam 24 jam) dari perangkat/jaringan ini.');
        }

        // Validasi email unik (double check)
        $emailExists = Participant::where('email', $request->email)->exists();
        if ($emailExists) {
            Log::warning('Duplicate email detected', ['email' => $request->email]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Email ini sudah terdaftar sebagai peserta. Mohon gunakan email lain.');
        }

        try {
            DB::beginTransaction();

            // Handle file upload
            $documentPaths = [];
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    // Generate unique filename
                    $originalName = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $document->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '_' . \Str::slug($originalName) . '.' . $extension;

                    // Store file
                    $path = $document->storeAs('participant-documents', $filename, 'public');
                    $documentPaths[] = [
                        'path' => $path,
                        'original_name' => $document->getClientOriginalName(),
                        'size' => $document->getSize(),
                        'mime_type' => $document->getMimeType(),
                    ];
                }
            }

            // Create participant
            $participant = Participant::create([
                'category_id' => $request->category,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'institution' => $request->institution,
                'innovation_title' => $request->innovation_title,
                'innovation_description' => $request->innovation_description,
                'documents' => $documentPaths,
                'is_approved' => false,
                'registration_date' => now(),
            ]);

            // Record upload history
            UploadHistory::create([
                'participant_id' => $participant->id,
                'email' => $request->email,
                'ip_address' => $ipAddress,
                'user_agent' => $request->header('User-Agent'),
                'uploaded_files_count' => count($documentPaths),
            ]);

            DB::commit();

            // Log success
            Log::info('Registration successful', [
                'participant_id' => $participant->id,
                'email' => $participant->email,
                'category_id' => $participant->category_id
            ]);

            return redirect()
                ->back()
                ->with('success', 'Pendaftaran berhasil! Terima kasih telah mendaftar. Kami akan menghubungi Anda melalui email untuk informasi lebih lanjut.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'email' => $request->email
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan sistem saat menyimpan data. Silakan coba lagi dalam beberapa saat. Jika masalah berlanjut, hubungi contact person yang tersedia.');
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
