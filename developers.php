<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" href="assets/img/miko.png" type="image/png">
  <title>Meet the Developers</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
  <style>
    :root {
      --cream: #f5f0e8;
      --warm-white: #faf8f4;
      --deep-navy: #1a2340;
      --slate: #3d4f6e;
      --teal: #2a9d8f;
      --teal-light: #52b8ac;
      --amber: #e9c46a;
      --coral: #e76f51;
      --muted: #8492a6;
    }

    * { box-sizing: border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background-color: var(--warm-white);
      color: var(--deep-navy);
      overflow-x: hidden;
    }

    h1, h2, h3, .serif { font-family: 'DM Serif Display', serif; }

    /* ── Animated gradient mesh background ── */
    .hero-bg {
      background: linear-gradient(135deg, #1a2340 0%, #2a3f6e 40%, #1e4a4a 80%, #2a9d8f 100%);
      position: relative;
      overflow: hidden;
    }
    .hero-bg::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 80% 60% at 20% 30%, rgba(42,157,143,0.25) 0%, transparent 60%),
                  radial-gradient(ellipse 60% 80% at 80% 70%, rgba(233,196,106,0.15) 0%, transparent 55%),
                  radial-gradient(ellipse 50% 50% at 50% 100%, rgba(231,111,81,0.12) 0%, transparent 60%);
      animation: meshShift 8s ease-in-out infinite alternate;
    }
    @keyframes meshShift {
      0%   { opacity: 1; transform: scale(1) translate(0,0); }
      100% { opacity: 0.85; transform: scale(1.04) translate(10px, -8px); }
    }

    .hero-bg::after {
      content: '';
      position: absolute; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      opacity: 0.35;
      pointer-events: none;
    }

    /* ── Floating orbs ── */
    .orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(60px);
      animation: float 10s ease-in-out infinite;
      pointer-events: none;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0) scale(1); }
      50%       { transform: translateY(-20px) scale(1.05); }
    }

    /* ── Section divider ── */
    .wave-divider { width: 100%; overflow: hidden; line-height: 0; }
    .wave-divider svg { display: block; }

    /* ── Card hover effects ── */
    .dev-card {
      transition: transform 0.35s cubic-bezier(.34,1.56,.64,1), box-shadow 0.35s ease;
      will-change: transform;
    }
    .dev-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 24px 60px rgba(26,35,64,0.15);
    }
    .dev-card:hover .card-accent { transform: scaleX(1); }
    .card-accent {
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s ease;
    }

    /* ── Avatar ring animation ── */
    .avatar-ring {
      background: conic-gradient(var(--teal), var(--amber), var(--coral), var(--teal));
      border-radius: 50%;
      padding: 3px;
    }
    .avatar-inner {
      background: white;
      border-radius: 50%;
      padding: 3px;
    }

    /* ── Fade-in on scroll ── */
    .fade-up {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .fade-up.visible { opacity: 1; transform: translateY(0); }

    /* ── Badge tags ── */
    .tech-badge {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .tech-badge:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(42,157,143,0.25);
    }

    /* ── Social icon hover ── */
    .social-btn {
      transition: background 0.2s, color 0.2s, transform 0.2s;
    }
    .social-btn:hover { transform: scale(1.12); }

    /* ── Animated underline heading ── */
    .underline-anim {
      position: relative;
      display: inline-block;
    }
    .underline-anim::after {
      content: '';
      position: absolute;
      bottom: -4px; left: 0;
      width: 100%; height: 3px;
      background: linear-gradient(90deg, var(--teal), var(--amber));
      border-radius: 2px;
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.5s ease;
    }
    .underline-anim.visible::after { transform: scaleX(1); }

    /* ── Stat cards ── */
    .stat-card {
      border-top: 3px solid;
      transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-4px); }

    /* ── System info tabs ── */
    .sys-tab {
      cursor: pointer;
      transition: all 0.25s ease;
      border-bottom: 2px solid transparent;
    }
    .sys-tab.active {
      border-bottom-color: var(--teal);
      color: var(--deep-navy);
    }
    .sys-tab-panel { display: none; }
    .sys-tab-panel.active { display: block; }
  </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════
     HERO SECTION
════════════════════════════════════════════════ -->
<section class="hero-bg relative min-h-screen flex flex-col items-center justify-center px-6 py-24 text-white">

  <div class="orb w-96 h-96 bg-teal-400 opacity-10 top-10 -left-20" style="animation-delay:0s"></div>
  <div class="orb w-72 h-72 bg-amber-300 opacity-10 bottom-20 right-10" style="animation-delay:3s"></div>
  <div class="orb w-56 h-56 bg-red-400 opacity-8 top-1/2 right-1/3" style="animation-delay:5s"></div>

  <div class="relative z-10 max-w-4xl mx-auto text-center">
    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 text-sm font-medium text-teal-200 mb-8 fade-up">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
      </svg>
      IT Internship Project — 2026
    </div>

    <h1 class="serif text-6xl md:text-8xl font-normal leading-tight mb-6 fade-up" style="transition-delay:0.1s">
      Meet the<br>
      <span class="italic" style="color: var(--amber);">Developers</span>
    </h1>

    <p class="text-lg md:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed fade-up" style="transition-delay:0.2s">
      This system was designed and developed by <strong class="text-white font-semibold">IT Interns</strong> as part of their internship project to support the organization's digital processes, streamline workflows, and modernize internal operations.
    </p>



    <div class="mt-12 fade-up" style="transition-delay:0.4s">
      <a href="#ict-unit" class="inline-flex flex-col items-center gap-2 text-sm text-slate-400 hover:text-teal-300 transition-colors">
        <span>Explore the Team</span>
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </a>
    </div>
  </div>
</section>

<div class="wave-divider" style="background: var(--deep-navy); margin-bottom: -2px;">
  <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
    <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="var(--warm-white)"/>
  </svg>
</div>

<!-- ═══════════════════════════════════════════════
     ABOUT THE TEAM SECTION
════════════════════════════════════════════════ -->
<section class="py-20 px-6" style="background: var(--warm-white);">
  <div class="max-w-5xl mx-auto">
    <div class="grid md:grid-cols-2 gap-12 items-center">
      <div class="fade-up">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3" style="color: var(--teal);">About the Team</p>
        <h2 class="serif text-4xl md:text-5xl leading-tight mb-6 underline-anim" style="color: var(--deep-navy);">
          Interns Who<br>Built Something Real
        </h2>
        <p class="text-base leading-relaxed mb-4" style="color: var(--slate);">
          The developers behind this system are IT interns who dedicated their internship program to building a fully functional, production-ready web application for the organization. Under the guidance of their supervisors, they worked collaboratively across every phase of the project.
        </p>
        <p class="text-base leading-relaxed" style="color: var(--slate);">
          From initial planning and system architecture to database design, backend development, frontend implementation,
          UI/UX refinement, and system deployment—each intern played a key role in delivering a solution
          that meets the organization's real operational needs.
        </p>
      </div>

      <div class="grid grid-cols-2 gap-4 fade-up" style="transition-delay:0.15s">
        <div class="p-5 rounded-2xl" style="background: var(--cream);">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: var(--teal); color: white;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <h4 class="font-semibold text-sm mb-1" style="color: var(--deep-navy);">System Design</h4>
          <p class="text-xs leading-relaxed" style="color: var(--muted);">Architecture planning and technical specifications</p>
        </div>

        <div class="p-5 rounded-2xl" style="background: var(--cream);">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: #e76f51; color: white;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
          </div>
          <h4 class="font-semibold text-sm mb-1" style="color: var(--deep-navy);">Database</h4>
          <p class="text-xs leading-relaxed" style="color: var(--muted);">Structured data modeling and MySQL implementation</p>
        </div>

        <div class="p-5 rounded-2xl" style="background: var(--cream);">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: #e9c46a; color: #1a2340;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
          </div>
          <h4 class="font-semibold text-sm mb-1" style="color: var(--deep-navy);">Development</h4>
          <p class="text-xs leading-relaxed" style="color: var(--muted);">Full-stack coding from backend PHP to frontend HTML</p>
        </div>

        <div class="p-5 rounded-2xl" style="background: var(--cream);">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: #264653; color: white;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
          <h4 class="font-semibold text-sm mb-1" style="color: var(--deep-navy);">Deployment</h4>
          <p class="text-xs leading-relaxed" style="color: var(--muted);">Server setup, configuration and application release</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     ICT UNIT SECTION
════════════════════════════════════════════════ -->
<section id="ict-unit" class="py-20 px-6" style="background: var(--cream);">
  <div class="max-w-6xl mx-auto">

    <div class="text-center mb-16 fade-up">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3" style="color: var(--teal);">The Masters</p>
      <h2 class="serif text-4xl md:text-5xl mb-4" style="color: var(--deep-navy);">ICT Unit</h2>
      <p class="text-base max-w-xl mx-auto" style="color: var(--muted);">The people who guided, supervised, and supported the team throughout the project.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-7">

      <!-- ICT Card 1 -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.05s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #2a9d8f, #e9c46a);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/carl.jpg" alt="Carlou Adao" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Carlou Adao, LPT, MPA</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#e6f6f4; color: var(--teal);">Information Technology Officer I</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">A dedicated educator and digital strategist leading ICT initiatives for the DepEd Schools Division of Dasmariñas City, overseeing the DepEd Computerization Program and driving award-winning digital innovations.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
            <a href="https://www.linkedin.com/in/carlou-adao-lpt-mpa-012a8a129/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #0a66c2;" title="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452H16.9v-5.569c0-1.328-.027-3.037-1.851-3.037-1.853 0-2.136 1.445-2.136 2.94v5.666H9.367V9h3.407v1.561h.049c.474-.9 1.632-1.85 3.357-1.85 3.591 0 4.255 2.363 4.255 5.438v6.303zM5.337 7.433a1.98 1.98 0 1 1 0-3.961 1.98 1.98 0 0 1 0 3.961zM6.88 20.452H3.792V9H6.88v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- ICT Card 2 -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.1s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #e76f51, #e9c46a);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#e76f51, #e9c46a, #264653, #e76f51);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/boyet.jpg" alt="Cristopher Historillo" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Cristopher Historillo</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#fdf0ec; color: #e76f51;">Administrative Assistant III</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Provides advanced administrative support, including managing documents, coordinating schedules, preparing reports, and assisting in office operations with minimal supervision.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
		<a href="https://www.linkedin.com/in/christopher-historillo-40721697/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #0a66c2;" title="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452H16.9v-5.569c0-1.328-.027-3.037-1.851-3.037-1.853 0-2.136 1.445-2.136 2.94v5.666H9.367V9h3.407v1.561h.049c.474-.9 1.632-1.85 3.357-1.85 3.591 0 4.255 2.363 4.255 5.438v6.303zM5.337 7.433a1.98 1.98 0 1 1 0-3.961 1.98 1.98 0 0 1 0 3.961zM6.88 20.452H3.792V9H6.88v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- ICT Card 3 -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.15s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #e9c46a, #2a9d8f);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#e9c46a, #264653, #2a9d8f, #e9c46a);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/edwin.jpg" alt="Edwin Jr. D. Manlangit" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Edwin Jr. D. Manlangit</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#e8f0ee; color: #264653;">ICT Support Staff</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Provides technical assistance by maintaining computer systems, troubleshooting hardware and software issues, supporting network operations, and ensuring smooth day-to-day IT functionality.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
            <a href="https://www.linkedin.com/in/edwin-jr-manlangit-403391122/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #0a66c2;" title="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452H16.9v-5.569c0-1.328-.027-3.037-1.851-3.037-1.853 0-2.136 1.445-2.136 2.94v5.666H9.367V9h3.407v1.561h.049c.474-.9 1.632-1.85 3.357-1.85 3.591 0 4.255 2.363 4.255 5.438v6.303zM5.337 7.433a1.98 1.98 0 1 1 0-3.961 1.98 1.98 0 0 1 0 3.961zM6.88 20.452H3.792V9H6.88v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
            <a href="https://dwnnauthy28.github.io/my-portfolio/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #181717;" title="Portfolio">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297a12 12 0 0 0-3.793 23.4c.6.111.82-.261.82-.577v-2.02c-3.338.726-4.042-1.61-4.042-1.61-.546-1.385-1.333-1.753-1.333-1.753-1.089-.745.083-.73.083-.73 1.205.085 1.84 1.238 1.84 1.238 1.07 1.833 2.809 1.304 3.495.997.108-.776.418-1.305.76-1.604-2.665-.305-5.467-1.332-5.467-5.931 0-1.31.469-2.381 1.236-3.221-.124-.303-.536-1.527.117-3.176 0 0 1.008-.322 3.301 1.23a11.52 11.52 0 0 1 6.003 0c2.292-1.552 3.299-1.23 3.299-1.23.653 1.649.241 2.873.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.61-2.807 5.624-5.479 5.921.43.371.814 1.103.814 2.222v3.293c0 .319.218.694.825.576A12.001 12.001 0 0 0 12 .297"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- ICT Card 4 -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.2s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #264653, #2a9d8f);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#52b8ac, #1a2340, #e9c46a, #52b8ac);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/migs.jpg" alt="Miguel Jay Faustino" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Miguel Jay Faustino</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#fdf8e8; color: #b8912a;">ICT Support Staff</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Provides technical assistance by maintaining computer systems, troubleshooting hardware and software issues, supporting network operations, and ensuring smooth day-to-day IT functionality.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     DEVELOPER TEAM SECTION
════════════════════════════════════════════════ -->
<section id="team" class="py-20 px-6" style="background: var(--cream); border-top: 1px solid rgba(0,0,0,0.05);">
  <div class="max-w-6xl mx-auto">

    <div class="text-center mb-16 fade-up">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3" style="color: var(--teal);">The Students</p>
      <h2 class="serif text-4xl md:text-5xl mb-4" style="color: var(--deep-navy);">Developer Team</h2>
      <p class="text-base max-w-xl mx-auto" style="color: var(--muted);">Each member brought unique skills and dedication that made this project a success.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">

      <!-- Card 1 — Miko R. Vargas -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.05s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #2a9d8f, #e9c46a);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/miko.png" alt="Miko R. Vargas" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Miko R. Vargas</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#e6f6f4; color: var(--teal);">Team Lead · Full-Stack Dev</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Led the overall system architecture and guided the full-stack development process. Designed and implemented core backend modules, developed scalable frontend components, and structured the database schema to ensure efficient, secure, and maintainable applications.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/Cadee123" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
   	    <a href="https://mekscsks.github.io/portfolio/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #181717;" title="Portfolio">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297a12 12 0 0 0-3.793 23.4c.6.111.82-.261.82-.577v-2.02c-3.338.726-4.042-1.61-4.042-1.61-.546-1.385-1.333-1.753-1.333-1.753-1.089-.745.083-.73.083-.73 1.205.085 1.84 1.238 1.84 1.238 1.07 1.833 2.809 1.304 3.495.997.108-.776.418-1.305.76-1.604-2.665-.305-5.467-1.332-5.467-5.931 0-1.31.469-2.381 1.236-3.221-.124-.303-.536-1.527.117-3.176 0 0 1.008-.322 3.301 1.23a11.52 11.52 0 0 1 6.003 0c2.292-1.552 3.299-1.23 3.299-1.23.653 1.649.241 2.873.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.61-2.807 5.624-5.479 5.921.43.371.814 1.103.814 2.222v3.293c0 .319.218.694.825.576A12.001 12.001 0 0 0 12 .297"/></svg>
            </a>
		 <a href="https://www.linkedin.com/in/miko-vargas-b3803a155/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #0a66c2;" title="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452H16.9v-5.569c0-1.328-.027-3.037-1.851-3.037-1.853 0-2.136 1.445-2.136 2.94v5.666H9.367V9h3.407v1.561h.049c.474-.9 1.632-1.85 3.357-1.85 3.591 0 4.255 2.363 4.255 5.438v6.303zM5.337 7.433a1.98 1.98 0 1 1 0-3.961 1.98 1.98 0 0 1 0 3.961zM6.88 20.452H3.792V9H6.88v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>

          </div>
        </div>
      </div>

      <!-- Card 2 — Tricia Kate E. Vila -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.1s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #e76f51, #e9c46a);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#e76f51, #e9c46a, #264653, #e76f51);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/kate.png" alt="Tricia Kate E. Vila" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Tricia Kate E. Vila</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#fdf0ec; color: #e76f51;">Full-Stack Dev</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Developed and maintained full-stack web applications using modern frontend and backend technologies. Designed responsive user interfaces, implemented RESTful APIs, and managed database operations while ensuring data integrity, performance optimization, and application security.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/kate.vila.740325" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Card 3 — Glenn Joshua D. Moscatiles -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.15s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #e9c46a, #2a9d8f);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#e9c46a, #264653, #2a9d8f, #e9c46a);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/glenn.png" alt="Glenn Joshua D. Moscatiles" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Glenn Joshua D. Moscatiles</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#e8f0ee; color: #264653;">Frontend Dev & DevOps</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Developed responsive user interfaces using HTML and CSS, creating interactive UI components for enhanced user experience. Integrated frontend views with backend data through dynamic PHP templates and supported DevOps operations including application deployment and server configuration.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/juswhat" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
		</a>
		 <a href="https://www.linkedin.com/in/glenn-joshua-moscatiles-99b165238/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #0a66c2;" title="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452H16.9v-5.569c0-1.328-.027-3.037-1.851-3.037-1.853 0-2.136 1.445-2.136 2.94v5.666H9.367V9h3.407v1.561h.049c.474-.9 1.632-1.85 3.357-1.85 3.591 0 4.255 2.363 4.255 5.438v6.303zM5.337 7.433a1.98 1.98 0 1 1 0-3.961 1.98 1.98 0 0 1 0 3.961zM6.88 20.452H3.792V9H6.88v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>

          </div>
        </div>
      </div>

      <!-- Card 4 — Ervin E. Elardo -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.2s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #264653, #2a9d8f);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#52b8ac, #1a2340, #e9c46a, #52b8ac);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/ervin.png" alt="Ervin E. Elardo" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Ervin E. Elardo</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#fdf8e8; color: #b8912a;">Quality Assurance</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Tested application features and functionality to identify bugs and ensure software quality. Created and executed test cases, documented issues, and collaborated with developers to resolve defects and improve system performance.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/ervinelardo" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
 		<a href="https://www.linkedin.com/in/ervin-elardo-0687a13ba/" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #0a66c2;" title="LinkedIn">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452H16.9v-5.569c0-1.328-.027-3.037-1.851-3.037-1.853 0-2.136 1.445-2.136 2.94v5.666H9.367V9h3.407v1.561h.049c.474-.9 1.632-1.85 3.357-1.85 3.591 0 4.255 2.363 4.255 5.438v6.303zM5.337 7.433a1.98 1.98 0 1 1 0-3.961 1.98 1.98 0 0 1 0 3.961zM6.88 20.452H3.792V9H6.88v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>

          </div>
        </div>
      </div>

      <!-- Card 5 — John Mark D. Onias -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.25s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #2a9d8f, #e76f51);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#e76f51, #2a9d8f, #e9c46a, #e76f51);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/mark.png" alt="John Mark D. Onias" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">John Mark D. Onias</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#eef0f6; color: #2a3f6e;">Quality Assurance</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Tested application features and functionality to identify bugs and ensure software quality. Created and executed test cases, documented issues, and collaborated with developers to resolve defects and improve system performance.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/Onias.13" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Card 6 — Fatima Granaderos -->
      <div class="dev-card bg-white rounded-3xl shadow-md overflow-hidden fade-up" style="transition-delay:0.3s">
        <div class="card-accent h-1 w-full" style="background: linear-gradient(90deg, #e9c46a, #e76f51);"></div>
        <div class="p-7 flex flex-col items-center text-center">
          <div class="avatar-ring mb-4 w-24 h-24 flex-shrink-0" style="background: conic-gradient(#264653, #e76f51, #e9c46a, #264653);">
            <div class="avatar-inner w-full h-full">
              <img src="/assets/pictures/fatima.jpg" alt="Fatima Granaderos" class="w-full h-full rounded-full object-cover">
            </div>
          </div>
          <h3 class="serif text-xl mb-1" style="color: var(--deep-navy);">Fatima Granaderos</h3>
          <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full mb-3" style="background:#fdf0ec; color: #c45a3a;">IT Support</span>
          <p class="text-sm leading-relaxed mb-5" style="color: var(--muted);">Provided technical support for development teams by troubleshooting software, hardware, and network issues. Assisted in maintaining development environments, managing system configurations, and ensuring smooth operation of development tools and platforms.</p>
          <div class="flex gap-3 mt-auto">
            <a href="https://facebook.com/fatimagranaderos" class="social-btn w-9 h-9 rounded-full flex items-center justify-center text-white" style="background: #1877f2;" title="Facebook">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35C.597 0 0 .597 0 1.326v21.348C0 23.403.597 24 1.326 24H12.82v-9.294H9.692V11.01h3.128V8.309c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.796.715-1.796 1.763v2.312h3.587l-.467 3.696h-3.12V24h6.116C23.403 24 24 23.403 24 22.674V1.326C24 .597 23.403 0 22.675 0z"/></svg>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     SYSTEM INFO SECTION — single, clean version
════════════════════════════════════════════════ -->
<section class="py-20 px-6" style="background: var(--warm-white);">
  <div class="max-w-5xl mx-auto">

    <div class="text-center mb-14 fade-up">
      <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3" style="color: var(--teal);">Project Details</p>
      <h2 class="serif text-4xl md:text-5xl" style="color: var(--deep-navy);">System Information</h2>
    </div>

    <div class="rounded-3xl overflow-hidden shadow-xl fade-up" style="transition-delay:0.1s">
      <!-- Mac-style header bar -->
      <div class="px-8 py-5 flex items-center gap-3" style="background: var(--deep-navy);">
        <div class="flex gap-2">
          <div class="w-3 h-3 rounded-full bg-red-400"></div>
          <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
          <div class="w-3 h-3 rounded-full bg-green-400"></div>
        </div>
        <span class="text-xs text-slate-400 font-mono ml-2">system_info.config</span>
      </div>

      <!-- Info body -->
      <div class="p-8 md:p-10" style="background: #f8f6f1;">
        <div class="grid md:grid-cols-2 gap-10">

          <!-- Left column: project metadata -->
          <div class="space-y-6">
            <div>
              <p class="text-xs uppercase tracking-widest mb-2" style="color: var(--muted);">Systems Built</p>
              <div class="space-y-2">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full flex-shrink-0" style="background: var(--teal);"></div>
                  <p class="serif text-xl" style="color: var(--deep-navy);">Procurement System</p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full flex-shrink-0" style="background: var(--amber);"></div>
                  <p class="serif text-xl" style="color: var(--deep-navy);">Service Record System</p>
                </div>
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full flex-shrink-0" style="background: var(--coral);"></div>
                  <p class="serif text-xl" style="color: var(--deep-navy);">Saro System</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
              <div class="p-4 rounded-2xl" style="background: white; border: 1px solid rgba(0,0,0,0.06);">
                <p class="text-xs uppercase tracking-widest mb-1" style="color: var(--muted);">Developed During</p>
                <p class="font-semibold text-sm" style="color: var(--slate);">IT Internship Program</p>
              </div>
              <div class="p-4 rounded-2xl" style="background: white; border: 1px solid rgba(0,0,0,0.06);">
                <p class="text-xs uppercase tracking-widest mb-1" style="color: var(--muted);">Year Developed</p>
                <p class="serif text-2xl" style="color: var(--deep-navy);">2026</p>
              </div>
              <div class="p-4 rounded-2xl col-span-2" style="background: white; border: 1px solid rgba(0,0,0,0.06);">
                <p class="text-xs uppercase tracking-widest mb-1" style="color: var(--muted);">Organization</p>
                <p class="font-semibold text-sm" style="color: var(--slate);">DepEd Dasmariñas — ICT Unit</p>
              </div>
            </div>
          </div>

          <!-- Right column: tech stack -->
          <div>
            <p class="text-xs uppercase tracking-widest mb-4" style="color: var(--muted);">Technologies Used</p>
            <div class="flex flex-wrap gap-3">
              <span class="tech-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" style="background: #fff3e0; color: #e06c00; border: 1.5px solid #f5c28a;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.565-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>
                HTML5
              </span>
              <span class="tech-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" style="background: #f1f8e9; color: #33691e; border: 1.5px solid #aed581;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M1.292 8.126h21.418l-1.91 13.686L12 24l-8.8-2.188L1.292 8.126zM3 0h18l-1.2 5.4H4.2L3 0zm9 14.4l-3.6-1.2-.6 2.4 4.2 1.2 4.2-1.2-.6-2.4L12 14.4zm0-9.6L7.2 6l.6 2.4h8.4L16.8 6 12 4.8z"/></svg>
                CSS3
              </span>
              <span class="tech-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" style="background: #e0f7fa; color: #006064; border: 1.5px solid #80cbc4;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.001 4.8c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624C13.666 10.618 15.027 12 18.001 12c3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C16.337 6.182 14.976 4.8 12.001 4.8zm-6 7.2c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624 1.177 1.194 2.538 2.576 5.512 2.576 3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C10.337 13.382 8.976 12 6.001 12z"/></svg>
                Tailwind CSS
              </span>
              <span class="tech-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" style="background: #e8f4fd; color: #1565c0; border: 1.5px solid #90caf9;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.84 1.515-.66.39.12.75.42.976.9 1.034-.676 1.034-.676 1.755-1.125-.27-.42-.404-.601-.586-.78-.63-.705-1.469-1.065-2.834-1.034l-.705.089c-.676.165-1.32.525-1.71 1.005-1.14 1.291-.811 3.541.569 4.471 1.365 1.02 3.361 1.244 3.616 2.205.24 1.17-.87 1.545-1.966 1.41-.811-.18-1.26-.586-1.755-1.336l-1.83 1.051c.21.48.45.689.81 1.109 1.74 1.756 6.09 1.666 6.871-1.004.029-.09.24-.705.074-1.65l.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.51 2.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l.004-.056z"/></svg>
                JavaScript
              </span>
              <span class="tech-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" style="background: #ede7f6; color: #4527a0; border: 1.5px solid #b39ddb;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.01 10.207h-.944l-.515 2.648h.838c.556 0 .97-.105 1.242-.314.272-.21.455-.559.55-1.049.092-.47.05-.802-.124-.995-.175-.193-.523-.29-1.047-.29zM12 .296l-9.75 5.63v11.549L12 23.104l9.75-5.629V5.926L12 .296zm-.127 12.496c-.426.34-.932.571-1.518.694a9.391 9.391 0 01-1.538.093H7.241l-.524 2.692H5.315l1.588-8.097H9.13c.762 0 1.351.064 1.769.191.418.127.742.354.974.68.23.327.354.7.354 1.116-.001.574-.12 1.085-.354 1.631z"/></svg>
                PHP
              </span>
              <span class="tech-badge inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" style="background: #fff8e1; color: #ff6f00; border: 1.5px solid #ffcc80;">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16.405 5.501c-.115 0-.193.014-.274.033v.013h.014c.054.104.146.18.214.273.054.107.1.214.154.32l.014-.015c.094-.066.14-.172.14-.333-.04-.047-.046-.094-.08-.14-.04-.067-.126-.1-.182-.151zM5.77 18.695h-.927a50.854 50.854 0 00-.27-4.41c1.02 1.51 2.058 3.021 3.085 4.41H5.77zm5.92 0h-.04c-.025-.285-.25-3.515-.667-4.41-.14.245-1.24 4.41-1.373 4.41H9.01c-.065-.085-1.375-3.54-1.495-3.82.425.305 2.44 3.395 2.55 3.82h.01zm3.08 0h-.03c-.07-.17-1.06-4.09-1.21-4.41-.06.205-.49 4.41-.55 4.41h-.67c-.025-.315-.25-3.49-.65-4.41-.03.22-.54 4.41-.54 4.41h-.67c.09-.775 1.29-4.245 1.45-4.41.075.205 1.04 4.41 1.14 4.41h.61c.125-.335 1.12-4.41 1.19-4.41.08.22 1.06 4.245 1.12 4.41zm3.07 0h-.77c-.065-.17-1.04-4.09-1.145-4.41l-.66 4.41h-.65c.12-.775 1.305-4.245 1.465-4.41.08.22 1.04 4.245 1.1 4.41h.66zM24 10.5c0 5.799-4.701 10.5-10.5 10.5S3 16.299 3 10.5 7.701 0 13.5 0 24 4.701 24 10.5z"/></svg>
                MySQL
              </span>
            </div>

            <!-- System description blurb -->
            <div class="mt-6 p-5 rounded-2xl" style="background: white; border: 1px solid rgba(0,0,0,0.06);">
              <p class="text-xs uppercase tracking-widest mb-2" style="color: var(--muted);">About the Systems</p>
              <p class="text-sm leading-relaxed" style="color: var(--slate);">Three interconnected web-based systems were developed to digitize and streamline core operations of the DepEd Dasmariñas ICT Unit — covering procurement workflows, employee service records, and SARO (Special Allotment Release Order) management.</p>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</section>

<!-- ═══════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════ -->
<footer class="py-12 px-6" style="background: var(--deep-navy);">
  <div class="max-w-2xl mx-auto text-center">
    <div class="flex items-center gap-4 justify-center mb-8">
      <div class="h-px flex-1" style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15));"></div>
      <div class="w-2 h-2 rounded-full" style="background: var(--teal);"></div>
      <div class="h-px flex-1" style="background: linear-gradient(90deg, rgba(255,255,255,0.15), transparent);"></div>
    </div>

    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl mb-5" style="background: rgba(42,157,143,0.2); border: 1px solid rgba(42,157,143,0.3);">
      <svg class="w-6 h-6" fill="none" stroke="#2a9d8f" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/>
      </svg>
    </div>

    <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.55);">
      Developed by <span style="color: var(--teal);">IT Intern Developers</span> as part of the<br>
      <span style="color: rgba(255,255,255,0.75);">System Development Internship Project</span>
    </p>

    <div class="mt-6 flex items-center justify-center gap-2 text-xs" style="color: rgba(255,255,255,0.3);">
      <span>©</span>
      <span id="year">2026</span>
      <span>·</span>
      <span>All Rights Reserved</span>
    </div>
  </div>
</footer>

<script>
  document.getElementById('year').textContent = new Date().getFullYear();

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

  const ulObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) entry.target.classList.add('visible');
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('.underline-anim').forEach(el => ulObserver.observe(el));

  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
  });
</script>
</body>
</html>