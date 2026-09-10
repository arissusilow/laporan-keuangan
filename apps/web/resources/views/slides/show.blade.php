@php
    $initialSlide = min(max(request()->integer('screen', 1), 1), $slideCount);
    $periodLabel = \Carbon\Carbon::parse($from)->translatedFormat('d M Y').' — '.\Carbon\Carbon::parse($to)->translatedFormat('d M Y');
    $categoryMaximum = max(1, (int) ($categories->max('total') ?? 1));
    $monthlyMaximum = max(1, (int) $months->max(fn ($month) => max((int) $month->incoming, (int) $month->outgoing)));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2b20">
    <title>Slide {{ $report->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .tv-slides { position: relative; z-index: 1; min-height: 0; }
        .tv-slide { display: none; height: 100%; align-content: center; animation: tv-fade .35s ease both; }
        .tv-slide.is-active { display: grid; }
        .slide-controls button { display: grid; min-width: 38px; min-height: 38px; place-items: center; border: 0; color: #fff; background: transparent; cursor: pointer; }
        .slide-controls button:focus-visible { outline-color: #f2d897; }
        .slide-arrow { border-radius: 50%; font-size: 1.55rem; line-height: 1; }
        .slide-arrow:hover { background: rgba(255,255,255,.12); }
        .slide-dots button { min-width: 12px; width: 12px; min-height: 12px; height: 12px; padding: 0; border-radius: 999px; background: rgba(255,255,255,.34); transition: width .2s ease, background .2s ease; }
        .slide-dots button span { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
        .slide-dots button.active { width: 42px; background: #d6a84b; box-shadow: 0 0 0 3px rgba(214,168,75,.16); }
        .tv-status { display: inline-flex; align-items: center; gap: .45rem; }
        .tv-status::before { content: ""; width: .55rem; height: .55rem; border-radius: 50%; background: #7dd3a8; box-shadow: 0 0 0 4px rgba(125,211,168,.13); }
        .tv-empty { color: #dce8e1; }
        @keyframes tv-fade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .tv-slide { animation: none; } }
    </style>
</head>
<body class="tv-page">
    <main class="tv-stage" aria-live="polite">
        @if(isset($config->settings['background_path']))
            <div
                class="tv-identity-layer"
                style="--tv-identity-image: url('{{ $backgroundUrl }}'); --tv-identity-opacity: {{ ((int) ($config->settings['background_opacity'] ?? 14)) / 100 }}"
                aria-hidden="true"
            ></div>
        @endif

        <header class="tv-header">
            <div class="tv-brand-lockup">
                <span class="tv-brand-mark">LK</span>
                <div>
                    <strong>{{ $report->name }}</strong>
                    <span class="tv-kicker">Laporan keuangan terbuka</span>
                </div>
            </div>
            <span class="tv-counter"><span data-slide-current>{{ str_pad((string) $initialSlide, 2, '0', STR_PAD_LEFT) }}</span> / {{ str_pad((string) $slideCount, 2, '0', STR_PAD_LEFT) }}</span>
        </header>

        <div class="tv-slides">
            <section class="tv-content tv-slide {{ $initialSlide === 1 ? 'is-active' : '' }}" data-slide="1" @if($initialSlide !== 1) hidden @endif>
                <p class="eyebrow light">Laporan Keuangan</p>
                <h1>{{ $report->name }}</h1>
                <p class="tv-accent">{{ $periodLabel }}</p>
                <p>Ringkas, transparan, dan mudah diperiksa bersama.</p>
            </section>

            <section class="tv-content tv-slide {{ $initialSlide === 2 ? 'is-active' : '' }}" data-slide="2" @if($initialSlide !== 2) hidden @endif>
                <p class="eyebrow light">Ringkasan {{ $currentMonthLabel }}</p>
                <h1>Arus kas bulan berjalan</h1>
                <div class="tv-metrics">
                    <div><span>Saldo bulan lalu</span><strong>Rp {{ number_format($monthlySummary['before'], 0, ',', '.') }}</strong></div>
                    <div><span>Pemasukan bulan ini</span><strong>Rp {{ number_format($monthlySummary['incoming'], 0, ',', '.') }}</strong></div>
                    <div><span>Pengeluaran bulan ini</span><strong>Rp {{ number_format($monthlySummary['outgoing'], 0, ',', '.') }}</strong></div>
                </div>
            </section>

            <section class="tv-content tv-slide {{ $initialSlide === 3 ? 'is-active' : '' }}" data-slide="3" @if($initialSlide !== 3) hidden @endif>
                <p class="eyebrow light">Komposisi pengeluaran</p>
                <h1>Pengeluaran per kategori</h1>
                <div class="tv-category-list">
                    @forelse($categories as $category)
                        <div>
                            <i style="width: {{ round(((int) $category->total / $categoryMaximum) * 100, 1) }}%" aria-hidden="true"></i>
                            <span>{{ $category->name }}</span>
                            <strong>Rp {{ number_format($category->total, 0, ',', '.') }}</strong>
                        </div>
                    @empty
                        <p class="tv-empty">Belum ada pengeluaran pada periode ini.</p>
                    @endforelse
                </div>
            </section>

            <section class="tv-content tv-slide {{ $initialSlide === 4 ? 'is-active' : '' }}" data-slide="4" @if($initialSlide !== 4) hidden @endif>
                <p class="eyebrow light">Perbandingan bulanan</p>
                <h1>Arus masuk dan keluar</h1>
                @if($months->isNotEmpty())
                    <div class="tv-months" aria-label="Grafik pemasukan dan pengeluaran bulanan">
                        @foreach($months as $month)
                            <div>
                                <span>{{ \Carbon\Carbon::createFromFormat('Y-m', $month->period_month)->translatedFormat('M y') }}</span>
                                <i class="tv-in" style="height: {{ max(4, round(((int) $month->incoming / $monthlyMaximum) * 100, 1)) }}%" title="Masuk Rp {{ number_format($month->incoming, 0, ',', '.') }}"></i>
                                <i class="tv-out" style="height: {{ max(4, round(((int) $month->outgoing / $monthlyMaximum) * 100, 1)) }}%" title="Keluar Rp {{ number_format($month->outgoing, 0, ',', '.') }}"></i>
                            </div>
                        @endforeach
                    </div>
                    <div class="tv-legend"><span><i class="tv-in"></i> Pemasukan</span><span><i class="tv-out"></i> Pengeluaran</span></div>
                @else
                    <p class="tv-empty">Belum ada data bulanan pada periode ini.</p>
                @endif
            </section>

            @unless($latest->isEmpty())
                <section class="tv-content tv-slide {{ $initialSlide === 5 ? 'is-active' : '' }}" data-slide="5" @if($initialSlide !== 5) hidden @endif>
                    <p class="eyebrow light">Aktivitas terkini</p>
                    <h1>Transaksi terbaru</h1>
                    <div class="tv-latest">
                        @foreach($latest as $transaction)
                            <div>
                                <span>{{ $transaction->description ?: 'Tanpa keterangan' }}</span>
                                <strong>{{ $transaction->type === 'IN' ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endunless

            <section class="tv-content tv-slide {{ $initialSlide === $qrSlide ? 'is-active' : '' }}" data-slide="{{ $qrSlide }}" @if($initialSlide !== $qrSlide) hidden @endif>
                <div class="tv-qr-layout">
                    <div class="tv-qr-copy">
                        <p class="eyebrow light">Laporan lengkap</p>
                        <h1>Pindai untuk membuka PDF</h1>
                        <p>Lihat seluruh transaksi aktif sejak awal laporan sampai hari ini melalui ponsel Anda.</p>
                        @if($publicPdfUrl)
                            <span class="tv-network-note">Hubungkan ponsel ke jaringan yang sama dengan server laporan.</span>
                        @else
                            <span class="tv-network-note">Aktifkan URL TV publik agar QR dapat digunakan.</span>
                        @endif
                    </div>
                    @if($qrCodeDataUri)
                        <div class="tv-qr-card">
                            <img src="{{ $qrCodeDataUri }}" alt="QR code untuk membuka PDF laporan lengkap">
                            <strong>Arahkan kamera ke QR code</strong>
                            <span>{{ parse_url($publicPdfUrl, PHP_URL_HOST) }}:{{ parse_url($publicPdfUrl, PHP_URL_PORT) }}</span>
                        </div>
                    @endif
                </div>
            </section>

            <section class="tv-content tv-slide {{ $initialSlide === $slideCount ? 'is-active' : '' }}" data-slide="{{ $slideCount }}" @if($initialSlide !== $slideCount) hidden @endif>
                <p class="eyebrow light">Amanah bersama</p>
                <h1>Terima kasih atas kepercayaan Anda.</h1>
                <p>Transparansi menjaga setiap rupiah tetap dapat dipertanggungjawabkan.</p>
            </section>
        </div>

        <footer class="tv-footer">
            <span>{{ $periodLabel }}</span>
            <span class="tv-status">Diperbarui {{ now()->format('d/m/Y H:i') }}</span>
        </footer>
    </main>

    <nav class="slide-controls" aria-label="Navigasi slide">
        <button class="slide-arrow" type="button" data-slide-previous aria-label="Slide sebelumnya">‹</button>
        <div class="slide-dots">
            @for($slide = 1; $slide <= $slideCount; $slide++)
                <button class="{{ $initialSlide === $slide ? 'active' : '' }}" type="button" data-slide-dot="{{ $slide }}" aria-current="{{ $initialSlide === $slide ? 'true' : 'false' }}">
                    <span>Slide {{ $slide }}</span>
                </button>
            @endfor
        </div>
        <button class="slide-arrow" type="button" data-slide-next aria-label="Slide berikutnya">›</button>
    </nav>

    <script>
        (function () {
            var slides = document.querySelectorAll('[data-slide]');
            var dots = document.querySelectorAll('[data-slide-dot]');
            var counter = document.querySelector('[data-slide-current]');
            var current = {{ $initialSlide - 1 }};
            var duration = {{ max(5, (int) $config->duration_seconds) * 1000 }};
            var refresh = {{ max(15, (int) $config->refresh_seconds) * 1000 }};
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var rotation;

            function activate(index) {
                current = (index + slides.length) % slides.length;

                for (var slideIndex = 0; slideIndex < slides.length; slideIndex += 1) {
                    var active = slideIndex === current;
                    slides[slideIndex].hidden = ! active;
                    slides[slideIndex].classList.toggle('is-active', active);
                    dots[slideIndex].classList.toggle('active', active);
                    dots[slideIndex].setAttribute('aria-current', active ? 'true' : 'false');
                }

                counter.textContent = String(current + 1).padStart(2, '0');

                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('screen', String(current + 1));
                    window.history.replaceState({}, '', url.toString());
                }
            }

            function restartRotation() {
                if (reduceMotion) return;
                window.clearInterval(rotation);
                rotation = window.setInterval(function () { activate(current + 1); }, duration);
            }

            document.querySelector('[data-slide-previous]').addEventListener('click', function () {
                activate(current - 1);
                restartRotation();
            });
            document.querySelector('[data-slide-next]').addEventListener('click', function () {
                activate(current + 1);
                restartRotation();
            });

            for (var dotIndex = 0; dotIndex < dots.length; dotIndex += 1) {
                dots[dotIndex].addEventListener('click', function () {
                    activate(Number(this.getAttribute('data-slide-dot')) - 1);
                    restartRotation();
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') {
                    activate(current - 1);
                    restartRotation();
                } else if (event.key === 'ArrowRight') {
                    activate(current + 1);
                    restartRotation();
                }
            });

            restartRotation();
            window.setTimeout(function () { window.location.reload(); }, refresh);
        }());
    </script>
</body>
</html>
