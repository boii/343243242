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

// --- PERBAIKAN: Logika URL yang lebih elegan dan tangguh ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'steril.rejiva.my.id'; // Fallback ke host yang diketahui jika tidak terdeteksi
$api_base_path = dirname($_SERVER['SCRIPT_NAME']);
$full_api_url = rtrim($protocol . $host . $api_base_path, '/');
$api_v1_url = $full_api_url . '/v1';
// --- AKHIR PERBAIKAN ---

$appInstanceName = $app_settings['app_instance_name'] ?? 'Sterilabel';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi API Sterilabel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-light: #f4f7f6;
            --sidebar-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --accent-blue: #3b82f6;
            --accent-green: #22c55e;
            --accent-yellow: #f59e0b;
            --code-bg: #f3f4f6;
            --code-text: #1f2937;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-secondary);
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
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 1rem 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar nav ul { list-style: none; padding: 0; margin: 1.5rem 0 0 0; }
        .sidebar nav li a {
            display: block;
            padding: 0.6rem 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s, border-color 0.2s, background-color 0.2s;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
            border-radius: 0 4px 4px 0;
        }
        .sidebar nav li a:hover {
            color: var(--text-primary);
            background-color: #f9fafb;
        }
        .sidebar nav li a.active {
            color: var(--accent-blue);
            border-left-color: var(--accent-blue);
            font-weight: 700;
        }
        .sidebar .nav-category {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 1.5rem;
            letter-spacing: 0.5px;
            padding: 0 0.5rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem 4rem;
            width: calc(100% - 280px);
            display: flex;
            gap: 4rem;
        }
        .content-column { flex: 1; min-width: 0; max-width: 720px; }
        .code-column { width: 45%; max-width: 500px; }
        .code-column-sticky { position: sticky; top: 2rem; }
        
        section { padding-top: 2.5rem; }
        h2 { font-size: 2.25rem; font-weight: 800; color: var(--text-primary); padding-bottom: 1rem; margin: 0 0 1.5rem 0; border-bottom: 1px solid var(--border-color); }
        h3 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 2.5rem 0 1rem 0; }
        p { line-height: 1.7; margin: 0 0 1rem 0; }
        ul { margin: 0 0 1rem 0; padding-left: 1.5rem; line-height: 1.7; }
        li { margin-bottom: 0.5rem; }
        
        code, .code { font-family: 'JetBrains Mono', monospace; background-color: var(--code-bg); color: var(--code-text); padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.875em; border: 1px solid #e5e7eb; }
        .endpoint-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; font-family: 'JetBrains Mono', monospace;}
        .method { font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 99px; color: white; font-size: 0.8rem; text-transform: uppercase; }
        .method.get { background-color: var(--accent-blue); }
        .method.post { background-color: var(--accent-green); }
        
        .param-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; font-size: 0.9rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; }
        .param-table th, .param-table td { border-bottom: 1px solid var(--border-color); padding: 0.8rem 1rem; text-align: left; }
        .param-table tr:last-child td { border-bottom: none; }
        .param-table th { background-color: #f9fafb; font-weight: 600; color: var(--text-primary); }
        .param-table code { font-size: 0.8em; }
        
        pre { background-color: var(--code-bg); color: var(--text-primary); padding: 1rem; border-radius: 8px; margin: 1rem 0; font-family: 'JetBrains Mono', monospace; font-size: 0.85em; white-space: pre-wrap; word-break: break-all; border: 1px solid var(--border-color); }
        
        .code-card { background-color: var(--sidebar-bg); border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); border: 1px solid var(--border-color); }
        .code-card-header { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;}
        .code-card-body { padding: 0.5rem 1rem 1rem; }
        #endpoint-selector {
            width: 100%;
            background-color: #f9fafb; color: var(--text-secondary); border: 1px solid var(--border-color);
            border-radius: 6px; padding: 0.5rem; font-family: 'Nunito', sans-serif; font-size: 0.9em; font-weight: 600;
        }
        .code-viewer { display: none; }
        .code-viewer.active { display: block; }

        .workflow-list {
            list-style: none;
            padding-left: 0;
            counter-reset: workflow-counter;
        }
        .workflow-list li {
            counter-increment: workflow-counter;
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 3rem;
        }
        .workflow-list li::before {
            content: counter(workflow-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 2rem;
            height: 2rem;
            background-color: var(--code-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent-blue);
        }
        .workflow-list li strong {
            font-weight: 700;
            color: var(--text-primary);
            display: block;
            margin-bottom: 0.25rem;
        }
        .feedback-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h1>Dokumentasi API <?php echo htmlspecialchars($appInstanceName); ?></h1>
        <nav id="sidebar-nav">
            <ul>
                <li><a href="#pendahuluan" class="active">Pendahuluan</a></li>
                <li><a href="#otentikasi">Otentikasi</a></li>
                <li><a href="#workflow">Alur Kerja Umum</a></li>
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
                <div class="feedback-box">
                    <p style="margin-bottom: 0.5rem;"><strong>Punya Feedback atau Permintaan Fitur?</strong></p>
                    <p style="margin: 0;">Kami selalu terbuka untuk masukan. Silakan kirim email ke <a href="mailto:fscking@icloud.com" style="color: var(--accent-blue); font-weight: 600;">fscking@icloud.com</a>.</p>
                </div>
            </section>

            <section id="otentikasi">
                <h2>Otentikasi</h2>
                <p>Setiap permintaan ke API harus menyertakan Kunci API yang valid di dalam HTTP Header `X-API-Key`. Kegagalan menyediakan kunci yang benar akan menghasilkan respons `401 Unauthorized`.</p>
                <p>Kunci API dapat memiliki izin `read_only` atau `read_write`. Endpoint yang melakukan perubahan data (POST) akan menghasilkan `403 Forbidden` jika kunci yang digunakan bersifat `read_only`.</p>
            </section>

            <section id="workflow">
                <h2>Alur Kerja Umum</h2>
                <p>Berikut adalah contoh alur kerja lengkap dari persiapan hingga pencetakan label menggunakan API:</p>
                <ol class="workflow-list">
                    <li><strong>Buat Muatan:</strong> Panggil <code>POST /loads</code> untuk membuat "keranjang" sterilisasi baru. Anda akan mendapatkan `load_id` sebagai respons.</li>
                    <li><strong>Tambah Item:</strong> Untuk setiap instrumen atau set, panggil <code>POST /loads/{id}/items</code> menggunakan `load_id` dari langkah sebelumnya.</li>
                    <li><strong>Proses Muatan:</strong> Setelah semua item ditambahkan, panggil <code>POST /loads/{id}/process</code>. Ini akan membuat siklus sterilisasi dan mengubah status muatan menjadi 'selesai'.</li>
                    <li><strong>Generate Label:</strong> Panggil <code>POST /loads/{id}/generate-labels</code> untuk membuat semua catatan label di database. Responsnya akan berisi daftar UID dari semua label baru.</li>
                    <li><strong>Cetak Label:</strong> Untuk setiap UID yang diterima, panggil <code>GET /labels/{uid}/print</code> untuk mendapatkan HTML label yang siap dicetak oleh sistem Anda.</li>
                </ol>
            </section>

            <section id="labels">
                <h2>Labels</h2>
                <h3 data-endpoint="get-label-details">Mendapatkan Detail Label</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/labels/{uid}</code></div>
                <p>Mengambil informasi detail dari sebuah label berdasarkan ID unik (UID).</p>
                
                <h3 data-endpoint="mark-label-used">Menandai Label Telah Digunakan</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/labels/{uid}/mark-used</code></div>
                <p>Mengubah status sebuah label dari 'aktif' menjadi 'telah digunakan'.</p>
                
                <h3 data-endpoint="get-label-print">Mendapatkan Hasil Cetak Label</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/labels/{uid}/print</code></div>
                <p>Mengembalikan konten HTML dari label yang siap dicetak dan menaikkan `print_count`.</p>
            </section>

            <section id="inventaris">
                <h2>Inventaris</h2>
                <h3 data-endpoint="get-instruments">Mendapatkan Daftar Instrumen</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/instruments</code></div>
                <p>Mengambil daftar instrumen dengan paginasi.</p>

                <h3 data-endpoint="get-instrument-details">Mendapatkan Detail Instrumen</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/instruments/{id}</code></div>
                <p>Mengambil detail satu instrumen spesifik berdasarkan ID-nya.</p>

                <h3 data-endpoint="get-sets">Mendapatkan Daftar Set</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/sets</code></div>
                <p>Mengambil daftar set instrumen dengan paginasi.</p>

                <h3 data-endpoint="get-set-details">Mendapatkan Detail Set</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/sets/{id}</code></div>
                <p>Mengambil detail satu set, termasuk daftar instrumen di dalamnya.</p>
            </section>
            
            <section id="proses">
                <h2>Proses Sterilisasi</h2>
                <h3 data-endpoint="get-loads">Mendapatkan Daftar Muatan</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/loads</code></div>
                <p>Mengambil daftar muatan sterilisasi dengan paginasi.</p>

                <h3 data-endpoint="create-load">Membuat Muatan Baru</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/loads</code></div>
                <p>Membuat entri muatan baru dengan status 'persiapan'.</p>

                <h3 data-endpoint="add-item-to-load">Menambahkan Item ke Muatan</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/loads/{id}/items</code></div>
                <p>Menambahkan instrumen atau set ke dalam muatan yang statusnya 'persiapan'.</p>
                
                <h3 data-endpoint="process-load">Memproses Muatan</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/loads/{id}/process</code></div>
                <p>Membuat siklus baru dan menyelesaikan muatan.</p>
                
                <h3 data-endpoint="generate-labels">Membuat Semua Label dari Muatan</h3>
                <div class="endpoint-header"><span class="method post">POST</span><code>/loads/{id}/generate-labels</code></div>
                <p>Membuat semua entri label untuk muatan yang sudah 'selesai'.</p>
                
                <h3 data-endpoint="get-cycles">Mendapatkan Daftar Siklus</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/cycles</code></div>
                <p>Mengambil daftar riwayat siklus sterilisasi dengan paginasi.</p>
            </section>

             <section id="pengguna">
                <h2>Pengguna</h2>
                <h3 data-endpoint="get-users">Mendapatkan Daftar Pengguna</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/users</code></div>
                <p>Mengembalikan daftar semua pengguna di sistem.</p>
                
                <h3 data-endpoint="get-user-details">Mendapatkan Detail Pengguna</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/users/{id}</code></div>
                <p>Mengembalikan detail seorang pengguna berdasarkan ID.</p>
            </section>

             <section id="laporan">
                <h2>Laporan & Master Data</h2>
                <h3 data-endpoint="get-reports">Mendapatkan Laporan KPI</h3>
                 <div class="endpoint-header"><span class="method get">GET</span><code>/reports</code></div>
                <p>Mengembalikan data Key Performance Indicator (KPI) utama.</p>

                <h3 data-endpoint="get-master-data">Mendapatkan Master Data</h3>
                <div class="endpoint-header"><span class="method get">GET</span><code>/master/{type}</code></div>
                <p>Mengembalikan daftar data master yang aktif. Ganti <code>{type}</code> dengan `departments`, `machines`, atau `instrument-types`.</p>
            </section>
        </div>
        <div class="code-column">
            <div class="code-column-sticky">
                <div class="code-card">
                    <div class="code-card-header">
                        <select id="endpoint-selector">
                            <optgroup label="Labels">
                                <option value="get-label-details">GET /labels/{uid}</option>
                                <option value="mark-label-used">POST /labels/{uid}/mark-used</option>
                                <option value="get-label-print">GET /labels/{uid}/print</option>
                            </optgroup>
                            <optgroup label="Proses">
                                <option value="create-load">POST /loads</option>
                                <option value="add-item-to-load">POST /loads/{id}/items</option>
                                <option value="process-load">POST /loads/{id}/process</option>
                                <option value="generate-labels">POST /loads/{id}/generate-labels</option>
                            </optgroup>
                             <optgroup label="Inventaris & Master">
                                <option value="get-instruments">GET /instruments</option>
                                <option value="get-users">GET /users</option>
                                <option value="get-reports">GET /reports</option>
                                <option value="get-master-data">GET /master/{type}</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="code-card-body">
                        <div id="viewer-get-label-details" class="code-viewer active">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X GET \
"<?php echo htmlspecialchars($api_v1_url); ?>/labels/CONTOH123" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": { "label_unique_id": "CONTOH123", ... }
}</code></pre>
                        </div>

                        <div id="viewer-mark-label-used" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X POST \
"<?php echo htmlspecialchars($api_v1_url); ?>/labels/CONTOH123/mark-used" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>" \
-d '{ "note": "Digunakan oleh Dr. Budi" }'</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": { "message": "Status item berhasil diperbarui..." }
}</code></pre>
                        </div>
                        
                        <div id="viewer-get-label-print" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X GET \
"<?php echo htmlspecialchars($api_v1_url); ?>/labels/CONTOH123/print" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": { "html_content": "&lt;!DOCTYPE html&gt;..." }
}</code></pre>
                        </div>

                        <div id="viewer-create-load" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X POST \
"<?php echo htmlspecialchars($api_v1_url); ?>/loads" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>" \
-d '{ "machine_id": 1 }'</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (201 Created)</p>
<pre><code>{
  "status": "success",
  "data": { "load_id": 151, "status": "persiapan", ... }
}</code></pre>
                        </div>

                        <div id="viewer-add-item-to-load" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X POST \
"<?php echo htmlspecialchars($api_v1_url); ?>/loads/151/items" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>" \
-d '{ "item_id": 5, "item_type": "set" }'</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (201 Created)</p>
<pre><code>{
  "status": "success",
  "data": { "message": "Item berhasil ditambahkan..." }
}</code></pre>
                        </div>
                        
                        <div id="viewer-process-load" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X POST \
"<?php echo htmlspecialchars($api_v1_url); ?>/loads/151/process" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>" \
-d '{ "operator_user_id": 2 }'</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": { "cycle_id": 78, "status": "completed", ... }
}</code></pre>
                        </div>
                        
                        <div id="viewer-generate-labels" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X POST \
"<?php echo htmlspecialchars($api_v1_url); ?>/loads/151/generate-labels" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>" \
-d '{ "user_id": 2 }'</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (201 Created)</p>
<pre><code>{
  "status": "success",
  "data": { "label_uids": [ "AEF1345B", ... ] }
}</code></pre>
                        </div>
                        
                        <div id="viewer-get-users" class="code-viewer">
                             <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X GET \
"<?php echo htmlspecialchars($api_v1_url); ?>/users" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": [ { "user_id": 1, "username": "admin", ... } ]
}</code></pre>
                        </div>

                        <div id="viewer-get-reports" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X GET \
"<?php echo htmlspecialchars($api_v1_url); ?>/reports" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": { "kpi_cycle_success_rate_percent": 98.5, ... }
}</code></pre>
                        </div>

                         <div id="viewer-get-master-data" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X GET \
"<?php echo htmlspecialchars($api_v1_url); ?>/master/machines" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": [ { "machine_id": 1, "machine_name": "Autoklaf A", ... } ]
}</code></pre>
                        </div>
                        
                        <div id="viewer-get-instruments" class="code-viewer">
                            <p class="font-semibold text-gray-300">Request (cURL)</p>
<pre><code>curl -X GET \
"<?php echo htmlspecialchars($api_v1_url); ?>/instruments?limit=2" \
-H "X-API-Key: <?php echo htmlspecialchars($sample_api_key); ?>"</code></pre>
                            <p class="font-semibold text-gray-300 mt-4">Response (200 OK)</p>
<pre><code>{
  "status": "success",
  "data": [
    { "instrument_id": 12, "instrument_name": "Gunting M...", ... },
    { "instrument_id": 15, "instrument_name": "Klem Arteri", ... }
  ]
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.content-column section');
            const navLinks = document.querySelectorAll('#sidebar-nav a');
            const endpointSelector = document.getElementById('endpoint-selector');
            const codeViewers = document.querySelectorAll('.code-viewer');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        navLinks.forEach(link => {
                            link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
                        });
                    }
                });
            }, { rootMargin: "-40% 0px -60% 0px" });

            sections.forEach(section => observer.observe(section));

            endpointSelector.addEventListener('change', () => {
                const selectedEndpoint = endpointSelector.value;
                codeViewers.forEach(viewer => {
                    viewer.classList.toggle('active', viewer.id === `viewer-${selectedEndpoint}`);
                });
            });

            const endpointHeaders = document.querySelectorAll('.content-column h3[data-endpoint]');
            const endpointObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const endpointId = entry.target.getAttribute('data-endpoint');
                        if (endpointSelector.value !== endpointId) {
                            endpointSelector.value = endpointId;
                            endpointSelector.dispatchEvent(new Event('change'));
                        }
                    }
                });
            }, { threshold: 0.8 });

            endpointHeaders.forEach(header => endpointObserver.observe(header));
        });
    </script>
</body>
</html>