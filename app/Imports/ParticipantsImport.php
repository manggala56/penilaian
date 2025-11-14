<?php

namespace App\Imports;

    use App\Models\Participant;
    use Maatwebsite\Excel\Concerns\ToModel;
    use Maatwebsite\Excel\Concerns\WithHeadingRow;
    use Maatwebsite\Excel\Concerns\WithValidation;
    use Maatwebsite\Excel\Concerns\SkipsOnFailure;
    use Maatwebsite\Excel\Concerns\SkipsFailures;

    class ParticipantsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
    {
        use SkipsFailures;

        /**
        * @param array $row
        *
        * @return \Illuminate\Database\Eloquent\Model|null
        */
        public function model(array $row)
        {
            return new Participant([
                // (Pastikan nama header di sini 'nama_inovasi_wajib' dll,
                // cocok dengan slug header dari file Export Anda)

                // Wajib
                'category_id'          => $row['kategori_id_wajib'],
                'innovation_title'     => $row['nama_inovasi_wajib'],

                // Opsional
                'name'                 => $row['nama_inisiator_opsional'],
                'email'                => $row['email_opsional'],
                'phone'                => $row['telepon_opsional'],
                'innovation_description' => $row['deskripsi_inovasi_opsional'],
                'institution'          => $row['nama_akun_opsional'],

                'is_approved'          => false,
            ]);
        }

        /**
         * @return array
         */
        public function rules(): array
        {
            // === INI BAGIAN YANG DIUBAH ===
            // Validasi sekarang hanya mewajibkan kategori dan nama inovasi
            return [
                // Wajib
                'kategori_id_wajib' => 'required|integer|exists:categories,id',
                'nama_inovasi_wajib' => 'required|string|max:255',

                // Opsional (nullable)
                'nama_inisiator_opsional' => 'nullable|string|max:255',
                'email_opsional' => 'nullable|email|unique:participants,email', // Email boleh null, tapi jika diisi, harus unik
                'telepon_opsional' => 'nullable|string|max:20',
                'deskripsi_inovasi_opsional' => 'nullable|string',
                'nama_akun_opsional' => 'nullable|string|max:255',
            ];
        }

        /**
         * @return array
         */
        public function customValidationMessages()
        {
            // Pesan kustom agar lebih mudah dibaca pengguna
            return [
                'kategori_id_wajib.required' => 'Kolom kategori_id (Wajib*) harus diisi.',
                'kategori_id_wajib.exists' => 'ID Kategori yang dimasukkan tidak valid (tidak ada di database).',
                'nama_inovasi_wajib.required' => 'Kolom nama_inovasi (Wajib*) harus diisi.',
                'email_opsional.unique' => 'Email yang dimasukkan sudah terdaftar.',
            ];
        }
    }
