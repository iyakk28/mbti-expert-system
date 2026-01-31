<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBTI Quiz - Question {{ $question_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .likert-scale { 
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .scale-option {
            flex: 1;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
        }
        .scale-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .scale-option.selected {
            border-color: #8b5cf6;
            background: linear-gradient(135deg, #f3f4f6 0%, #fdf4ff 100%);
            transform: translateY(-3px);
        }
        .scale-label {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            color: #6b7280;
        }
        .emoji-scale {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .progress-bar {
            height: 10px;
            border-radius: 5px;
            background: linear-gradient(90deg, #8b5cf6 0%, #ec4899 100%);
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bounce-in {
            animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 to-pink-50 min-h-screen" 
      x-data="{ 
          selectedScore: null,
          submitting: false 
      }">
    
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header & Progress -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ strtoupper(substr($session->name, 0, 2)) }}
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">Halo, {{ $session->name }}! 👋</h2>
                       <p class="text-sm text-gray-600">{{ $session->age }} tahun • Pertanyaan {{ $question_number }}/60</p>
                    </div>
                </div>
                
                <div class="text-right">
                    <div class="text-2xl font-bold text-purple-600 mb-1">
    {{ round(($question_number / 60) * 100) }}%
</div>
                    <div class="text-sm text-gray-500">Progress</div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="overflow-hidden h-3 mb-4 text-xs flex rounded-full bg-gray-200">
               <div class="progress-bar rounded-full shadow-lg" 
     style="width: {{ ($question_number / 60) * 100 }}%">
</div>
            </div>
        </div>

        <!-- Question Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-8 bounce-in">
            <!-- Question Header -->
            <div class="bg-gradient-to-r from-purple-100 to-pink-100 p-6 border-b">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-purple-600 text-sm font-semibold mb-1">
                            Pertanyaan {{ $question_number }}
                        </div>
                        @if(isset($question['hint']))
                        <div class="text-sm text-gray-600">
                            {{ $question['hint'] }}
                        </div>
                        @endif
                    </div>
                    <div class="text-4xl">
                        @if($question_number <= 5) 🤔
                        @elseif($question_number <= 10) 💭
                        @else 🔮
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Statement -->
            <div class="p-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-8 text-center leading-relaxed">
                    "{{ $question['statement'] }}"
                </h1>
                
                <!-- Likert Scale -->
                <form action="{{ route('quiz.answer', ['session' => $session->session_id]) }}" 
                      method="POST" 
                      id="quizForm"
                      @submit.prevent="submitForm">
                    @csrf
                    
                    <input type="hidden" name="question_number" value="{{ $question_number }}">
                    <input type="hidden" name="question_id" value="{{ $question['id'] }}">
                    <input type="hidden" name="score" x-model="selectedScore">
                    
                    <!-- Scale -->
                    <div class="likert-scale mb-10">
                        @foreach([1, 2, 3, 4, 5] as $score)
                            <div 
                                class="scale-option bg-white rounded-xl p-4 text-center shadow-sm"
                                :class="{ 'selected': selectedScore == {{ $score }} }"
                                @click="selectedScore = {{ $score }}"
                            >
                                <!-- Emoji Indicator -->
                                <div class="emoji-scale">
                                    @if($score == 1) 😠
                                    @elseif($score == 2) 😕
                                    @elseif($score == 3) 😐
                                    @elseif($score == 4) 🙂
                                    @elseif($score == 5) 😄
                                    @endif
                                </div>
                                
                                <!-- Score Circle -->
                                <div class="w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-2
                                    @if($score == 1) bg-red-100 text-red-800
                                    @elseif($score == 2) bg-orange-100 text-orange-800
                                    @elseif($score == 3) bg-yellow-100 text-yellow-800
                                    @elseif($score == 4) bg-green-100 text-green-800
                                    @elseif($score == 5) bg-blue-100 text-blue-800
                                    @endif">
                                    <span class="font-bold">{{ $score }}</span>
                                </div>
                                
                                <!-- Label -->
                                <div class="scale-label font-medium">
                                    @php
                                        $labels = $question['scale_labels'] ?? ['Sangat Tidak Setuju', 'Tidak Setuju', 'Netral', 'Setuju', 'Sangat Setuju'];
                                        echo $labels[$score-1] ?? '';
                                    @endphp
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Visual Scale Bar -->
                    <div class="relative mb-12">
                        <div class="flex justify-between text-sm text-gray-500 mb-2">
                            <span>Sangat Tidak Setuju</span>
                            <span>Sangat Setuju</span>
                        </div>
                        <div class="h-3 bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 rounded-full"></div>
                        <div class="absolute top-0 left-0 right-0 h-3">
                            <div class="absolute top-0 w-6 h-6 -mt-1.5 -ml-3 rounded-full border-4 border-white bg-purple-600 shadow-lg transition-all duration-300"
                                 :style="selectedScore ? 'left: ' + ((selectedScore - 1) * 25) + '%' : 'display: none'">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="text-center">
                        <button 
                            type="submit"
                            :disabled="selectedScore === null || submitting"
                            class="relative bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-4 px-12 rounded-full text-lg hover:shadow-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden group"
                        >
                            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-purple-700 to-pink-700 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-500"></span>
                            
                            <span class="relative flex items-center justify-center">
                                <template x-if="!submitting">
                                    <span>
                                       <!-- Update button text -->
<template x-if="{{ $question_number }} < 60">
    Lanjut ke Pertanyaan {{ $question_number + 1 }} 
    <span class="ml-2 group-hover:translate-x-2 transition-transform">➡️</span>
</template>
<template x-if="{{ $question_number }} >= 60">
    🎯 Hitung Hasil MBTI Saya!
</template>

                                    </span>
                                </template>
                                <template x-if="submitting">
                                    <span class="flex items-center">
                                        <svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </template>
                            </span>
                        </button>
                        
                        <p class="text-gray-500 text-sm mt-4">
                            ⚡ Jawab dengan jujur sesuai perasaanmu saat ini!
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tips -->
        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-5 border border-blue-200 mb-6">
            <div class="flex items-center">
                <div class="text-2xl mr-4">💡</div>
                <div>
                    <h3 class="font-bold text-blue-800 mb-1">Tips Mengisi Skala</h3>
                    <p class="text-blue-700 text-sm">
                        • <strong>1-2</strong>: Jika sangat tidak sesuai dengan kamu<br>
                        • <strong>3</strong>: Jika netral atau kadang-kadang<br>
                        • <strong>4-5</strong>: Jika sangat sesuai dengan kepribadianmu
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation Dots -->
        <div class="flex justify-center space-x-1 mb-8 flex-wrap">
    @for($i = 1; $i <= 60; $i += 5)
        <div class="w-2 h-2 rounded-full 
            @if($i < $question_number) bg-green-500
            @elseif($i == $question_number) bg-purple-500
            @else bg-gray-300 @endif">
        </div>
    @endfor
</div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm">
            <p>✨ MBTI Personality Assessment • Skala Likert 5-Point</p>
            <p class="mt-1">Hasil lebih akurat dengan respons spontan!</p>
            <p>Since jan 2026 by ~~iyakk✌️</p>
        </div>
    </div>

    <script>
        function submitForm() {
            if (this.selectedScore === null) {
                alert('Pilih tingkat kesetujuan dulu ya! 😊');
                return;
            }
            
            this.submitting = true;
            
            setTimeout(() => {
                document.getElementById('quizForm').submit();
            }, 500);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key >= '1' && e.key <= '5') {
                this.selectedScore = parseInt(e.key);
            }
            
            if (e.key === 'Enter' && this.selectedScore !== null) {
                this.submitForm();
            }
        });
        
        // Auto-progress after 45 seconds (optional)
        setTimeout(() => {
            if (!this.selectedScore) {
                this.selectedScore = 3; // Auto-select neutral
                alert('⏰ Pilihannya otomatis ke "Netral" ya! Jawaban spontan lebih akurat~');
            }
        }, 45000);
    </script>
</body>
</html>