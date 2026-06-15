const { FFmpeg } = window.FFmpegWASM;
const { fetchFile, toBlobURL } = window.FFmpegUtil;

let ffmpeg = null;
let isReady = false;
let sessionHistory = [];

async function loadFFmpeg() {
    if (isReady) return true;
    try {
        document.getElementById('ffmpegStatus').classList.remove('hidden');
        ffmpeg = new FFmpeg();
        ffmpeg.on('progress', ({ progress }) => {
            const pct = Math.round(progress * 100);
            const pb = document.getElementById('progressBar');
            const pt = document.getElementById('progressText');
            if (pb && pt) {
                pb.style.width = pct + '%';
                pt.innerText = pct + '%';
            }
        });

        // Menggunakan unpkg.com karena lebih stabil untuk file WebAssembly Emscripten
        const coreURL = 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd';
        const ffmpegURL = 'https://unpkg.com/@ffmpeg/ffmpeg@0.12.10/dist/umd';
        
        await ffmpeg.load({
            coreURL: await toBlobURL(`${coreURL}/ffmpeg-core.js`, 'text/javascript'),
            wasmURL: await toBlobURL(`${coreURL}/ffmpeg-core.wasm`, 'application/wasm'),
            classWorkerURL: await toBlobURL(`${ffmpegURL}/814.ffmpeg.js`, 'text/javascript')
        });

        isReady = true;
        document.getElementById('ffmpegStatus').classList.add('hidden');
        return true;
        
    } catch (e) {
        console.error("Gagal memuat FFmpeg", e);
        document.getElementById('ffmpegStatusText').innerHTML = `<span class='text-red-600'>Gagal memuat engine. Error: ${e.message || String(e)}</span>`;
        
        // Tampilkan error tambahan ke layar jika ada stack trace
        const errDiv = document.createElement('div');
        errDiv.className = 'w-full max-w-[90rem] mx-auto mb-4 bg-red-100 text-red-700 text-xs p-3 overflow-auto';
        errDiv.innerText = e.stack || String(e);
        document.getElementById('ffmpegStatus').insertAdjacentElement('afterend', errDiv);
        
        return false;
    }
}

function getAudioFilterCommand(filterName) {
    switch (filterName) {
        case 'chipmunk': return "-af asetrate=44100*1.5,aresample=44100,atempo=1/1.5";
        case 'monster': return "-af asetrate=44100*0.7,aresample=44100,atempo=1/0.7";
        case 'vader': return "-af asetrate=44100*0.8,aresample=44100,atempo=1/0.8,flanger=delay=5:depth=2";
        case 'robot': return "-af aecho=0.8:0.88:6:0.4";
        case 'radio': return "-af highpass=f=200,lowpass=f=3000";
        case 'echo': return "-af aecho=0.8:0.9:1000:0.3";
        case 'alien': return "-af vibrato=f=10.0:d=0.8,flanger";
        case 'ghost': return "-af vibrato=f=3.0:d=0.8,aecho=0.8:0.9:1000:0.5,asetrate=44100*0.8,aresample=44100,atempo=1/0.8";
        case 'underwater': return "-af lowpass=f=300,aecho=0.8:0.9:1000:0.3";
        case 'muffled': return "-af lowpass=f=400,volume=0.8";
        case 'nightcore': return "-af asetrate=44100*1.25,aresample=44100,atempo=1.2";
        case 'slowmo': return "-af atempo=0.6";
        case 'telephone': return "-af highpass=f=400,lowpass=f=2000,volume=1.5";
        case 'megaphone': return "-af highpass=f=500,lowpass=f=3000,volume=3.0";
        case 'concert': return "-af aecho=0.8:0.88:60:0.4,aecho=0.8:0.88:100:0.3";
        case '8bit': return "-af aformat=sample_fmts=u8,aresample=8000";
        default: return "-c:a copy";
    }
}

function getVideoFilterCommands(audioFilter, videoFilter) {
    let filters = [];
    switch (videoFilter) {
        case 'grayscale': filters.push("format=gray"); break;
        case 'sepia': filters.push("colorchannelmixer=.393:.769:.189:0:.349:.686:.168:0:.272:.534:.131"); break;
        case 'invert': filters.push("negate"); break;
        case 'blur': filters.push("boxblur=5:1"); break;
        case 'vintage': filters.push("curves=vintage,noise=alls=20:allf=t+u"); break;
    }

    if (audioFilter === 'nightcore') filters.push("setpts=(1/1.5)*PTS");
    else if (audioFilter === 'slowmo') filters.push("setpts=(1/0.6)*PTS");

    if (filters.length > 0) {
        return ["-vf", filters.join(",")];
    }
    return ["-c:v", "copy"];
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressText').innerText = '0%';
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

async function processAudio(e) {
    e.preventDefault();
    
    // Lazy Load: Muat FFmpeg hanya jika pengguna mengklik Submit
    const loaded = await loadFFmpeg();
    if (!loaded) return;

    const fileInput = document.getElementById('audio');
    if (!fileInput.files.length) return alert("Pilih file audio!");
    
    const file = fileInput.files[0];
    const filter = document.getElementById('filter').value;
    let title = document.getElementById('judul').value.trim();
    if (!title) title = 'Audio Tanpa Judul';

    showLoading();

    try {
        const inputName = 'input' + getExtension(file.name);
        const outputName = 'output.mp3';
        
        await ffmpeg.writeFile(inputName, await fetchFile(file));

        let cmdArgs = ['-i', inputName];
        
        const filterStr = getAudioFilterCommand(filter);
        if (filterStr !== "-c:a copy") {
            // pisahkan spasi (misal "-af" dan "asetrate=...")
            const parts = filterStr.split(" ");
            cmdArgs.push(parts[0], parts[1]);
        } else {
            cmdArgs.push('-c:a', 'copy');
        }
        cmdArgs.push(outputName);

        await ffmpeg.exec(cmdArgs);

        const data = await ffmpeg.readFile(outputName);
        const blob = new Blob([data.buffer], { type: 'audio/mpeg' });
        const url = URL.createObjectURL(blob);

        handleSuccess(title, filter, 'audio', file.name, url);

    } catch (err) {
        console.error(err);
        alert("Gagal memproses audio.");
    }

    hideLoading();
}

async function processVideo(e) {
    e.preventDefault();
    
    // Lazy Load: Muat FFmpeg hanya jika pengguna mengklik Submit
    const loaded = await loadFFmpeg();
    if (!loaded) return;

    const fileInput = document.getElementById('video');
    if (!fileInput.files.length) return alert("Pilih file video!");
    
    const file = fileInput.files[0];
    const audioFilter = document.getElementById('filter_suara_video').value;
    const videoFilter = document.getElementById('filter_video').value;
    let title = document.getElementById('judul_video').value.trim();
    if (!title) title = 'Video Tanpa Judul';

    showLoading();

    try {
        const inputName = 'input' + getExtension(file.name);
        const outputName = 'output.mp4';
        
        await ffmpeg.writeFile(inputName, await fetchFile(file));

        let cmdArgs = ['-i', inputName];
        
        // Tambah command Video
        const vCmds = getVideoFilterCommands(audioFilter, videoFilter);
        cmdArgs.push(...vCmds);

        // Tambah command Audio
        const aCmdStr = getAudioFilterCommand(audioFilter);
        if (aCmdStr !== "-c:a copy") {
            const parts = aCmdStr.split(" ");
            cmdArgs.push(parts[0], parts[1]);
        } else {
            cmdArgs.push('-c:a', 'copy');
        }

        cmdArgs.push(outputName);

        await ffmpeg.exec(cmdArgs);

        const data = await ffmpeg.readFile(outputName);
        const blob = new Blob([data.buffer], { type: 'video/mp4' });
        const url = URL.createObjectURL(blob);

        const filterName = `Video: ${videoFilter} | Audio: ${audioFilter}`;
        handleSuccess(title, filterName, 'video', file.name, url);

    } catch (err) {
        console.error(err);
        alert("Gagal memproses video. " + err.message);
    }

    hideLoading();
}

function getExtension(filename) {
    const p = filename.lastIndexOf('.');
    return p !== -1 ? filename.substring(p) : '';
}

function handleSuccess(title, filter, type, originalName, url) {
    const dateStr = new Date().toLocaleString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'});
    
    const historyItem = {
        title, filter, type, originalName, url, dateStr
    };
    
    sessionHistory.unshift(historyItem);
    renderHistory();
    renderOutput(historyItem);
}

function renderOutput(item) {
    document.getElementById('waitingState').classList.add('hidden');
    document.getElementById('resultState').classList.remove('hidden');

    document.getElementById('resultAvatar').src = `https://api.dicebear.com/9.x/bottts/svg?seed=${encodeURIComponent(item.title)}`;
    document.getElementById('resultMessage').innerHTML = `File <strong>${item.title}</strong> berhasil diproses! 🎉`;

    const aPlayer = document.getElementById('resultAudio');
    const vPlayer = document.getElementById('resultVideo');
    const btn = document.getElementById('btnDownload');

    if (item.type === 'video') {
        aPlayer.classList.add('hidden');
        aPlayer.pause();
        vPlayer.classList.remove('hidden');
        vPlayer.src = item.url;
        btn.download = item.title + '.mp4';
    } else {
        vPlayer.classList.add('hidden');
        vPlayer.pause();
        aPlayer.classList.remove('hidden');
        aPlayer.src = item.url;
        btn.download = item.title + '.mp3';
    }
    btn.href = item.url;
}

function renderHistory() {
    const list = document.getElementById('historyListContainer');
    const empty = document.getElementById('emptyHistory');
    const count = document.getElementById('historyCount');

    if (sessionHistory.length === 0) {
        list.classList.add('hidden');
        empty.classList.remove('hidden');
        count.classList.add('hidden');
        return;
    }

    empty.classList.add('hidden');
    list.classList.remove('hidden');
    count.classList.remove('hidden');
    count.innerText = sessionHistory.length + ' Total';

    list.innerHTML = '';

    sessionHistory.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = "border border-gray-200 rounded-lg p-4 bg-gray-50 hover:bg-indigo-50 transition border-l-4 border-l-indigo-400 cursor-pointer shadow-sm hover:shadow";
        div.onclick = () => openAudioModal(item);

        div.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <div class="truncate pr-2">
                    <div class="font-bold text-gray-800 truncate">${item.title}</div>
                    <div class="text-[10px] text-gray-500 truncate mt-1">${item.originalName}</div>
                </div>
                <span class="shrink-0 bg-indigo-100 text-indigo-800 text-[10px] font-bold px-2 py-1 rounded shadow-sm uppercase">
                    ${item.filter}
                </span>
            </div>
            <div class="text-xs text-gray-400 mb-4 font-medium">${item.dateStr}</div>
            <div class="flex gap-2" onclick="event.stopPropagation();">
                <a href="${item.url}" download="${item.title}.${item.type === 'video' ? 'mp4' : 'mp3'}" class="flex-1 text-center text-indigo-600 bg-indigo-100 hover:bg-indigo-200 font-semibold rounded text-xs px-2 py-2 transition">Unduh</a>
            </div>
        `;
        list.appendChild(div);
    });
}

function openAudioModal(item) {
    document.getElementById('modalTitle').innerText = item.title;
    document.getElementById('modalOriginal').innerText = '📁 Asli: ' + item.originalName;
    document.getElementById('modalFilter').innerText = item.filter;
    document.getElementById('modalDate').innerText = item.dateStr;

    const mAudio = document.getElementById('modalAudio');
    const mVideo = document.getElementById('modalVideo');
    const mIcon = document.getElementById('modalIcon');

    if (item.type === 'video') {
        mAudio.classList.add('hidden');
        mVideo.classList.remove('hidden');
        mIcon.innerText = '🎞️';
        mVideo.src = item.url;
    } else {
        mVideo.classList.add('hidden');
        mAudio.classList.remove('hidden');
        mIcon.innerText = '🎵';
        mAudio.src = item.url;
    }

    document.getElementById('audioModal').classList.remove('hidden');
}

window.closeAudioModal = function () {
    document.getElementById('audioModal').classList.add('hidden');
    const a = document.getElementById('modalAudio');
    const v = document.getElementById('modalVideo');
    if (a) { a.pause(); a.currentTime = 0; }
    if (v) { v.pause(); v.currentTime = 0; }
};

// ==========================================
// FITUR REKAMAN SUARA (MIC)
// ==========================================
let mediaRecorder;
let audioChunks = [];
let isRecording = false;

const recordBtn = document.getElementById('recordBtn');
const recordIndicator = document.getElementById('recordIndicator');
const audioInput = document.getElementById('audio');
const judulInput = document.getElementById('judul');
const previewContainer = document.getElementById('previewContainer');
const audioPreview = document.getElementById('audioPreview');

if (recordBtn) {
    recordBtn.addEventListener('click', async () => {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const fileName = "Rekaman_Langsung_" + new Date().getTime() + ".webm";
                    const audioFile = new File([audioBlob], fileName, {
                        type: 'audio/webm',
                        lastModified: new Date().getTime()
                    });

                    // Gunakan DataTransfer untuk memasukkan file ke input type="file"
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(audioFile);
                    audioInput.files = dataTransfer.files;

                    // Buat pratinjau
                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPreview.src = audioUrl;
                    previewContainer.classList.remove('hidden');

                    // Isi judul otomatis jika kosong
                    if (judulInput.value.trim() === "") {
                        judulInput.value = "Rekaman Suara Saya";
                    }

                    // Kembalikan tombol ke mode awal
                    recordBtn.innerHTML = "🔄 Rekam Ulang";
                    recordBtn.classList.replace('bg-red-600', 'bg-red-100');
                    recordBtn.classList.replace('text-white', 'text-red-600');
                    recordIndicator.classList.add('hidden');
                };

                mediaRecorder.start();
                isRecording = true;

                // Ubah tampilan tombol saat merekam
                recordBtn.innerHTML = "⏹️ Hentikan Rekaman";
                recordBtn.classList.replace('bg-red-100', 'bg-red-600');
                recordBtn.classList.replace('text-red-600', 'text-white');
                recordIndicator.classList.remove('hidden');

                // Sembunyikan pratinjau sebelumnya jika ada
                previewContainer.classList.add('hidden');
                audioPreview.src = "";

            } catch (err) {
                alert("Akses mikrofon ditolak atau perangkat tidak ditemukan. Cek pengaturan browser Anda.");
                console.error("Mic error:", err);
            }
        } else {
            mediaRecorder.stop();
            isRecording = false;
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
    });
}

// Reset judul otomatis jika file manual dipilih
if (audioInput) {
    audioInput.addEventListener('change', () => {
        if (audioInput.files.length > 0) {
            previewContainer.classList.add('hidden');
            audioPreview.src = "";
            if (judulInput.value === "Rekaman Suara Saya" || judulInput.value.trim() === "") {
                let namaFileAsli = audioInput.files[0].name;
                judulInput.value = namaFileAsli.substring(0, namaFileAsli.lastIndexOf('.')) || namaFileAsli;
            }
        }
    });
}
