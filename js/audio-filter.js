let mediaRecorder;
let audioChunks = [];
let isRecording = false;

const recordBtn = document.getElementById('recordBtn');
const recordIndicator = document.getElementById('recordIndicator');
const audioInput = document.getElementById('audio');
const judulInput = document.getElementById('judul');
const previewContainer = document.getElementById('previewContainer');
const audioPreview = document.getElementById('audioPreview');
const audioModal = document.getElementById('audioModal');
const modalAudio = document.getElementById('modalAudio');

if (recordBtn) {
    recordBtn.addEventListener('click', async () => {
        if (!isRecording) {
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
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const fileName = "Rekaman_Langsung_" + new Date().getTime() + ".webm";
                    const audioFile = new File([audioBlob], fileName, {
                        type: 'audio/webm',
                        lastModified: new Date().getTime()
                    });

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(audioFile);
                    audioInput.files = dataTransfer.files;

                    const audioUrl = URL.createObjectURL(audioBlob);
                    audioPreview.src = audioUrl;
                    previewContainer.classList.remove('hidden');

                    if (judulInput.value.trim() === "") {
                        judulInput.value = "Rekaman Suara Saya";
                    }

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

if (audioInput) {
    audioInput.addEventListener('change', () => {
        if (audioInput.files.length > 0) {
            previewContainer.classList.add('hidden');
            audioPreview.src = "";

            if (judulInput.value === "Rekaman Suara Saya" || judulInput.value.trim() === "") {
                let namaFileAsli = audioInput.files[0].name;
                // Buang ekstensi (.mp3, dll) dari nama file untuk dijadikan judul
                judulInput.value = namaFileAsli.substring(0, namaFileAsli.lastIndexOf('.')) || namaFileAsli;
            }
        }
    });
}

window.openAudioModal = function (element) {
    const title = element.getAttribute('data-title');
    const original = element.getAttribute('data-original');
    const filter = element.getAttribute('data-filter');
    const date = element.getAttribute('data-date');
    const fileUrl = element.getAttribute('data-file');
    const type = element.getAttribute('data-type');

    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalOriginal').innerText = '📁 Asli: ' + original;
    document.getElementById('modalFilter').innerText = filter;
    document.getElementById('modalDate').innerText = date;

    const modalAudio = document.getElementById('modalAudio');
    const modalVideo = document.getElementById('modalVideo');
    const modalIcon = document.getElementById('modalIcon');

    if (type === 'video') {
        modalAudio.classList.add('hidden');
        modalVideo.classList.remove('hidden');
        modalIcon.innerText = '🎞️';
        modalVideo.src = fileUrl;
        modalVideo.load();
    } else {
        modalVideo.classList.add('hidden');
        modalAudio.classList.remove('hidden');
        modalIcon.innerText = '🎵';
        modalAudio.src = fileUrl;
        modalAudio.load();
    }

    audioModal.classList.remove('hidden');
};

window.closeAudioModal = function () {
    audioModal.classList.add('hidden');

    const modalAudio = document.getElementById('modalAudio');
    const modalVideo = document.getElementById('modalVideo');

    if (modalAudio) {
        modalAudio.pause();
        modalAudio.currentTime = 0;
    }
    if (modalVideo) {
        modalVideo.pause();
        modalVideo.currentTime = 0;
    }
};

if (audioModal) {
    audioModal.addEventListener('click', function (e) {
        if (e.target === this) {
            closeAudioModal();
        }
    });
}

let isLive = false;
let currentFilter = 'none';

let audioContext;
let liveStream;
let mediaStreamSource;
let currentNodes = [];

const startBtn = document.getElementById('startLiveBtn');
const stopBtn = document.getElementById('stopLiveBtn');
const micIcon = document.getElementById('micIcon');
const filterBtns = document.querySelectorAll('.filter-btn');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => {
            b.classList.remove('bg-indigo-600', 'hover:bg-indigo-700', 'text-white', 'font-bold', 'shadow-md');
            b.classList.add('bg-gray-100', 'hover:bg-gray-200', 'text-gray-700', 'font-medium');
        });

        btn.classList.remove('bg-gray-100', 'hover:bg-gray-200', 'text-gray-700', 'font-medium');
        btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700', 'text-white', 'font-bold', 'shadow-md');

        currentFilter = btn.getAttribute('data-filter');
        applyFilter();
    });
});

function makeDistortionCurve(amount) {
    const k = typeof amount === 'number' ? amount : 50;
    const n_samples = 44100;
    const curve = new Float32Array(n_samples);
    const deg = Math.PI / 180;
    for (let i = 0; i < n_samples; ++i) {
        const x = i * 2 / n_samples - 1;
        curve[i] = (3 + k) * x * 20 * deg / (Math.PI + k * Math.abs(x));
    }
    return curve;
}

function makeBitCrusherCurve(bits) {
    const n_samples = 44100;
    const curve = new Float32Array(n_samples);
    const steps = Math.pow(2, bits);
    for (let i = 0; i < n_samples; ++i) {
        const x = i * 2 / n_samples - 1;
        curve[i] = Math.round(x * steps) / steps;
    }
    return curve;
}

function applyFilter() {
    if (!audioContext || !mediaStreamSource) return;

    mediaStreamSource.disconnect();
    currentNodes.forEach(node => {
        try { node.disconnect(); } catch (e) { }
    });
    currentNodes = [];

    const addNode = (node) => {
        currentNodes.push(node);
        return node;
    };

    const masterDelay = addNode(audioContext.createDelay(0.1));
    masterDelay.delayTime.value = 0.1;

    masterDelay.connect(audioContext.destination);

    const outputTarget = masterDelay;

    if (currentFilter === 'none') {
        mediaStreamSource.connect(outputTarget);
    }
    else if (currentFilter === 'radio') {
        const highpass = addNode(audioContext.createBiquadFilter());
        highpass.type = 'highpass';
        highpass.frequency.value = 400;

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 3000;

        const distortion = addNode(audioContext.createWaveShaper());
        distortion.curve = makeDistortionCurve(10);

        mediaStreamSource.connect(highpass);
        highpass.connect(lowpass);
        lowpass.connect(distortion);
        distortion.connect(outputTarget);
    }
    else if (currentFilter === 'monster') {
        const osc = addNode(audioContext.createOscillator());
        osc.type = 'sine';
        osc.frequency.value = 40;

        const gainNode = addNode(audioContext.createGain());
        osc.connect(gainNode.gain);
        osc.start();

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 800;

        mediaStreamSource.connect(gainNode);
        gainNode.connect(lowpass);
        lowpass.connect(outputTarget);
    }
    else if (currentFilter === 'chipmunk') {
        const highpass = addNode(audioContext.createBiquadFilter());
        highpass.type = 'highpass';
        highpass.frequency.value = 600;

        const peak = addNode(audioContext.createBiquadFilter());
        peak.type = 'peaking';
        peak.frequency.value = 3000;
        peak.Q.value = 1.5;
        peak.gain.value = 15;

        mediaStreamSource.connect(highpass);
        highpass.connect(peak);
        peak.connect(outputTarget);
    }
    else if (currentFilter === 'robot') {
        const delay = addNode(audioContext.createDelay());
        delay.delayTime.value = 0.03;

        const osc = addNode(audioContext.createOscillator());
        osc.type = 'sawtooth';
        osc.frequency.value = 50;

        const gainNode = addNode(audioContext.createGain());
        osc.connect(gainNode.gain);
        osc.start();

        mediaStreamSource.connect(delay);
        delay.connect(gainNode);

        mediaStreamSource.connect(outputTarget);
        gainNode.connect(outputTarget);
    }
    else if (currentFilter === 'vader') {
        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 500;

        const bass = addNode(audioContext.createBiquadFilter());
        bass.type = 'lowshelf';
        bass.frequency.value = 200;
        bass.gain.value = 15;

        const distortion = addNode(audioContext.createWaveShaper());
        distortion.curve = makeDistortionCurve(10);

        mediaStreamSource.connect(lowpass);
        lowpass.connect(bass);
        bass.connect(distortion);
        distortion.connect(outputTarget);
    }
    else if (currentFilter === 'alien') {
        const osc = addNode(audioContext.createOscillator());
        osc.type = 'sine';
        osc.frequency.value = 400;

        const gainNode = addNode(audioContext.createGain());
        osc.connect(gainNode.gain);
        osc.start();

        const delay = addNode(audioContext.createDelay());
        delay.delayTime.value = 0.05;

        mediaStreamSource.connect(gainNode);
        gainNode.connect(delay);
        delay.connect(outputTarget);
        mediaStreamSource.connect(outputTarget);
    }
    else if (currentFilter === 'ghost') {
        const delay = addNode(audioContext.createDelay());
        delay.delayTime.value = 0.4;

        const feedback = addNode(audioContext.createGain());
        feedback.gain.value = 0.6;

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 1000;

        mediaStreamSource.connect(delay);
        delay.connect(feedback);
        feedback.connect(lowpass);
        lowpass.connect(delay);
        delay.connect(outputTarget);
        mediaStreamSource.connect(outputTarget);
    }
    else if (currentFilter === 'telephone') {
        const highpass = addNode(audioContext.createBiquadFilter());
        highpass.type = 'highpass';
        highpass.frequency.value = 500;

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 2000;

        mediaStreamSource.connect(highpass);
        highpass.connect(lowpass);
        lowpass.connect(outputTarget);
    }
    else if (currentFilter === 'megaphone') {
        const highpass = addNode(audioContext.createBiquadFilter());
        highpass.type = 'highpass';
        highpass.frequency.value = 800;

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 3000;

        const distortion = addNode(audioContext.createWaveShaper());
        distortion.curve = makeDistortionCurve(50);

        mediaStreamSource.connect(highpass);
        highpass.connect(lowpass);
        lowpass.connect(distortion);
        distortion.connect(outputTarget);
    }
    else if (currentFilter === 'cave') {
        const delay = addNode(audioContext.createDelay(2.0));
        delay.delayTime.value = 0.8;

        const feedback = addNode(audioContext.createGain());
        feedback.gain.value = 0.8;

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 1500;

        mediaStreamSource.connect(delay);
        delay.connect(lowpass);
        lowpass.connect(feedback);
        feedback.connect(delay);
        delay.connect(outputTarget);
        mediaStreamSource.connect(outputTarget);
    }
    else if (currentFilter === 'hall') {
        const delay = addNode(audioContext.createDelay());
        delay.delayTime.value = 0.15;

        const feedback = addNode(audioContext.createGain());
        feedback.gain.value = 0.5;

        mediaStreamSource.connect(delay);
        delay.connect(feedback);
        feedback.connect(delay);
        delay.connect(outputTarget);
        mediaStreamSource.connect(outputTarget);
    }
    else if (currentFilter === 'underwater') {
        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 300;

        mediaStreamSource.connect(lowpass);
        lowpass.connect(outputTarget);
    }
    else if (currentFilter === 'muffled') {
        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 700;

        mediaStreamSource.connect(lowpass);
        lowpass.connect(outputTarget);
    }
    else if (currentFilter === '8bit') {
        const bitcrusher = addNode(audioContext.createWaveShaper());
        bitcrusher.curve = makeBitCrusherCurve(4);

        const lowpass = addNode(audioContext.createBiquadFilter());
        lowpass.type = 'lowpass';
        lowpass.frequency.value = 3000;

        mediaStreamSource.connect(bitcrusher);
        bitcrusher.connect(lowpass);
        lowpass.connect(outputTarget);
    }
}

startBtn.addEventListener('click', async () => {
    try {
        liveStream = await navigator.mediaDevices.getUserMedia({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: false
            }
        });

        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        mediaStreamSource = audioContext.createMediaStreamSource(liveStream);

        isLive = true;
        applyFilter();

        startBtn.classList.add('hidden');
        stopBtn.classList.remove('hidden');
        micIcon.classList.add('mic-active', 'bg-rose-100', 'border-rose-300');
        micIcon.classList.remove('bg-gray-100', 'border-gray-200');

    } catch (err) {
        alert("Gagal mengakses mikrofon. Pastikan Anda mengizinkan akses mikrofon.");
        console.error(err);
    }
});

stopBtn.addEventListener('click', () => {
    isLive = false;

    if (liveStream) {
        liveStream.getTracks().forEach(track => track.stop());
    }

    if (audioContext) {
        audioContext.close();
        audioContext = null;
    }

    startBtn.classList.remove('hidden');
    stopBtn.classList.add('hidden');
    micIcon.classList.remove('mic-active', 'bg-rose-100', 'border-rose-300');
    micIcon.classList.add('bg-gray-100', 'border-gray-200');
});