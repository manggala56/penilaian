<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['competition_title'] ?? 'Lomba Inovasi Kabupaten Nganjuk' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @php

    @endphp
    <style>
       :root {
        --highlight: {{ $settings['primary_color'] ?? '#0066cc' }};
        --primary: {{ $settings['secondary_color'] ?? '#0a1931' }};
        --secondary: {{ $settings['secondary_color'] ?? '#1e40af' }};
        --gradient-start: {{ $settings['secondary_color'] ?? '#0a1931' }};
        --gradient-end: {{ $settings['primary_color'] ?? '#1e40af' }};
        --accent: {{ $settings['primary_color'] ?? '#0066cc' }};
    }
    .btn-primary, .btn {
        background-color: var(--highlight) !important;
        border-color: var(--highlight) !important;
    }

    .btn-primary:hover, .btn:hover {
        filter: brightness(90%);
    }    .feature-icon {
        color: var(--highlight) !important;
    }
    .feature-card:hover .feature-icon {
        background-color: var(--highlight) !important;
        color: white !important;
    }    .poster-badge, .poster-highlight {
        background: var(--highlight) !important;
    }
        .swal2-popup {
            font-family: 'Poppins', 'Segoe UI', sans-serif !important;
            border-radius: 12px !important;
        }
        .swal2-confirm.swal2-styled {
            background-color:  color: var(--highlight) !important;
            color: white !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 10px 28px !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4) !important;
            transition: all 0.3s ease !important;
        }

        .swal2-confirm.swal2-styled:hover {
            background-color:  color: var(--highlight) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.5) !important;
        }

        .swal2-confirm.swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.5) !important;
        }

        /* Kalau ada tombol Cancel/Tidak → tetap merah supaya kontras */
        .swal2-cancel.swal2-styled {
            background-color: #dc2626 !important;
            border-radius: 8px !important;
        }

        .swal2-cancel.swal2-styled:hover {
            background-color: #b91c1c !important;
        }

        /* Icon success & error biar lebih bold */
        .swal2-success-ring { border-color: rgba(34, 197, 94, 0.3) !important; }
        .swal2-error { border-color: #fca5a5 !important; }
        .error-text { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; display: block; }
        .form-group.has-error input,
        .form-group.has-error textarea,
        .form-group.has-error select { border-color: #dc2626; background-color: #fef2f2; }
        .file-upload.has-error { border-color: #dc2626; background-color: #fef2f2; }
        .loading { opacity: 0.7; pointer-events: none; }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-lightbulb"></i>
            <span>Lomba Inovasi</span>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        <nav>
            <ul id="navMenu">
                <li><a href="#" class="active"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="#features"><i class="fas fa-info-circle"></i> Tentang</a></li>
                @if($activeCompetitions->isNotEmpty())
                    <li><a href="#registration"><i class="fas fa-edit"></i> Pendaftaran</a></li>
                @endif
                <li><a href="#contact"><i class="fas fa-phone"></i> Kontak</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Hero -->
<section class="hero">
    <div class="container hero-content">
        <h1 class="reveal-on-scroll">{{ $settings['competition_title'] ?? 'Lomba Inovasi Kabupaten Nganjuk 2024' }}</h1>
        <p class="reveal-on-scroll">{{ $settings['competition_theme'] ?? 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing' }}</p>
        @if($activeCompetitions->isNotEmpty())
        <a href="#registration" class="btn btn-primary reveal-on-scroll">Daftar Sekarang</a>
        @else
            <a href="#" class="btn btn-secondary reveal-on-scroll" style="background-color: grey; cursor: not-allowed;">Pendaftaran Ditutup</a>
        @endif
    </div>
</section>

<!-- Peserta -->
<section class="features" id="features">
    <div class="container">
        <div class="section-title">
            <h2 class="reveal-on-scroll">Peserta Lomba</h2>
            <p class="reveal-on-scroll">Terbuka untuk seluruh masyarakat Kabupaten Nganjuk</p>
        </div>
        <div class="features-grid">
            <div class="feature-card reveal-on-scroll">
                <div class="feature-icon"><i class="fas fa-laptop"></i></div>
                <h3>Perangkat Daerah</h3>
                <p>Semua instansi pemerintah daerah</p>
            </div>
            <div class="feature-card reveal-on-scroll">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h3>Masyarakat</h3>
                <p>Warga dari berbagai latar belakang</p>
            </div>
            <div class="feature-card reveal-on-scroll">
                <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Pelajar</h3>
                <p>Siswa dan mahasiswa</p>
            </div>
        </div>
        <div class="poster-highlight reveal-on-scroll" style="margin-top: 3rem;">
            <h3>{{ $settings['prize_total'] ?? 'TOTAL HADIAH 90 JUTA!' }}</h3>
        </div>
    </div>
</section>

<!-- Tema -->
<section class="theme-section">
    <div class="container">
        <h2 class="reveal-on-scroll">TEMA LOMBA</h2>
        <p class="reveal-on-scroll">{{ $settings['competition_theme'] ?? 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing' }}</p>
    </div>
</section>

<!-- Form Pendaftaran -->
<section class="registration" id="registration">
    <div class="container">
        @if($activeCompetitions->isNotEmpty())
        <div class="section-title">
            <h2 class="reveal-on-scroll">Formulir Pendaftaran</h2>
            <p class="reveal-on-scroll">Isi formulir di bawah ini dengan lengkap</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif

        <div class="form-container reveal-on-scroll">
            <form id="registrationForm" action="{{ route('participant.register') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group @error('name') has-error @enderror">
                    <label for="name"><i class="fas fa-user"></i> Nama Lengkap *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Masukkan nama lengkap">
                    @error('name') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('email') has-error @enderror">
                    <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="contoh@email.com">
                    @error('email') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('phone') has-error @enderror">
                    <label for="phone"><i class="fas fa-phone"></i> Nomor Telepon *</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="08xxxxxxxxxx">
                    @error('phone') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="institution"><i class="fas fa-building"></i> Institusi</label>
                    <input type="text" id="institution" name="institution" value="{{ old('institution') }}" placeholder="Instansi/Sekolah/Universitas (opsional)">
                </div>

                <div class="form-group @error('category') has-error @enderror">
                    <label for="category"><i class="fas fa-list"></i> Kategori Lomba *</label>
                    <select id="category" name="category" required>
                        @if($activeCompetitions->isNotEmpty())
                            @foreach($activeCompetitions as $competition)
                                <optgroup label="{{ $competition->name }}">
                                    @foreach($competition->activeCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @elseif($orphanCategories->isNotEmpty())
                            <optgroup label="Umum">
                                @foreach($orphanCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('category') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('innovation_title') has-error @enderror">
                    <label for="innovation_title"><i class="fas fa-lightbulb"></i> Judul Inovasi *</label>
                    <input type="text" id="innovation_title" name="innovation_title" value="{{ old('innovation_title') }}" required placeholder="Judul inovasi Anda">
                    @error('innovation_title') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('innovation_description') has-error @enderror">
                    <label for="innovation_description"><i class="fas fa-file-alt"></i> Deskripsi Inovasi *</label>
                    <textarea id="innovation_description" name="innovation_description" rows="4" required placeholder="Jelaskan inovasi Anda (min. 25 karakter)">{{ old('innovation_description') }}</textarea>
                    @error('innovation_description') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('documents') has-error @enderror @error('documents.*') has-error @enderror">
                    <label><i class="fas fa-upload"></i> Upload Dokumen Pendukung * (max 10MB )</label>
                    <div class="file-upload" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Klik atau seret file ke sini</p>
                        <p class="file-info">PDF, DOC, DOCX, JPG, PNG, ZIP</p>
                        <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" style="display:none;">
                    </div>
                    <div id="fileList"></div>
                    @error('documents') <span class="error-text">{{ $message }}</span> @enderror
                    @error('documents.*') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn" style="width:100%;">
                    <i class="fas fa-paper-plane"></i> Kirim Pendaftaran
                </button>
            </form>
        </div>

        @if($errors->any())
            <div class="alert alert-error" style="margin-top: 2rem;">
                <strong><i class="fas fa-exclamation-circle"></i> Terdapat kesalahan:</strong>
                <ul style="margin-left:1.5rem; margin-top:0.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @else
            <div class="section-title reveal-on-scroll" style="padding: 4rem 0; text-align: center;">
                <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h2>Pendaftaran Ditutup</h2>
                <p>Saat ini tidak ada periode lomba yang aktif atau waktu pendaftaran telah berakhir.<br>Nantikan informasi selanjutnya!</p>
            </div>
        @endif
    </div>
</section>

<!-- Footer -->
<footer id="contact">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section reveal-on-scroll">
                <h3><i class="fas fa-trophy"></i> Lomba Inovasi</h3>
                <p>Mendorong kreativitas masyarakat Kabupaten Nganjuk.</p>
                {{-- 5. Badge Tanggal Dinamis --}}
                <div class="poster-badge" style="background: var(--color-primary)"><i class="fas fa-calendar"></i> {{ $settings['footer_badge_date'] ?? '1–31 Oktober 2024' }}</div>
            </div>
            <div class="footer-section reveal-on-scroll">
                <h3><i class="fas fa-map-marker-alt"></i> Lokasi</h3>
                <a href="https://jendelalitbang.nganjukkab.go.id/#contact-area">{{ $settings['registration_location'] ?? 'Bidang Litbang Bappeda Kab. Nganjuk' }}

                </a>
            </div>
            <div class="footer-section reveal-on-scroll">
                <h3><i class="fas fa-link"></i> Tautan</h3>
                <a href="#"><i class="fas fa-home"></i> Beranda</a>
                <a href="#features"><i class="fas fa-info-circle"></i> Tentang</a>
                @if($activeCompetitions->isNotEmpty())
                <a href="#registration"><i class="fas fa-edit"></i> Pendaftaran</a>
            @endif
            </div>
        </div>
        <div class="copyright">
            <p>&copy; {{ date('Y') }} {{ $settings['competition_title'] ?? 'Lomba Inovasi Kabupaten Nganjuk' }}</p>
            <p>Info lengkap: <a href="https://jendelalitbang.nganjukkab.go.id/litbang/berita" target="_blank">jendelalitbang.nganjukkab.go.id</a></p>
            <p>Contact Person: {{ $settings['contact_person'] ?? 'YULI' }} - WA: {{ $settings['contact_phone'] ?? '081335109003' }}</p>
        </div>
    </div>
</footer>

<!-- Success Modal -->
<div class="modal" id="successModal">
    <div class="modal-content">
        <button class="close-btn" id="closeModal">×</button>
        <h2><i class="fas fa-check-circle"></i> Pendaftaran Berhasil!</h2>
        <p>Terima kasih! Bukti pendaftaran telah dikirim ke email Anda.</p>
        <button class="btn" id="okBtn">OK</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Notifikasi Session (Tetap jalan di kondisi apapun)
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            timer: 10000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: '{{ session('error') }}'
        });
    @endif

    // 2. Logika Form Pendaftaran (HANYA JALAN JIKA FORM ADA)
    const form = document.getElementById('registrationForm');

    if (form) {
        const submitBtn     = document.getElementById('submitBtn');
        const fileArea      = document.getElementById('fileUploadArea');
        const fileInput     = document.getElementById('documents');
        const fileList      = document.getElementById('fileList');
        let selectedFiles   = [];

        // === Event Listeners untuk File Upload ===
        // Cek fileArea dulu untuk keamanan ekstra
        if (fileArea && fileInput) {
            fileArea.addEventListener('click', () => fileInput.click());

            ['dragover', 'dragenter'].forEach(ev => {
                fileArea.addEventListener(ev, e => {
                    e.preventDefault();
                    fileArea.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(ev => {
                fileArea.addEventListener(ev, e => {
                    e.preventDefault();
                    fileArea.classList.remove('dragover');
                });
            });

            fileArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
            fileInput.addEventListener('change', () => handleFiles(fileInput.files));
        }

        function handleFiles(files) {
            [...files].forEach(file => {
                // Ukuran maks 10MB
                if (file.size > 10 * 1024 * 1024) {
                    Swal.fire('Error', `File "${file.name}" terlalu besar! Maksimal 10MB.`, 'error');
                    return;
                }

                // Tipe file yang diizinkan
                const allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/jpg','image/png','application/zip'];
                if (!allowed.includes(file.type)) {
                    Swal.fire('Error', `Tipe file "${file.name}" tidak diperbolehkan.`, 'error');
                    return;
                }

                // Maksimal 5 file
                if (selectedFiles.length >= 5) {
                    Swal.fire('Peringatan', 'Maksimal 5 file saja!', 'warning');
                    return;
                }

                // Cek duplikat (nama + ukuran)
                const duplicate = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                if (!duplicate) {
                    selectedFiles.push(file);
                }
            });

            renderFileList();
        }

        function renderFileList() {
            if (!fileList) return;
            fileList.innerHTML = '';

            if (selectedFiles.length === 0) {
                fileArea.innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Klik atau seret file ke sini</p>
                    <p class="file-info">PDF, DOC, DOCX, JPG, PNG, ZIP (maks 10MB)</p>
                    <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" style="display:none;">
                `;
                return;
            }

            fileArea.innerHTML = `
                <i class="fas fa-check-circle" style="color:#22c55e;font-size:2rem;"></i>
                <p>${selectedFiles.length} file dipilih</p>
                <p class="file-info">Klik area ini untuk tambah/ganti</p>
            `;

            selectedFiles.forEach((file, i) => {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f1f5f9;margin:8px 0;border-radius:8px;';
                div.innerHTML = `
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fas fa-file-alt"></i>
                        <span style="font-size:0.9rem;">
                            ${file.name} <small>(${(file.size/1024/1024).toFixed(2)} MB)</small>
                        </span>
                    </div>
                    <button type="button" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:1.2rem;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                div.querySelector('button').addEventListener('click', () => {
                    selectedFiles.splice(i, 1);
                    renderFileList();
                });
                fileList.appendChild(div);
            });
        }

        // === Handle Submit ===
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const descInput = document.getElementById('innovation_description');
            if (descInput) {
                const desc = descInput.value.trim();
                if (desc.length < 25) {
                    Swal.fire('Peringatan', 'Deskripsi inovasi minimal 25 karakter!', 'warning');
                    return;
                }
            }

            if (selectedFiles.length === 0) {
                Swal.fire('Peringatan', 'Harap upload minimal 1 dokumen pendukung!', 'warning');
                return;
            }

            const formData = new FormData(form);
            formData.delete('documents[]');
            selectedFiles.forEach(file => formData.append('documents[]', file));

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            }

            fetch('{{ route("participant.register") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message || 'Pendaftaran berhasil!',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(err => {
                let message = 'Terjadi kesalahan saat mendaftar.';
                if (err.message) message = err.message;
                else if (err.errors) message = Object.values(err.errors)[0][0];

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: message,
                    confirmButtonText: 'OK'
                });
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Pendaftaran';
                }
            });
        });
    }

    // 3. UI Global (Menu & Scroll Animation) - INI HARUS DI LUAR IF(FORM)
    // Agar menu dan animasi tetap jalan walau tidak ada form pendaftaran
    const mobileBtn = document.getElementById('mobileMenuBtn');
    if (mobileBtn) {
        mobileBtn.addEventListener('click', () => {
            document.getElementById('navMenu').classList.toggle('show');
        });
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    });
    document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
});
</script>
</body>
</html>
