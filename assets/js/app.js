/**
 * Sistem IT Ticket Advent - App JavaScript
 * Sidebar Collapsible Toggle, Scroll Retention, Live Filter, 
 * Drag & Drop File Upload with 5MB validation, Image Zoom, & AJAX Reply
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

    // 5. Drag & Drop File Upload with Realtime 5MB Validation & Remove Button
    const dropzone = document.getElementById('uploadDropzone');
    const fileInput = document.getElementById('ticketAttachmentInput');
    const previewCard = document.getElementById('attachmentPreviewCard');
    const previewImg = document.getElementById('previewImg');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileSizeBadge = document.getElementById('fileSizeBadge');
    const fileSizeAlert = document.getElementById('fileSizeAlert');
    const fileSizeAlertText = document.getElementById('fileSizeAlertText');
    const removeBtn = document.getElementById('removeAttachmentBtn');
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    function formatBytes(bytes, decimals = 1) {
        if (!+bytes) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    function processSelectedFile(file) {
        if (!file) return;

        // Validasi Ukuran File Realtime (Batas 5MB)
        if (file.size > MAX_FILE_SIZE) {
            if (fileSizeAlert && fileSizeAlertText) {
                fileSizeAlertText.innerText = `Ukuran berkas (${formatBytes(file.size)}) melebihi batas maksimal 5MB! Silakan pilih foto lain yang lebih kecil.`;
                fileSizeAlert.classList.remove('d-none');
                fileSizeAlert.classList.add('d-flex');
            }
            if (fileInput) fileInput.value = '';
            if (previewCard) previewCard.classList.add('d-none');
            return;
        }

        // Ukuran Valid: Sembunyikan Alert
        if (fileSizeAlert) {
            fileSizeAlert.classList.add('d-none');
            fileSizeAlert.classList.remove('d-flex');
        }

        // Tampilkan Nama & Ukuran
        if (fileNameDisplay) fileNameDisplay.innerText = file.name;
        if (fileSizeBadge) fileSizeBadge.innerText = formatBytes(file.size);

        // Baca Pratinjau Gambar
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) previewImg.src = e.target.result;
                if (previewCard) previewCard.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            if (previewImg) previewImg.src = 'https://placehold.co/70x70?text=DOC';
            if (previewCard) previewCard.classList.remove('d-none');
        }
    }

    if (dropzone && fileInput) {
        // Klik dropzone untuk buka dialog file
        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        // Drag & Drop Events
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.backgroundColor = '#eef2ff';
                dropzone.style.borderColor = '#4f46e5';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.backgroundColor = '#f8fafc';
                dropzone.style.borderColor = '';
            }, false);
        });

        dropzone.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                fileInput.files = files;
                processSelectedFile(files[0]);
            }
        });

        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                processSelectedFile(this.files[0]);
            }
        });
    }

    // Tombol Hapus Berkas Lampiran Terpilih
    if (removeBtn && fileInput) {
        removeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fileInput.value = '';
            if (previewCard) previewCard.classList.add('d-none');
            if (fileSizeAlert) {
                fileSizeAlert.classList.add('d-none');
                fileSizeAlert.classList.remove('d-flex');
            }
        });
    }

    // 6. Image Modal Zoom (Klik foto tiket untuk memperbesar)
    function attachZoomListeners() {
        const zoomableImages = document.querySelectorAll('.zoomable-image:not(.zoom-bound)');
        const zoomModalEl = document.getElementById('imageZoomModal');
        const modalZoomImg = document.getElementById('modalZoomImg');
        if (zoomModalEl && modalZoomImg) {
            zoomableImages.forEach(img => {
                img.classList.add('zoom-bound');
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
    }
    attachZoomListeners();

    // 7. Auto-hide Alert Flash Messages after 4 seconds
    const flashAlerts = document.querySelectorAll('.auto-dismiss-alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // 8. AJAX Quick Reply Submission (Instant Append Tanpa Full Reload)
    const replyForm = document.getElementById('replyTicketForm');
    const replyThreadContainer = document.getElementById('replyThreadContainer');
    const replyCountBadge = document.getElementById('replyCountBadge');
    const noRepliesNotice = document.getElementById('noRepliesNotice');
    const replySubmitBtn = document.getElementById('replySubmitBtn');
    const replyAlertPlaceholder = document.getElementById('replyAlertPlaceholder');

    if (replyForm && replyThreadContainer && replySubmitBtn) {
        replyForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const messageTextarea = document.getElementById('replyMessageTextarea');
            const message = messageTextarea ? messageTextarea.value.trim() : '';

            if (!message) {
                alert('Isi pesan tidak boleh kosong.');
                return;
            }

            // Tampilkan Status Loading di Tombol
            const originalBtnHtml = replySubmitBtn.innerHTML;
            replySubmitBtn.disabled = true;
            replySubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengirim...';

            if (replyAlertPlaceholder) replyAlertPlaceholder.innerHTML = '';

            const formData = new FormData(replyForm);
            formData.append('ajax', '1');

            fetch('index.php?action=reply_ticket', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Gagal mengirim balasan.');
                    }
                    return data;
                });
            })
            .then(res => {
                if (res.status === 'success' && res.reply) {
                    const r = res.reply;

                    // Hilangkan notice kosong jika ada
                    if (noRepliesNotice) {
                        noRepliesNotice.remove();
                    }

                    // Sembunyikan dan bangun elemen kartu balasan baru
                    const isInternal = r.is_internal == 1;
                    const roleBadge = r.user_role === 'it' 
                        ? '<span class="badge bg-primary text-white" style="font-size: 0.65rem;">Tim IT</span>' 
                        : '<span class="badge bg-secondary text-white" style="font-size: 0.65rem;">Staf Kantor</span>';
                    const internalBadge = isInternal 
                        ? '<span class="badge bg-warning text-dark small"><i class="bi bi-shield-lock"></i> Catatan Internal IT</span>' 
                        : '';
                    const avatarRoleClass = r.user_role === 'it' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success';
                    const avatarIcon = r.user_role === 'it' ? 'bi-tools' : 'bi-person';
                    const cardBg = isInternal ? 'border-start border-4 border-warning bg-warning-subtle' : 'bg-white';

                    let attachmentHtml = '';
                    if (r.attachment) {
                        attachmentHtml = `
                            <div class="mt-2">
                                <img src="uploads/${encodeURIComponent(r.attachment)}" class="img-thumbnail zoomable-image shadow-sm" style="max-height: 160px; max-width: 240px; object-fit: cover;" alt="Lampiran Balasan" title="Klik untuk memperbesar">
                            </div>
                        `;
                    }

                    const newCard = document.createElement('div');
                    newCard.className = `card border-0 shadow-sm rounded-4 mb-2 reply-card-item ${cardBg}`;
                    newCard.id = `reply-card-${r.id}`;
                    newCard.style.animation = 'fadeInUp 0.35s ease forwards';
                    newCard.innerHTML = `
                        <div class="card-body p-3 px-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ticket-user-avatar ${avatarRoleClass}" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                        <i class="bi ${avatarIcon}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small d-flex align-items-center gap-2">
                                            ${escapeHtml(r.sender_name)}
                                            ${roleBadge}
                                            ${internalBadge}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.72rem;">${escapeHtml(r.time_ago)} • ${escapeHtml(r.formatted_date)}</div>
                                    </div>
                                </div>
                            </div>
                            <div style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">${escapeHtml(r.message)}</div>
                            ${attachmentHtml}
                        </div>
                    `;

                    // Tambahkan ke container thread
                    replyThreadContainer.appendChild(newCard);

                    // Update Angka Counter Balasan
                    if (replyCountBadge) {
                        const currentCount = parseInt(replyCountBadge.innerText, 10) || 0;
                        replyCountBadge.innerText = currentCount + 1;
                    }

                    // Reset Form
                    replyForm.reset();

                    // Scroll Halus ke Pesan yang Baru Masuk
                    newCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                    // Re-bind zoom listener untuk foto baru
                    attachZoomListeners();

                    // Notifikasi sukses kecil
                    if (replyAlertPlaceholder) {
                        replyAlertPlaceholder.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show py-2 px-3 small rounded-3" role="alert">
                                <i class="bi bi-check-circle-fill me-1"></i> ${escapeHtml(res.message)}
                                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        setTimeout(() => {
                            const alertEl = replyAlertPlaceholder.querySelector('.alert');
                            if (alertEl) alertEl.remove();
                        }, 3000);
                    }
                }
            })
            .catch(err => {
                if (replyAlertPlaceholder) {
                    replyAlertPlaceholder.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small rounded-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> ${escapeHtml(err.message)}
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                } else {
                    alert(err.message);
                }
            })
            .finally(() => {
                replySubmitBtn.disabled = false;
                replySubmitBtn.innerHTML = originalBtnHtml;
            });
        });
    }

    // Helper escape HTML untuk proteksi XSS
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
