<?php
$pageTitle = 'Verify Email';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/header.php';

$email = normalizeEmail($_GET['email'] ?? $_POST['email'] ?? '');
if ($email === '') {
    header('Location: ' . SITE_URL . '/pages/register.php');
    exit;
}

$successMessage = '';
$errorMessage = '';
$cooldownSeconds = (int) VERIFICATION_RESEND_COOLDOWN_SECONDS;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid request. Please try again.';
    } elseif (hasRecaptchaConfig()) {
        $recaptchaResult = verifyRecaptchaToken($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '');
        if (!$recaptchaResult['success']) {
            $errorMessage = $recaptchaResult['message'];
        } else {
            $result = resendVerificationEmail($email);

            if (in_array($result['status'], ['sent', 'generic_success'], true)) {
                $successMessage = $result['message'];
                $cooldownSeconds = (int) VERIFICATION_RESEND_COOLDOWN_SECONDS;
            } elseif ($result['status'] === 'cooldown') {
                $errorMessage = $result['message'];

                if (preg_match('/(\d+)/', $result['message'], $matches)) {
                    $cooldownSeconds = max(0, (int) $matches[1]);
                }
            } elseif ($result['status'] === 'already_verified') {
                header('Location: ' . SITE_URL . '/pages/verify_email.php?verified=1');
                exit;
            } else {
                $errorMessage = $result['message'] ?? 'Unable to resend verification email right now.';
            }
        }
    } else {
        $result = resendVerificationEmail($email);

        if (in_array($result['status'], ['sent', 'generic_success'], true)) {
            $successMessage = $result['message'];
            $cooldownSeconds = (int) VERIFICATION_RESEND_COOLDOWN_SECONDS;
        } elseif ($result['status'] === 'cooldown') {
            $errorMessage = $result['message'];

            if (preg_match('/(\d+)/', $result['message'], $matches)) {
                $cooldownSeconds = max(0, (int) $matches[1]);
            }
        } elseif ($result['status'] === 'already_verified') {
            header('Location: ' . SITE_URL . '/pages/verify_email.php?verified=1');
            exit;
        } else {
            $errorMessage = $result['message'] ?? 'Unable to resend verification email right now.';
        }
    }
}
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-7">
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?= e($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <?= e($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center">
                <h1 class="display-6 mb-3">Verify Your Email</h1>
                <p class="lead mb-4">
                    We sent a verification link to
                    <strong><?= e($email) ?></strong>.
                    Please check your inbox before logging in.
                </p>

                <div id="resendCountdownBox" class="alert alert-info text-center py-4 mb-4">
                    You can resend the verification email in
                    <strong><span id="resendCountdownText"><?= (int) $cooldownSeconds ?></span> seconds</strong>.
                </div>

                <div id="resendButtonWrap" style="display: none;">
                    <form method="post" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                        <input type="hidden" name="email" value="<?= e($email) ?>">
                        <?php if (hasRecaptchaConfig()): ?>
                        <div class="mb-3 text-center">
                            <div class="d-inline-block">
                                <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <button id="resendButton" type="submit" name="resend_verification" class="btn-store py-3 rounded w-100">
                            RESEND VERIFICATION EMAIL
                        </button>
                    </form>
                </div>

                <a class="btn btn-outline-secondary py-3 rounded w-100" href="<?= SITE_URL ?>/pages/login.php">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const countdownBox = document.getElementById('resendCountdownBox');
    const countdownText = document.getElementById('resendCountdownText');
    const resendButtonWrap = document.getElementById('resendButtonWrap');

    let remaining = <?= (int) $cooldownSeconds ?>;

    function renderCountdown() {
        if (remaining > 0) {
            countdownBox.style.display = 'block';
            resendButtonWrap.style.display = 'none';
            countdownText.textContent = remaining;
        } else {
            countdownBox.style.display = 'none';
            resendButtonWrap.style.display = 'block';
        }
    }

    renderCountdown();

    const timer = setInterval(function () {
        if (remaining > 0) {
            remaining--;
            renderCountdown();
        }

        if (remaining <= 0) {
            clearInterval(timer);
        }
    }, 1000);
});
</script>

<?php if (hasRecaptchaConfig()): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>