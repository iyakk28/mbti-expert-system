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
        .option-card { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
        }
        .option-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .option-card.selected {
            border-color: #8b5cf6;
            background: linear-gradient(135deg, #f3f4f6 0%, #fdf4ff 100%);
            transform: translateY(-3px);
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
        .pulse-ring {
            animation: pulseRing 2s infinite;
        }
        @keyframes pulseRing {
            0% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(139, 92, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, 0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 to-pink-50 min-h-screen" 
      x-data="{ 
          selectedOption: null,
          selectedIndex: null,
          submitting: false 
      }">
    
    <!-- Header dengan Progress Bar -->
    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <div class="mb-8">
            <!-- User Info -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ strtoupper(substr($session->name, 0, 2)) }}
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800">Halo, {{ $session->name }}! 👋</h2>
                        <p class="text-sm text-gray-600">{{ $session->age }} tahun • Question {{ $question_number }}/10</p>
                    </div>
                </div>
                
                <!-- Progress Indicator -->
                <div class="text-right">
                    <div class="text-2xl font-bold text-purple-600 mb-1">
                        {{ round(($question_number / 10) * 100) }}%
                    </div>
                    <div class="text-sm text-gray-500">Completion</div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="relative pt-1">
                <div class="flex mb-2 items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-purple-600 bg-purple-200">
                            Petualangan MBTI
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-semibold inline-block text-purple-600">
                            {{ $question_number }} dari 10
                        </span>
                    </div>
                </div>
                <div class="overflow-hidden h-3 mb-4 text-xs flex rounded-full bg-gray-200">
                    <div class="progress-bar rounded-full shadow-lg" 
                         :style="{ width: ({{ $question_number }} / 10) * 100 + '%' }">
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-8 bounce-in">
            <!-- Scenario Header -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-white/90 text-sm font-semibold mb-1">
                            Scenario {{ $question_number }}
                        </div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ $question['scenario'] }}
                        </h1>
                    </div>
                    <div class="text-4xl">
                        @if($question_number == 1) 🎬
                        @elseif($question_number == 2) 👩‍💻
                        @elseif($question_number == 3) 🏝️
                        @elseif($question_number == 4) 💌
                        @elseif($question_number == 5) 💰
                        @elseif($question_number == 6) 🌙
                        @elseif($question_number == 7) 🎞️
                        @elseif($question_number == 8) 📱
                        @elseif($question_number == 9) 🔋
                        @elseif($question_number == 10) 🏆
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Question Description -->
            <div class="p-8">
                <p class="text-gray-700 text-lg mb-8 leading-relaxed">
                    {{ $question['description'] }}
                </p>
                
                <!-- Options -->
                <form action="{{ route('quiz.answer', ['session' => $session->session_id]) }}" 
                      method="POST" 
                      id="quizForm"
                      @submit.prevent="submitForm">
                    @csrf
                    
                    <input type="hidden" name="question_number" value="{{ $question_number }}">
                    <input type="hidden" name="question_id" value="{{ $question['id'] }}">
                    <input type="hidden" name="option_index" x-model="selectedIndex">
                    <input type="hidden" name="traits" :value="JSON.stringify(selectedOption ? selectedOption.traits : {})">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($question['options'] as $index => $option)
                            <div 
                                class="option-card bg-white rounded-xl p-5 shadow-md"
                                :class="{ 'selected': selectedIndex == {{ $index }} }"
                                @click="
                                    selectedOption = {{ json_encode($option) }};
                                    selectedIndex = {{ $index }};
                                "
                            >
                                <div class="flex items-start">
                                    <div class="text-3xl mr-4">{{ $option['icon'] }}</div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-800 text-lg mb-2">
                                            {{ $option['text'] }}
                                        </div>
                                        <!-- Traits Indicator (debug) -->
                                        <div class="flex flex-wrap gap-1 mt-3">
                                            @foreach($option['traits'] as $trait => $value)
                                                <span class="text-xs px-2 py-1 rounded-full 
                                                    @if($trait == 'E') bg-yellow-100 text-yellow-800
                                                    @elseif($trait == 'I') bg-blue-100 text-blue-800
                                                    @elseif($trait == 'S') bg-green-100 text-green-800
                                                    @elseif($trait == 'N') bg-purple-100 text-purple-800
                                                    @elseif($trait == 'T') bg-red-100 text-red-800
                                                    @elseif($trait == 'F') bg-pink-100 text-pink-800
                                                    @elseif($trait == 'J') bg-indigo-100 text-indigo-800
                                                    @elseif($trait == 'P') bg-teal-100 text-teal-800
                                                    @endif">
                                                    {{ $trait }}+{{ $value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="ml-2">
                                        <div class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center"
                                             :class="{ 'bg-purple-500 border-purple-500': selectedIndex == {{ $index }} }">
                                            <svg x-show="selectedIndex == {{ $index }}" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="mt-10 text-center">
                        <button 
                            type="submit"
                            :disabled="selectedIndex === null || submitting"
                            class="relative bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-4 px-12 rounded-full text-lg hover:shadow-2xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden group"
                            :class="{ 'pulse-ring': selectedIndex !== null && !submitting }"
                        >
                            <!-- Animated background -->
                            <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-purple-700 to-pink-700 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-500"></span>
                            
                            <!-- Button content -->
                            <span class="relative flex items-center justify-center">
                                <template x-if="!submitting">
                                    <span>
                                        <template x-if="{{ $question_number }} < 10">
                                            Lanjut ke Question {{ $question_number + 1 }} 
                                            <span class="ml-2 group-hover:translate-x-2 transition-transform">➡️</span>
                                        </template>
                                        <template x-if="{{ $question_number }} >= 10">
                                            🎯 Lihat Hasil Akhir!
                                        </template>
                                    </span>
                                </template>
                                <template x-if="submitting">
                                    <span class="flex items-center">
                                        <svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                </template>
                            </span>
                        </button>
                        
                        <!-- Skip for now (optional) -->
                        @if($question_number < 10)
                        <p class="text-gray-500 text-sm mt-4">
                            ⏱️ Jawab dengan spontan ya! First instinct paling akurat~
                        </p>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Fun Tips -->
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-5 border border-amber-200 mb-6">
            <div class="flex items-center">
                <div class="text-2xl mr-4">💡</div>
                <div>
                    <h3 class="font-bold text-amber-800 mb-1">Tips MBTI Seru!</h3>
                    <p class="text-amber-700 text-sm">
                        @if($question_number == 1)
                        Pilihan spontanmu mengungkap cara kamu berinteraksi dengan dunia!
                        @elseif($question_number == 2)
                        Cara kamu handle deadline menunjukkan preferensi Judging vs Perceiving!
                        @elseif($question_number == 3)
                        Response terhadap perubahan menunjukkan Sensing vs Intuition!
                        @elseif($question_number == 4)
                        Pendekatan terhadap perasaan menunjukkan Thinking vs Feeling!
                        @elseif($question_number == 5)
                        Keputusan etika mengungkap nilai-nilai terdalammu!
                        @else
                        Setiap pilihan membentuk gambaran kepribadianmu yang unik!
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation Dots -->
        <div class="flex justify-center space-x-2 mb-8">
            @for($i = 1; $i <= 10; $i++)
                <div class="w-3 h-3 rounded-full 
                    @if($i < $question_number) bg-green-500
                    @elseif($i == $question_number) bg-purple-500
                    @else bg-gray-300 @endif
                    @if($i <= $question_number) cursor-pointer 
                    @endif"
                    @if($i < $question_number)
                    onclick="alert('Kamu sudah melewati question ini!')"
                    @endif>
                </div>
            @endfor
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm">
            <p>✨ MBTI Adventure • Made with ❤️ for Gen Z & Millennials</p>
            <p class="mt-1">Setiap pilihan adalah bagian dari ceritamu!</p>
            <p>Since jan 2026 by ~~iyakk✌️</p>
        </div>
    </div>

    <script>
        function submitForm() {
            if (this.selectedIndex === null) {
                alert('Pilih salah satu jawaban dulu ya! 😊');
                return;
            }
            
            this.submitting = true;
            
            // Add a little delay for better UX
            setTimeout(() => {
                document.getElementById('quizForm').submit();
            }, 500);
        }
        
        // Auto-select first option after 30 seconds (optional)
        setTimeout(() => {
            if (!document.querySelector('.option-card.selected')) {
                const firstOption = document.querySelector('.option-card');
                if (firstOption) {
                    firstOption.click();
                    alert('⏰ Cepetan milih! Jawaban spontan lebih akurat~');
                }
            }
        }, 30000);
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key >= '1' && e.key <= '4') {
                const index = parseInt(e.key) - 1;
                const option = document.querySelectorAll('.option-card')[index];
                if (option) option.click();
            }
            
            if (e.key === 'Enter' && this.selectedIndex !== null) {
                this.submitForm();
            }
        });
    </script>
</body>
</html>