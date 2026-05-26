<?php

namespace App\Http\Controllers;

use App\Models\AudioHistory;
use Illuminate\Http\Request;

class AudioFilterController extends Controller
{
    /**
     * Show the main application view with history and any flash results.
     */
    public function index()
    {
        $history = AudioHistory::orderBy('created_at', 'desc')->get();
        
        $outputMessage = '';
        $outputFileUrl = '';
        $pesanDinamis = '';
        $avatarUrl = '';
        $outputFileJudul = 'Audio_Filter';
        
        if (session()->has('flash_result')) {
            $flash = session('flash_result');
            $outputMessage = $flash['output_msg'] ?? '';
            if (($flash['status'] ?? '') === 'success') {
                $pesanDinamis = $flash['pesan'] ?? '';
                $outputFileUrl = $flash['file_url'] ?? '';
                $avatarUrl = $flash['avatar'] ?? '';
                $outputFileJudul = $flash['judul'] ?? 'Audio_Filter';
            }
        }
        
        return view('filter', compact(
            'history', 
            'outputMessage', 
            'outputFileUrl', 
            'pesanDinamis', 
            'avatarUrl', 
            'outputFileJudul'
        ));
    }

    /**
     * Process the uploaded audio using FFmpeg and save history to database.
     */
    public function process(Request $request)
    {
        $request->validate([
            'audio'  => 'required|file',
            'judul'  => 'nullable|string',
            'filter' => 'required|string',
        ]);

        $filter = $request->input('filter', 'none');
        $judulAudio = $request->input('judul', '');

        if (trim($judulAudio) === '') {
            $judulFinal = 'Audio Tanpa Judul';
        } else {
            $judulFinal = ucwords(trim($judulAudio));
        }

        $avatarUrl = "https://api.dicebear.com/9.x/bottts/svg?seed=" . urlencode($judulFinal);

        $file = $request->file('audio');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        // Fallback for custom micro recordings without extension
        if (empty($ext)) {
            $ext = 'webm';
        }

        $allowedExts = ['mp3', 'wav', 'ogg', 'm4a', 'webm'];

        if (in_array($ext, $allowedExts)) {
            $uniqueId = uniqid();
            
            $uploadDir = public_path('uploads/');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $inputFile = $uploadDir . $uniqueId . '_input.' . $ext;
            $outputFile = $uploadDir . $uniqueId . '_output.mp3';

            if ($file->move($uploadDir, $uniqueId . '_input.' . $ext)) {

                // Menjalankan perintah FFmpeg dari sistem
                $ffmpegCmd = "C:\\ffmpeg\\bin\\ffmpeg.exe -y -i " . escapeshellarg($inputFile);
                $audioFilter = "";

                switch ($filter) {
                    case 'chipmunk':
                        $audioFilter = "-af \"asetrate=44100*1.5,aresample=44100,atempo=1/1.5\"";
                        break;
                    case 'monster':
                        $audioFilter = "-af \"asetrate=44100*0.7,aresample=44100,atempo=1/0.7\"";
                        break;
                    case 'vader':
                        $audioFilter = "-af \"asetrate=44100*0.8,aresample=44100,atempo=1/0.8,flanger=delay=5:depth=2\"";
                        break;
                    case 'robot':
                        $audioFilter = "-af \"aecho=0.8:0.88:6:0.4\"";
                        break;
                    case 'radio':
                        $audioFilter = "-af \"highpass=f=200,lowpass=f=3000\"";
                        break;
                    case 'echo':
                        $audioFilter = "-af \"aecho=0.8:0.9:1000:0.3\"";
                        break;
                    case 'alien':
                        $audioFilter = "-af \"vibrato=f=10.0:d=0.8,flanger\"";
                        break;
                    case 'ghost':
                        $audioFilter = "-af \"vibrato=f=3.0:d=0.8,aecho=0.8:0.9:1000:0.5,asetrate=44100*0.8,aresample=44100,atempo=1/0.8\"";
                        break;
                    case 'underwater':
                        $audioFilter = "-af \"lowpass=f=300,aecho=0.8:0.9:1000:0.3\"";
                        break;
                    case 'muffled':
                        $audioFilter = "-af \"lowpass=f=400,volume=0.8\"";
                        break;
                    case 'nightcore':
                        $audioFilter = "-af \"asetrate=44100*1.25,aresample=44100,atempo=1.2\"";
                        break;
                    case 'slowmo':
                        $audioFilter = "-af \"atempo=0.6\"";
                        break;
                    case 'telephone':
                        $audioFilter = "-af \"highpass=f=400,lowpass=f=2000,volume=1.5\"";
                        break;
                    case 'megaphone':
                        $audioFilter = "-af \"highpass=f=500,lowpass=f=3000,volume=3.0\"";
                        break;
                    case 'concert':
                        $audioFilter = "-af \"aecho=0.8:0.88:60:0.4,aecho=0.8:0.88:100:0.3\"";
                        break;
                    case '8bit':
                        $audioFilter = "-af \"aformat=sample_fmts=u8,aresample=8000\"";
                        break;
                    default:
                        $audioFilter = "-c:a copy";
                        break;
                }

                $cmd = "$ffmpegCmd $audioFilter " . escapeshellarg($outputFile) . " 2>&1";
                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    return response("<h3>Command yang dijalankan:</h3> <pre>$cmd</pre><h3>Output dari FFmpeg:</h3> <pre>" . implode("\n", $output) . "</pre>", 500);
                }

                if ($returnCode === 0 && file_exists($outputFile)) {
                    @unlink($inputFile);

                    $dbFileRelativePath = 'uploads/' . $uniqueId . '_output.mp3';

                    // Save history record in database
                    AudioHistory::create([
                        'id'            => $uniqueId,
                        'judul'         => $judulFinal,
                        'filter'        => $filter,
                        'file'          => $dbFileRelativePath,
                        'original_name' => $originalName,
                    ]);

                    $pesanDinamis = "Audio <strong>{$judulFinal}</strong> dengan efek <strong>{$filter}</strong> berhasil diproses.";

                    session()->flash('flash_result', [
                        'status'     => 'success',
                        'pesan'      => $pesanDinamis,
                        'output_msg' => "<span class='text-green-600'>Audio berhasil diproses! 🎉</span>",
                        'file_url'   => '/' . $dbFileRelativePath,
                        'avatar'     => $avatarUrl,
                        'judul'      => $judulFinal
                    ]);
                } else {
                    session()->flash('flash_result', [
                        'status'     => 'error',
                        'output_msg' => "<span class='text-red-600'>Gagal memproses audio. Pastikan FFmpeg sudah terinstal dan terdaftar di sistem.</span>"
                    ]);
                }
            } else {
                session()->flash('flash_result', [
                    'status'     => 'error',
                    'output_msg' => "<span class='text-red-600'>Gagal mengunggah file. Pastikan folder server memiliki izin untuk menulis file.</span>"
                ]);
            }
        } else {
            session()->flash('flash_result', [
                'status'     => 'error',
                'output_msg' => "<span class='text-red-600'>Format file tidak didukung. Harap unggah MP3, WAV, OGG, atau M4A.</span>"
            ]);
        }

        return redirect('/');
    }

    /**
     * Delete an audio file and its database record.
     */
    public function delete(Request $request)
    {
        $deleteId = $request->input('delete_id');
        $item = AudioHistory::find($deleteId);
        
        if ($item) {
            $filePath = public_path($item->file);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $item->delete();
        }
        
        return redirect('/');
    }

    /**
     * Delete all history items and their physical files.
     */
    public function deleteAll()
    {
        $histories = AudioHistory::all();
        foreach ($histories as $item) {
            $filePath = public_path($item->file);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $item->delete();
        }
        
        return redirect('/');
    }

    /**
     * Compress all processed audios into a ZIP archive and download it.
     */
    public function downloadAll()
    {
        $histories = AudioHistory::all();
        
        if ($histories->isEmpty()) {
            return redirect('/')->with('error', 'Belum ada riwayat proses audio.');
        }

        $zip = new \ZipArchive();
        $zipName = 'Audio_Filter_Collection_' . time() . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($histories as $item) {
                $filePath = public_path($item->file);
                if (file_exists($filePath)) {
                    $safeName = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $item->judul ?? 'Audio');
                    $fileNameInZip = $safeName . '_' . substr($item->id, -4) . '.mp3';
                    $zip->addFile($filePath, $fileNameInZip);
                }
            }
            $zip->close();

            return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
        }

        return redirect('/')->with('error', 'Gagal membuat file ZIP.');
    }
}
