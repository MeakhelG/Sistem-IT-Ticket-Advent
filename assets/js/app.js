/**
 * Sistem IT Ticket Advent - App JavaScript
 * Sidebar Collapsible Toggle, Scroll Retention, Live Filter, Image Preview & Zoom
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Status Collapsed Sidebar (Desktop Mini Sidebar)
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed && window.innerWidth >= 992) {
        document.body.classList.add('sidebar-collapsed');
    }

    // Toggle Sidebar (Hanya 1 Tombol Toggle di Header)
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth >= 992) {
                // Mode Desktop: Toggle Mini Sidebar (Icon Mode)
                document.body.classList.toggle('sidebar-collapsed');
                const collapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', collapsed);
            } else {
                // Mode Mobile: Toggle Offcanvas Sidebar
                const sidebar = document.getElementById('appSidebar');
                if (sidebar) {
                    sidebar.classList.toggle('show');
                }
            }
        });
    }

    // 2. Pertahankan Posisi Scroll Sidebar saat Berpindah Halaman
    const sidebarBody = document.querySelector('.sidebar-body');
    if (sidebarBody) {
        const savedScrollPos = sessionStorage.getItem('sidebarScrollPosition');
        if (savedScrollPos !== null) {
            sidebarBody.scrollTop = parseInt(savedScrollPos, 10);
        }

        sidebarBody.addEventListener('scroll', function () {
            sessionStorage.setItem('sidebarScrollPosition', sidebarBody.scrollTop);
        });

        const sidebarLinks = sidebarBody.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function () {
                sessionStorage.setItem('sidebarScrollPosition', sidebarBody.scrollTop);
            });
        });
    }

    // 3. Realtime Search / Live Filter di List Tiket (Mailbox)
    const searchInput = document.getElementById('ticketSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const ticketRows = document.querySelectorAll('.ticket-row');
            let visibleCount = 0;

            ticketRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = 'flex';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptyNotice = document.getElementById('searchEmptyNotice');
            if (emptyNotice) {
                emptyNotice.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
            }
        });
    }

    // 4. Category Selector Card Click di Form Buat Tiket
    const catCards = document.querySelectorAll('.category-option-card');
    const catHiddenInput = document.getElementById('selectedCategoryId');
    if (catCards.length > 0 && catHiddenInput) {
        catCards.forEach(card => {
            card.addEventListener('click', function () {
                catCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                const catId = this.getAttribute('data-id');
                catHiddenInput.value = catId;
            });
        });
    }

    // 5. Image Upload Preview
    const fileInput = document.getElementById('ticketAttachmentInput');
    const previewContainer = document.getElementById('attachmentPreview');
    const previewImg = document.getElementById('previewImg');
    if (fileInput && previewContainer && previewImg) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
                previewImg.src = '';
            }
        });
    }

    // 6. Image Modal Zoom (Klik foto tiket untuk memperbesar)
    const zoomableImages = document.querySelectorAll('.zoomable-image');
    const zoomModalEl = document.getElementById('imageZoomModal');
    const modalZoomImg = document.getElementById('modalZoomImg');
    if (zoomableImages.length > 0 && modalZoomImg) {
        zoomableImages.forEach(img => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function () {
                modalZoomImg.src = this.src;
                if (window.bootstrap && bootstrap.Modal) {
                    const modal = new bootstrap.Modal(zoomModalEl);
                    modal.show();
                }
            });
        });
    }

    // 7. Auto-hide Alert Flash Messages after 4 seconds
    const flashAlerts = document.querySelectorAll('.auto-dismiss-alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
});
