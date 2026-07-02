<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Premium Full-Stack Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f8fafc;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .hero {
            background: radial-gradient(circle at top right, #4f46e5, #1e1b4b);
            color: white;
            padding: 160px 0 100px;
            position: relative;
            overflow: hidden;
        }
        .hero-mockup {
            background: #0f172a;
            border: 4px solid #334155;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 10px;
            margin-top: 50px;
        }
        .mockup-header {
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
            padding-left: 5px;
        }
        .mockup-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ef4444;
        }
        .mockup-dot:nth-child(2) { background: #eab308; }
        .mockup-dot:nth-child(3) { background: #22c55e; }
        
        .feature-card {
            border: 1px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05);
            border-color: #6366f1;
        }
        .pricing-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            transition: transform 0.3s ease;
        }
        .pricing-card.popular {
            border: 2px solid #4f46e5;
            position: relative;
        }
        .badge-popular {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #4f46e5;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="#"><i class="bi bi-layers-half text-primary me-2"></i>AdminHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navmenu">
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 px-3" href="page/login.php">Sign In</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-primary text-white px-4 shadow-sm" href="page/login.php">Live Demo <i class="bi bi-arrow-right ms-1"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-3">V2.0 Release Complete</span>
                    <h1 class="display-4 fw-black text-white mb-4 style-heading" style="font-weight: 800; letter-spacing: -1px;">Production Ready Full-Stack Admin Engine</h1>
                    <p class="lead text-white-50 mb-5 px-md-5 fs-5">Save months of raw engineering hours. A secure, interactive ecosystem featuring live authentication safeguards, product directories, and inventory managers built directly on PHP and PDO.</p>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="page/login.php" class="btn btn-light btn-lg px-4 py-3 fs-6 fw-semibold shadow">Deploy Application Instance</a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-4 py-3 fs-6">Explore Architecture</a>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="hero-mockup">
                        <div class="mockup-header">
                            <div class="mockup-dot"></div>
                            <div class="mockup-dot"></div>
                            <div class="mockup-dot"></div>
                        </div>
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80" alt="Dashboard Core Preview" class="img-fluid rounded-1 opacity-90">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="py-5 bg-white">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1">Frequently Asked Questions</h2>
                <p class="text-muted">Everything you need to know about the AdminHub source stack.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion accordion-flush shadow-sm rounded-4 border" id="faqAccordion">
                        <div class="accordion-item rounded-top-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What tech stack does AdminHub use?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    It runs entirely on raw **PHP (8.x compatible)** using secure **PDO database drivers**, stylized with modern **Bootstrap 5.3** components. No heavy node modules or complex frameworks required.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Is the database schema included in the download pack?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Yes! You receive the complete, fully structured `.sql` source files containing pre-configured definitions for the administrative managers, active user arrays, product catalogs, and transactional trackers.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item rounded-bottom-4">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Can I use this to build client projects or SaaS apps?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Absolutely. The Commercial Extended license gives you full authority to white-label the software engine and deploy it for commercial applications, client jobs, or SaaS backends.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="py-5 bg-light border-top border-bottom">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-1">Simple, Transparent Pricing</h2>
                <p class="text-muted">Get the source files and clear instructions to launch your instance.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="card pricing-card bg-white p-4 h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold text-muted mb-1">Developer License</h5>
                            <p class="small text-muted">Perfect for solo founders and developers</p>
                            <div class="my-4"><span class="display-5 fw-bold">$49</span><span class="text-muted">/one-time</span></div>
                            <hr>
                            <ul class="list-unstyled space-y-3 my-4">
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> Full Source Code Access</li>
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> Unlimited Database Tables</li>
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> 1 Year of Core Updates</li>
                                <li class="text-muted"><i class="bi bi-x text-danger me-2"></i> Premium Priority Support</li>
                            </ul>
                            <a href="page/login.php" class="btn btn-outline-primary w-100 py-2 mt-2">Purchase Source Stack</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="card pricing-card popular bg-white p-4 h-100 shadow">
                        <div class="badge-popular">Most Popular</div>
                        <div class="card-body">
                            <h5 class="fw-bold text-primary mb-1">Extended Commercial</h5>
                            <p class="small text-muted">For businesses requiring production deployments</p>
                            <div class="my-4"><span class="display-5 fw-bold">$129</span><span class="text-muted">/one-time</span></div>
                            <hr>
                            <ul class="list-unstyled space-y-3 my-4">
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> Everything in Developer tier</li>
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> Saas-Ready White Label License</li>
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> Direct Setup Documentation</li>
                                <li class="mb-3"><i class="bi bi-check2 text-success me-2"></i> 24/7 Server Deployment Support</li>
                            </ul>
                            <a href="page/login.php" class="btn btn-primary w-100 py-2 mt-2 shadow-sm">Buy Commercial Access</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-dark text-white py-5 text-center">
        <div class="container py-3">
            <h2 class="fw-bold mb-3 fs-2">Ready to ship your next web application?</h2>
            <p class="text-white-50 mb-4 px-md-5 mx-auto" style="max-width: 600px;">Stop building your user directories and database abstraction scripts from scratch every time.</p>
            <a href="page/login.php" class="btn btn-primary btn-lg px-5 py-3 fs-6 fw-semibold">Get Started Instantly</a>
        </div>
    </section>

    <footer class="bg-white py-4 border-top">
        <div class="container text-center text-muted small">
            <p class="mb-1">&copy; 2026 AdminHub Toolkits. All Rights Reserved.</p>
            <p class="mb-0">Designed to maximize deployment efficiency.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>