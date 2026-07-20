<?php

namespace Database\Seeders;

use App\Models\Lomba;
use App\Models\User;
use App\Models\Partner;
use App\Models\TimelineEvent;
use App\Models\FaqItem;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ifest.com'],
            [
                'name' => 'Admin I-FEST',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        $lombas = [
            [
                'kode' => 'NAT-01',
                'title' => 'Competitive Programming',
                'scale' => 'Nasional',
                'tagline' => 'ALGORITMA & PROBLEM SOLVING',
                'fee' => 'Rp 50.000 / Tim',
                'fee_gelombang_1' => 'Rp 40.000 / Tim',
                'fee_gelombang_2' => 'Rp 50.000 / Tim',
                'target' => 'Terbuka untuk Umum (Usia 16-25 Tahun)',
                'team_requirements' => 'Tim (2-3 Orang)',
                'languages' => 'Semua Bahasa Pemrograman (Platform HackerRank)',
                'babak' => 'Penyisihan & Final Online',
                'description' => 'Uji ketajaman pemikiran logis dan kecepatan menyelesaikan persoalan algoritma kompleks di bawah tekanan waktu yang ketat.',
                'long_description' => 'Competitive Programming I-FEST 2026 adalah ajang kompetisi pemrograman tingkat nasional yang menantang para pelajar dan mahasiswa di seluruh Indonesia untuk memecahkan berbagai persoalan algoritma dan matematika yang kompleks secara daring menggunakan platform HackerRank. Peserta dituntut untuk memecahkan soal algoritma dengan efisiensi memori dan waktu yang optimal.',
                'rules' => json_encode([
                    'Perlombaan bersifat kelompok yang terdiri dari 2-3 orang/tim.',
                    'Terbuka untuk umum dengan batasan usia 16 - 25 tahun.',
                    'Seluruh babak (penyisihan & final) dilaksanakan secara daring (online) melalui platform HackerRank.',
                    'Di babak penyisihan, setiap tim memiliki hak untuk menggunakan maksimal 3 komputer/laptop dengan webcam menyala (open camera & share screen).',
                    'Dilarang keras menggunakan bantuan AI, melakukan kecurangan seperti plagiarisme kode, atau bertukar jawaban antar tim.',
                ]),
                'schedule' => 'Pendaftaran: 9 Juli - 9 Agustus 2026 | Technical Meeting: 15 Agustus 2026 | Warm Up: 17 Agustus 2026 | Babak Penyisihan: 28 Agustus 2026 | Pengumuman Top 5: 4 September 2026 | Final: 5 September 2026',
                'gelombang_1_start' => '2026-07-09',
                'gelombang_1_end' => '2026-07-17',
                'gelombang_2_end' => '2026-08-09',
                'registration_link' => 'https://forms.gle/PiRWMLUo9NyKS6Kc8',
                'guidebook_link' => 'https://drive.google.com/drive/folders/1WqwaGvTRvwXyBxFZsK5266hjBLUI7RI7?usp=sharing',
                'contact_person' => 'Alma (+62 822-1488-2845)',
                'card_bg' => '#DCEEB1',
                'accent_color' => '#FF3D8B',
                'text_color' => '#04000D',
            ],
            [
                'kode' => 'NAT-02',
                'title' => 'UI/UX Design',
                'scale' => 'Nasional',
                'tagline' => 'DESAIN DIGITAL BERDAMPAK',
                'fee' => 'Rp 50.000 / Tim',
                'fee_gelombang_1' => 'Rp 40.000 / Tim',
                'fee_gelombang_2' => 'Rp 50.000 / Tim',
                'target' => 'Terbuka untuk Umum (Usia 17-25 Tahun)',
                'team_requirements' => 'Tim (3 Orang)',
                'languages' => 'Figma, Adobe XD, atau sejenisnya',
                'babak' => 'Kualifikasi Proposal + Final Online',
                'description' => 'Rancang solusi antarmuka digital yang intuitif, inklusif, dan memecahkan permasalahan nyata masyarakat secara kreatif.',
                'long_description' => 'UI/UX Design Competition I-FEST 2026 mengajak desainer digital muda untuk merancang purwarupa (high-fidelity prototype) aplikasi mobile maupun web yang fungsional dan ramah pengguna berdasarkan Design Thinking. Fokus utama adalah memecahkan isu riil masyarakat menggunakan pendekatan User-Centered Design.',
                'rules' => json_encode([
                    'Perlombaan bersifat kelompok beranggotakan tepat 3 orang.',
                    'Setiap tim wajib mengumpulkan Dokumen Proposal UX Case Study (PDF), tautan Figma Prototype aktif, dan Video UI/UX Demo (3-10 menit).',
                    'Video demonstrasi wajib memuat perkenalan tim, masalah, solusi, penjelasan desain, dan diunggah ke Reels Instagram kolaborator.',
                    'Desain harus orisinal, belum pernah menjuarai kompetisi lain, dan tidak menggunakan UI Kit instan secara utuh.',
                    'Babak final diikuti oleh 5 tim terbaik yang akan mempresentasikan karya mereka secara online di hadapan juri.',
                ]),
                'schedule' => 'Pendaftaran: 9 Juli - 9 Agustus 2026 | Technical Meeting: 10 Agustus 2026 | Pengumpulan Proposal Karya: 28 Agustus 2026 | Penilaian Awal: 29 Agustus - 3 September 2026 | Pengumuman Top 5: 4 September 2026 | Pengumpulan Link Figma: 8 September 2026 | Presentasi Karya: 12 September 2026 | Pengumuman Pemenang: Hari H I-Fest',
                'gelombang_1_start' => '2026-07-09',
                'gelombang_1_end' => '2026-07-17',
                'gelombang_2_end' => '2026-08-09',
                'registration_link' => 'https://forms.gle/aud4FoHggF3qow1G8',
                'guidebook_link' => 'https://drive.google.com/drive/folders/10qgvLbmJIKpdVZpM_oV9Ak8wyg4dH7T1?usp=sharing',
                'contact_person' => 'Cut (+62 813-3237-0684)',
                'card_bg' => '#EFD4D4',
                'accent_color' => '#FF3D8B',
                'text_color' => '#04000D',
            ],
            [
                'kode' => 'NAT-03',
                'title' => 'Digital Business Plan',
                'scale' => 'Nasional',
                'tagline' => 'IDE BISNIS INOVATIF',
                'fee' => 'Rp 85.000 / Tim',
                'fee_gelombang_1' => 'Rp 70.000 / Tim',
                'fee_gelombang_2' => 'Rp 85.000 / Tim',
                'target' => 'Terbuka untuk Umum',
                'team_requirements' => 'Tim (Maks. 3 orang)',
                'languages' => 'Pitch Deck, Canva, Excel (Financial Modeling)',
                'babak' => 'Kualifikasi Online + Final',
                'description' => 'Ubah gagasan bisnis kreatif berbasis digital menjadi rancangan proposal bisnis yang matang, realistis, dan siap investasi.',
                'long_description' => 'Digital Business Plan mengajak inovator muda dan calon wirausahawan untuk menyusun model bisnis digital yang berdaya saing tinggi, berkelanjutan, dan memiliki potensi skalabilitas pasar yang besar. Peserta akan ditantang memformulasikan riset pasar yang solid, strategi go-to-market yang cerdas, rancangan operasional yang matang, serta proyeksi keuangan (financial modeling) yang realistis.',
                'rules' => json_encode([
                    'Tim terdiri dari maksimal 3 orang peserta umum.',
                    'Format proposal harus mengikuti template standar sistematika BMC (Business Model Canvas) dan Executive Summary resmi panitia.',
                    'Wajib menyertakan rancangan model monetisasi dan analisis SWOT yang komprehensif.',
                    'Di babak final, peserta diwajibkan memaparkan Pitch Deck di hadapan investor dan juri profesional dalam durasi 10 menit presentasi + tanya jawab.',
                ]),
                'schedule' => 'Pendaftaran: 9 Juli - 9 Agustus 2026 | Technical Meeting: 11 Agustus 2026 | Pengumpulan Proposal Bisnis: 28 Agustus 2026 | Pengumuman Top 5: 4 September 2026 | Final: 11 September 2026 | Pengumuman Pemenang: Hari H I-Fest',
                'gelombang_1_start' => '2026-07-09',
                'gelombang_1_end' => '2026-07-17',
                'gelombang_2_end' => '2026-08-09',
                'registration_link' => '#detail-kegiatan',
                'guidebook_link' => 'https://drive.google.com/drive/folders/1EFmunjQoGOKC-hcQjCHFYYX0rb54xJ6g?usp=sharing',
                'contact_person' => 'Saiful (+62 819-4356-2655)',
                'card_bg' => '#C8E6CD',
                'accent_color' => '#FF3D8B',
                'text_color' => '#04000D',
            ],
            [
                'kode' => 'REG-01',
                'title' => 'Creative Video',
                'scale' => 'Regional',
                'tagline' => 'VIDEO EDUKATIF & KREATIF',
                'fee' => 'Rp 25.000 / Orang',
                'fee_gelombang_1' => 'Rp 20.000 / Orang',
                'fee_gelombang_2' => 'Rp 25.000 / Orang',
                'target' => 'Terbuka untuk Umum (Usia 15-23 Tahun)',
                'team_requirements' => 'Individu',
                'languages' => 'CapCut, Alight Motion, Premiere, dll.',
                'babak' => 'Kurasi Online (Reels Instagram)',
                'description' => 'Salurkan kreativitas sinematik Anda untuk merancang video edukasi bertema Literasi Digital yang mampu menarik dan mengedukasi masyarakat luas.',
                'long_description' => 'Creative Video Competition I-FEST 2026 adalah kompetisi multimedia tingkat regional untuk mewadahi ide kreatif, teknik visual, dan kemampuan storytelling. Peserta ditantang memproduksi video pendek edukatif berdurasi 30 detik hingga 2 menit dengan tema \'Youth Digital Orchestra : Exploring the Future Through Digital Creativity\'.',
                'rules' => json_encode([
                    'Perlombaan bersifat individu dengan batasan usia peserta 15 - 23 tahun.',
                    'Video berdurasi minimal 30 detik dan maksimal 2 menit, resolusi minimal 1080p, format MP4.',
                    'Rasio video diperbolehkan 16:9 (Landscape) atau 9:16 (Portrait).',
                    'Karya diunggah sebagai Reels ke Instagram ketua tim dengan menandai akun @ifest_untad, akun tidak privat, dan menggunakan caption resmi.',
                    'Wajib menggunakan template logo panitia sebagai opening dan logo sponsor sebagai closing.',
                ]),
                'schedule' => 'Pendaftaran: 9 Juli - 9 Agustus 2026 | Technical Meeting: 12 Agustus 2026 | Pengumpulan Video: 28 Agustus 2026 | Pengumuman Top 5: 4 September 2026 | Pengumuman Pemenang: Hari H I-Fest',
                'gelombang_1_start' => '2026-07-09',
                'gelombang_1_end' => '2026-07-17',
                'gelombang_2_end' => '2026-08-09',
                'registration_link' => 'https://forms.gle/2AaDqvh8ZKPa8etJA',
                'guidebook_link' => 'https://drive.google.com/drive/folders/1XNmBvEaB4mRfVv3_pZKwiUsKLXLlUJCk?usp=sharing',
                'contact_person' => 'Naila (+62 838-4711-4963)',
                'card_bg' => '#FFF9E6',
                'accent_color' => '#8839FF',
                'text_color' => '#04000D',
            ],
            [
                'kode' => 'REG-02',
                'title' => 'Digital Education Poster',
                'scale' => 'Regional',
                'tagline' => 'DESAIN VISUAL EDUKATIF',
                'fee' => 'Rp 25.000 / Orang',
                'fee_gelombang_1' => 'Rp 20.000 / Orang',
                'fee_gelombang_2' => 'Rp 25.000 / Orang',
                'target' => 'Terbuka untuk Umum (Usia 15-23 Tahun)',
                'team_requirements' => 'Individu',
                'languages' => 'Canva, Illustrator, Photoshop, Figma, dll.',
                'babak' => 'Kurasi Online (Instagram Feed)',
                'description' => 'Visualisasikan gagasan Anda dalam bentuk poster edukatif bertema literasi siber untuk menyampaikan pesan penting teknologi secara grafis.',
                'long_description' => 'Digital Education Poster I-FEST 2026 mengajak desainer muda regional menyalurkan ide kreatif dan komunikatif lewat desain visual edukatif. Poster diharapkan menyederhanakan pesan inovasi teknologi menjadi konten visual yang menarik dan mendidik masyarakat umum.',
                'rules' => json_encode([
                    'Perlombaan bersifat individu dengan batasan usia peserta 15 - 23 tahun.',
                    'Karya dibuat dalam format A3 (29.7 x 42 cm), resolusi 300 DPI, format PNG/JPEG, portrait.',
                    'Karya diunggah sebagai Feed ke akun Instagram peserta (tidak privat) dengan menandai @ifest_untad, serta diunggah ke link formulir panitia.',
                    'Wajib menyertakan template logo resmi yang disediakan oleh panitia pada karya poster.',
                    'Karya harus orisinal, belum pernah memenangkan kompetisi lain, dan tidak mengandung SARA.',
                ]),
                'schedule' => 'Pendaftaran: 9 Juli - 9 Agustus 2026 | Technical Meeting: 14 Agustus 2026 | Pengumpulan Karya: 28 Agustus 2026 | Pengumuman Top 5: 4 September 2026 | Pengumuman Pemenang: Hari H I-Fest',
                'gelombang_1_start' => '2026-07-09',
                'gelombang_1_end' => '2026-07-17',
                'gelombang_2_end' => '2026-08-09',
                'registration_link' => 'https://forms.gle/n6YQRPEqUJX9PrHJ6',
                'guidebook_link' => 'https://drive.google.com/drive/folders/1N_JYDUUHaqHZLSjN-YFhJzQ270yVq46c?usp=sharing',
                'contact_person' => 'Kisya (+62 821-9597-3499)',
                'card_bg' => '#EFE5FF',
                'accent_color' => '#8839FF',
                'text_color' => '#04000D',
            ],
            [
                'kode' => 'REG-03',
                'title' => 'Sulteng Digital Innovation Hub (S-DIH)',
                'scale' => 'Regional',
                'tagline' => 'HACKATHON + SHOWCASE',
                'fee' => 'Gratis',
                'fee_gelombang_1' => 'Gratis',
                'fee_gelombang_2' => 'Gratis',
                'target' => 'Terbuka untuk Umum',
                'team_requirements' => 'Tim (Maks. 3 - 5 orang)',
                'languages' => 'Web/Mobile Stack (React, Vue, Flutter, Laravel, Node.js, IoT Tools)',
                'babak' => 'Intensive Hackathon + Showcase',
                'description' => 'Inkubator inovasi digital murni untuk melahirkan solusi lokal. Ikuti kompetisi Hackathon multi-hari intensif untuk memecahkan problem statement krusial daerah.',
                'long_description' => 'Sulteng Digital Innovation Hub (S-DIH) Engine adalah puncak wadah inkubator teknologi IFeST 2026. Ini merupakan kompetisi Hackathon regional yang intensif di mana tim desainer, programmer, dan pemikir bisnis bersinergi memecahkan problem statement krusial yang dihadapi Sulawesi Tengah saat ini.',
                'rules' => json_encode([
                    'Satu kelompok beranggotakan 3 hingga maksimal 5 orang.',
                    'Peserta diwajibkan membuat produk digital (web app, mobile app, atau IoT mockup prototype) yang berfungsi.',
                    'Seluruh kode pemrograman harus dibuat selama durasi hackathon berlangsung (Greenfield code).',
                    'Wajib melakukan commit kode ke repositori GitHub yang dipantau mentor berkala.',
                    'Hasil akhir wajib dipamerkan secara interaktif di Paviliun S-DIH pada Expo Utama Hari-H.',
                ]),
                'schedule' => 'Registrasi: 11 Juli - 5 Agustus 2026 | Technical Briefing: Akhir Juli 2026 | Hackathon & Pitching: Agustus 2026',
                'gelombang_1_start' => '2026-07-05',
                'gelombang_1_end' => '2026-07-25',
                'gelombang_2_end' => '2026-08-05',
                'registration_link' => '#detail-kegiatan',
                'guidebook_link' => '#',
                'card_bg' => '#04000D',
                'accent_color' => '#FF3D8B',
                'text_color' => '#FFFFFF',
            ],
        ];

        foreach ($lombas as $lomba) {
            // S-DIH (REG-03) is hidden from public view — only displayed when explicitly activated via admin panel
            if ($lomba['kode'] === 'REG-03') {
                $lomba['is_active'] = false;
            } else {
                $lomba['is_active'] = true;
            }
            Lomba::updateOrCreate(
                ['kode' => $lomba['kode']],
                $lomba
            );
        }

        // Seed Partners
        $partners = [
            // MAIN STRATEGIC PARTNER
            [
                'name' => 'Hannah Asa Indonesia',
                'type' => 'main_strategic',
                'logo_url' => '/Sponsor (Strategic Partner)/Hannah Asa.png',
                'instagram_url' => 'https://www.instagram.com/hannahasaindonesia/',
                'description' => 'Official partner mengawal eskalasi lobi pendanaan Tier-1 dan kurikulum 25 Titik Roadshow Inklusif.',
                'order' => 1,
                'is_active' => true,
            ],
            // STRATEGIC PARTNERS
            [
                'name' => 'Sultan Music',
                'type' => 'strategic_partner',
                'logo_url' => '/Sponsor (Strategic Partner)/Sultan Music.png',
                'instagram_url' => 'https://www.instagram.com/sultan_musik.id/',
                'description' => 'Official Production & Vendor partner menjamin mutu infrastruktur panggung dan malam puncak acara.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Google Student Ambasador',
                'type' => 'strategic_partner',
                'logo_url' => '/Sponsor (Strategic Partner)/gsa.png',
                'instagram_url' => '',
                'description' => 'Strategic execution partner yang mendukung operasional kolaborasi dan aktivasi lintas program.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Green Generation',
                'type' => 'strategic_partner',
                'logo_url' => '/Sponsor (Strategic Partner)/Green Generation.PNG',
                'instagram_url' => 'https://www.instagram.com/greengnrid/',
                'description' => 'Strategic partner dalam kolaborasi aksi lingkungan hidup dan pemberdayaan pemuda menuju pembangunan berkelanjutan.',
                'order' => 4,
                'is_active' => true,
            ],
            // MEDIA PARTNERS
            [
                'name' => 'INFOCAMABA',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(1) INFOCAMABA.png',
                'instagram_url' => 'https://www.instagram.com/infocamaba_/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'HMPTI UNISA PALU',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(2) HMPTI UNISA PALU.png',
                'instagram_url' => 'https://www.instagram.com/hmpti_unisa/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'LPM HITAM PUTIH',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(3) LPM HITAM PUTIH.JPG',
                'instagram_url' => 'https://www.instagram.com/lpm.hitamputih/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'LPM NASEHA',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(4) LPM NASEHA.png',
                'instagram_url' => 'https://www.instagram.com/lpmnaseha/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'HIMA - SI UIN',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(5) HIMA - SI UIN.png',
                'instagram_url' => 'https://www.instagram.com/himasi.uindkpalu/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'PROGRAMMING TADULAKO',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(6) programmig_tad.png',
                'instagram_url' => 'https://www.instagram.com/programming.tadulako/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'HMPSSI STMIK ADHI GUNA PALU',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(7) HMPSI STMIK Adhi Guna Palu (1) (1).png',
                'instagram_url' => 'https://www.instagram.com/hmpssi_adhiguna/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 11,
                'is_active' => true,
            ],
            [
                'name' => 'ANIMEDIA TADULAKO',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(8) Animedia Tadulako.png',
                'instagram_url' => 'https://www.instagram.com/animediatadulako/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'PERMIKOMNAS WILAYAH X',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(9) Permikomnas Wilayah X.png',
                'instagram_url' => 'https://www.instagram.com/permikomnaswilayahx/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 13,
                'is_active' => true,
            ],
            [
                'name' => 'HIMATIF UIN',
                'type' => 'media_partner',
                'logo_url' => '/Media Partner/(10) HIMATIF UIN.jpeg',
                'instagram_url' => 'https://www.instagram.com/himatif.uindkpalu/',
                'description' => 'Media partner publikasi rangkaian kegiatan I-FEST 2026.',
                'order' => 14,
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partner) {
            Partner::updateOrCreate(
                ['name' => $partner['name']],
                $partner
            );
        }

        // Seed Timeline Events
        $timelineEvents = [
            [
                'phase' => '01',
                'title' => 'Identity & Foundation',
                'date_range' => 'Januari - Maret',
                'accent_color' => '#8B5CF6',
                'status' => 'completed',
                'description_items' => [
                    'Januari: Pembentukan Tim Inti & Penyusunan Konsep Kasar.',
                    'Februari: Penyusunan Proposal Kegiatan.',
                    'Maret: Finalisasi struktur kepanitiaan (80+ personil).',
                    'Maret: Audiensi Mitra Strategis & Pencarian Dana.'
                ],
                'order' => 1,
                'is_active' => true,
            ],
            [
                'phase' => '02',
                'title' => 'Inklusif Roadshow',
                'date_range' => 'Mei - Agustus',
                'accent_color' => '#10B981',
                'status' => 'ongoing',
                'description_items' => [
                    'Mei - Jun: Awal pergerakan menyasar sekolah umum, sekolah alam/alternatif, dan komunitas disabilitas.',
                    'Jul - Agust: Roadshow di 25 titik wilayah Sulawesi Tengah.'
                ],
                'order' => 2,
                'is_active' => true,
            ],
            [
                'phase' => '03',
                'title' => 'Awareness & Reg',
                'date_range' => 'Agustus - September',
                'accent_color' => '#3B82F6',
                'status' => 'upcoming',
                'description_items' => [
                    'Agustus: Pembukaan Registrasi Kompetisi Nasional (3 Bidang Lomba).',
                    'September: Kampanye digital masif & pendampingan teknis peserta.'
                ],
                'order' => 3,
                'is_active' => true,
            ],
            [
                'phase' => '04',
                'title' => 'Benchmark & Exploration',
                'date_range' => 'September - Oktober',
                'accent_color' => '#F59E0B',
                'status' => 'upcoming',
                'description_items' => [
                    'September: Batas akhir pengumpulan karya gelombang 1.',
                    'Oktober: Penjurian Tahap Penyisihan kompetisi.'
                ],
                'order' => 4,
                'is_active' => true,
            ],
            [
                'phase' => '05',
                'title' => 'Local Intellectual Series',
                'date_range' => 'Oktober - November',
                'accent_color' => '#EF4444',
                'status' => 'upcoming',
                'description_items' => [
                    'Oktober: Pengumuman finalis utama & pembukaan ticketing Grand Closing.',
                    'November: Technical Meeting & persiapan akomodasi finalis nasional.'
                ],
                'order' => 5,
                'is_active' => true,
            ],
            [
                'phase' => '06',
                'title' => 'Grand Symphony & Legacy',
                'date_range' => 'November - Desember',
                'accent_color' => '#F59E0B',
                'status' => 'upcoming',
                'description_items' => [
                    'November: 3 HARI PUNCAK I-FEST 2026 (Expo Inovasi, Seminar Internasional, Awarding Night, Grand Closing Concert).',
                    'Desember: Perilisan Official Aftermovie & Penyerahan Impact Report.'
                ],
                'order' => 6,
                'is_active' => true,
            ]
        ];

        foreach ($timelineEvents as $event) {
            TimelineEvent::updateOrCreate(
                ['phase' => $event['phase']],
                $event
            );
        }

        // Seed FAQs
        $faqs = [
            [
                'question' => 'Apa itu I-FEST 2026?',
                'answer' => 'I-FEST (Informatics Festival) 2026 adalah festival IT nasional tahunan yang diselenggarakan oleh Himpunan Mahasiswa Teknik Informatika (HMTI) Universitas Tadulako.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'Siapa saja yang bisa mendaftar kompetisi?',
                'answer' => 'Kompetisi di I-FEST 2026 terbuka untuk siswa SMA/SMK sederajat dan mahasiswa aktif di seluruh Indonesia sesuai dengan kategori lomba masing-masing.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'Bagaimana cara melakukan pendaftaran?',
                'answer' => 'Pendaftaran dapat dilakukan secara online melalui website resmi ini dengan membuat akun peserta, melengkapi data profil, dan melakukan pembayaran biaya registrasi.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Apakah pendaftaran bisa dibatalkan atau direfund?',
                'answer' => 'Biaya pendaftaran yang sudah dibayarkan tidak dapat dikembalikan (refund) jika peserta membatalkan keikutsertaannya secara sepihak.',
                'order' => 4,
                'is_active' => true,
            ]
        ];

        foreach ($faqs as $faq) {
            FaqItem::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
