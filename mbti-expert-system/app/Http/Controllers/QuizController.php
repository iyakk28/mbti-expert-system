<?php

namespace App\Http\Controllers;

use App\Models\QuizSession;
use App\Services\MBTICalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mpdf\Mpdf;

class QuizController extends Controller
{
    protected $questions;
    
    public function __construct()
    {
        $this->questions = $this->loadQuestions();
    }
    
    // LANDING PAGE
    public function landing()
    {
        return view('landing');
    }
    
    // START QUIZ
    public function startQuiz(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'age' => 'required|integer|min:13|max:60'
        ]);
        
        $session = QuizSession::create([
            'session_id' => Str::uuid(),
            'name' => $validated['name'],
            'age' => $validated['age'],
            'answers' => []
        ]);
        
        return redirect()->route('quiz.question', [
            'session' => $session->session_id,
            'question_number' => 1
        ]);
    }
    
    // SHOW QUESTION
  public function showQuestion($session_id, $question_number)
{
    $session = QuizSession::where('session_id', $session_id)->firstOrFail();
    
    if ($session->mbti_result) {
        return redirect()->route('quiz.result', ['session' => $session_id]);
    }
    
    if ($question_number > 60) {
        return $this->calculateResult($session);
    }
    
    $question = $this->questions[$question_number] ?? null;
    
    if (!$question) {
        return $this->calculateResult($session);
    }
    
    // Gunakan template likert baru
    return view('quiz.likert_question', compact('session', 'question', 'question_number'));
}
    
  private function getTraitsMapping()
{
    // Tidak perlu mapping terpisah, sudah ada di question array
    return []; // Kosongkan karena sudah ada di question
}

public function saveAnswer(Request $request, $session_id)
{
    $session = QuizSession::where('session_id', $session_id)->firstOrFail();
    
    $answers = $session->answers ?? [];
    $questionNumber = $request->question_number;
    $score = $request->score; // 1-5
    
    // Ambil data pertanyaan
    $question = $this->questions[$questionNumber] ?? null;
    
    if ($question) {
        // Konversi skala 1-5 ke nilai 0-4
        $convertedScore = $score - 1; // 1->0, 2->1, 3->2, 4->3, 5->4
        
        // Jika reverse, nilai dibalik
        if ($question['reverse'] ?? false) {
            $convertedScore = 4 - $convertedScore; // 0->4, 1->3, 2->2, 3->1, 4->0
        }
        
        // Tentukan trait berdasarkan direction
        $trait = $question['direction'];
        $traits = [$trait => $convertedScore];
        
        $answers[$questionNumber] = [
            'question_id' => $question['id'],
            'score' => $score,
            'converted_score' => $convertedScore,
            'traits' => $traits,
            'dimension' => $question['dimension'],
            'direction' => $trait,
            'timestamp' => now()->toDateTimeString()
        ];
        
        $session->update(['answers' => $answers]);
    }
    
    $nextQuestion = $questionNumber + 1;
    
    // Update jadi 60 pertanyaan
    if ($nextQuestion > 60) {
        return $this->calculateResult($session);
    }
    
    return redirect()->route('quiz.question', [
        'session' => $session_id,
        'question_number' => $nextQuestion
    ]);
}
    // CALCULATE RESULT
 private function calculateResult($session)
{
    $calculator = new MBTICalculator();
    
    // Re-structure answers untuk calculator
    $answersForCalculation = [];
    foreach ($session->answers as $qNum => $answer) {
        $answersForCalculation[] = [
            'question_id' => $answer['question_id'],
            'traits' => $answer['traits']
        ];
    }
    
    $result = $calculator->calculate($answersForCalculation);
    
    // Simpan juga breakdown per dimension
    $dimensionScores = $this->calculateDimensionScores($session->answers);
    $result['dimension_scores'] = $dimensionScores;
    $result['total_questions'] = count($session->answers);
    
    $session->update([
        'mbti_result' => $result['type'],
        'result_details' => $result
    ]);
    
    return redirect()->route('quiz.result', ['session' => $session->session_id]);
}

private function calculateDimensionScores($answers)
{
    $dimensionScores = [
        'E' => 0, 'I' => 0,
        'S' => 0, 'N' => 0,
        'T' => 0, 'F' => 0,
        'J' => 0, 'P' => 0
    ];
    
    foreach ($answers as $answer) {
        foreach ($answer['traits'] as $trait => $value) {
            if (isset($dimensionScores[$trait])) {
                $dimensionScores[$trait] += $value;
            }
        }
    }
    
    return $dimensionScores;
}
    
    // SHOW RESULT
    public function showResult($session_id)
    {
        $session = QuizSession::where('session_id', $session_id)->firstOrFail();
        
        if (!$session->mbti_result) {
            return redirect()->route('quiz.question', [
                'session' => $session_id,
                'question_number' => 1
            ]);
        }
        
        $result = $session->result_details;
        $shareUrl = route('quiz.result', ['session' => $session_id]);
        
        return view('quiz.result', compact('session', 'result', 'shareUrl'));
    }
    
    // DOWNLOAD RESULT AS PDF
    public function downloadResultPDF($session_id)
    {
        $session = QuizSession::where('session_id', $session_id)->firstOrFail();
        $result = $session->result_details;
        
        if (!$result) {
            return response()->json(['error' => 'Result not found'], 404);
        }
        
        $html = $this->generatePDFHTML($session, $result);
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);
        
        $mpdf->WriteHTML($html);
        
        $filename = "MBTI-{$session->name}-{$result['type']}.pdf";
        return $mpdf->Output($filename, 'D');
    }
    
    // Generate HTML for PDF
    private function generatePDFHTML($session, $result)
    {
        $strengths = implode(', ', $result['strengths']);
        $weaknesses = implode(', ', $result['weaknesses']);
        $careers = implode(', ', $result['careers']);
        $selebriti = implode(', ', $result['celebrities']['artists']);
        $karakter = implode(', ', $result['celebrities']['characters']);
        $tipsPengembangan = $result['growth_tips'];
        $created = $session->created_at->format('d/m/Y H:i');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #8b5cf6;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 36px;
            color: #8b5cf6;
            margin: 0 0 10px 0;
        }
        .header h2 {
            font-size: 20px;
            color: #666;
            margin: 0 0 15px 0;
        }
        .info-row {
            margin: 5px 0;
            font-size: 14px;
        }
        .section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .section h3 {
            font-size: 16px;
            color: #8b5cf6;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .content {
            font-size: 13px;
            margin-left: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            color: #999;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{$result['type']}</h1>
        <h2>{$result['label']}</h2>
        <div class="info-row">Akurasi: <strong>{$result['purity']}%</strong></div>
        <div class="info-row">Nama: <strong>{$session->name}</strong></div>
        <div class="info-row">Usia: <strong>{$session->age} tahun</strong></div>
    </div>
    
    <div class="section">
        <h3>🎭 Kepribadian Utamamu</h3>
        <div class="content">
            $strengths
        </div>
    </div>
    
    <div class="section">
        <h3>⚠️ Area Pengembangan</h3>
        <div class="content">
            $weaknesses
        </div>
    </div>
    
    <div class="section">
        <h3>🧠 Fungsi Kognitif</h3>
        <table class="table">
            <tr><td><strong>Dominan:</strong></td><td>{$result['cognitive_functions']['dominant']}</td></tr>
            <tr><td><strong>Auxiliary:</strong></td><td>{$result['cognitive_functions']['auxiliary']}</td></tr>
            <tr><td><strong>Tertiary:</strong></td><td>{$result['cognitive_functions']['tertiary']}</td></tr>
            <tr><td><strong>Inferior:</strong></td><td>{$result['cognitive_functions']['inferior']}</td></tr>
        </table>
    </div>
    
    <div class="section">
        <h3>💼 Karir yang Cocok</h3>
        <div class="content">
            $careers
        </div>
    </div>
    
    <div class="section">
        <h3>💖 Pasangan MBTI Alami</h3>
        <div class="content">
HTML;

        foreach ($result['relationship_matches'] as $match) {
            $html .= "<p><strong>{$match['type']}</strong>: {$match['reason']}</p>";
        }

        $html .= <<<HTML
        </div>
    </div>
    
    <div class="section">
        <h3>⭐ Tokoh Serupa</h3>
        <div class="content">
            <p><strong>Selebriti:</strong> $selebriti</p>
            <p><strong>Karakter:</strong> $karakter</p>
        </div>
    </div>
    
    <div class="section">
        <h3>🌱 Tips Pengembangan Diri</h3>
        <div class="content">
            $tipsPengembangan
        </div>
    </div>
    
    <div class="footer">
        <p>MBTI Adventure - Sistem Pakar Kepribadian</p>
        <p>Dibuat: $created</p>
        <p>Created by:iyak~~✌️</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
    
    // SEND RESULT VIA WHATSAPP/EMAIL
    public function sendResult(Request $request, $session_id)
    {
        $session = QuizSession::where('session_id', $session_id)->firstOrFail();
        
        $validated = $request->validate([
            'contact_method' => 'required|in:whatsapp,email',
            'contact_address' => 'required'
        ]);
        
        $contactAddress = $validated['contact_method'] == 'whatsapp' 
            ? '62' . preg_replace('/[^0-9]/', '', $validated['contact_address'])
            : $validated['contact_address'];
        
        $session->update([
            'contact_method' => $validated['contact_method'],
            'contact_address' => $contactAddress
        ]);
        
        $sent = $this->sendViaContactMethod($session);
        
        if ($sent) {
            $session->update(['contact_sent' => true]);
            return response()->json([
                'success' => true,
                'message' => 'Hasil berhasil dikirim ke ' . 
                           ($validated['contact_method'] == 'whatsapp' ? 'WhatsApp' : 'Email') . ' kamu!'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim. Coba lagi nanti.'
        ], 500);
    }
    //load questions utk pertanyaan
 private function loadQuestions()
{
    return [
        // ========== EXTRAVERSION (E) vs INTROVERSION (I) ==========
        // 15 pertanyaan untuk E/I
        1 => [
            'id' => 1,
            'statement' => "Saya merasa lebih berenergi setelah menghabiskan waktu bersama banyak orang.",
            'dimension' => 'E/I',
            'direction' => 'E', // Positif untuk E
            'reverse' => false
        ],
        2 => [
            'id' => 2,
            'statement' => "Saya perlu waktu menyendiri setelah acara sosial untuk mengisi ulang energi.",
            'dimension' => 'E/I', 
            'direction' => 'I', // Positif untuk I
            'reverse' => false
        ],
        3 => [
            'id' => 3,
            'statement' => "Saya mudah memulai percakapan dengan orang yang belum saya kenal.",
            'dimension' => 'E/I',
            'direction' => 'E',
            'reverse' => false
        ],
        4 => [
            'id' => 4,
            'statement' => "Saya lebih suka mendengarkan daripada berbicara dalam kelompok.",
            'dimension' => 'E/I',
            'direction' => 'I',
            'reverse' => false
        ],
        5 => [
            'id' => 5,
            'statement' => "Ide-ide saya biasanya berkembang lebih baik ketika saya mendiskusikannya dengan orang lain.",
            'dimension' => 'E/I',
            'direction' => 'E',
            'reverse' => false
        ],
        6 => [
            'id' => 6,
            'statement' => "Saya sering merasa lelah secara mental setelah terlalu banyak interaksi sosial.",
            'dimension' => 'E/I',
            'direction' => 'I',
            'reverse' => false
        ],
        7 => [
            'id' => 7,
            'statement' => "Saya menikmati menjadi pusat perhatian dalam situasi sosial.",
            'dimension' => 'E/I',
            'direction' => 'E',
            'reverse' => false
        ],
        8 => [
            'id' => 8,
            'statement' => "Saya lebih memilih pertemuan kecil dengan 1-2 orang daripada pesta besar.",
            'dimension' => 'E/I',
            'direction' => 'I',
            'reverse' => false
        ],
        9 => [
            'id' => 9,
            'statement' => "Saya cenderung berpikir keras sebelum berbicara.",
            'dimension' => 'E/I',
            'direction' => 'I', // REVERSED untuk E
            'reverse' => true
        ],
        10 => [
            'id' => 10,
            'statement' => "Saya senang bertemu orang baru dan memperluas jaringan sosial.",
            'dimension' => 'E/I',
            'direction' => 'E',
            'reverse' => false
        ],
        11 => [
            'id' => 11,
            'statement' => "Waktu sendirian sangat penting bagi kesejahteraan mental saya.",
            'dimension' => 'E/I',
            'direction' => 'I',
            'reverse' => false
        ],
        12 => [
            'id' => 12,
            'statement' => "Saya merasa bosan atau tidak sabar ketika terlalu lama sendirian.",
            'dimension' => 'E/I',
            'direction' => 'E',
            'reverse' => false
        ],
        13 => [
            'id' => 13,
            'statement' => "Saya lebih memilih bekerja sendiri daripada dalam tim.",
            'dimension' => 'E/I',
            'direction' => 'I',
            'reverse' => false
        ],
        14 => [
            'id' => 14,
            'statement' => "Energi saya cenderung meningkat ketika ada di sekitar orang lain.",
            'dimension' => 'E/I',
            'direction' => 'E',
            'reverse' => false
        ],
        15 => [
            'id' => 15,
            'statement' => "Saya sering merasa perlu 'mengisi baterai' dengan menyendiri setelah hari yang sibuk.",
            'dimension' => 'E/I',
            'direction' => 'I',
            'reverse' => false
        ],

        // ========== SENSING (S) vs INTUITION (N) ==========
        16 => [
            'id' => 16,
            'statement' => "Saya lebih mempercayai fakta dan data konkret daripada teori atau spekulasi.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        17 => [
            'id' => 17,
            'statement' => "Saya sering melihat pola dan kemungkinan di balik informasi yang ada.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        18 => [
            'id' => 18,
            'statement' => "Saya lebih memilih instruksi yang jelas dan langkah-langkah praktis.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        19 => [
            'id' => 19,
            'statement' => "Imajinasi saya sering melayang ke masa depan atau kemungkinan yang belum terjadi.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        20 => [
            'id' => 20,
            'statement' => "Saya lebih baik dalam menerapkan metode yang sudah terbukti daripada menciptakan cara baru.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        21 => [
            'id' => 21,
            'statement' => "Saya mudah bosan dengan rutinitas dan tugas-tugas yang repetitif.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        22 => [
            'id' => 22,
            'statement' => "Detail-detail kecil sering kali menarik perhatian saya.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        23 => [
            'id' => 23,
            'statement' => "Saya lebih tertarik pada 'gambaran besar' daripada detail spesifik.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        24 => [
            'id' => 24,
            'statement' => "Saya lebih memilih pembicaraan tentang hal-hal yang praktis dan realistis.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        25 => [
            'id' => 25,
            'statement' => "Saya sering menemukan makna simbolis atau koneksi tersembunyi dalam berbagai hal.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        26 => [
            'id' => 26,
            'statement' => "Saya lebih percaya pada pengalaman langsung daripada intuisi.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        27 => [
            'id' => 27,
            'statement' => "Saya menikmati berandai-andai tentang 'bagaimana jika' di masa depan.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        28 => [
            'id' => 28,
            'statement' => "Saya lebih fokus pada apa yang terjadi saat ini daripada kemungkinan masa depan.",
            'dimension' => 'S/N',
            'direction' => 'S',
            'reverse' => false
        ],
        29 => [
            'id' => 29,
            'statement' => "Saya mudah melihat potensi dan kemungkinan dalam situasi yang tampaknya biasa.",
            'dimension' => 'S/N',
            'direction' => 'N',
            'reverse' => false
        ],
        30 => [
            'id' => 30,
            'statement' => "Saya merasa tidak nyaman dengan hal-hal yang terlalu abstrak atau teoritis.",
            'dimension' => 'S/N',
            'direction' => 'S', // REVERSED untuk N
            'reverse' => true
        ],

        // ========== THINKING (T) vs FEELING (F) ==========
        31 => [
            'id' => 31,
            'statement' => "Dalam membuat keputusan, logika dan objektivitas lebih penting daripada perasaan.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        32 => [
            'id' => 32,
            'statement' => "Saya sering mempertimbangkan bagaimana keputusan saya akan mempengaruhi perasaan orang lain.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        33 => [
            'id' => 33,
            'statement' => "Kebenaran yang keras lebih baik daripada kebohongan yang menenangkan.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        34 => [
            'id' => 34,
            'statement' => "Harmoni dan hubungan baik dalam kelompok adalah prioritas utama bagi saya.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        35 => [
            'id' => 35,
            'statement' => "Saya bisa tetap tenang dan objektif bahkan dalam situasi emosional.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        36 => [
            'id' => 36,
            'statement' => "Saya sulit mengambil keputusan jika tahu akan menyakiti perasaan seseorang.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        37 => [
            'id' => 37,
            'statement' => "Keadilan dan prinsip universal lebih penting daripada pengecualian berdasarkan kasus per kasus.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        38 => [
            'id' => 38,
            'statement' => "Saya peka terhadap atmosfer emosional dalam suatu ruangan.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        39 => [
            'id' => 39,
            'statement' => "Dalam diskusi, kebenaran fakta lebih penting daripada menjaga perasaan.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        40 => [
            'id' => 40,
            'statement' => "Saya sering membuat keputusan berdasarkan apa yang 'terasa benar' secara pribadi.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        41 => [
            'id' => 41,
            'statement' => "Saya lebih menghargai kompetensi daripada simpati.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        42 => [
            'id' => 42,
            'statement' => "Saya mudah merasakan emosi orang lain dan sering ikut merasakannya.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        43 => [
            'id' => 43,
            'statement' => "Kritik yang membangun lebih berharga daripada pujian yang tidak tulus.",
            'dimension' => 'T/F',
            'direction' => 'T',
            'reverse' => false
        ],
        44 => [
            'id' => 44,
            'statement' => "Saya cenderung menghindari konfrontasi untuk menjaga kedamaian.",
            'dimension' => 'T/F',
            'direction' => 'F',
            'reverse' => false
        ],
        45 => [
            'id' => 45,
            'statement' => "Sulit bagi saya untuk memahami mengapa orang membuat keputusan berdasarkan emosi daripada logika.",
            'dimension' => 'T/F',
            'direction' => 'T', // REVERSED untuk F
            'reverse' => true
        ],

        // ========== JUDGING (J) vs PERCEIVING (P) ==========
        46 => [
            'id' => 46,
            'statement' => "Saya merasa lebih tenang ketika hari-hari saya terencana dengan baik.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        47 => [
            'id' => 47,
            'statement' => "Saya lebih suka menjaga pilihan tetap terbuka daripada membuat rencana yang kaku.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        48 => [
            'id' => 48,
            'statement' => "Daftar tugas (to-do list) sangat membantu saya tetap produktif.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        49 => [
            'id' => 49,
            'statement' => "Saya melihat tenggat waktu sebagai sesuatu yang fleksibel, bukan mutlak.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        50 => [
            'id' => 50,
            'statement' => "Saya cenderung mengambil keputusan dengan cepat untuk menghindari ketidakpastian.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        51 => [
            'id' => 51,
            'statement' => "Saya menikmati spontanitas dan kejutan dalam kehidupan sehari-hari.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        52 => [
            'id' => 52,
            'statement' => "Saya merasa tidak nyaman ketika rencana berubah di menit-menit terakhir.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        53 => [
            'id' => 53,
            'statement' => "Saya sering menunda keputusan untuk mempertimbangkan lebih banyak informasi.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        54 => [
            'id' => 54,
            'statement' => "Saya lebih suka menyelesaikan pekerjaan sebelum bersantai.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        55 => [
            'id' => 55,
            'statement' => "Saya merasa terkekang oleh jadwal yang terlalu terstruktur.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        56 => [
            'id' => 56,
            'statement' => "Saya sering mempersiapkan segala sesuatu jauh sebelum waktunya.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        57 => [
            'id' => 57,
            'statement' => "Saya bekerja lebih baik di bawah tekanan deadline yang mendekat.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        58 => [
            'id' => 58,
            'statement' => "Saya lebih memilih lingkungan yang teratur dan rapi.",
            'dimension' => 'J/P',
            'direction' => 'J',
            'reverse' => false
        ],
        59 => [
            'id' => 59,
            'statement' => "Saya mudah beradaptasi dengan perubahan rencana yang tiba-tiba.",
            'dimension' => 'J/P',
            'direction' => 'P',
            'reverse' => false
        ],
        60 => [
            'id' => 60,
            'statement' => "Saya merasa kesulitan ketika harus membuat keputusan impulsif tanpa perencanaan.",
            'dimension' => 'J/P',
            'direction' => 'J', // REVERSED untuk P
            'reverse' => true
        ]
    ];
}
    
    // SEND VIA CONTACT METHOD
    private function sendViaContactMethod($session)
    {
        $result = $session->result_details;
        
        $message = $this->formatResultMessage($session->name, $result, $session->session_id);
        
        if ($session->contact_method == 'whatsapp') {
            return $this->sendWhatsApp($session->contact_address, $message);
        } else {
            return $this->sendEmail($session->contact_address, $message);
        }
    }
    
    private function formatResultMessage($name, $result, $sessionId)
    {
        return "🌟 *HASIL MBTI ADVENTURE* 🌟

Halo {$name}! 

📊 *MBTI Type:* {$result['type']}
🏷️ *Label:* {$result['label']}
🎯 *Accuracy:* {$result['purity']}%

*🎭 KEPRIBADIAN:*
" . implode(', ', $result['strengths']) . "

*💼 KARIR YANG COCOK:*
" . implode(', ', $result['careers']) . "

*⭐ ARTIS/KARAKTER SERUPA:*
" . implode(', ', $result['celebrities']['artists'] ?? []) . "

Simpan hasil ini ya! Share ke temen biar pada tahu~ 

🔗 *Link Lengkap:* " . route('quiz.result', ['session' => $sessionId]);
    }
    
    private function sendWhatsApp($number, $message)
    {
        // Untuk prototype: buat WhatsApp link
        $encodedMessage = urlencode($message);
        $whatsappUrl = "https://wa.me/{$number}?text={$encodedMessage}";
        
        // Log untuk debugging
        Log::info("WhatsApp Link Generated: {$whatsappUrl}");
        
        // Untuk production, bisa gunakan API
        return true;
    }
    
    private function sendEmail($email, $message)
    {
        try {
            Mail::raw($message, function ($mail) use ($email) {
                $mail->to($email)
                     ->subject('🎉 Hasil MBTI Adventure Kamu!');
            });
            return true;
        } catch (\Exception $e) {
            Log::error("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    // SEND PDF TO WHATSAPP
    public function sendPdfWhatsapp(Request $request)
    {
        try {
            if (!$request->hasFile('pdf')) {
                return response()->json([
                    'success' => false,
                    'message' => 'File PDF tidak ditemukan'
                ], 400);
            }
            
            $session = QuizSession::where('session_id', $request->session_id)->first();
            
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session tidak ditemukan'
                ], 404);
            }
            
            // Get WhatsApp number
            $whatsappNumber = $request->whatsapp_number ?? $session->contact_address;
            
            if (!$whatsappNumber) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nomor WhatsApp tidak tersimpan, tapi PDF siap diunduh'
                ]);
            }
            
            // Store PDF temporarily
            $pdf = $request->file('pdf');
            $filename = 'MBTI-' . $session->name . '-' . $session->session_id . '.pdf';
            $path = storage_path('app/public/mbti-results/' . $filename);
            
            // Create directory if not exists
            @mkdir(dirname($path), 0755, true);
            
            $pdf->move(dirname($path), basename($path));
            
            // Generate WhatsApp link dengan text
            $purity = $session->result_details['purity'] ?? 'N/A';
            $text = "🎉 Hasil MBTI Adventure saya!\n\n"
                  . "📊 Tipe: {$session->mbti_result}\n"
                  . "🎯 Akurasi: {$purity}%\n\n"
                  . "Lihat detail lengkapnya di: " . route('quiz.result', ['session' => $session->session_id]);
            
            $waLink = "https://wa.me/{$whatsappNumber}?text=" . urlencode($text);
            
            Log::info("PDF generated for WhatsApp", [
                'session_id' => $session->session_id,
                'filename' => $filename,
                'wa_number' => $whatsappNumber
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'PDF berhasil disiapkan untuk WhatsApp',
                'whatsapp_link' => $waLink,
                'file_url' => asset('storage/mbti-results/' . $filename)
            ]);
            
        } catch (\Exception $e) {
            Log::error("PDF WhatsApp Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}