<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Army PME System</title>
    <link rel="stylesheet" href="{{ asset('css/portal-home.css') }}">
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
                    <h2 class="sidebar-title">Quick Access</h2>
                    <ul>
                        <li><a href="{{ route('login') }}" class="sidebar-link">Staff Portal Login</a></li>
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
