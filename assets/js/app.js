// ===================== GLOBAL ALERT (Toast) =====================
// Pengganti alert() bawaan browser untuk PHP Desktop
function appAlert(pesan, tipe) {
    tipe = tipe || 'info';
    var bgMap = { success: 'bg-success', danger: 'bg-danger', warning: 'bg-warning text-dark', info: 'bg-info' };
    var $toast = document.getElementById('toastAlert');
    if (!$toast) return;
    $toast.className = 'toast align-items-center border-0 ' + (bgMap[tipe] || 'bg-info') + (tipe === 'warning' ? '' : ' text-white');
    document.getElementById('toastPesan').innerHTML = pesan.replace(/\\n|\n/g, '<br>');
    var toast = bootstrap.Toast.getOrCreateInstance($toast, { delay: 3000 });
    toast.show();
}

// ===================== GLOBAL CONFIRM (Modal) =====================
// Pengganti confirm() bawaan browser untuk PHP Desktop
function appConfirm(pesan, callback) {
    var modal = document.getElementById('modalKonfirmasi');
    if (!modal) return;
    document.getElementById('konfirmasiPesan').innerHTML = pesan.replace(/\\n|\n/g, '<br>');
    var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
    var btnOk = document.getElementById('konfirmasiOk');
    // Hapus event lama
    var newBtn = btnOk.cloneNode(true);
    btnOk.parentNode.replaceChild(newBtn, btnOk);
    newBtn.addEventListener('click', function () {
        bsModal.hide();
        if (callback) callback();
    });
    bsModal.show();
}

// ===================== GLOBAL PRINT (Modal + iframe) =====================
// Pengganti window.print() / window.open() untuk PHP Desktop
function appPrint(url) {
    var modal = document.getElementById('modalCetak');
    var frame = document.getElementById('cetakFrame');
    if (!modal || !frame) return;
    frame.src = url;
    var bsModal = bootstrap.Modal.getOrCreateInstance(modal);

    var btnPrint = document.getElementById('btnCetakPrint');
    var newBtn = btnPrint.cloneNode(true);
    btnPrint.parentNode.replaceChild(newBtn, btnPrint);
    newBtn.addEventListener('click', function () {
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (e) {
            window.print();
        }
    });

    modal.addEventListener('hidden.bs.modal', function handler() {
        frame.src = 'about:blank';
        modal.removeEventListener('hidden.bs.modal', handler);
    });

    bsModal.show();
}

// Print halaman saat ini (untuk laporan)
function appPrintPage() {
    window.print();
}

// ===================== MAIN =====================
document.addEventListener('DOMContentLoaded', function () {
    // Toggle Sidebar
    var toggleBtn = document.getElementById('toggleSidebar');
    var sidebar = document.getElementById('sidebar');
    var content = document.querySelector('.content-wrapper');
    var backdrop = document.getElementById('sidebarBackdrop');

    function closeSidebar() {
        sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                if (backdrop) backdrop.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            }
        });
    }

    // Tutup sidebar saat klik backdrop
    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // Tutup sidebar saat klik link di sidebar (mobile)
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) closeSidebar();
            });
        });
    }

    // Warna Sidebar - Preset & Color Picker
    var presetButtons = document.querySelectorAll('.btn-preset-warna');
    var inputWarna = document.getElementById('inputWarnaSidebar');
    var kodeWarna = document.getElementById('kodeWarnaSidebar');
    var pratinjau = document.getElementById('pratinjauSidebar');

    function updateWarnaSidebar(warna) {
        if (inputWarna) inputWarna.value = warna;
        if (kodeWarna) kodeWarna.textContent = warna;
        if (pratinjau) pratinjau.style.backgroundColor = warna;
        if (sidebar) sidebar.style.backgroundColor = warna;
        presetButtons.forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.warna === warna);
        });
    }

    presetButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            updateWarnaSidebar(btn.dataset.warna);
        });
    });

    if (inputWarna) {
        inputWarna.addEventListener('input', function () {
            updateWarnaSidebar(this.value);
        });
    }

    // Mode Font Sidebar (light/dark)
    window.setModeFont = function (mode) {
        var inputMode = document.getElementById('inputModeFont');
        var pratinjauFont = document.getElementById('pratinjauFont');
        var btnContainer = document.getElementById('modeFontSidebar');

        if (inputMode) inputMode.value = mode;

        // Update pratinjau
        if (pratinjauFont) {
            pratinjauFont.style.color = mode === 'dark' ? '#212529' : 'white';
        }

        // Update tombol aktif
        if (btnContainer) {
            btnContainer.querySelectorAll('button').forEach(function (btn) {
                var isActive = btn.dataset.mode === mode;
                if (btn.dataset.mode === 'light') {
                    btn.className = 'btn btn-sm ' + (isActive ? 'btn-dark' : 'btn-outline-dark');
                } else {
                    btn.className = 'btn btn-sm ' + (isActive ? 'btn-secondary' : 'btn-outline-secondary');
                }
            });
        }

        // Live update sidebar
        if (sidebar) {
            if (mode === 'dark') {
                sidebar.classList.remove('text-white');
                sidebar.classList.add('font-dark');
            } else {
                sidebar.classList.remove('font-dark');
                sidebar.classList.add('text-white');
            }
        }
    };

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirm delete via appConfirm
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var form = btn.closest('form');
            appConfirm('Yakin ingin menghapus data ini?', function () {
                if (form) form.submit();
            });
        });
    });

    // Form confirm via data-confirm attribute
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed) {
                delete form.dataset.confirmed;
                return true;
            }
            e.preventDefault();
            appConfirm(form.dataset.confirm, function () {
                form.dataset.confirmed = 'true';
                form.submit();
            });
            return false;
        });
    });

    // Format currency input
    document.querySelectorAll('.input-rupiah').forEach(function (input) {
        input.addEventListener('input', function () {
            var value = this.value.replace(/[^0-9]/g, '');
            this.value = new Intl.NumberFormat('id-ID').format(value);
        });
        input.addEventListener('focus', function () {
            this.value = this.value.replace(/\./g, '');
        });
        input.addEventListener('blur', function () {
            var value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = new Intl.NumberFormat('id-ID').format(value);
            }
        });
    });
});
