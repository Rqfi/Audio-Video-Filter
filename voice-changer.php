<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Audio API Real-Time Filter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                transform: scale(1.1);
                box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(0.8);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .mic-active {
            animation: pulse-ring 2s infinite;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen p-4 py-8 font-sans flex flex-col items-center">

    <div class="max-w-3xl w-full mb-8 relative flex items-center justify-between">
        <a href="index.php" class="text-indigo-600 bg-white hover:bg-indigo-50 border border-indigo-200 font-medium rounded-md text-sm px-4 py-2 transition shadow-sm flex items-center gap-2">
            Kembali
        </a>
        <div class="text-right">
            <h1 class="text-3xl font-bold text-gray-800">Voice Changer <span class="text-rose-500">Live</span></h1>
        </div>
    </div>

    <div class="max-w-3xl w-full bg-white rounded-xl shadow-lg p-8 border-t-4 border-rose-500 relative">

        <div class="flex justify-center mb-8">
            <div id="micIcon" class="w-28 h-28 rounded-full bg-gray-100 border-4 border-gray-200 flex items-center justify-center text-5xl transition-all duration-300">
            </div>
        </div>

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-3 text-center uppercase tracking-wider">Pilih Filter</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-h-72 overflow-y-auto p-2 no-scrollbar">
                    <button class="filter-btn active bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-2 rounded-lg font-bold text-xs transition shadow-md" data-filter="none">
                        Murni (Normal)
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="chipmunk">
                        🐿️ Tupai
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="monster">
                        🧟 Monster
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="vader">
                        🦹 Penjahat (Vader)
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="robot">
                        🤖 Robot
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="alien">
                        👽 Alien
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="ghost">
                        👻 Hantu / Seram
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="radio">
                        📻 Radio Lama
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="telephone">
                        📞 Telepon
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="megaphone">
                        📣 Megaphone
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="cave">
                        ⛰️ Gua (Echo)
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="hall">
                        🏛️ Konser / Aula
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="underwater">
                        🫧 Di Bawah Air
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="muffled">
                        🚪 Teredam
                    </button>
                    <button class="filter-btn bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 py-3 px-2 rounded-lg font-medium text-xs transition" data-filter="8bit">
                        👾 8-Bit Retro
                    </button>
                </div>
            </div>

            <div class="pt-6 flex justify-center border-t border-gray-100">
                <button id="startLiveBtn" class="w-full max-w-sm bg-rose-500 hover:bg-rose-600 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-transform transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
                    <span class="text-xl">▶️</span> Mulai Stream
                </button>

                <button id="stopLiveBtn" class="hidden w-full max-w-sm bg-gray-800 hover:bg-gray-900 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-transform transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
                    <span class="text-xl">⏹️</span> Hentikan Stream
                </button>
            </div>
        </div>

    </div>
    <script src="js/audio-filter.js?v=<?php echo time(); ?>"></script>
</body>

</html>