<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Filter Suara (FFmpeg)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Animasi berkedip untuk indikator rekaman */
        @keyframes pulse-red {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-red { animation: pulse-red 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    </style>
</head>

<body class="bg-gray-100 min-h-screen p-4 py-8 font-sans">
    <div class="max-w-[90rem] mx-auto mb-8">
        <h1 class="text-3xl font-bold text-gray-800 text-center">Audio Filter</h1>
        <p class="text-sm text-gray-500 text-center mt-2">Diproses di server menggunakan Laravel, MySQL & FFmpeg</p>
    </div>

    <div class="max-w-[90rem] mx-auto w-full grid grid-cols-1 lg:grid-cols-3 gap-6 h-auto lg:h-[75vh]">

        <!-- COLUMN 1: INPUT VARIABEL -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col h-full overflow-y-auto no-scrollbar border-t-4 border-indigo-500">
            <h2 class="text-lg font-bold text-gray-800 mb-6 border-b pb-3 flex items-center">
                <span class="bg-indigo-100 text-indigo-700 w-8 h-8 rounded-full flex items-center justify-center mr-3">1</span>
                Input Variabel
            </h2>

            <form action="{{ url('/') }}" method="POST" enctype="multipart/form-data" class="space-y-6 flex-1">
                @csrf
                <div>
                    <label for="judul" class="block text-sm font-medium text-gray-700 mb-2">Judul Audio</label>
                    <input type="text" name="judul" id="judul" placeholder="Masukkan judul audio..."
                        class="mt-1 block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <label class="block text-sm font-bold text-gray-700 mb-3">Sumber Audio (Pilih File / Rekam)</label>
                    
                    <div class="flex items-center gap-3 mb-4">
                        <button type="button" id="recordBtn" class="bg-red-100 text-red-600 px-4 py-2 rounded-md text-sm font-bold hover:bg-red-200 transition flex items-center gap-2 shadow-sm border border-red-200">
                            🎙️ Mulai Merekam
                        </button>
                        <span id="recordIndicator" class="text-xs text-red-600 font-bold hidden animate-pulse-red flex items-center gap-1">
                            <span class="w-2.5 h-2.5 bg-red-600 rounded-full inline-block"></span> Merekam...
                        </span>
                    </div>

                    <!-- AREA PRATINJAU REKAMAN -->
                    <div id="previewContainer" class="hidden mb-4 p-3 bg-indigo-50 border border-indigo-100 rounded-md shadow-inner">
                        <p class="text-xs font-bold text-indigo-800 mb-2">🎧 Pratinjau Rekaman Anda:</p>
                        <audio id="audioPreview" controls class="w-full h-8 rounded-full"></audio>
                    </div>

                    <div class="relative flex items-center justify-center w-full">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="px-2 bg-gray-50 text-xs text-gray-400">ATAU UNGGAH MANUAL</span>
                        </div>
                    </div>

                    <input type="file" name="audio" id="audio" accept=".mp3, .wav, .ogg, .m4a, .webm" required
                        class="mt-4 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-medium
                                file:bg-indigo-100 file:text-indigo-700
                                hover:file:bg-indigo-200 cursor-pointer border border-gray-300 p-1 rounded-md bg-white">
                </div>

                <div>
                    <label for="filter" class="block text-sm font-medium text-gray-700 mb-2">Pilih Efek Suara</label>
                    <select name="filter" id="filter" class="mt-1 block w-full pl-3 pr-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <option value="none">Asli (Tanpa Efek)</option>
                        <option value="chipmunk">🐿️ Suara Tupai</option>
                        <option value="monster">🧟 Suara Monster</option>
                        <option value="vader">🦹 Suara Penjahat (Vader)</option>
                        <option value="robot">🤖 Suara Robot</option>
                        <option value="alien">👽 Suara Alien</option>
                        <option value="ghost">👻 Suara Hantu / Seram</option>
                        <option value="radio">📻 Suara Radio Lama</option>
                        <option value="telephone">📞 Telepon Terkompresi</option>
                        <option value="megaphone">📣 Suara Megaphone (Toa)</option>
                        <option value="echo">⛰️ Suara Menggema di Gua</option>
                        <option value="concert">🏟️ Suara Konser / Aula Besar</option>
                        <option value="underwater">🫧 Di Bawah Air</option>
                        <option value="muffled">🚪 Suara Teredam (Di Balik Pintu)</option>
                        <option value="nightcore">⚡ Musik Nightcore</option>
                        <option value="slowmo">🐢 Slow Motion</option>
                        <option value="8bit">👾 Suara 8-Bit / Retro Game</option>
                    </select>
                </div>

                <div class="pt-4 mt-auto">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                        Submit
                    </button>
                </div>
            </form>
        </div>

        <!-- COLUMN 2: HASIL PROSES FILTER -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col h-full overflow-y-auto no-scrollbar border-t-4 border-green-500 bg-gradient-to-b from-white to-gray-50">
            <h2 class="text-lg font-bold text-gray-800 border-b pb-3 flex items-center">
                <span class="bg-green-100 text-green-700 w-8 h-8 rounded-full flex items-center justify-center mr-3">2</span>
                Hasil Proses Filter
            </h2>

            <div class="flex-1 flex flex-col items-center justify-center">
                @if ($outputMessage !== '')
                    <div class="w-full flex flex-col items-center animate-fade-in">
                        @if ($avatarUrl !== '')
                            <img src="{{ $avatarUrl }}" alt="Avatar" class="w-28 h-28 rounded-full bg-white shadow-md border-4 border-green-100 mb-5 hover:scale-105 transition-transform">
                        @endif

                        @if ($pesanDinamis !== '')
                            <div class="text-center text-gray-800 mb-5 p-4 bg-green-50 rounded-lg border border-green-200 w-full text-sm">
                                {!! $pesanDinamis !!}
                            </div>
                        @endif

                        <p class="text-center text-sm font-medium mb-5">{!! $outputMessage !!}</p>

                        @if ($outputFileUrl !== '')
                            <audio controls class="w-full mb-5 shadow-sm rounded-full">
                                <source src="{{ $outputFileUrl }}" type="audio/mpeg">
                                Browser Anda tidak mendukung elemen audio.
                            </audio>
                            <a href="{{ $outputFileUrl }}" download="{{ $outputFileJudul }}.mp3" class="w-full flex justify-center py-3 px-4 rounded-md shadow-md text-sm font-bold text-white bg-green-600 hover:bg-green-700 transition">
                                Download File Output
                            </a>
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center text-gray-400 opacity-60">
                        <span class="text-6xl mb-4">📼</span>
                        <p class="text-sm font-medium text-center">Menunggu input.<br>Hasil audio akan muncul di sini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- COLUMN 3: RIWAYAT -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col h-full overflow-y-auto no-scrollbar border-t-4 border-gray-400">
            <h2 class="text-lg font-bold text-gray-800 mb-6 border-b pb-3 flex justify-between items-center">
                <div class="flex items-center">
                    <span class="bg-gray-200 text-gray-700 w-8 h-8 rounded-full flex items-center justify-center mr-3">3</span>
                    Riwayat
                </div>
                @if ($history->isNotEmpty())
                    <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2.5 py-1 rounded-full">{{ $history->count() }} Total</span>
                @endif
            </h2>

            <div class="flex-1">
                @if ($history->isNotEmpty())

                    <div class="flex gap-2 mb-4">
                        <form method="POST" action="{{ url('/download-all') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full text-indigo-700 bg-indigo-100 hover:bg-indigo-200 font-bold rounded-md text-[11px] px-2 py-2 transition flex justify-center items-center h-full">
                                Unduh Semua
                            </button>
                        </form>
                        <form method="POST" action="{{ url('/delete-all') }}" class="flex-1" onsubmit="return confirm('Yakin ingin menghapus SEMUA riwayat dan file audio secara permanen?');">
                            @csrf
                            <button type="submit" class="w-full text-red-700 bg-red-100 hover:bg-red-200 font-bold rounded-md text-[11px] px-2 py-2 transition flex justify-center items-center h-full">
                                Hapus Semua
                            </button>
                        </form>
                    </div>

                    <div class="space-y-4">
                        @foreach ($history as $item)
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:bg-indigo-50 transition border-l-4 border-l-indigo-400 cursor-pointer shadow-sm hover:shadow"
                                data-title="{{ $item->judul }}"
                                data-original="{{ $item->original_name }}"
                                data-filter="{{ $item->filter }}"
                                data-date="{{ $item->waktu }}"
                                data-file="{{ asset($item->file) }}"
                                onclick="openAudioModal(this)">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="truncate pr-2">
                                        <div class="font-bold text-gray-800 truncate">{{ $item->judul }}</div>
                                        <div class="text-[10px] text-gray-500 truncate mt-1">{{ $item->original_name }}</div>
                                    </div>
                                    <span class="shrink-0 bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-1 rounded shadow-sm uppercase">
                                        {{ $item->filter }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 mb-4 font-medium">{{ $item->waktu }}</div>

                                <div class="flex gap-2" onclick="event.stopPropagation();">
                                    <a href="{{ asset($item->file) }}" download="{{ $item->judul }}.mp3" class="flex-1 text-center text-indigo-600 bg-indigo-100 hover:bg-indigo-200 font-semibold rounded text-xs px-2 py-2 transition">Unduh</a>
                                    <form method="POST" action="{{ url('/delete') }}" class="flex-1" onsubmit="return confirm('Hapus file output ini secara permanen dari server?');">
                                        @csrf
                                        <input type="hidden" name="delete_id" value="{{ $item->id }}">
                                        <button type="submit" class="w-full text-red-600 bg-red-100 hover:bg-red-200 font-semibold rounded text-xs px-2 py-2 transition">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 opacity-60">
                        <span class="text-5xl mb-4">📁</span>
                        <p class="text-sm font-medium">Belum ada riwayat proses audio.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- AUDIO DETAIL POPUP MODAL -->
    <div id="audioModal" class="fixed inset-0 bg-gray-900/60 hidden flex items-center justify-center z-50 transition-opacity backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full relative transform transition-all">
            <button onclick="closeAudioModal()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 bg-gray-100 hover:bg-red-50 rounded-full w-8 h-8 flex items-center justify-center transition font-bold">
                &times;
            </button>

            <div class="flex items-center gap-3 mb-4 pr-6">
                <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-500 flex items-center justify-center text-2xl shrink-0 shadow-inner">
                    🎵
                </div>
                <div class="overflow-hidden">
                    <h3 id="modalTitle" class="text-lg font-bold text-gray-800 truncate">Judul</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span id="modalFilter" class="bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-0.5 rounded shadow-sm uppercase">FILTER</span>
                        <span id="modalDate" class="text-xs text-gray-500 font-medium">Tanggal</span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-5">
                <p id="modalOriginal" class="text-[11px] text-gray-500 truncate" title="File Asli">📁 Asli: file.mp3</p>
            </div>

            <audio id="modalAudio" controls class="w-full shadow-sm rounded-full bg-gray-50">
                <source src="" type="audio/mpeg">
                Browser Anda tidak mendukung elemen audio.
            </audio>
        </div>
    </div>

    <script>
        // Variabel untuk menyimpan mesin perekam dan data audionya
        let mediaRecorder;
        let audioChunks = [];
        let isRecording = false;

        const recordBtn = document.getElementById('recordBtn');
        const recordIndicator = document.getElementById('recordIndicator');
        const audioInput = document.getElementById('audio');
        const judulInput = document.getElementById('judul');
        
        // VARIABEL UNTUK PREVIEW
        const previewContainer = document.getElementById('previewContainer');
        const audioPreview = document.getElementById('audioPreview');

        recordBtn.addEventListener('click', async () => {
            if (!isRecording) {
                // MULAI MEREKAM
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    
                    mediaRecorder = new MediaRecorder(stream);
                    audioChunks = [];

                    mediaRecorder.ondataavailable = (event) => {
                        if (event.data.size > 0) {
                            audioChunks.push(event.data);
                        }
                    };

                    mediaRecorder.onstop = () => {
                        // 1. Buat file
                        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                        const fileName = "Rekaman_Langsung_" + new Date().getTime() + ".webm";
                        const audioFile = new File([audioBlob], fileName, { 
                            type: 'audio/webm', 
                            lastModified: new Date().getTime() 
                        });

                        // 2. Masukkan ke form
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(audioFile);
                        audioInput.files = dataTransfer.files;

                        // 3. TAMPILKAN PRATINJAU (Preview)
                        const audioUrl = URL.createObjectURL(audioBlob);
                        audioPreview.src = audioUrl;
                        previewContainer.classList.remove('hidden'); // Munculkan kotak pemutar

                        // 4. Isi judul otomatis
                        if(judulInput.value.trim() === "") {
                            judulInput.value = "Rekaman Suara Saya";
                        }

                        // Reset tombol
                        recordBtn.innerHTML = "🔄 Rekam Ulang";
                        recordBtn.classList.replace('bg-red-600', 'bg-red-100');
                        recordBtn.classList.replace('text-white', 'text-red-600');
                        recordIndicator.classList.add('hidden');
                    };

                    mediaRecorder.start();
                    isRecording = true;

                    recordBtn.innerHTML = "⏹️ Hentikan Rekaman";
                    recordBtn.classList.replace('bg-red-100', 'bg-red-600');
                    recordBtn.classList.replace('text-red-600', 'text-white');
                    recordIndicator.classList.remove('hidden');
                    
                    // Sembunyikan pratinjau sebelumnya (jika ada) saat merekam ulang
                    previewContainer.classList.add('hidden');
                    audioPreview.src = "";

                } catch (err) {
                    alert("Akses mikrofon ditolak atau perangkat tidak ditemukan.");
                    console.error("Mic error:", err);
                }
            } else {
                // BERHENTI MEREKAM
                mediaRecorder.stop();
                isRecording = false;
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
        });

        // Logika Tambahan: Sembunyikan preview jika pengguna memilih file manual
        audioInput.addEventListener('change', () => {
            if (audioInput.files.length > 0) {
                previewContainer.classList.add('hidden');
                audioPreview.src = "";
                
                if(judulInput.value === "Rekaman Suara Saya" || judulInput.value.trim() === "") {
                    let namaFileAsli = audioInput.files[0].name;
                    judulInput.value = namaFileAsli.substring(0, namaFileAsli.lastIndexOf('.')) || namaFileAsli;
                }
            }
        });

        // Logika Modal Pop-up
        function openAudioModal(element) {
            const title = element.getAttribute('data-title');
            const original = element.getAttribute('data-original');
            const filter = element.getAttribute('data-filter');
            const date = element.getAttribute('data-date');
            const fileUrl = element.getAttribute('data-file');

            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalOriginal').innerText = '📁 Asli: ' + original;
            document.getElementById('modalFilter').innerText = filter;
            document.getElementById('modalDate').innerText = date;

            const audioEl = document.getElementById('modalAudio');
            audioEl.src = fileUrl;
            audioEl.load();

            document.getElementById('audioModal').classList.remove('hidden');
        }

        function closeAudioModal() {
            document.getElementById('audioModal').classList.add('hidden');
            const audioEl = document.getElementById('modalAudio');
            audioEl.pause();
            audioEl.currentTime = 0;
        }

        document.getElementById('audioModal').addEventListener('click', function(e) {
            if (e.target === this) closeAudioModal();
        });
    </script>
</body>
</html>
