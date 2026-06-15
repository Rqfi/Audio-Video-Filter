# Audio & Video Filter System 🎬🎧

Proyek ini adalah aplikasi berbasis web yang memungkinkan pengguna untuk memanipulasi dan menerapkan berbagai efek filter pada file Audio dan Video. Proyek ini dibangun dengan dukungan sistem pemrosesan media tingkat lanjut, menawarkan dua pendekatan arsitektur yang berbeda: **Native Server (PHP + FFmpeg)** dan **Client-Side (FFmpeg.wasm)**.

---

## ⚙️ Cara Kerja (Arsitektur)

Proyek ini dipisah menjadi dua lingkungan arsitektur utama agar dapat disesuaikan dengan kebutuhan:

### 1. Mode Native Server (Versi Utama / Root Folder)
Pada mode ini, komputasi kelas berat untuk merender video atau mengonversi audio ditugaskan secara penuh kepada **Server / Komputer**.
*   **Cara Kerja:** Pengguna mengunggah file melalui halaman web HTML/Tailwind. Backend `index.php` menerima file sementara, kemudian memanggil perintah eksternal (`exec()`) ke program `ffmpeg.exe` yang tertanam pada sistem operasi server. Hasilnya kemudian disimpan di folder `uploads/` dan disajikan kembali ke pengguna via session.
*   **Keunggulan:** Kecepatan rendering video sangat maksimal karena memakan kekuatan penuh CPU Server.
*   **Catatan:** Fitur efek mikrofon secara langsung (*Voice Changer* / *Live Camera*) tetap diproses murni di dalam browser (Javascript Web Audio API) demi menjamin *zero-latency*.

### 2. Mode Client-Side (Folder `client-version/`)
Pada mode ini, aplikasi berjalan 100% secara statis tanpa melibatkan bahasa backend seperti PHP.
*   **Cara Kerja:** Alih-alih mengirim file ke server, file pengguna diproses *langsung di dalam memori browser HP/Laptop mereka sendiri* menggunakan teknologi **FFmpeg.wasm** (WebAssembly).
*   **Keunggulan:** Sangat aman (privasi terjamin karena tidak ada file yang diunggah ke internet), dapat dihosting gratis di layanan statis seperti **GitHub Pages** atau **Vercel**, dan server tidak akan pernah macet/kehabisan ruang disk meskipun dipakai ribuan orang bersamaan.

---

## 📋 Syarat Penggunaan

Pastikan sistem memenuhi persyaratan berikut:

**Untuk menjalankan Mode Native Server (PHP):**
1. **Web Server Lokal:** Aplikasi seperti Laragon, XAMPP, atau MAMP yang memiliki modul Apache/Nginx.
2. **PHP:** Versi 7.4 ke atas. Pastikan fungsi `exec()` **tidak dimatikan** (*disabled*) di dalam file `php.ini`.
3. **FFmpeg Binary:** **Wajib** mengunduh dan menginstal program FFmpeg di sistem operasi.
    *   *Windows:* Download FFmpeg, ekstrak, dan catat letak file `.exe`-nya. Secara bawaan, kode proyek ini mencari FFmpeg di direktori `C:\ffmpeg\bin\ffmpeg.exe`. Jika Anda meletakkannya di tempat lain, Anda harus mengubah variabel path tersebut di `index.php`.
    *   Pastikan folder `uploads/` memiliki izin Tulis (*Write Permission / chmod 777*).

**Untuk menjalankan Mode Client-Side (WASM):**
1. **Web Browser Modern:** Chrome, Firefox, Safari, atau Edge versi terbaru yang mendukung WebAssembly.
2. **Koneksi Internet:** Wajib saat membuka halaman pertama kali, karena browser perlu mengunduh `ffmpeg-core.js` dan `.wasm` (sekitar 25-30MB) melalui jaringan CDN.
3. Node.js (Opsional): Hanya diperlukan jika Anda berencana menginstal *Vercel CLI* untuk keperluan *deployment* otomatis ke *cloud*.

---

## 🎨 Fitur Utama
*   **Pemrosesan Audio Murni:** Terdapat 15+ filter audio (*Chipmunk, Vader, Monster, 8-Bit, Nightcore, Slowmo, Ghost, dll*).
*   **Real-time Voice Changer:** Ubah suara Anda langsung dari rekaman Mikrofon perangkat tanpa *delay*.
*   **Pemrosesan Video:** Ubah efek visual video (Grayscale, Invert, Sepia) secara bersamaan dengan mengubah suara yang ada di dalam video tersebut.
*   **Durasi Sinkron:** Filter penentu kecepatan seperti *Nightcore* dan *Slowmo* secara otomatis menyinkronkan *frame video* agar tidak terputus dengan panjang audio yang baru.
