<?php
$pageTitle = 'Email Verified';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/cart_helpers.php';
require_once __DIR__ . '/../includes/header.php';

$result = verifyEmailToken($_GET['token'] ?? '');
$isSuccess = in_array($result['status'], ['success'], true);
$alertType = $isSuccess ? 'success' : 'danger';

if ($isSuccess) {
    unset($_SESSION['pending_verification_email']);
}
?>

<section class="section-pad" aria-labelledby="verify-email-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-6">
                <div class="form-wrapper text-center">
                    <h1 id="verify-email-heading" class="h3 mb-3">Email Verification</h1>

                    <div class="alert alert-<?= e($alertType) ?>" role="alert">
                        <?= e($result['message']) ?>
                    </div>

                    <div class="d-grid gap-2">
                        <a class="btn-store py-3 rounded" href="<?= SITE_URL ?>/pages/login.php">Go to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
