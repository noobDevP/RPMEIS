<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Army PME System</title>
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; background: linear-gradient(135deg, #0f172a, #1e293b); color: #e5eefb; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { width: min(960px, 100%); background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(148, 163, 184, 0.22); border-radius: 24px; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.35); overflow: hidden; }
        .hero { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 0; }
        .panel { padding: 32px; }
        .eyebrow { text-transform: uppercase; letter-spacing: 0.25em; font-size: 0.82rem; color: #60a5fa; }
        h1 { font-size: clamp(2rem, 4vw, 3rem); line-height: 1.08; margin: 10px 0 14px; }
        p { color: #cbd5e1; line-height: 1.6; }
        .pill-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
        .pill { border: 1px solid rgba(148, 163, 184, 0.25); border-radius: 999px; padding: 8px 12px; background: rgba(30, 41, 59, 0.9); color: #dbeafe; font-size: 0.95rem; }
        .sidebar { background: linear-gradient(180deg, #1d4ed8, #2563eb 55%, #1e40af); padding: 32px; display: flex; flex-direction: column; justify-content: center; }
        ul { margin: 0; padding-left: 18px; color: #dbeafe; }
        li { margin-bottom: 8px; }
        .small { font-size: 0.92rem; color: #bfdbfe; }
        @media (max-width: 860px) { .hero { grid-template-columns: 1fr; } .sidebar { order: -1; } }
    </style>
</head>
<body>
    <main>
        <section class="card">
            <div class="hero">
                <article class="panel">
                    <p class="eyebrow">Philippine Army</p>
                    <h1>Recruiting Office Visayas — Laboratory Information System</h1>
                    <p>
                        Streamlined applicant registration, medical examination processing, and data management for the Philippine Army Recruiting Office Visayas.
                    </p>
                    <p class="small">Authorized personnel may log in below to access station portals, registrar records, medical forms, and executive dashboards.</p>
                    <div class="pill-row">
                        <span class="pill">Applicant Registration</span>
                        <span class="pill">Medical Examination</span>
                        <span class="pill">Recruitment Processing</span>
                    </div>
                </article>
                <aside class="sidebar">
                    <h2 style="margin-top:0; font-size:1.25rem;">Quick Access</h2>
                    <ul>
                        <li><a href="{{ route('login') }}" style="color:#dbeafe;text-decoration:underline;">Staff Portal Login</a></li>
                        <li>Station Portal &mdash; Scan, process, and manage applicants</li>
                        <li>Registrar &mdash; Record keeping and applicant tracking</li>
                        <li>Executive &mdash; Analytics and overview dashboards</li>
                    </ul>
                </aside>
            </div>
        </section>
    </main>
</body>
</html>
