<?php

namespace App\Services;

class MBTICalculator
{
    private $typeData;
    
    public function __construct()
    {
        $this->typeData = $this->loadTypeData();
    }
    
  public function calculate(array $answers)
{
    // Initialize scores dengan weighting
    $scores = [
        'E' => 0, 'I' => 0, 
        'S' => 0, 'N' => 0, 
        'T' => 0, 'F' => 0, 
        'J' => 0, 'P' => 0
    ];
    
    $totalQuestions = count($answers);
    
    // Calculate weighted scores
    foreach ($answers as $answer) {
        if (isset($answer['traits'])) {
            foreach ($answer['traits'] as $trait => $value) {
                if (isset($scores[$trait])) {
                    // Normalize value (0-4) to percentage
                    $normalizedValue = ($value / 4) * 100;
                    $scores[$trait] += $normalizedValue;
                }
            }
        }
    }
    
    // Average scores
    $questionsPerDimension = $totalQuestions / 4; // 60/4 = 15
    foreach ($scores as $trait => $score) {
        $scores[$trait] = round($score / $questionsPerDimension, 1);
    }
    
    // Determine MBTI type dengan threshold
    $type = '';
    
    // E vs I dengan threshold 10%
    $eiDiff = $scores['E'] - $scores['I'];
    $type .= (abs($eiDiff) < 10) ? 'X' : ($eiDiff > 0 ? 'E' : 'I');
    
    // S vs N
    $snDiff = $scores['S'] - $scores['N'];
    $type .= (abs($snDiff) < 10) ? 'X' : ($snDiff > 0 ? 'S' : 'N');
    
    // T vs F
    $tfDiff = $scores['T'] - $scores['F'];
    $type .= (abs($tfDiff) < 10) ? 'X' : ($tfDiff > 0 ? 'T' : 'F');
    
    // J vs P
    $jpDiff = $scores['J'] - $scores['P'];
    $type .= (abs($jpDiff) < 10) ? 'X' : ($jpDiff > 0 ? 'J' : 'P');
    
    // Jika ada X, tentukan berdasarkan skor tertinggi
    if (strpos($type, 'X') !== false) {
        $type = $this->resolveAmbiguousType($scores, $type);
    }
    
    // Calculate purity score
    $purity = $this->calculatePurity($scores);
    
    // Get type information
    $typeInfo = $this->typeData[$type] ?? $this->getDefaultTypeData($type);
    
    return array_merge([
        'type' => $type,
        'scores' => $scores,
        'purity' => $purity,
        'label' => $this->getLabel($purity, $type),
        'type_development' => $this->getTypeDevelopment($scores)
    ], $typeInfo);
}

private function resolveAmbiguousType($scores, $ambiguousType)
{
    $finalType = '';
    $pairs = [
        ['E', 'I'], ['S', 'N'], ['T', 'F'], ['J', 'P']
    ];
    
    $typeArray = str_split($ambiguousType);
    
    foreach ($typeArray as $index => $char) {
        if ($char === 'X') {
            $pair = $pairs[$index];
            $finalType .= ($scores[$pair[0]] > $scores[$pair[1]]) ? $pair[0] : $pair[1];
        } else {
            $finalType .= $char;
        }
    }
    
    return $finalType;
}

private function getTypeDevelopment($scores)
{
    $development = [];
    
    $pairs = [
        'Extraversion' => ['E', 'I'],
        'Intuition' => ['N', 'S'],
        'Feeling' => ['F', 'T'],
        'Perceiving' => ['P', 'J']
    ];
    
    foreach ($pairs as $traitName => $pair) {
        $dominant = $scores[$pair[0]] > $scores[$pair[1]] ? $pair[0] : $pair[1];
        $difference = abs($scores[$pair[0]] - $scores[$pair[1]]);
        
        if ($difference < 15) {
            $development[] = "Balanced {$traitName}";
        } elseif ($difference < 30) {
            $development[] = "Moderate {$traitName} ({$dominant})";
        } else {
            $development[] = "Strong {$traitName} ({$dominant})";
        }
    }
    
    return $development;
}
    
    private function calculatePurity($scores)
    {
        $pairs = [
            ['E', 'I'],
            ['S', 'N'],
            ['T', 'F'],
            ['J', 'P']
        ];
        
        $totalDifference = 0;
        foreach ($pairs as $pair) {
            $totalDifference += abs($scores[$pair[0]] - $scores[$pair[1]]);
        }
        
        // Calculate percentage (max difference is 40)
        $purity = min(100, ($totalDifference / 40) * 100);
        return round($purity, 1);
    }
    
    private function getLabel($purity, $type)
    {
        if ($purity >= 80) return "Murni {$type}";
        if ($purity >= 60) return "Kuat {$type}";
        if ($purity >= 40) return "Cenderung {$type}";
        return "Seimbang / Hybrid";
    }
    
  private function loadTypeData()
{
    // Complete data for 16 personality types
    return [
        'ENFP' => $this->getENFPData(),
        'ENFJ' => $this->getENFJData(),
        'ENTP' => $this->getENTPData(),
        'ENTJ' => $this->getENTJData(),
        'ESFP' => $this->getESFPData(),
        'ESFJ' => $this->getESFJData(),
        'ESTP' => $this->getESTPData(),
        'ESTJ' => $this->getESTJData(),
        'INFP' => $this->getINFPData(),
        'INFJ' => $this->getINFJData(),
        'INTP' => $this->getINTPData(),
        'INTJ' => $this->getINTJData(),
        'ISFP' => $this->getISFPData(),
        'ISFJ' => $this->getISFJData(),
        'ISTP' => $this->getISTPData(),
        'ISTJ' => $this->getISTJData(),
    ];
}
    
    private function getENFPData()
    {
        return [
            'strengths' => ['Energik', 'Kreatif', 'Empati Tinggi', 'Komunikator Ulung', 'Optimis'],
            'weaknesses' => ['Mudah Terdistraksi', 'Kurang Terorganisir', 'Overthinking'],
            'careers' => ['Marketing', 'Psikolog', 'Event Planner', 'Content Creator', 'Actor', 'Teacher'],
            'relationship_matches' => [
                ['type' => 'INFJ', 'reason' => 'Koneksi emosional yang dalam'],
                ['type' => 'INTJ', 'reason' => 'Keseimbangan idealis-praktis']
            ],
            'celebrities' => [
                'artists' => ['Will Smith', 'Robin Williams', 'Ellen DeGeneres', 'Robert Downey Jr.'],
                'characters' => ['Joy (Inside Out)', 'Peter Pan', 'Maui (Moana)', 'Spider-Man (Tom Holland)']
            ],
            'cognitive_functions' => [
                'dominant' => 'Ne (Extraverted Intuition)',
                'auxiliary' => 'Fi (Introverted Feeling)',
                'tertiary' => 'Te (Extraverted Thinking)',
                'inferior' => 'Si (Introverted Sensing)'
            ],
            'growth_tips' => 'Fokus pada follow-through, belajar mengatakan "tidak", kembangkan skill organisasi, praktik mindfulness.'
        ];
    }
    
    private function getINTJData()
    {
        return [
            'strengths' => ['Strategis', 'Analitis', 'Visioner', 'Independen', 'Determinasi Tinggi'],
            'weaknesses' => ['Terlalu Kritis', 'Sulit Mengekspresikan Emosi', 'Perfeksionis'],
            'careers' => ['Data Scientist', 'System Architect', 'Researcher', 'Strategic Planner', 'Software Engineer'],
            'relationship_matches' => [
                ['type' => 'ENFP', 'reason' => 'Kreativitas & energi positif'],
                ['type' => 'ENTP', 'reason' => 'Diskusi intelektual yang stimulatif']
            ],
            'celebrities' => [
                'artists' => ['Elon Musk', 'Michelle Obama', 'Christopher Nolan', 'Mark Zuckerberg'],
                'characters' => ['Sherlock Holmes', 'Wednesday Addams', 'Thanos', 'Katniss Everdeen']
            ],
            'cognitive_functions' => [
                'dominant' => 'Ni (Introverted Intuition)',
                'auxiliary' => 'Te (Extraverted Thinking)',
                'tertiary' => 'Fi (Introverted Feeling)',
                'inferior' => 'Se (Extraverted Sensing)'
            ],
            'growth_tips' => 'Belajar mengekspresikan emosi, praktik fleksibilitas, hargai perspektif orang lain, kelola ekspektasi.'
        ];
    }
        private function getENFJData()
    {
        return [
            'strengths' => ['Empati Tinggi', 'Pemimpin Alami', 'Inspiratif', 'Harmonis'],
            'weaknesses' => ['Terlalu Idealistis', 'Mudah Kecewa', 'Sulit Mengatakan Tidak'],
            'careers' => ['Guru', 'Konselor', 'HR Manager', 'Event Coordinator', 'Social Worker'],
            'relationship_matches' => [
                ['type' => 'INFP', 'reason' => 'Koneksi emosional yang dalam'],
                ['type' => 'ISFP', 'reason' => 'Keseimbangan energi']
            ],
            'celebrities' => [
                'artists' => ['Oprah Winfrey', 'Barack Obama', 'Beyoncé', 'John Cena'],
                'characters' => ['Mufasa (Lion King)', 'Leslie Knope (Parks & Rec)', 'Captain America']
            ],
            'cognitive_functions' => [
                'dominant' => 'Fe (Extraverted Feeling)',
                'auxiliary' => 'Ni (Introverted Intuition)',
                'tertiary' => 'Se (Extraverted Sensing)',
                'inferior' => 'Ti (Introverted Thinking)'
            ],
            'growth_tips' => 'Belajar menerima ketidaksempurnaan, beri waktu untuk diri sendiri, jangan terlalu mengorbankan kebutuhan pribadi.'
        ];
    }
    
    private function getENTPData()
    {
        return [
            'strengths' => ['Kreatif', 'Debater Ulung', 'Cepat Beradaptasi', 'Visioner'],
            'weaknesses' => ['Argumentatif', 'Tidak Sabaran', 'Sulit Follow Through'],
            'careers' => ['Entrepreneur', 'Pengacara', 'Marketing Strategist', 'Inventor', 'Consultant'],
            'relationship_matches' => [
                ['type' => 'INFJ', 'reason' => 'Diskusi intelektual yang dalam'],
                ['type' => 'INTJ', 'reason' => 'Berbagi visi masa depan']
            ],
            'celebrities' => [
                'artists' => ['Thomas Edison', 'Leonardo da Vinci', 'Mark Twain', 'Robert Downey Jr.'],
                'characters' => ['Iron Man', 'The Joker', 'Jack Sparrow', 'Deadpool']
            ],
            'cognitive_functions' => [
                'dominant' => 'Ne (Extraverted Intuition)',
                'auxiliary' => 'Ti (Introverted Thinking)',
                'tertiary' => 'Fe (Extraverted Feeling)',
                'inferior' => 'Si (Introverted Sensing)'
            ],
            'growth_tips' => 'Fokus pada penyelesaian proyek, pertimbangkan perasaan orang lain, kembangkan konsistensi.'
        ];
    }
    
    private function getENTJData()
    {
        return [
            'strengths' => ['Pemimpin Visioner', 'Strategis', 'Efisien', 'Tegas'],
            'weaknesses' => ['Dominan', 'Tidak Sabaran', 'Kurang Sabar dengan Inefisiensi'],
            'careers' => ['CEO', 'Manajer Proyek', 'Hakim', 'Militer', 'Politikus'],
            'relationship_matches' => [
                ['type' => 'INTP', 'reason' => 'Keseimbangan logika & kreativitas'],
                ['type' => 'INFP', 'reason' => 'Melunakkan sisi keras']
            ],
            'celebrities' => [
                'artists' => ['Steve Jobs', 'Gordon Ramsay', 'Margaret Thatcher', 'Franklin D. Roosevelt'],
                'characters' => ['Professor X (X-Men)', 'Miranda Priestly (Devil Wears Prada)', 'Tywin Lannister']
            ],
            'cognitive_functions' => [
                'dominant' => 'Te (Extraverted Thinking)',
                'auxiliary' => 'Ni (Introverted Intuition)',
                'tertiary' => 'Se (Extraverted Sensing)',
                'inferior' => 'Fi (Introverted Feeling)'
            ],
            'growth_tips' => 'Belajar mendengarkan, hargai input orang lain, ekspresikan emosi dengan sehat.'
        ];
    }
    
    private function getESFPData()
    {
        return [
            'strengths' => ['Energik', 'Penyemangat', 'Praktis', 'Spontan'],
            'weaknesses' => ['Tidak Suka Teori', 'Impulsif', 'Kurang Perencanaan Jangka Panjang'],
            'careers' => ['Entertainer', 'Tour Guide', 'Sales', 'Fashion Designer', 'Bartender'],
            'relationship_matches' => [
                ['type' => 'ISFJ', 'reason' => 'Keseimbangan sosial & perhatian'],
                ['type' => 'ISTJ', 'reason' => 'Stabilitas & keandalan']
            ],
            'celebrities' => [
                'artists' => ['Elvis Presley', 'Adele', 'Marilyn Monroe', 'Jamie Oliver'],
                'characters' => ['Joey Tribbiani (Friends)', 'Starlord (Guardians)', 'Ariel (Little Mermaid)']
            ],
            'cognitive_functions' => [
                'dominant' => 'Se (Extraverted Sensing)',
                'auxiliary' => 'Fi (Introverted Feeling)',
                'tertiary' => 'Te (Extraverted Thinking)',
                'inferior' => 'Ni (Introverted Intuition)'
            ],
            'growth_tips' => 'Belajar planning jangka panjang, pertimbangkan konsekuensi, kembangkan disiplin.'
        ];
    }
    
    private function getESFJData()
    {
        return [
            'strengths' => ['Peduli', 'Bertanggung Jawab', 'Kooperatif', 'Praktis'],
            'weaknesses' => ['Sensitif terhadap Kritik', 'Takut Konflik', 'Sulit dengan Perubahan'],
            'careers' => ['Perawat', 'Guru', 'Administrator', 'Customer Service', 'Event Planner'],
            'relationship_matches' => [
                ['type' => 'ISFP', 'reason' => 'Keseimbangan sosial & artistik'],
                ['type' => 'ISTP', 'reason' => 'Keseimbangan emosi & logika']
            ],
            'celebrities' => [
                'artists' => ['Taylor Swift', 'Bill Clinton', 'Jennifer Garner', 'Sally Field'],
                'characters' => ['Monica Geller (Friends)', 'Samwise Gamgee (LOTR)', 'Leslie Knope']
            ],
            'cognitive_functions' => [
                'dominant' => 'Fe (Extraverted Feeling)',
                'auxiliary' => 'Si (Introverted Sensing)',
                'tertiary' => 'Ne (Extraverted Intuition)',
                'inferior' => 'Ti (Introverted Thinking)'
            ],
            'growth_tips' => 'Belajar mengatakan tidak, terima perubahan, hargai kebutuhan diri sendiri.'
        ];
    }
    
    private function getESTPData()
    {
        return [
            'strengths' => ['Spontan', 'Praktis', 'Cepat Tanggap', 'Pemberani'],
            'weaknesses' => ['Tidak Sabaran', 'Sulit dengan Rutinitas', 'Impulsif'],
            'careers' => ['Atlet', 'Polisi', 'Sales', 'Entrepreneur', 'Paramedis'],
            'relationship_matches' => [
                ['type' => 'ISFJ', 'reason' => 'Keseimbangan spontan & stabil'],
                ['type' => 'ISTJ', 'reason' => 'Grounding & struktur']
            ],
            'celebrities' => [
                'artists' => ['Ernest Hemingway', 'Madonna', 'Jack Nicholson', 'Angelina Jolie'],
                'characters' => ['James Bond', 'Han Solo', 'Arya Stark', 'Mulan']
            ],
            'cognitive_functions' => [
                'dominant' => 'Se (Extraverted Sensing)',
                'auxiliary' => 'Ti (Introverted Thinking)',
                'tertiary' => 'Fe (Extraverted Feeling)',
                'inferior' => 'Ni (Introverted Intuition)'
            ],
            'growth_tips' => 'Pertimbangkan konsekuensi jangka panjang, kembangkan kesabaran, rencanakan masa depan.'
        ];
    }
    
    private function getESTJData()
    {
        return [
            'strengths' => ['Terorganisir', 'Bertanggung Jawab', 'Praktis', 'Jujur'],
            'weaknesses' => ['Kaku', 'Kurang Fleksibel', 'Tidak Sabar dengan Ketidakefisienan'],
            'careers' => ['Manajer', 'Akuntan', 'Polisi', 'Hakim', 'Project Manager'],
            'relationship_matches' => [
                ['type' => 'ISFP', 'reason' => 'Keseimbangan struktur & kreativitas'],
                ['type' => 'INFP', 'reason' => 'Melunakkan sisi kaku']
            ],
            'celebrities' => [
                'artists' => ['Judge Judy', 'George W. Bush', 'James Monroe', 'Lucy (Peanuts)'],
                'characters' => ['Hermione Granger (Harry Potter)', 'Dwight Schrute (The Office)', 'Captain Holt (Brooklyn 99)']
            ],
            'cognitive_functions' => [
                'dominant' => 'Te (Extraverted Thinking)',
                'auxiliary' => 'Si (Introverted Sensing)',
                'tertiary' => 'Ne (Extraverted Intuition)',
                'inferior' => 'Fi (Introverted Feeling)'
            ],
            'growth_tips' => 'Belajar fleksibel, pertimbangkan perasaan orang lain, terima pendapat berbeda.'
        ];
    }
    
    private function getINFPData()
    {
        return [
            'strengths' => ['Idealistis', 'Empati Tinggi', 'Kreatif', 'Setia pada Nilai'],
            'weaknesses' => ['Terlalu Sensitif', 'Tidak Praktis', 'Sulit dengan Konflik'],
            'careers' => ['Penulis', 'Psikolog', 'Art Therapist', 'Librarian', 'Humanitarian Worker'],
            'relationship_matches' => [
                ['type' => 'ENFJ', 'reason' => 'Koneksi emosional & inspirasi'],
                ['type' => 'ENTJ', 'reason' => 'Keseimbangan idealis & praktis']
            ],
            'celebrities' => [
                'artists' => ['J.R.R. Tolkien', 'William Shakespeare', 'Johnny Depp', 'Princess Diana'],
                'characters' => ['Luna Lovegood (Harry Potter)', 'Frodo Baggins (LOTR)', 'Amélie Poulain']
            ],
            'cognitive_functions' => [
                'dominant' => 'Fi (Introverted Feeling)',
                'auxiliary' => 'Ne (Extraverted Intuition)',
                'tertiary' => 'Si (Introverted Sensing)',
                'inferior' => 'Te (Extraverted Thinking)'
            ],
            'growth_tips' => 'Belajar lebih praktis, hadapi konflik dengan sehat, set boundaries dengan orang lain.'
        ];
    }
    
    private function getINFJData()
    {
        return [
            'strengths' => ['Visioner', 'Intuitif', 'Idealistis', 'Konsisten dengan Nilai'],
            'weaknesses' => ['Perfeksionis', 'Sulit Membuka Diri', 'Mudah Terluka'],
            'careers' => ['Psikolog', 'Konselor', 'Penulis', 'Arsitek', 'Spiritual Advisor'],
            'relationship_matches' => [
                ['type' => 'ENFP', 'reason' => 'Koneksi spiritual yang dalam'],
                ['type' => 'ENTP', 'reason' => 'Diskusi filosofis yang stimulatif']
            ],
            'celebrities' => [
                'artists' => ['Carl Jung', 'Nelson Mandela', 'Mother Teresa', 'Lady Gaga'],
                'characters' => ['Gandalf (LOTR)', 'Yoda (Star Wars)', 'Doctor Strange', 'Dumbledore']
            ],
            'cognitive_functions' => [
                'dominant' => 'Ni (Introverted Intuition)',
                'auxiliary' => 'Fe (Extraverted Feeling)',
                'tertiary' => 'Ti (Introverted Thinking)',
                'inferior' => 'Se (Extraverted Sensing)'
            ],
            'growth_tips' => 'Belajar terbuka dengan orang lain, terima ketidaksempurnaan, praktik self-care.'
        ];
    }
    
    private function getINTPData()
    {
        return [
            'strengths' => ['Analitis', 'Inovatif', 'Objektif', 'Pemikir Mendalam'],
            'weaknesses' => ['Tidak Praktis', 'Sulit Mengekspresikan Emosi', 'Cenderung Menunda'],
            'careers' => ['Ilmuwan', 'Programmer', 'Filsuf', 'Matematikawan', 'Research Analyst'],
            'relationship_matches' => [
                ['type' => 'ENTJ', 'reason' => 'Keseimbangan logika & eksekusi'],
                ['type' => 'ENFJ', 'reason' => 'Melengkapi sisi emosional']
            ],
            'celebrities' => [
                'artists' => ['Albert Einstein', 'Bill Gates', 'Socrates', 'Isaac Newton'],
                'characters' => ['Sherlock Holmes', 'The Doctor (Doctor Who)', 'Spock (Star Trek)', 'L (Death Note)']
            ],
            'cognitive_functions' => [
                'dominant' => 'Ti (Introverted Thinking)',
                'auxiliary' => 'Ne (Extraverted Intuition)',
                'tertiary' => 'Si (Introverted Sensing)',
                'inferior' => 'Fe (Extraverted Feeling)'
            ],
            'growth_tips' => 'Praktikkan ekspresi emosi, kembangkan disiplin, terhubung dengan orang lain.'
        ];
    }
    
    private function getISFPData()
    {
        return [
            'strengths' => ['Artistik', 'Sensitif', 'Fleksibel', 'Setia'],
            'weaknesses' => ['Menghindari Konflik', 'Tidak Suka Teori', 'Sulit dengan Kritik'],
            'careers' => ['Artis', 'Musisi', 'Fotografer', 'Florist', 'Fashion Designer'],
            'relationship_matches' => [
                ['type' => 'ESFJ', 'reason' => 'Keseimbangan artistik & sosial'],
                ['type' => 'ESTJ', 'reason' => 'Stabilitas & keandalan']
            ],
            'celebrities' => [
                'artists' => ['Michael Jackson', 'David Bowie', 'Prince', 'Frida Kahlo'],
                'characters' => ['Belle (Beauty & Beast)', 'Mulan', 'Bambi', 'Neytiri (Avatar)']
            ],
            'cognitive_functions' => [
                'dominant' => 'Fi (Introverted Feeling)',
                'auxiliary' => 'Se (Extraverted Sensing)',
                'tertiary' => 'Ni (Introverted Intuition)',
                'inferior' => 'Te (Extraverted Thinking)'
            ],
            'growth_tips' => 'Belajar menghadapi konflik, ekspresikan pendapat, kembangkan planning jangka panjang.'
        ];
    }
    
    private function getISFJData()
    {
        return [
            'strengths' => ['Setia', 'Perhatian', 'Bertanggung Jawab', 'Praktis'],
            'weaknesses' => ['Takut Perubahan', 'Sulit Mengatakan Tidak', 'Menghindari Konflik'],
            'careers' => ['Perawat', 'Guru', 'Librarian', 'Administrator', 'Social Worker'],
            'relationship_matches' => [
                ['type' => 'ESFP', 'reason' => 'Keseimbangan perhatian & spontanitas'],
                ['type' => 'ESTP', 'reason' => 'Grounding & aksi']
            ],
            'celebrities' => [
                'artists' => ['Kate Middleton', 'Rosa Parks', 'Dr. Watson (Sherlock Holmes)', 'Selena Gomez'],
                'characters' => ['Samwise Gamgee (LOTR)', 'Cinderella', 'Bilbo Baggins', 'Peggy Carter']
            ],
            'cognitive_functions' => [
                'dominant' => 'Si (Introverted Sensing)',
                'auxiliary' => 'Fe (Extraverted Feeling)',
                'tertiary' => 'Ti (Introverted Thinking)',
                'inferior' => 'Ne (Extraverted Intuition)'
            ],
            'growth_tips' => 'Belajar menerima perubahan, set boundaries, ekspresikan kebutuhan pribadi.'
        ];
    }
    
    private function getISTPData()
    {
        return [
            'strengths' => ['Praktis', 'Logis', 'Cepat Beradaptasi', 'Problem Solver'],
            'weaknesses' => ['Tidak Sabaran', 'Tidak Suka Komitmen Panjang', 'Sulit Mengekspresikan Emosi'],
            'careers' => ['Mekanik', 'Pilot', 'Atlet Ekstrem', 'Forensic Scientist', 'Engineer'],
            'relationship_matches' => [
                ['type' => 'ESFJ', 'reason' => 'Keseimbangan logika & emosi'],
                ['type' => 'ESTJ', 'reason' => 'Berbagi nilai praktis']
            ],
            'celebrities' => [
                'artists' => ['Clint Eastwood', 'Tom Cruise', 'Bear Grylls', 'Steve McQueen'],
                'characters' => ['Indiana Jones', 'James Bond', 'Katniss Everdeen', 'Arya Stark']
            ],
            'cognitive_functions' => [
                'dominant' => 'Ti (Introverted Thinking)',
                'auxiliary' => 'Se (Extraverted Sensing)',
                'tertiary' => 'Ni (Introverted Intuition)',
                'inferior' => 'Fe (Extraverted Feeling)'
            ],
            'growth_tips' => 'Kembangkan ekspresi emosi, pertimbangkan komitmen jangka panjang, hargai hubungan.'
        ];
    }
    
    private function getISTJData()
    {
        return [
            'strengths' => ['Bertanggung Jawab', 'Logis', 'Praktis', 'Terorganisir'],
            'weaknesses' => ['Kaku', 'Tidak Fleksibel', 'Sulit dengan Perubahan Mendadak'],
            'careers' => ['Akuntan', 'Pustakawan', 'Manajer', 'Polisi', 'Auditor'],
            'relationship_matches' => [
                ['type' => 'ESFP', 'reason' => 'Keseimbangan serius & fun'],
                ['type' => 'ENFP', 'reason' => 'Melunakkan sisi rigid']
            ],
            'celebrities' => [
                'artists' => ['George Washington', 'Warren Buffett', 'Natalie Portman', 'Queen Elizabeth II'],
                'characters' => ['Hermione Granger (Harry Potter)', 'Captain America', 'Mr. Darcy (Pride & Prejudice)']
            ],
            'cognitive_functions' => [
                'dominant' => 'Si (Introverted Sensing)',
                'auxiliary' => 'Te (Extraverted Thinking)',
                'tertiary' => 'Fi (Introverted Feeling)',
                'inferior' => 'Ne (Extraverted Intuition)'
            ],
            'growth_tips' => 'Belajar fleksibel, terima perubahan, eksplorasi kreativitas, pertimbangkan kemungkinan baru.'
        ];
    }
    

    
    private function getDefaultTypeData($type)
    {
        return [
            'strengths' => ['Adaptif', 'Analitis', 'Kreatif'],
            'weaknesses' => ['Terkadang ragu-ragu', 'Perlu waktu sendiri'],
            'careers' => ['Manager', 'Consultant', 'Analyst'],
            'relationship_matches' => [
                ['type' => 'ENFP', 'reason' => 'Keseimbangan energi'],
                ['type' => 'ISTJ', 'reason' => 'Stabilitas']
            ],
            'celebrities' => [
                'artists' => ['Unknown'],
                'characters' => ['Unknown']
            ],
            'cognitive_functions' => [
                'dominant' => 'Unknown',
                'auxiliary' => 'Unknown',
                'tertiary' => 'Unknown',
                'inferior' => 'Unknown'
            ],
            'growth_tips' => 'Eksplorasi diri lebih dalam, temukan passion, kembangkan skill komunikasi.'
        ];
    }
}