<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synaplan - AI Model Management Platform | All AI Chatbots Online</title>
    <meta name="description" content="Synaplan is a comprehensive AI model management platform. Manage your AI communication, save prompts, and optimize workflows across services with full data ownership and model freedom.">
    <meta name="keywords" content="AI platform, AI models, chatbot management, AI workflow, data ownership, AI integration">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.synaplan.com/">
    <meta property="og:title" content="Synaplan - AI Model Management Platform">
    <meta property="og:description" content="Manage your AI communication in one place. Save prompts, results and optimize workflows with full data ownership and model freedom.">
    <meta property="og:image" content="https://synaplan.com/assets/member_area.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://www.synaplan.com/">
    <meta property="twitter:title" content="Synaplan - AI Model Management Platform">
    <meta property="twitter:description" content="Manage your AI communication in one place. Save prompts, results and optimize workflows with full data ownership and model freedom.">
    <meta property="twitter:image" content="https://synaplan.com/assets/member_area.png">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://www.synaplan.com/">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@900,700,500,301,701,300,501,401,901,400&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Satoshi', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Navigation Styles */
        .navbar-custom {
            background-color: #061c3e;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 32px;
            width: auto;
        }
        .navbar-brand:hover {
            opacity: 0.8;
        }
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #10d876 !important;
        }
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .btn-nav-cta {
            background: linear-gradient(135deg, #00E5FF 0%, #00FF9D 100%);
            border: none;
            color: black;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            margin-left: 1rem;
            transition: all 0.3s ease;
        }
        .btn-nav-cta:hover {
            background: linear-gradient(135deg, #00c4a7 0%, #00b49a 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 212, 170, 0.3);
        }
        
        .hero {
            background: linear-gradient(135deg, #0a2147 0%, #061c3e 50%, #0a2147 100%);
            color: white;
            padding: 80px 0 120px 0;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .screenshot-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }
        .screenshot-placeholder {
            background: linear-gradient(45deg, #f8f9fa 25%, transparent 25%), 
                        linear-gradient(-45deg, #f8f9fa 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #f8f9fa 75%), 
                        linear-gradient(-45deg, transparent 75%, #f8f9fa 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            background-color: #e9ecef;
            min-height: 300px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.1rem;
            border: 2px dashed #dee2e6;
        }
        .section {
            padding: 80px 0;
        }
        .section-dark {
            background: #061c3e;
            color: white;
        }
        .placeholder-img {
            background-color: #dee2e6;
            height: 300px;
            width: 100%;
            border-radius: 8px;
        }
        .stat-box {
            text-align: center;
            padding: 2rem 1rem;
        }
        .stat-box h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #061c3e;
            margin-bottom: 0.5rem;
        }
        .btn-success {
            background: linear-gradient(135deg, #00d4aa 0%, #00c4a7 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #00c4a7 0%, #00b49a 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 212, 170, 0.3);
        }
        .btn-outline-primary {
            border-color: #061c3e;
            color: #061c3e;
            padding: 12px 30px;
            font-weight: 500;
            border-radius: 6px;
        }
        .btn-outline-primary:hover {
            background-color: #061c3e;
            border-color: #061c3e;
        }
        .logos-section {
            padding: 40px 0;
            background-color: white;
            border-bottom: 1px solid #e9ecef;
        }
        .logo-item {
            text-align: center;
            font-weight: 500;
            color: #6c757d;
            padding: 10px;
        }
        .features-grid {
            padding: 2rem 0;
        }
        .feature-item {
            text-align: center;
            padding: 2rem 1rem;
        }
        .feature-item h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #061c3e;
        }
        footer {
            background-color: #061c3e;
            color: white;
            padding: 40px 0;
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            .hero p {
                font-size: 1rem;
            }
            .screenshot-placeholder {
                height: 250px;
            }
            .stat-box h3 {
                font-size: 2rem;
            }
            .btn-nav-cta {
                margin-left: 0;
                margin-top: 0.5rem;
            }
            .hero-tick-circles .tick-circle-sm {
                display: none !important;
            }
            .hero-tick-circles .tick-label-sm {
                margin-left: 0.5rem !important;
                font-size: 1.1rem;
                white-space: normal;
            }
            .hero-tick-circles .d-flex.align-items-center.gap-2 {
                gap: 0.5rem !important;
            }
        }
        .hero-tick-circles .tick-circle-sm {
            width: 28px;
            height: 28px;
            background: #e3f0ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.25rem;
        }
        .hero-tick-circles .tick-label-sm {
            color: #fff;
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 0.01em;
            color: #061c3e;
        }
        @media (max-width: 768px) {
            .hero-tick-circles .tick-label-sm {
                font-size: 0.95rem;
            }
            .hero-tick-circles .tick-circle-sm {
                width: 22px;
                height: 22px;
            }
        }
    </style>
</head>
<body>
    <!-- Schema.org Organization markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "metadist GmbH",
        "url": "https://synaplan.com",
        "logo": "https://synaplan.com/assets/synaplan_logo_ondark.svg",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Königsallee 82",
            "addressLocality": "Düsseldorf",
            "postalCode": "40212",
            "addressCountry": "DE"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+49-211-90760084",
            "contactType": "customer service",
            "email": "team@synaplan.com"
        }
    }
    </script>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/synaplan_logo_ondark.svg" alt="Synaplan">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#functionality">Functionality</a>
                    </li>
                    <!-- li class="nav-item">
                        <a class="nav-link" href="#FAQ">FAQ</a>
                    </li -->
                    <li class="nav-item">
                        <a class="nav-link" href="#developers">Developers</a>
                    </li>
                    <!-- li class="nav-item">
                        <a class="nav-link" href="#prices">Prices</a>
                    </li -->
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a href="#waitinglist" class="btn btn-nav-cta">Waiting List</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero" aria-label="Main introduction">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 col-xl-8 mx-auto">
                        <div class="hero-content text-center">
                            <h1>One platform, every AI model</h1>
                            <p>For any kind of task - across services, workflows and files.</p>
                            <!-- Tick Circles Row (Updated) -->
                            <div class="d-flex justify-content-center gap-4 mb-5 hero-tick-circles align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="tick-circle-sm d-flex align-items-center justify-content-center">
                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="9" cy="9" r="9" fill="#e3f0ff"/>
                                            <path d="M5 9.5L8 12L13 7" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span class="tick-label-sm text-white">AI model freedom</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="tick-circle-sm d-flex align-items-center justify-content-center">
                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="9" cy="9" r="9" fill="#e3f0ff"/>
                                            <path d="M5 9.5L8 12L13 7" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span class="tick-label-sm text-white">Easy data handling</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="tick-circle-sm d-flex align-items-center justify-content-center">
                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="9" cy="9" r="9" fill="#e3f0ff"/>
                                            <path d="M5 9.5L8 12L13 7" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span class="tick-label-sm text-white">Secure and compliant</span>
                                </div>
                            </div>
                            
                            <a href="#waitinglist" class="btn btn-lg btn-nav-cta">Waiting List</a>
                            <!-- Screenshot Container -->
                            <div class="screenshot-container">
                                <div class="screenshot-placeholder">
                                    <video
                                    id="productVideo"
                                    muted
                                    autoplay
                                    playsinline
                                    preload="none"
                                    poster="assets/member_area.png"
                                    style="width: 100%; height: auto;">
                                    <source src="assets/synaplan_video.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                  </video>                            
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Logos Section -->
        <section class="logos-section" aria-label="Trusted by">
            <div class="container">
                <div class="row justify-content-center align-items-center">
                    <div class="col-6 col-md-3 col-lg-2 d-flex align-items-center justify-content-center">
                        <div class="logo-item">
                            <img src="assets/logo_seedvc.png" alt="Seed VC" style="max-width:200px; height:auto; display:block; margin:auto;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 d-flex align-items-center justify-content-center">
                        <div class="logo-item">
                            <img src="assets/logo_ress.png" alt="Balthasar Ress" style="max-width:200px; height:auto; display:block; margin:auto;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 d-flex align-items-center justify-content-center">
                        <div class="logo-item">
                            <img src="assets/logo_voelker.png" alt="Völker Digital" style="max-width:200px; height:auto; display:block; margin:auto;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 d-flex align-items-center justify-content-center">
                        <div class="logo-item">
                            <img src="assets/logo_plateart.png" alt="plateART" style="max-width:200px; height:auto; display:block; margin:auto;">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="functionality" class="container section" aria-label="Platform features">
            <div class="text-center mb-5">
                <h2>Manage your AI communication in one place:<br class="d-none d-md-block"> 
                    save prompts, results and optimize workflows.</h2>
            </div>
            <div class="row features-grid">
                <div class="col-md-4">
                    <div class="feature-item">
                        <h4>Full data ownership</h4>
                        <p>Save your data on European, US or your OWN servers, integrate RAG by clicking a button.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item">
                        <h4>Select your AI model</h4>
                        <p>Quick and cheap text or a full blown analysis? Your choice of API, MCP and models.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item">
                        <h4>Freedom of Integration</h4>
                        <p>Web widget, WhatsApp, E-Mail or classical web chat window? Use all of them for free.</p>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <a href="#waitinglist" class="btn btn-outline-primary btn-lg">Get notified!</a>
            </div>
        </section>

        <!-- Waiting List Section -->
        <section id="waitinglist" class="section section-dark" aria-label="Join waiting list">
            <div class="container">
                <h2>Ready to get started?</h2>
                <p>We are releasing the first version in July 2025. Join our waiting list to get early access.</p>
            </div>
        </section>
        <div class="section-dark">
            <div style="margin:auto; width: 400px;">
                <script async data-uid="3a02cc4dec" src="https://synaplan.kit.com/3a02cc4dec/index.js"></script>
            </div>
        </div>

        <!-- Developers Section -->
        <section id="developers" class="container section" aria-label="Developer information">
            <div class="row align-items-center">
                <div class="col-lg-6 p-2 mb-lg-0">
                    <h2>Open Source, Enterprise Ready</h2>
                    <ul class="list-unstyled mt-4">
                        <li class="mb-2">✓ API Services</li>
                        <li class="mb-2">✓ Scalable K8s Containers</li>
                        <li class="mb-2">✓ Full Open Core announced</li>
                        <li class="mb-2">✓ Fair dual license model</li>
                    </ul>
                    <a href="https://github.com/orgaralf/synaplan.com" class="btn btn-success btn-lg mt-4">GitHub</a>
                </div>
                <div class="col-lg-6 p-2">
                    <a href="https://github.com/orgaralf/synaplan.com" target="_blank"><img src="assets/github_screen.png" style="max-width:90%;"></a>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="container section" aria-label="Contact information">
            <div class="row justify-content-center">
                <h2 class="text-center mb-5">Imprint / Contact</h2>
                <div class="col-5">
                    <img src="assets/single_bird.svg" alt="Synaplan" style="max-height:40px;"><BR>
                    metadist GmbH<br>
                    K&ouml;nigsallee 82<br>
                    40212 Dusseldorf<br>
                    Germany<br>
                </div>
                <div class="col-5">
                    CEO: Ralf Schwoebel<br>
                    <br>
                    Registered at Dusseldorf District Court<br>
                    HRB 101206 - VAT-ID: DE301805620<br>
                    <br>
                    Phone: +49 (0)211 90760084<br>
                    <a href="mailto:team@synaplan.com">team@synaplan.com</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="text-center" role="contentinfo">
        <div class="container">
            <p>&copy; 2025 metadist GmbH</p>
            <!-- small>Impressum | Datenschutz | Contact</small -->
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
