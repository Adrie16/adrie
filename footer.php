                </main>
                
                <!-- Footer -->
                <footer class="mt-auto py-3 px-4 border-top">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <span class="text-muted">
                                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
                                </span>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="text-muted">
                                    Logged in as: <strong><?php echo $_SESSION['full_name'] ?? 'Guest'; ?></strong>
                                    (<?php echo ucfirst($_SESSION['role'] ?? 'Unknown'); ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>