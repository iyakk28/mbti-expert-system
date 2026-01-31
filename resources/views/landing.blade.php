<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MBTI Adventure - Discover Your Personality</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .input-field { transition: all 0.3s ease; }
        .input-field:focus { transform: translateY(-2px); }
        .animate-float { animation: float 3s ease-in-out infinite; }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full">
        <!-- Animated Elements -->
        <div class="flex justify-center space-x-4 mb-6">
            <div class="text-3xl animate-float" style="animation-delay: 0s;">🔮</div>
            <div class="text-3xl animate-float" style="animation-delay: 0.5s;">✨</div>
            <div class="text-3xl animate-float" style="animation-delay: 1s;">🌟</div>
        </div>
        
        <!-- Main Card -->
        <div class="bg-white rounded-3xl card-shadow overflow-hidden transform transition-all duration-500 hover:scale-[1.01]">
            <!-- Header -->
            <div class="relative h-56 overflow-hidden bg-gradient-to-r from-purple-500 via-pink-500 to-purple-600">
                <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-6 text-center">
                    <h1 class="text-4xl font-bold mb-2">MBTI ADVENTURE</h1>
                    <p class="text-lg opacity-90">Temukan Kepribadianmu dalam 60 Pertanyaan Seru!</p>
                    <div class="mt-4 flex space-x-2">
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">Gen Z Edition</span>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">Millennial</span>
                    </div>
                </div>
                
                <!-- Decorative Circles -->
                <div class="absolute -top-10 -left-10 w-40 h-40 bg-white/10 rounded-full"></div>
                <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
            </div>
            
            <!-- Form Section -->
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">Halo, Calon Explorer! 👋</h2>
                <p class="text-gray-600 mb-8">Isi data diri dulu yuk biar hasilnya personal banget~</p>
                
                <form action="{{ route('start.quiz') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <!-- Name Field -->
                    <div>
                        <label class="block text-gray-700 mb-3 font-semibold">
                            <div class="flex items-center">
                                <span class="text-xl mr-2">👤</span>
                                <span>Nama / Inisial Kamu</span>
                            </div>
                        </label>
                        <input type="text" 
                               name="name" 
                               required
                               class="w-full px-5 py-4 input-field border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 focus:outline-none text-lg"
                               placeholder="Contoh: Rara / RZK"
                               maxlength="30"
                               autocomplete="off">
                        <p class="text-sm text-gray-500 mt-2 ml-1">Bisa pake nama panggilan atau inisial aja kok!</p>
                    </div>
                    
                    <!-- Age Field -->
                    <div>
                        <label class="block text-gray-700 mb-3 font-semibold">
                            <div class="flex items-center">
                                <span class="text-xl mr-2">🎂</span>
                                <span>Umur Kamu</span>
                            </div>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   name="age" 
                                   min="1" 
                                   max="100" 
                                   required
                                   class="w-full px-5 py-4 input-field border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 focus:outline-none text-lg"
                                   placeholder="Contoh: 21"
                                   id="ageInput">
                            <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-medium">
                                tahun
                            </div>
                        </div>
                        
                        <!-- Age Slider -->
                        <div class="mt-4">
                            <input type="range" 
                                   min="1" 
                                   max="100" 
                                   value="21"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                                   id="ageSlider"
                                   oninput="document.getElementById('ageInput').value = this.value">
                            <div class="flex justify-between text-sm text-gray-500 mt-1">
                                <span>1</span>
                                <span>25</span>
                                <span>50</span>
                                <span>100</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generation Display -->
                    <div id="generationDisplay" class="hidden p-5 rounded-xl bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-100">
                        <div class="flex items-center">
                            <div id="genIcon" class="text-3xl mr-4">👶</div>
                            <div>
                                <h4 id="genTitle" class="font-bold text-blue-800 text-lg">Generasi Kamu</h4>
                                <p id="genDesc" class="text-blue-600">Deskripsi generasi</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Start Button -->
                    <button type="submit" 
                            class="w-full mt-8 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-5 rounded-xl text-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 active:translate-y-0 group">
                        <div class="flex items-center justify-center">
                            <span class="mr-3 text-xl group-hover:scale-110 transition-transform">🚀</span>
                            <span class="text-xl">Mulai Petualangan MBTI!</span>
                            <span class="ml-3 text-xl group-hover:translate-x-2 transition-transform">➡️</span>
                        </div>
                        <div class="text-sm opacity-80 mt-1">Hanya 5 menit • Hasil akurat • Gratis!</div>
                    </button>
                    
                    <!-- Features -->
                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">✅</span>
                            <span class="text-sm">60 Pertanyaan Seru</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">✅</span>
                            <span class="text-sm">Hasil Lengkap</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">✅</span>
                            <span class="text-sm">Kirim ke WA/Email</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">✅</span>
                            <span class="text-sm">Gratis 100%</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-white/80 mt-6 text-sm">
            <p>Dibuat dengan ❤️ untuk kamu </p>
            <p class="mt-1">Hasil berdasarkan teori MBTI & fungsi kognitif</p>
            <p>Since jan 2026 by ~~iyakk✌️</p>
        </div>
    </div>

    <script>
        // Generation detection
        const ageInput = document.getElementById('ageInput');
        const ageSlider = document.getElementById('ageSlider');
        const genDisplay = document.getElementById('generationDisplay');
        const genIcon = document.getElementById('genIcon');
        const genTitle = document.getElementById('genTitle');
        const genDesc = document.getElementById('genDesc');
        
        function updateGeneration(age) {
            if (age >= 1 && age <= 100) {
                let gen = '', icon = '', desc = '', color = '';
                if (age <=12){
                    gen='GEN ALPHA'; icon='🍼'; desc='Generasi masa depan, penuh potensi!'; color='from-gray-50 to-gray-50';
                }
                else if (age <= 29) { 
                    gen = 'GEN Z'; icon = '📱'; desc = 'Digital native, kreatif, suka konten viral!'; color = 'from-purple-50 to-pink-50';
                } 
                else if (age <= 60) { 
                    gen = 'MILLENNIAL'; icon = '💼'; desc = 'Tech-savvy, work-life balance, suka kopi!'; color = 'from-blue-50 to-cyan-50';
                }
                else if (age <= 100) { 
                    gen = 'GEN X'; icon = '🌟'; desc = 'Pengalaman luas, bijaksana, stabil!'; color = 'from-green-50 to-teal-50';
                }
                
                genIcon.textContent = icon;
                genTitle.textContent = `Kamu ${gen}!`;
                genDesc.textContent = desc;
                genDisplay.className = `p-5 rounded-xl bg-gradient-to-r ${color} border border-gray-200`;
                genDisplay.classList.remove('hidden');
            } else {
                genDisplay.classList.add('hidden');
            }
        }
        
        // Event listeners
        ageInput.addEventListener('input', function(e) {
            const age = parseInt(e.target.value) || 21;
            ageSlider.value = age;
            updateGeneration(age);
        });
        
        ageSlider.addEventListener('input', function(e) {
            ageInput.value = e.target.value;
            updateGeneration(parseInt(e.target.value));
        });
        
        // Initialize
        updateGeneration(21);
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const age = parseInt(ageInput.value);
            if (age < 1 || age > 100) {
                e.preventDefault();
                alert('Umur harus antara 1-100 tahun ya!');
                ageInput.focus();
            }
        });
    </script>
</body>
</html>