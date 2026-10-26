<?php
declare(strict_types=1);
?>
</main>
<footer class="footer">
    <div class="container footer-inner">
        <div>
            <strong><?= htmlspecialchars(APP_NAME) ?> v<?= htmlspecialchars(APP_VERSION) ?></strong> &mdash;
            Official 9-Band IELTS Preparation &amp; AI Assessment Suite (Local XAMPP &amp; Cloud HTTPS Ready)
        </div>
        <div>
            <a href="<?= htmlspecialchars(BASE_URL) ?>/tests/acceptance_audit.php">System Audit &amp; Acceptance Tests</a>
        </div>
    </div>
</footer>
<script>
    window.IELTS_CONFIG = {
        baseUrl: <?= json_encode(BASE_URL) ?>,
        csrfToken: <?= json_encode(csrf_token()) ?>
    };
</script>
<script src="<?= htmlspecialchars(BASE_URL) ?>/assets/js/app.js"></script>
</body>
</html>
