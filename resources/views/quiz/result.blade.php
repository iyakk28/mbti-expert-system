<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hasil MBTI - {{ $session->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Define resultApp before Alpine initializes
        function resultApp() {
            return {
                contactMethod: 'whatsapp',
                contactAddress: '',
                sending: false,
                sent: false,
                successMessage: '',
                
                async sendResult() {
                    if (!this.contactAddress) {
                        alert('Masukkan nomor WhatsApp atau email dulu ya!');
                        return;
                    }
                    
                    this.sending = true;
                    
                    try {
                        const response = await fetch('{{ route("send.result", ["session" => $session->session_id]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                contact_method: this.contactMethod,
                                contact_address: this.contactMethod === 'whatsapp' 
                                    ? this.contactAddress 
                                    : this.contactAddress
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.successMessage = data.message;
                            this.sent = true;
                            alert('✅ ' + data.message);
                            
                            // Open WhatsApp if method is WhatsApp
                            if (this.contactMethod === 'whatsapp') {
                                setTimeout(() => {
                                    const message = `Halo! Ini hasil MBTI Adventure saya:\n\n🌟 Tipe: {{ $result['type'] }}\n🎯 Akurasi: {{ $result['purity'] }}%\n👤 Nama: {{ $session->name }}\n\n🔗 Link Lengkap: {{ $shareUrl }}`;
                                    const url = `https://wa.me/62${this.contactAddress}?text=${encodeURIComponent(message)}`;
                                    window.open(url, '_blank');
                                }, 1000);
                            }
                        } else {
                            alert('❌ Gagal mengirim: ' + (data.message || 'Coba lagi nanti'));
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('❌ Terjadi kesalahan jaringan. Coba lagi.');
                    }
                    
                    this.sending = false;
                },
                
                downloadPDFAndShare() {
                    console.log('Starting PDF generation...');
                    const element = document.querySelector('#pdf-content');
                    if (!element) {
                        alert('❌ Error: Tidak dapat menemukan konten untuk di-PDF. Coba refresh halaman.');
                        return;
                    }
                    console.log('Element found, generating PDF...');
                    const opt = {
                        margin: 10,
                        filename: `MBTI-{{ $session->name }}-{{ $result['type'] }}.pdf`,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, logging: true },
                        jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
                    };
                    try {
                        html2pdf().set(opt).from(element).toPdf().output('blob').then(blob => {
                            console.log('PDF generated, blob size:', blob.size);
                            const formData = new FormData();
                            formData.append('pdf', blob, `MBTI-{{ $session->name }}-{{ $result['type'] }}.pdf`);
                            formData.append('whatsapp_number', '{{ $session->contact_address ?? "" }}');
                            formData.append('session_id', '{{ $session->session_id }}');
                            console.log('Sending to server...');
                            fetch('{{ route("quiz.send-pdf-whatsapp") }}', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                }
                            })
                            .then(response => {
                                console.log('Response status:', response.status);
                                return response.json();
                            })
                            .then(data => {
                                console.log('Server response:', data);
                                if (data.success) {
                                    alert('✅ PDF berhasil diunduh dan dikirim ke WhatsApp!');
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = `MBTI-{{ $session->name }}-{{ $result['type'] }}.pdf`;
                                    a.click();
                                    window.URL.revokeObjectURL(url);
                                } else {
                                    alert('⚠️ PDF diunduh tapi gagal dikirim ke WA. Download manual: ' + (data.message || ''));
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = `MBTI-{{ $session->name }}-{{ $result['type'] }}.pdf`;
                                    a.click();
                                    window.URL.revokeObjectURL(url);
                                }
                            })
                            .catch(error => {
                                console.error('Fetch error:', error);
                                alert('❌ Gagal mengirim PDF. Download manual saja.\n\nError: ' + error.message);
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = `MBTI-{{ $session->name }}-{{ $result['type'] }}.pdf`;
                                a.click();
                                window.URL.revokeObjectURL(url);
                            });
                        }).catch(pdfError => {
                            console.error('PDF generation error:', pdfError);
                            alert('❌ Gagal membuat PDF: ' + pdfError.message);
                        });
                    } catch (error) {
                        console.error('Error in downloadPDFAndShare:', error);
                        alert('❌ Terjadi kesalahan: ' + error.message);
                    }
                },
                
                shareToWhatsApp() {
                    const text = `🔥 Baru aja cek MBTI Adventure! Aku tipe *{{ $result['type'] }}* dengan akurasi *{{ $result['purity'] }}%*! 🎯\n\nCoba kamu juga di: {{ $shareUrl }}`;
                    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
                },
                
                shareToInstagram() {
                    alert('📸 Screenshot halaman ini dan share di Instagram Story ya!');
                },
                
                shareToTwitter() {
                    const text = `Just discovered my MBTI type: {{ $result['type'] }}! 🎯 Check yours: {{ $shareUrl }} #MBTI #Personality`;
                    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`, '_blank');
                },
                
                copyLink() {
                    navigator.clipboard.writeText('{{ $shareUrl }}');
                    alert('✅ Link berhasil disalin ke clipboard!');
                },
                
                takeAgain() {
                    if (confirm('Mau isi quiz lagi dengan identitas baru?')) {
                        window.location.href = '{{ route("landing") }}';
                    }
                },
                
                initConfetti() {
                    const container = document.getElementById('confetti-container');
                    if (!container) return;
                    const colors = ['#8b5cf6', '#ec4899', '#3b82f6', '#10b981', '#f59e0b'];
                    for (let i = 0; i < 50; i++) {
                        const confetti = document.createElement('div');
                        confetti.className = 'confetti';
                        confetti.style.left = Math.random() * 100 + 'vw';
                        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                        confetti.style.animationDelay = Math.random() * 5 + 's';
                        confetti.style.width = Math.random() * 10 + 5 + 'px';
                        confetti.style.height = confetti.style.width;
                        container.appendChild(confetti);
                    }
                }
            };
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .type-gradient { 
            background: linear-gradient(135deg, 
                #8b5cf6 0%, #7c3aed 25%, 
                #ec4899 50%, #db2777 75%, 
                #8b5cf6 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #f0f;
            border-radius: 50%;
            animation: fall 5s linear infinite;
        }
        @keyframes fall {
            to { transform: translateY(100vh) rotate(360deg); }
        }
        .card-hover { 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        .purity-ring {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen text-white" 
      x-data="resultApp()"
      x-init="initConfetti()">
    
    <!-- Confetti Effect -->
    <div id="confetti-container"></div>
    
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Celebration Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center space-x-4 mb-6">
                <div class="text-4xl animate-bounce" style="animation-delay: 0s;">🎉</div>
                <div class="text-4xl animate-bounce" style="animation-delay: 0.2s;">✨</div>
                <div class="text-4xl animate-bounce" style="animation-delay: 0.4s;">🌟</div>
            </div>
            
            <h1 class="text-5xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-400">
                SELAMAT, {{ strtoupper($session->name) }}!
            </h1>
            <p class="text-xl text-gray-300">
                Petualangan MBTI {{ $session->age }} tahun-mu telah membuahkan hasil! 🎯
            </p>
        </div>

        <!-- MBTI Type Card -->
        <div class="type-gradient rounded-3xl p-8 shadow-2xl mb-12 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 text-9xl">{{ $result['type'][0] }}</div>
                <div class="absolute bottom-0 right-0 text-9xl">{{ $result['type'][3] }}</div>
            </div>
            
            <div class="relative z-10 text-center">
                <div class="text-sm font-semibold text-purple-200 mb-2">YOUR PERSONALITY TYPE IS</div>
                <div class="text-8xl font-black mb-4 text-white drop-shadow-lg">{{ $result['type'] }}</div>
                <div class="text-2xl font-bold text-white/90 mb-6">{{ $result['label'] }}</div>
                
                <!-- Purity Score -->
                <div class="inline-flex items-center justify-center space-x-4 bg-white/20 backdrop-blur-sm rounded-full px-6 py-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-400 rounded-full mr-2 purity-ring"></div>
                        <span class="font-semibold">Accuracy Score:</span>
                    </div>
                    <div class="text-2xl font-bold">{{ $result['purity'] }}%</div>
                </div>
            </div>
        </div>

        <!-- Results Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            
            <!-- Left Column -->
            <div class="space-y-8">
                <!-- Personality Traits -->
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 card-hover border border-gray-700">
                    <h3 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="mr-3">🎭</span> KEPRIBADIAN UTAMAMU
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($result['strengths'] as $strength)
                        <div class="bg-gradient-to-r from-purple-900/50 to-pink-900/50 rounded-xl p-4">
                            <div class="text-lg font-semibold mb-1">{{ $strength }}</div>
                            <div class="text-sm text-gray-400">Strength</div>
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Cognitive Functions -->
                    <div class="mt-8 pt-6 border-t border-gray-700">
                        <h4 class="text-lg font-bold mb-4 text-gray-300">Fungsi Kognitif:</h4>
                        <div class="space-y-3">
                            @foreach($result['cognitive_functions'] as $key => $function)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">{{ ucfirst($key) }}:</span>
                                <span class="font-semibold text-purple-300">{{ $function }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Career Matches -->
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 card-hover border border-gray-700">
                    <h3 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="mr-3">💼</span> KARIR YANG COCOK
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        @foreach($result['careers'] as $career)
                        <span class="px-4 py-2 bg-gradient-to-r from-blue-900/40 to-cyan-900/40 rounded-full border border-blue-700/30">
                            {{ $career }}
                        </span>
                        @endforeach
                    </div>
                    <p class="text-gray-400 text-sm mt-4">
                        💡 Tips: Fokus pada bidang yang sesuai dengan kekuatan alami kamu!
                    </p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Relationship Matches -->
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 card-hover border border-gray-700">
                    <h3 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="mr-3">💖</span> PASANGAN ALAMI MBTI
                    </h3>
                    <div class="space-y-4">
                        @foreach($result['relationship_matches'] as $match)
                        <div class="bg-gradient-to-r from-pink-900/30 to-rose-900/30 rounded-xl p-4">
                            <div class="flex justify-between items-center">
                                <div class="text-3xl font-bold text-pink-300">{{ $match['type'] }}</div>
                                <div class="text-4xl">✨</div>
                            </div>
                            <p class="text-gray-300 mt-2">{{ $match['reason'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Celebrities & Characters -->
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 card-hover border border-gray-700">
                    <h3 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="mr-3">⭐</span> ARTIS & KARAKTER SERUPA
                    </h3>
                    
                    <!-- Artists -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-3 text-gray-300">Artis dengan MBTI Sama:</h4>
                        <div class="space-y-3">
                            @foreach($result['celebrities']['artists'] as $artist)
                            <div class="flex items-center p-3 bg-gray-900/50 rounded-lg">
                                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-xl mr-4">
                                    {{ substr($artist, 0, 1) }}
                                </div>
                                <div class="font-medium text-lg">{{ $artist }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Characters -->
                    <div>
                        <h4 class="text-lg font-semibold mb-3 text-gray-300">Karakter Fiksi:</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($result['celebrities']['characters'] as $character)
                            <span class="px-4 py-2 bg-gradient-to-r from-purple-900/40 to-violet-900/40 rounded-lg border border-purple-700/30">
                                {{ $character }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth Tips -->
        <div class="bg-gradient-to-r from-emerald-900/30 to-teal-900/30 rounded-2xl p-8 mb-12 border border-emerald-700/30">
            <h3 class="text-2xl font-bold mb-6 flex items-center">
                <span class="mr-3">🌱</span> TIPS PENGEMBANGAN DIRI
            </h3>
            <div class="bg-black/30 rounded-xl p-6">
                <p class="text-lg leading-relaxed">{{ $result['growth_tips'] }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl p-8 border border-gray-700 mb-12">
            <h3 class="text-2xl font-bold mb-8 text-center">📬 MARI BAGIKAN HASIL INI!</h3>
            
            <!-- WhatsApp/Email Form -->
            <div x-show="!sent" x-transition class="space-y-6">
                <!-- Method Selection -->
                <div class="flex justify-center space-x-4">
                    <button @click="contactMethod = 'whatsapp'" 
                            :class="contactMethod === 'whatsapp' 
                                    ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white' 
                                    : 'bg-gray-700 text-gray-300'"
                            class="px-8 py-4 rounded-xl font-bold flex items-center transition-all hover:scale-105">
                        <span class="mr-3 text-2xl">💚</span>
                        WhatsApp
                    </button>
                    
                    <button @click="contactMethod = 'email'" 
                            :class="contactMethod === 'email' 
                                    ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white' 
                                    : 'bg-gray-700 text-gray-300'"
                            class="px-8 py-4 rounded-xl font-bold flex items-center transition-all hover:scale-105">
                        <span class="mr-3 text-2xl">📧</span>
                        Email
                    </button>
                </div>

                <!-- Contact Input -->
                <div x-show="contactMethod === 'whatsapp'" class="max-w-md mx-auto">
                    <label class="block text-gray-300 mb-3 font-semibold">
                        Nomor WhatsApp (contoh: 81234567890)
                    </label>
                    <div class="flex">
                        <div class="bg-gray-700 px-4 py-4 rounded-l-xl border border-r-0 border-gray-600 text-gray-400">
                            +62
                        </div>
                        <input type="tel" 
                               x-model="contactAddress"
                               placeholder="81234567890"
                               class="flex-1 px-4 py-4 bg-gray-800 border border-gray-600 rounded-r-xl focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/30"
                               required>
                    </div>
                </div>

                <div x-show="contactMethod === 'email'" class="max-w-md mx-auto" style="display: none;">
                    <label class="block text-gray-300 mb-3 font-semibold">
                        Alamat Email
                    </label>
                    <input type="email" 
                           x-model="contactAddress"
                           placeholder="nama@email.com"
                           class="w-full px-4 py-4 bg-gray-800 border border-gray-600 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                           required>
                </div>

                <!-- Send Button -->
                <div class="text-center">
                    <button @click="sendResult" 
                            :disabled="sending || !contactAddress"
                            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-4 px-12 rounded-xl text-lg hover:shadow-2xl transition-all hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!sending">
                            <span class="flex items-center justify-center">
                                <span x-text="contactMethod === 'whatsapp' ? '💚' : '📧'"></span>
                                <span class="ml-2">Kirim ke </span>
                                <span x-text="contactMethod === 'whatsapp' ? 'WhatsApp' : 'Email'" class="ml-1"></span>
                            </span>
                        </template>
                        <template x-if="sending">
                            <span class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengirim...
                            </span>
                        </template>
                    </button>
                </div>
            </div>

            <!-- Success Message -->
            <div x-show="sent" x-transition class="text-center">
                <div class="text-5xl mb-4">🎉</div>
                <h4 class="text-2xl font-bold mb-2">BERHASIL DIKIRIM!</h4>
                <p class="text-lg text-gray-300 mb-6" x-text="successMessage"></p>
                <p class="text-gray-400">Cek <span x-text="contactMethod === 'whatsapp' ? 'WhatsApp' : 'Email'"></span> kamu ya!</p>
            </div>
        </div>

        <!-- Share & Actions -->
        <div class="text-center space-y-8">
            <div>
                <h4 class="text-xl font-bold mb-4">📞 Hubungi Kami</h4>
                <div class="flex justify-center space-x-4">
                    <a href="https://wa.me/6282286908467" target="_blank" class="bg-green-600 text-white p-4 rounded-full hover:bg-green-700 transition-all hover:scale-110">
                        <span class="text-2xl">💚</span>
                    </a>
                    <a href="https://instagram.com/_biawak._" target="_blank" class="bg-pink-600 text-white p-4 rounded-full hover:bg-pink-700 transition-all hover:scale-110">
                        <span class="text-2xl">📸</span>
                    </a>
                    <a href="https://tiktok.com/@gakwaras5.0" target="_blank" class="bg-black/40 border border-gray-500 text-white p-4 rounded-full hover:bg-black/60 transition-all hover:scale-110">
                        <span class="text-2xl">🎵</span>
                    </a>
                </div>
            </div>

            <!-- Final Actions -->
            <div class="pt-6 border-t border-gray-700">
                <a href="{{ route('landing') }}" 
                   class="inline-block bg-gradient-to-r from-gray-700 to-gray-800 text-white font-bold py-4 px-12 rounded-xl hover:shadow-lg transition-all hover:scale-105 mr-4 text-lg">
                    🏠 Kembali ke Home
                </a>
                <a href="{{ route('quiz.download-pdf', ['session' => $session->session_id]) }}" 
                   class="inline-block bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold py-4 px-12 rounded-xl hover:shadow-lg transition-all hover:scale-105 text-lg">
                    📄 Download PDF
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-12 pt-8 border-t border-gray-800">
            <p>✨ MBTI Adventure - Sistem Pakar Kepribadian ✨</p>
            <p class="mt-1">Hasil berdasarkan teori MBTI & fungsi kognitif | Valid untuk Gen Z & Millennials</p>
            <p class="mt-2 text-gray-600">Session ID: {{ substr($session->session_id, 0, 8) }}... | Tanggal: {{ $session->created_at->format('d/m/Y') }}</p>
            <p>Since jan 2026 by ~~iyakk✌️</p>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in-up');
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.card-hover').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>