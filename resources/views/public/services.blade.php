<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | Aqualine Laundry Services</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style1.css') }}">
    <script src="{{ asset('js/script.js') }}" defer></script>
</head>
<body>
<header class="site-header">
    <a href="{{ route('public.services') }}" class="brand-logo">
        <img src="{{ asset('img/logo.png') }}" alt="Aqualine Laundry Logo">
    </a>

    <h1>AQUALINE LAUNDRY SERVICES</h1>
</header>

<nav class="main-navigation" aria-label="Main navigation">
    <ul>
        <li><a href="{{ route('public.services') }}">Home</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#pricing-catalog">Pricing</a></li>
        <li><a href="{{ route('login') }}">Track Order</a></li>
        <li><a href="{{ route('login') }}">Staff / Customer Login</a></li>
    </ul>
</nav>

<main class="services-main-content">
    <!-- ================= HERO SECTION ================= -->
    <section class="services-hero-section">
        <div class="container">
            <div class="hero-text-content">
                <span class="section-label">PREMIUM GARMENT CARE</span>
                <h2>Expert Laundry Services Tailored for You</h2>
                <p><em>Welcome to Aqualine Laundry Services! We appreciate your trust in allowing us to care for your garments. Whether it is your daily attire, university wear, or fine corporate suits, our team handles every item with extreme precision, hygiene, and care—ensuring you always step out looking and feeling your best.</em></p>
                <p class="hero-description">
                    Experience professional laundry care designed around your lifestyle. We handle your garments with precision and eco-friendly care so you can spend more time on what matters most.
                </p>
                <div class="hero-buttons">
                    <a href="#pricing-catalog" class="primary-button">View Pricing Tiers</a>
                    <a href="{{ route('login') }}" class="secondary-button">Track Your Order</a>
                </div>
            </div>
            <div class="hero-visual">
                <img src="{{ asset('img/laundry-hero.jpg') }}" alt="Clean, freshly folded laundry at Aqualine Hub" class="hero-image">
            </div>
        </div>
    </section>

    <!-- ================= SERVICES / PRODUCTS GRID ================= -->
    <section class="services-section" id="services">
        <div class="container">
            <div class="section-heading">
                <span class="section-label">WHAT WE OFFER</span>
                <h2>Our Comprehensive Laundry Services</h2>
                <p>Professional care for your everyday clothing, delicate corporate wear, heavy bedding, and household fabrics across <strong>Madaraka Estate, Nairobi</strong> and neighboring areas.</p>
            </div>

            <div class="services-grid">
                <!-- Service 1 -->
                <article class="service-card">
                    <div class="service-visual">
                        <img src="{{ asset('img/washing-service.jpg') }}" alt="Clothes being washed with care" class="service-image">
                    </div>
                    <div class="service-content">
                        <h3>Professional Washing</h3>
                        <p>
                            Keep your clothes clean and fresh with our advanced washing service. We sort by fabric type and color, utilizing premium detergents to maintain garment integrity.
                        </p>
                        <a href="#pricing-catalog" class="service-link">View Rates &rarr;</a>
                    </div>
                </article>

                <!-- Service 2 -->
                <article class="service-card">
                    <div class="service-visual">
                        <img src="{{ asset('img/drying-service.jpg') }}" alt="Fresh laundry drying efficiently" class="service-image">
                    </div>
                    <div class="service-content">
                        <h3>Clothes Drying</h3>
                        <p>
                            Get your laundry dried safely using modern tumbling and controlled aeration. Our drying process preserves fabric elasticity and eliminates moisture completely.
                        </p>
                        <a href="#pricing-catalog" class="service-link">View Rates &rarr;</a>
                    </div>
                </article>

                <!-- Service 3 -->
                <article class="service-card">
                    <div class="service-visual">
                        <img src="{{ asset('img/ironing-service.jpg') }}" alt="Professionally ironed shirts and trousers" class="service-image">
                    </div>
                    <div class="service-content">
                        <h3>Ironing and Pressing</h3>
                        <p>
                            Enjoy crisp, razor-sharp creases suitable for corporate work, university lectures, business meetings, and special occasions. Total boardroom readiness guaranteed.
                        </p>
                        <a href="#pricing-catalog" class="service-link">View Rates &rarr;</a>
                    </div>
                </article>

                <!-- Service 4 -->
                <article class="service-card">
                    <div class="service-visual">
                        <img src="{{ asset('img/folding-service.jpg') }}" alt="Clean clothes folded neatly" class="service-image">
                    </div>
                    <div class="service-content">
                        <h3>Folding and Packaging</h3>
                        <p>
                            Receive neatly stacked, dust-proof packaged laundry that is effortless to organize, store, and unpack. Perfect for busy professionals and students alike.
                        </p>
                        <a href="#pricing-catalog" class="service-link">View Rates &rarr;</a>
                    </div>
                </article>

                <!-- Service 5 -->
                <article class="service-card">
                    <div class="service-visual">
                        <img src="{{ asset('img/garment-care.jpg') }}" alt="Delicate clothing being handled carefully" class="service-image">
                    </div>
                    <div class="service-content">
                        <h3>Special Garment Care</h3>
                        <p>
                            Give delicate woolens, silks, traditional regalia, and structured suits specialized dry-cleaning attention with customized solvent treatments.
                        </p>
                        <a href="#pricing-catalog" class="service-link">View Rates &rarr;</a>
                    </div>
                </article>

                <!-- Dynamic Services from Aqualine Database (FR08/FR09) -->
                @foreach ($services as $srv)
                    <article class="service-card">
                        <div class="service-content">
                            <span class="section-label">{{ strtoupper($srv->unit) }}</span>
                            <h3>{{ $srv->service_name }}</h3>
                            <p>
                                Standard rate: <strong>KES {{ number_format($srv->unit_price, 2) }}</strong> ({{ $srv->unit }}). Dropped off at our Madaraka counter with real-time garment tag tracking.
                            </p>
                            <a href="{{ route('login') }}" class="service-link">Log In to Track &rarr;</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ================= LIVE PRICE LIST FROM DATABASE ================= -->
    <section class="services-section" id="pricing-catalog">
        <div class="container">
            <div class="section-heading">
                <span class="section-label">TRANSPARENT PRICING</span>
                <h2>All Active Service Rates</h2>
                <p>Updated live by Aqualine Management. Whenever the Owner adds or updates a service price, it appears here automatically.</p>
            </div>

            <div class="services-grid">
                @forelse ($services as $srv)
                    <article class="service-card">
                        <div class="service-content">
                            <h3>{{ $srv->service_name }}</h3>
                            <p>Billing Unit: <strong>{{ $srv->unit }}</strong></p>
                            <p style="font-size: 1.25rem; font-weight: 700;">KES {{ number_format($srv->unit_price, 2) }}</p>
                        </div>
                    </article>
                @empty
                    <p>No active services listed yet.</p>
                @endforelse
            </div>
        </div>
    </section>
</main>

<!-- ================= FOOTER ================= -->
<footer>
    <div class="footer-principles">
        <h3>Core Principles</h3>
        <ul>
            <li>Transparent Garment Tracking</li>
            <li>Data Privacy &amp; Security</li>
            <li>Seamless M-Pesa Integration</li>
        </ul>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Aqualine Laundry. All rights reserved. Founded by Brandon Ochieng &amp; Jama Bashir.</p>
    </div>
</footer>
</body>
</html>