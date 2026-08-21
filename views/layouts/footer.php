        </main>
        
        <!-- Footer Sederhana -->
        <footer class="text-center py-3 text-muted border-top bg-white mt-auto small">
            <div class="container-fluid">
                &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> - <?= APP_ORGANIZATION ?> | Berjalan di Localhost
            </div>
        </footer>
    </div>
</div>

<!-- Modal Zoom Foto / Screenshot Lampiran -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="" id="modalZoomImg" class="img-fluid rounded-3 shadow-lg" style="max-height: 85vh;" alt="Zoom Lampiran">
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- App JavaScript -->
<script src="assets/js/app.js"></script>
</body>
</html>
