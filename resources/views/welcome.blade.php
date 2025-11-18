<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lomba Inovasi Kabupaten Nganjuk 2024</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <style>
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
                <li><a href="#registration"><i class="fas fa-edit"></i> Pendaftaran</a></li>
                <li><a href="#contact"><i class="fas fa-phone"></i> Kontak</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Hero -->
<section class="hero">
    <div class="container hero-content">
        <h1 class="reveal-on-scroll">Lomba Inovasi Kabupaten Nganjuk 2024</h1>
        <p class="reveal-on-scroll">{{ $settings['competition_theme'] ?? 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing' }}</p>
        <a href="#registration" class="btn btn-primary reveal-on-scroll">Daftar Sekarang</a>
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
            <h3>TOTAL HADIAH 90 JUTA!</h3>
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
                        <option value="">Pilih kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
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
                    <textarea id="innovation_description" name="innovation_description" rows="4" required placeholder="Jelaskan inovasi Anda (min. 50 karakter)">{{ old('innovation_description') }}</textarea>
                    @error('innovation_description') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group @error('documents') has-error @enderror @error('documents.*') has-error @enderror">
                    <label><i class="fas fa-upload"></i> Upload Dokumen Pendukung * (max 5 file, 10MB/file)</label>
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
    </div>
</section>

<!-- Footer -->
<footer id="contact">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section reveal-on-scroll">
                <h3><i class="fas fa-trophy"></i> Lomba Inovasi</h3>
                <p>Mendorong kreativitas masyarakat Kabupaten Nganjuk.</p>
                <div class="poster-badge"><i class="fas fa-calendar"></i> 1–31 Oktober 2024</div>
            </div>
            <div class="footer-section reveal-on-scroll">
                <h3><i class="fas fa-map-marker-alt"></i> Lokasi</h3>
                <p>{{ $settings['registration_location'] ?? 'Bidang Litbang Bappeda Kab. Nganjuk' }}</p>
            </div>
            <div class="footer-section reveal-on-scroll">
                <h3><i class="fas fa-link"></i> Tautan</h3>
                <a href="#"><i class="fas fa-home"></i> Beranda</a>
                <a href="#features"><i class="fas fa-info-circle"></i> Tentang</a>
                <a href="#registration"><i class="fas fa-edit"></i> Pendaftaran</a>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2024 Lomba Inovasi Kabupaten Nganjuk</p>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('successModal');
        const closeModal = () => modal.classList.remove('show');
        document.getElementById('closeModal')?.addEventListener('click', closeModal);
        document.getElementById('okBtn')?.addEventListener('click', closeModal);
        window.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        // Tampilkan modal sukses
        @if(session('success'))
            modal.classList.add('show');
            setTimeout(closeModal, 8000);
        @endif

        // === FILE UPLOAD DENGAN PREVIEW & DRAG-DROP (AMAN, TIDAK HAPUS CSRF) ===
        const fileArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('documents');
        const fileList = document.getElementById('fileList');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('registrationForm');

        // Simpan file hanya untuk preview (tidak sentuh fileInput.files lagi!)
        let selectedFiles = [];

        // Drag & Drop + Click
        fileArea.addEventListener('click', () => fileInput.click());
        ['dragover', 'dragenter'].forEach(ev => fileArea.addEventListener(ev, e => {
            e.preventDefault();
            fileArea.classList.add('dragover');
        }));
        ['dragleave', 'drop'].forEach(ev => fileArea.addEventListener(ev, e => {
            e.preventDefault();
            fileArea.classList.remove('dragover');
        }));

        fileArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));

        function handleFiles(files) {
            Array.from(files).forEach(file => {
                // Validasi ukuran
                if (file.size > 10 * 1024 * 1024) {
                    alert(`File "${file.name}" terlalu besar! Maksimal 10MB.`);
                    return;
                }

                // Validasi tipe
                const allowedTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'application/zip'
                ];
                if (!allowedTypes.includes(file.type)) {
                    alert(`Tipe file "${file.name}" tidak diperbolehkan.`);
                    return;
                }

                // Cek jumlah file
                if (selectedFiles.length >= 5) {
                    alert('Maksimal 5 file saja!');
                    return;
                }

                // Cek duplikat nama + ukuran
                const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
                if (!exists) {
                    selectedFiles.push(file);
                }
            });

            renderFileList();
        }

        function renderFileList() {
            fileList.innerHTML = '';
            if (selectedFiles.length === 0) {
                fileArea.innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Klik atau seret file ke sini</p>
                    <p class="file-info">PDF, DOC, DOCX, JPG, PNG, ZIP (maks 10MB, 5 file)</p>
                `;
                return;
            }

            fileArea.innerHTML = `
                <i class="fas fa-check-circle" style="color:#22c55e;font-size:2rem;"></i>
                <p>${selectedFiles.length} file dipilih</p>
                <p class="file-info">Klik area ini untuk tambah/ganti</p>
            `;

            selectedFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:10px; background:#f1f5f9; margin:8px 0; border-radius:8px;';
                div.innerHTML = `
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-file-alt"></i>
                        <span style="font-size:0.9rem;">
                            ${file.name} <small>(${(file.size/1024/1024).toFixed(2)} MB)</small>
                        </span>
                    </div>
                    <button type="button" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:1.2rem;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                div.querySelector('button').addEventListener('click', () => removeFile(index));
                fileList.appendChild(div);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderFileList();
        }

        // === SUBMIT PAKAI FormData (100% AMAN CSRF + FILE) ===
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validasi deskripsi minimal 50 karakter
            const desc = document.getElementById('innovation_description').value.trim();
            if (desc.length < 50) {
                alert('Deskripsi inovasi minimal 50 karakter!');
                return;
            }

            if (selectedFiles.length === 0) {
                alert('Harap upload minimal 1 dokumen pendukung!');
                return;
            }

            // Buat FormData dari form asli (termasuk CSRF token)
            const formData = new FormData(form);

            // Hapus file lama dari input (kalau ada), lalu tambah yang baru
            formData.delete('documents[]');
            selectedFiles.forEach(file => {
                formData.append('documents[]', file);
            });

            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

            fetch('{{ url("/register") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (response.ok || response.redirected) {
                    // Sukses → reload halaman biar muncul session('success')
                    window.location.reload();
                } else {
                    return response.text().then(html => {
                        document.open();
                        document.write(html);
                        document.close();
                    });
                }
            })
            .catch(() => {
                alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Pendaftaran';
            });
        });

        // Mobile menu & scroll animation tetap jalan
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('navMenu').classList.toggle('show');
        });

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        });
        document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));

        // Auto hide alert
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-error)').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
            });
        }, 10000);
    });
    </script>
</body>
</html>
