<?php
/**
 * Reusable Footer File (with Nerd Info & Elegant Contact Modal)
 *
 * Contains the closing HTML structure and displays a dynamic footer.
 * This version includes performance metrics (nerd info) and app version.
 * Adheres to PSR-12.
 *
 * PHP version 7.4 or higher
 *
 * @category Partial
 * @package  Sterilabel
 * @author   Your Name <you@example.com>
 * @license  MIT License
 * @link     null
 */
declare(strict_types=1);

// $app_settings and $page_load_start should be available from header.php
global $app_settings, $page_load_start, $page_specific_js;
$appInstanceNameForFooter = $app_settings['app_instance_name'] ?? 'Sterilabel';

// Calculate performance metrics
$page_load_end = microtime(true);
$load_time = round($page_load_end - $page_load_start, 4);
$memory_usage = round(memory_get_peak_usage(true) / (1024 * 1024), 2); // in MB

?>
        </div> 
    </div> 
    
    <footer class="bg-white border-t border-gray-200 mt-auto py-4">
        <div class="container mx-auto px-6 text-xs text-gray-500">
            <div class="flex flex-col sm:flex-row justify-between items-center text-center sm:text-left gap-y-2">
                <div class="mb-2 sm:mb-0">
                    &copy; <span id="currentYearFooter"></span> <?php echo htmlspecialchars($appInstanceNameForFooter); ?>
                    <span class="font-semibold">v<?php echo APP_VERSION; ?></span>
                </div>
                
                <div class="nerd-info">
                    <span>Load: <strong><?php echo $load_time; ?>s</strong></span>
                    <span class="mx-2">|</span>
                    <span>Memory: <strong><?php echo $memory_usage; ?>MB</strong></span>
                </div>

                <div class="text-gray-500">
                    Magically deployed by 
                    <a href="#" id="openContactModalBtn" class="font-semibold text-blue-600">Davion Dev</a> 
                    & <strong>PT. Lifira Mitra Abadi</strong>
                </div>
            </div>
        </div>
    </footer>
    
    <div id="contactModal" class="modal-overlay">
        <div class="contact-card-prestige">
            <button id="closeContactModalBtn" class="btn-icon btn-icon-action absolute top-3 right-3 text-gray-400 hover:bg-gray-200"><span class="material-icons">close</span></button>
            <div class="prestige-header">
                <img src="https://i.pravatar.cc/150?u=daviondev" alt="Foto Profil Davion Dev" class="prestige-profile-picture">
                <div class="prestige-identity">
                    <h3 class="prestige-name">Davion Dev</h3>
                    <p class="prestige-title">UI/UX Wizard (level 99) – Dev by day, insomnia by night</p>
                </div>
            </div>
            <div class="prestige-body">
                <div class="prestige-contact-list mt-0">
                    <div class="text-center mb-4">
                         <p class="font-semibold text-gray-700">Business Inquiry?</p>
                         <p class="text-sm text-gray-500">Let's connect and create something amazing.</p>
                    </div>
                    <a href="mailto:fscking@icloud.com" class="prestige-contact-item justify-center bg-gray-100 hover:bg-gray-200">
                        <span class="material-icons">email</span>
                        <span>fscking@icloud.com</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    
    <script>
        if (document.getElementById('currentYearFooter')) {
            document.getElementById('currentYearFooter').textContent = new Date().getFullYear();
        }

        const notyf = new Notyf({
            duration: 4000,
            position: { x: 'right', y: 'bottom' },
            ripple: false,
            types: [
                { type: 'success', background: '#22c55e', icon: { className: 'material-icons', tagName: 'i', text: 'check_circle' } },
                { type: 'error', background: '#ef4444', icon: { className: 'material-icons', tagName: 'i', text: 'error' } },
                { type: 'info', background: '#3b82f6', icon: { className: 'material-icons', tagName: 'i', text: 'info' } }
            ]
        });

        function showToast(text, type = 'info') {
            const formattedText = Array.isArray(text) ? text.join('<br>') : text.replace(/\\n/g, '<br>');
            
            if(type === 'success' || type === 'error') {
                notyf[type](formattedText);
            } else {
                notyf.open({ type: 'info', message: formattedText });
            }
        }
        
        const contactModal = document.getElementById('contactModal');
        const openContactBtn = document.getElementById('openContactModalBtn');
        const closeContactBtn = document.getElementById('closeContactModalBtn');

        if(openContactBtn && contactModal) {
            openContactBtn.addEventListener('click', (e) => {
                e.preventDefault();
                contactModal.classList.add('active');
            });
        }
        if(closeContactBtn && contactModal) {
            closeContactBtn.addEventListener('click', () => {
                contactModal.classList.remove('active');
            });
        }
        if(contactModal) {
            contactModal.addEventListener('click', (e) => {
                if (e.target === contactModal) {
                    contactModal.classList.remove('active');
                }
            });
        }

    </script>
    
    <script src="assets/js/app.js?v=<?php echo APP_VERSION; ?>"></script>

    <?php
    if (isset($page_specific_js) && !empty($page_specific_js)) {
        if (file_exists($page_specific_js)) {
            echo '<script src="' . htmlspecialchars($page_specific_js) . '?v=' . APP_VERSION . '"></script>';
        } else {
            error_log("Peringatan: File JavaScript spesifik halaman tidak ditemukan di '{$page_specific_js}'");
        }
    }
    ?>
    </body>
</html>