</main>
    <footer class="footer bg-white border-top py-3 mt-auto text-center text-muted small">
        <div class="container-fluid">
            <span>&copy; <?php echo date('Y'); ?> Diálogo y Desarrollo - Panel de Gestión</span>
        </div>
    </footer>
</div>

<!-- Scripts Generales Bootstrap & Sidebar -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const collapseBtn = document.getElementById('sidebarCollapse');
    const closeBtn = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }
</script>
</body>
</html>