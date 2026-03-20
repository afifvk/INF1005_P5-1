<?php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
$successMessage = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        $emailVal = normalizeEmail(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));

        if (hasRecaptchaConfig()) {
            $recaptchaResult = verifyRecaptchaToken($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '');
            if (!$recaptchaResult['success']) {
                $errors[] = $recaptchaResult['message'];
            }
        }

        if (empty($errors)) {
            $result = requestPasswordReset($emailVal);
            $successMessage = $result['message'];
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-pad" aria-labelledby="forgot-password-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5">
                <div class="form-wrapper">
                    <h1 id="forgot-password-heading" class="h3 mb-1 text-center">Forgot Password</h1>
                    <p class="text-muted text-center small mb-4">Enter your email and we will send reset instructions.</p>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert" aria-live="assertive">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success" role="alert">
                        <?= e($successMessage) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="forgot_password.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= e($emailVal) ?>"
                                   autocomplete="email"
                                   required
                                   placeholder="you@example.com">
                        </div>

                        <?php if (hasRecaptchaConfig()): ?>
                        <div class="mb-3 text-center">
                            <div class="d-inline-block">
                                <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-store w-100 py-3 rounded">Send Reset Link</button>
                    </form>

                    <hr class="divider-line">

                    <p class="text-center text-muted small mb-0">
                        Remembered your password?
                        <a href="<?= SITE_URL ?>/pages/login.php" class="text-decoration-underline">Back to login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (hasRecaptchaConfig()): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
