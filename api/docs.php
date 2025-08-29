<?php
// /api/docs.php

declare(strict_types=1);

// --- Konfigurasi & Pengambilan Data Dinamis ---
require_once '../config.php';
$sample_api_key = 'KUNCI_API_ANDA_AKAN_MUNCUL_DI_SINI';
$conn = connectToDatabase();
if ($conn) {
    $result = $conn->query("SELECT api_key FROM api_keys WHERE is_active = 1 AND permissions = 'read_write' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $sample_api_key = $row['api_key'];
    }
    $conn->close();
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http") . "://" . $_SERVER['HTTP_HOST'];
$api_base_path = dirname($_SERVER['SCRIPT_NAME']);
$full_api_url = rtrim($base_url . $api_base_path, '/');
$api_v1_url = $full_api_url . '/v1';

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
            --bg: #111827; --sidebar-bg: #1f2937; --content-bg: #111827;
            --text-primary: #e5e7eb; --text-secondary: #9ca3af; --text-heading: #ffffff;
            --border-color: #374151; --accent-blue: #3b82f6; --accent-green: #22c55e;
            --accent-yellow: #f59e0b; --code-bg: #374151; --code-text: #e5e7eb;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif; background-color: var(--bg);
            color: var(--text-primary); margin: 0; display: flex;
            scroll-behavior: smooth;
        }
        .sidebar {
            width: 280px; background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color); padding: 2rem;
            position: fixed; height: 100%; overflow-y: auto;
        }
        .sidebar h1 {
            font-size: 1.2rem; font-weight: 600; color: var(--text-heading);
            margin-top: 0; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);
        }
        .sidebar nav ul { list-style: none; padding: 0; margin: 1.5rem 0 0 0; }
        .sidebar nav li a {
            display: block; padding: 0.6rem 0.2rem; color: var(--text-secondary);
            text-decoration: none; font-weight: 500; transition: color 0.2s;
            font-size: 0.9rem; border-left: 2px solid transparent; padding-left: 0.5rem;
        }
        .sidebar nav li a:hover, .sidebar nav li a.active { color: var(--text-heading); border-left-color: var(--accent-blue); }
        .sidebar .nav-category { font-size: 0.75rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; margin-top: 1.5rem; letter-spacing: 0.5px; }

        .main-content {
            margin-left: 280px; padding: 2rem 4rem; width: calc(100% - 280px);
            display: flex; gap: 4rem;
        }
        .content-column { flex: 1; min-width: 0; max-width: 720px; }
        .code-column { width: 45%; max-width: 500px; }
        .code-column-sticky { position: sticky; top: 2rem; }
        section { padding-top: 2rem; }
        h2 { font-size: 2.2rem; font-weight: 700; color: var(--text-heading); padding-bottom: 1rem; margin: 0 0 1.5rem 0; border-bottom: 1px solid var(--border-color);}
        h3 { font-size: 1.2rem; font-weight: 600; color: var(--text-heading); margin: 2.5rem 0 1rem 0; }
        p { line-height: 1.7; margin: 0 0 1rem 0; color: var(--text-secondary); }
        code, .code { font-family: 'JetBrains Mono', monospace; background-color: var(--code-bg); color: var(--code-text); padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.85em; }
        .endpoint-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; }
        .method { font-weight: 500; padding: 0.25rem 0.6rem; border-radius: 99px; color: var(--bg); font-size: 0.8rem; text-transform: uppercase; }
        .method.get { background-color: var(--accent-blue); }
        .method.post { background-color: var(--accent-green); }
        .param-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; font-size: 0.9rem; }
        .param-table th, .param-table td { border: 1px solid var(--border-color); padding: 0.75rem; text-align: left; }
        .param-table th { background-color: var(--sidebar-bg); font-weight: 600; }
        .param-table code { font-size: 0.8em; }
        pre { background-color: #0d1117; color: var(--text-primary); padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: 'JetBrains Mono', monospace; font-size: 0.85em; white-space: pre-wrap; word-break: break-all; border: 1px solid var(--border-color); }
        .code-title { font-size: 0.9rem; font-weight: 600; color: var(--text-heading); margin-bottom: 0.5rem; }
        .code-card { background-color: var(--sidebar-bg); border-radius: 8px; border: 1px solid var(--border-color); }
        .code-card-header { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;}
        .code-card-body { padding: 0.5rem 1rem 1rem; }
        .response-status { font-size: 0.8rem; font-weight: 500; }
        .status-200 { color: var(--accent-green); }
        .status-404 { color: var(--accent-yellow); }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h1>API Sterilabel</h1>
        <nav id="sidebar-nav">
            <ul>
                <li><a href="#pendahuluan">Pendahuluan</a></li>
                <li><a href="#otentikasi">Otentikasi</a></li>
                <li class="nav-category">Endpoints</li>
                <li><a href="#labels">Labels</a></li>
                <li><a href="#inventaris">Inventaris</a></li>
                <li><a href="#proses">Proses Sterilisasi</a></li>
                <li><a href="#pengguna">Pengguna</a></li>
                <li><a href="#laporan">Laporan & Master</a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <div class="content-column">
            <section id="pendahuluan">
                <h2>Pendahuluan</h2>
                <p>Selamat datang di dokumentasi API Sterilabel versi 1. API ini memungkinkan Anda untuk berinteraksi secara programatik dengan data sterilisasi, inventaris, dan proses yang ada di dalam aplikasi.</p>
                <p>API ini didesain mengikuti prinsip REST dan mengembalikan respons dalam format JSON. Semua endpoint berada di bawah base URL berikut:</p>
                <pre><code><?php echo htmlspecialchars($api_v1_url); ?></code></pre>
            </section>

            <section id="otentikasi">
                <h2>Otentikasi</h2>
                <p>Setiap permintaan ke API harus menyertakan Kunci API yang valid di dalam HTTP Header `X-API-Key`. Kegagalan menyediakan kunci yang benar akan menghasilkan respons `401 Unauthorized`.</p>
                <p>Kunci API dapat memiliki izin `read_only` atau `read_write`. Endpoint yang melakukan perubahan data (POST, PUT, DELETE) akan menghasilkan `403 Forbidden` jika kunci yang digunakan bersifat `read_only`.</p>
            </section>

            <section id="labels">
                <h2>Labels</h2>
                <h3>Mendapatkan Detail Label</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/labels/{uid}</code></div>
                <p>Mengambil informasi detail dari sebuah label berdasarkan ID unik (UID) yang ada di QR code.</p>
                
                <h3>Menandai Label Telah Digunakan</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/labels/{uid}/mark-used</code></div>
                <p>Mengubah status sebuah label dari 'aktif' menjadi 'telah digunakan'. Aksi ini hanya dapat dilakukan pada label yang aktif.</p>
                <p><strong>Body Request (JSON):</strong></p>
                 <table class="param-table">
                    <thead><tr><th>Key</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                    <tbody><tr><td><code>note</code></td><td>string (opsional)</td><td>Catatan tambahan, misalnya nama pengguna atau lokasi penggunaan.</td></tr></tbody>
                </table>
            </section>

            <section id="inventaris">
                <h2>Inventaris</h2>
                <h3>Mendapatkan Daftar Instrumen</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/instruments</code></div>
                <p>Mengambil daftar instrumen dengan paginasi. Mendukung query parameter `page` dan `limit`.</p>
                
                <h3>Mendapatkan Detail Instrumen</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/instruments/{id}</code></div>
                <p>Mengambil detail satu instrumen spesifik berdasarkan ID-nya.</p>

                <h3>Mendapatkan Daftar Set</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/sets</code></div>
                <p>Mengambil daftar set instrumen dengan paginasi.</p>

                <h3>Mendapatkan Detail Set</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/sets/{id}</code></div>
                <p>Mengambil detail satu set, termasuk daftar instrumen di dalamnya.</p>
            </section>
            
            <section id="proses">
                <h2>Proses Sterilisasi</h2>
                <h3>Membuat Muatan Baru</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/loads</code></div>
                <p>Membuat entri muatan baru dengan status 'persiapan'.</p>
                 <p><strong>Body Request (JSON):</strong></p>
                 <table class="param-table">
                    <thead><tr><th>Key</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td><code>machine_id</code></td><td>integer</td><td><strong>Wajib.</strong> ID dari mesin yang akan digunakan.</td></tr>
                        <tr><td><code>destination_department_id</code></td><td>integer (opsional)</td><td>ID departemen tujuan.</td></tr>
                        <tr><td><code>notes</code></td><td>string (opsional)</td><td>Catatan untuk muatan.</td></tr>
                        <tr><td><code>created_by_user_id</code></td><td>integer (opsional)</td><td>ID pengguna yang membuat muatan.</td></tr>
                    </tbody>
                </table>

                <h3>Menambahkan Item ke Muatan</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/loads/{id}/items</code></div>
                <p>Menambahkan instrumen atau set ke dalam muatan yang statusnya masih 'persiapan'.</p>
                 <p><strong>Body Request (JSON):</strong></p>
                 <table class="param-table">
                    <thead><tr><th>Key</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                    <tbody>
                        <tr><td><code>item_id</code></td><td>integer</td><td><strong>Wajib.</strong> ID dari instrumen atau set.</td></tr>
                        <tr><td><code>item_type</code></td><td>string</td><td><strong>Wajib.</strong> Harus `instrument` atau `set`.</td></tr>
                    </tbody>
                </table>

                <h3>Mendapatkan Daftar Siklus</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/cycles</code></div>
                <p>Mengambil daftar riwayat siklus sterilisasi dengan paginasi.</p>

                <h3>Mendapatkan Detail Siklus</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/cycles/{id}</code></div>
                <p>Mengambil detail satu siklus, termasuk daftar muatan di dalamnya.</p>
            </section>

             <section id="pengguna">
                <h2>Pengguna</h2>
                <h3>Mendapatkan Daftar Pengguna</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/users</code></div>
                <p>Mengembalikan daftar semua pengguna di sistem.</p>
                <h3>Mendapatkan Detail Pengguna</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/users/{id}</code></div>
                <p>Mengembalikan detail seorang pengguna berdasarkan ID.</p>
            </section>

             <section id="laporan">
                <h2>Laporan & Master Data</h2>
                <h3>Mendapatkan Laporan KPI</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/reports</code></div>
                <p>Mengembalikan data Key Performance Indicator (KPI) utama seperti tingkat keberhasilan siklus dan jumlah item yang akan kedaluwarsa.</p>

                <h3>Mendapatkan Master Data</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/master/{type}</code></div>
                <p>Mengembalikan daftar data master yang aktif. Ganti `{type}` dengan `departments`, `machines`, atau `instrument-types`.</p>
            </section>
        </div>
        <div class="code-column">
            <div class="code-column-sticky">
                <div class="code-card">
                    <div class="code-card-header"><p class="code-title">Contoh Permintaan (cURL)</p></div>
                    <div class="code-card-body">
<pre><code>curl -X GET \
  "<?php echo htmlspecialchars($api_v1_url); ?>/labels/CONTOH123" \
  -H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                    </div>
                </div>

                <div class="code-card" style="margin-top: 1.5rem;">
                    <div class="code-card-header">
                        <p class="code-title">Contoh Respons</p>
                        <span class="response-status status-200">200 OK</span>
                    </div>
                     <div class="code-card-body">
<pre><code>{
  "status": "success",
  "data": {
    "label_unique_id": "CONTOH123",
    "label_title": "Set Bedah Minor",
    "item_type": "set",
    "status": "active",
    "created_at": "2025-08-28 10:00:00",
    "expiry_date": "2025-09-28 10:00:00",
    "status_display": "Aktif",
    "_links": {
      "self": {
        "href": "/api/v1/labels/CONTOH123"
      },
      "load": {
        "href": "/api/v1/loads/101"
      },
      "mark_used": {
        "href": "/api/v1/labels/CONTOH123/mark-used",
        "method": "POST"
      }
    }
  }
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.content-column section');
            const navLinks = document.querySelectorAll('#sidebar-nav a');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        navLinks.forEach(link => {
                            link.classList.toggle('active', link.getAttribute('href').substring(1) === entry.target.id);
                        });
                    }
                });
            }, { rootMargin: "-50% 0px -50% 0px" });

            sections.forEach(section => {
                observer.observe(section);
            });
        });
    </script>
</body>
</html>