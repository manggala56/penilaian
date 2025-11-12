<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lomba Inovasi Kabupaten Nganjuk 2024</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Menambahkan Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('css/style.css')}}">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-lightbulb"></i>
                <span>Lomba Inovasi</span>
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
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

    <!-- Hero Section -->
    <section class="hero">
        <!-- Elemen konten diberi kelas animasi -->
        <div class="container hero-content">
            <h1 class="reveal-on-scroll">Lomba Inovasi Kabupaten Nganjuk 2024</h1>
            <p class="reveal-on-scroll">Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing</p>
            <a href="#registration" class="btn btn-primary reveal-on-scroll">Daftar Sekarang</a>
        </div>
    </section>

    <!-- Features Section (Peserta) -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2 class="reveal-on-scroll">Peserta Lomba</h2>
                <p class="reveal-on-scroll">Kami membuka kesempatan bagi semua kelompok masyarakat untuk berpartisipasi</p>
            </div>
            <!-- Setiap kartu diberi kelas animasi -->
            <div class="features-grid">
                <div class="feature-card reveal-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3>Perangkat Daerah</h3>
                    <p>Semua instansi pemerintah daerah di Kabupaten Nganjuk</p>
                </div>
                <div class="feature-card reveal-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Masyarakat</h3>
                    <p>Warga masyarakat Kabupaten Nganjuk dari berbagai latar belakang</p>
                </div>
                <div class="feature-card reveal-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Pelajar</h3>
                    <p>Siswa dan mahasiswa di Kabupaten Nganjuk</p>
                </div>
            </div>

            <div class="poster-highlight reveal-on-scroll" style="margin-top: 3rem;">
                <h3>TOTAL HADIAH 90 JUTA!</h3>
            </div>

        </div>
    </section>

    <!-- Theme Section (Posisi Baru) -->
    <section class="theme-section">
        <div class="container">
            <h2 class="reveal-on-scroll">TEMA LOMBA</h2>
            <p class="reveal-on-scroll">Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing</p>
        </div>
    </section>

    <!-- Registration Section -->
    <section class="registration" id="registration">
        <div class="container">
            <div class="section-title">
                <h2 class="reveal-on-scroll">Formulir Pendaftaran</h2>
                <p class="reveal-on-scroll">Isi formulir berikut untuk mendaftar lomba</p>
            </div>

            <!-- Notifikasi -->
            @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Terdapat kesalahan dalam pengisian form. Silakan periksa kembali.
            </div>
            @endif

            <div class="form-container reveal-on-scroll">
                <form id="registrationForm" action="{{ route('participant.register') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Nama Lengkap *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Masukkan nama lengkap Anda">
                        @error('name') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Masukkan email Anda">
                        @error('email') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Nomor Telepon *</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="Masukkan nomor telepon aktif">
                        @error('phone') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="institution"><i class="fas fa-building"></i> Institusi</label>
                        <input type="text" id="institution" name="institution" value="{{ old('institution') }}" placeholder="Nama instansi/sekolah/universitas">
                        @error('institution') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="category"><i class="fas fa-list"></i> Pilih Kategori Lomba *</label>
                        <select id="category" name="category" required>
                            <option value="">Pilih kategori lomba</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="innovation_title"><i class="fas fa-lightbulb"></i> Judul Inovasi *</label>
                        <input type="text" id="innovation_title" name="innovation_title" value="{{ old('innovation_title') }}" required placeholder="Masukkan judul inovasi Anda">
                        @error('innovation_title') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="innovation_description"><i class="fas fa-file-alt"></i> Deskripsi Inovasi *</label>
                        <textarea id="innovation_description" name="innovation_description" rows="4" required placeholder="Jelaskan inovasi Anda secara singkat">{{ old('innovation_description') }}</textarea>
                        @error('innovation_description') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="documents"><i class="fas fa-upload"></i> Upload File Pendukung *</label>
                        <div class="file-upload" id="fileUploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik atau seret file ke sini</p>
                            <p class="file-info">Format: PDF, DOC, DOCX, JPG, PNG, ZIP (Maks: 10MB per file)</p>
                            <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.png,.zip" style="display: none;">
                        </div>
                        <div id="fileList"></div>
                        @error('documents') <span class="error-text">{{ $message }}</span> @enderror
                        @error('documents.*') <span class="error-text">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Kirim Pendaftaran
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <!-- Setiap bagian footer diberi kelas animasi -->
                <div class="footer-section reveal-on-scroll">
                    <h3><i class="fas fa-trophy "></i> Lomba Inovasi</h3>
                    <p>Platform terpercaya untuk mendorong inovasi dan kreativitas masyarakat Kabupaten Nganjuk.</p>
                    <div class="poster-badge" style="margin-top: 1rem;">
                        <i class="fas fa-calendar"></i> 1-31 Oktober 2024
                    </div>
                </div>
                <div class="footer-section reveal-on-scroll">
                    <h3><i class="fas fa-map-marker-alt"></i> Lokasi Pendaftaran</h3>
                    <p>di Bidang Litbang Bappeda</p>
                    <p>Kab. Nganjuk (pada jam kerja)</p>
                </div>
                <div class="footer-section reveal-on-scroll">
                    <h3><i class="fas fa-link"></i> Link Terkait</h3>
                    <a href="#"><i class="fas fa-home"></i> Beranda</a>
                    <a href="#features"><i class="fas fa-info-circle"></i> Tentang</a>
                    <a href="#registration"><i class="fas fa-edit"></i> Pendaftaran</a>
                    <a href="#contact"><i class="fas fa-phone"></i> Kontak</a>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2024 Lomba Inovasi Kabupaten Nganjuk. Hak Cipta Dilindungi.</p>
                <p style="margin-top: 0.5rem;">INFORMASI LENGKAP: <a href="https://jendelalitbang.nganjukkab.go.id/litbang/berita">https://jendelalitbang.nganjukkab.go.id/litbang/berita</a></p>
                <p style="margin-top: 0.5rem;">CONTACT PERSON: YULI - WA: 081335109003</p>
            </div>
        </div>
    </footer>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <button class="close-btn" id="closeModal">&times;</button>
            <h2><i class="fas fa-check-circle"></i> Pendaftaran Berhasil!</h2>
            <!-- Teks di modal ini diperbarui sesuai permintaan Anda -->
            <p>Terima kasih telah mendaftar! Silakan periksa email Anda untuk bukti pendaftaran. Kami akan menghubungi Anda untuk informasi lebih lanjut.</p>
            <button class="btn" id="okBtn">OK</button>
        </div>
    </div>

    <div id="notificationToast"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile menu toggle
            document.getElementById('mobileMenuBtn').addEventListener('click', function() {
                const navMenu = document.getElementById('navMenu');
                navMenu.classList.toggle('show');
            });

            // File upload handling
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('documents');
            const fileList = document.getElementById('fileList');
            const originalFileUploadHTML = fileUploadArea.innerHTML;

            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });

            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                updateFileList();
            });

            fileInput.addEventListener('change', updateFileList);

            function updateFileList() {
                fileList.innerHTML = '';
                if (fileInput.files.length > 0) {
                    fileUploadArea.innerHTML = `
                        <i class="fas fa-file-upload"></i>
                        <p>${fileInput.files.length} file dipilih</p>
                    `;

                    Array.from(fileInput.files).forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        fileItem.innerHTML = `
                            <span>${file.name}</span>
                            <span>(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                            <button type="button" onclick="removeFile(${index})"><i class="fas fa-times"></i></button>
                        `;
                        fileList.appendChild(fileItem);
                    });
                } else {
                    fileUploadArea.innerHTML = originalFileUploadHTML;
                }
            }

            window.removeFile = function(index) {
                const dt = new DataTransfer();
                const files = Array.from(fileInput.files);

                files.splice(index, 1);

                files.forEach(file => {
                    dt.items.add(file);
                });

                fileInput.files = dt.files;
                updateFileList();
            };

            // Modal variables
            const successModal = document.getElementById('successModal');
            const closeModalBtn = document.getElementById('closeModal');
            const okBtn = document.getElementById('okBtn');

            // Toast notification
            const notificationToast = document.getElementById('notificationToast');
            let toastTimer;

            function showToast(message, type = 'info') {
                if (toastTimer) {
                    clearTimeout(toastTimer);
                }

                notificationToast.textContent = message;
                notificationToast.className = '';
                notificationToast.classList.add('show');

                if (type === 'error') {
                    notificationToast.classList.add('error');
                }

                toastTimer = setTimeout(() => {
                    notificationToast.classList.remove('show');
                }, 3000);
            }

            // Form submission handling
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let valid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('error');
                    } else {
                        field.classList.remove('error');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    showToast('Harap isi semua field yang wajib diisi!', 'error');
                }
            });

            // Close modal functions
            function closeModal() {
                successModal.classList.remove('show');
            }
            closeModalBtn.addEventListener('click', closeModal);
            okBtn.addEventListener('click', closeModal);
            window.addEventListener('click', function(event) {
                if (event.target === successModal) {
                    closeModal();
                }
            });

            // Smooth scrolling for navigation links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();

                    const targetId = this.getAttribute('href');
                    if(targetId === '#') return;

                    const targetElement = document.querySelector(targetId);
                    if(targetElement) {
                        const headerOffset = document.querySelector('header').offsetHeight || 80;
                        const elementPosition = targetElement.offsetTop;
                        const offsetPosition = elementPosition - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });

                        document.getElementById('navMenu').classList.remove('show');
                    }
                });
            });

            // Scroll animation
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '0px',
                threshold: 0.1
            });

            const elementsToReveal = document.querySelectorAll('.reveal-on-scroll');
            elementsToReveal.forEach(el => {
                observer.observe(el);
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.display = 'none';
                });
            }, 5000);
        });
    </script>
</body>
</html>
