<?php
// /api/docs.php

// Ambil contoh API Key dari database untuk ditampilkan di dokumentasi
require_once '../config.php';
$sample_api_key = 'KUNCI_API_ANDA_AKAN_MUNCUL_DI_SINI';
$conn = connectToDatabase();
if ($conn) {
    $result = $conn->query("SELECT api_key FROM api_keys WHERE is_active = 1 LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $sample_api_key = $row['api_key'];
    }
    $conn->close();
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$api_base_path = dirname($_SERVER['SCRIPT_NAME']);
$full_api_url = rtrim($base_url . $api_base_path, '/');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API Sterilabel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #111827;
            --sidebar-bg: #1f2937;
            --content-bg: #111827;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-heading: #ffffff;
            --border-color: #374151;
            --accent-blue: #3b82f6;
            --accent-green: #22c55e;
            --code-bg: #374151;
            --code-text: #e5e7eb;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-primary);
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            padding: 2rem;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        .sidebar h1 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-heading);
            margin-top: 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar nav ul { list-style: none; padding: 0; margin: 1.5rem 0 0 0; }
        .sidebar nav li a {
            display: block; padding: 0.6rem 0; color: var(--text-secondary);
            text-decoration: none; font-weight: 500; transition: color 0.2s;
            font-size: 0.9rem;
        }
        .sidebar nav li a:hover, .sidebar nav li a.active { color: var(--text-heading); }
        .sidebar .nav-category { font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; margin-top: 1.5rem; letter-spacing: 0.5px; }

        .main-content {
            margin-left: 280px;
            padding: 2rem 3rem;
            width: calc(100% - 280px);
            display: flex;
        }
        .content-column { flex: 1; min-width: 0; }
        .code-column { width: 40%; min-width: 350px; max-width: 450px; margin-left: 3rem; }
        .code-column-sticky { position: sticky; top: 2rem; }
        h2 { font-size: 2.2rem; font-weight: 700; color: var(--text-heading); padding-bottom: 1rem; margin: 0 0 1.5rem 0; }
        h3 { font-size: 1.2rem; font-weight: 600; color: var(--text-heading); margin: 2.5rem 0 1rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); }
        p { line-height: 1.7; margin-bottom: 1.5rem; color: var(--text-secondary); }
        code, .code { font-family: 'JetBrains Mono', monospace; background-color: var(--code-bg); color: var(--code-text); padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.85em; }
        .method { font-weight: 500; padding: 0.25rem 0.6rem; border-radius: 99px; color: var(--bg); font-size: 0.8rem; text-transform: uppercase; }
        .method.get { background-color: var(--accent-blue); }
        .method.post { background-color: var(--accent-green); }
        .param-table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
        .param-table th, .param-table td { border: 1px solid var(--border-color); padding: 0.75rem; text-align: left; }
        .param-table th { background-color: var(--sidebar-bg); font-weight: 600; }
        pre { background-color: var(--sidebar-bg); color: var(--text-primary); padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: 'JetBrains Mono', monospace; font-size: 0.85em; white-space: pre-wrap; word-break: break-all; border: 1px solid var(--border-color); }
        .code-title { font-size: 0.9rem; font-weight: 600; color: var(--text-heading); margin-bottom: 0.5rem; }
        .code-card { background-color: var(--sidebar-bg); border-radius: 8px; border: 1px solid var(--border-color); }
        .code-card-header { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); }
        .code-card-body { padding: 1rem; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h1>API Sterilabel</h1>
        <nav>
            <ul>
                <li><a href="#otentikasi">Otentikasi</a></li>
                <li class="nav-category">Endpoints</li>
                <li><a href="#labels">Labels</a></li>
                <li><a href="#inventaris">Inventaris</a></li>
                <li><a href="#proses">Proses Sterilisasi</a></li>
                <li><a href="#laporan">Laporan & Master</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="content-column">
            <section id="otentikasi">
                <h2>Otentikasi</h2>
                <p>Setiap permintaan ke API harus menyertakan Kunci API yang valid di dalam HTTP Header. Kegagalan menyediakan kunci yang benar akan menghasilkan respons <code>401 Unauthorized</code>.</p>
                <p>Base URL untuk API v1 adalah: <code><?php echo htmlspecialchars($full_api_url); ?>/v1</code></p>
            </section>

            <section id="labels">
                <h2>Labels</h2>
                <h3>Mendapatkan Detail Label</h3>
                <p>Mengambil informasi detail dari sebuah label berdasarkan ID unik (UID).</p>
                <p><span class="method get">GET</span> <code class="url">/labels/{uid}</code></p>

                <h3>Menandai Label Telah Digunakan</h3>
                <p>Mengubah status sebuah label dari 'aktif' menjadi 'telah digunakan'.</p>
                <p><span class="method post">POST</span> <code class="url">/labels/{uid}/mark-used</code></p>
            </section>

            <section id="inventaris">
                <h2>Inventaris</h2>
                <h3>Mendapatkan Daftar Instrumen</h3>
                <p>Mengambil daftar instrumen dengan paginasi.</p>
                <p><span class="method get">GET</span> <code class="url">/instruments</code></p>
                
                <h3>Mendapatkan Detail Instrumen</h3>
                <p>Mengambil detail satu instrumen spesifik.</p>
                <p><span class="method get">GET</span> <code class="url">/instruments/{id}</code></p>

                <h3>Mendapatkan Daftar Set</h3>
                <p>Mengambil daftar set instrumen.</p>
                <p><span class="method get">GET</span> <code class="url">/sets</code></p>

                <h3>Mendapatkan Detail Set</h3>
                <p>Mengambil detail satu set, termasuk daftar instrumen di dalamnya.</p>
                <p><span class="method get">GET</span> <code class="url">/sets/{id}</code></p>
            </section>
            
            <section id="proses">
                <h2>Proses Sterilisasi</h2>
                <h3>Membuat Muatan Baru</h3>
                <p>Membuat entri muatan baru dengan status 'persiapan'.</p>
                <p><span class="method post">POST</span> <code class="url">/loads</code></p>
                
                <h3>Menambahkan Item ke Muatan</h3>
                <p>Menambahkan item ke dalam muatan yang statusnya masih 'persiapan'.</p>
                <p><span class="method post">POST</span> <code class="url">/loads/{id}/items</code></p>

                <h3>Mendapatkan Daftar Siklus</h3>
                <p>Mengambil daftar riwayat siklus sterilisasi.</p>
                <p><span class="method get">GET</span> <code class="url">/cycles</code></p>
            </section>

             <section id="laporan">
                <h2>Laporan & Master Data</h2>
                <h3>Mendapatkan Laporan KPI</h3>
                <p>Mengembalikan data Key Performance Indicator (KPI) utama.</p>
                <p><span class="method get">GET</span> <code class="url">/reports</code></p>

                <h3>Mendapatkan Master Data</h3>
                <p>Mengembalikan daftar data master yang aktif.</p>
                <p><span class="method get">GET</span> <code class="url">/master/{type}</code></p>
                <p>Ganti <code>{type}</code> dengan salah satu dari: <code>departments</code>, <code>machines</code>, <code>instrument-types</code>.</p>
            </section>

        </div>
        <div class="code-column">
            <div class="code-column-sticky">
                <div class="code-card">
                    <div class="code-card-header">
                        <p class="code-title">Contoh Permintaan (cURL)</p>
                    </div>
                    <div class="code-card-body">
<pre><code>curl -X GET \
  "<?php echo htmlspecialchars($full_api_url); ?>/v1/labels/CONTOH123" \
  -H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                    </div>
                </div>

                <div class="code-card" style="margin-top: 1.5rem;">
                    <div class="code-card-header">
                        <p class="code-title">Contoh Respons</p>
                    </div>
                     <div class="code-card-body">
<pre><code>{
    "success": true,
    "data": {
        "label_unique_id": "CONTOH123",
        "status": "active",
        "expiry_date": "2025-09-28 10:00:00",
        "item_name": "Gunting Bedah"
    }
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>