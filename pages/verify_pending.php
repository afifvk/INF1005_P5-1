<?php
$pageTitle = 'Verify Your Email';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';

$errors = [];
$infoMessage = '';
$email = normalizeEmail($_GET['email'] ?? ($_SESSION['pending_verification_email'] ?? ''));

if ($email === '') {
    $_SESSION['flash'] = [
        'type' => 'warning',
        'message' => 'We could not find a pending verification email address.'
    ];
    redirect(SITE_URL . '/pages/register.php');
}

$_SESSION['pending_verification_email'] = $email;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        if (hasRecaptchaConfig()) {
            $recaptchaResult = verifyRecaptchaToken(
                $_POST['g-recaptcha-response'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

            if (!$recaptchaResult['success']) {
                $errors[] = $recaptchaResult['message'];
            }
        }

        if (empty($errors)) {
            $result = resendVerificationEmail($email);

            if (($result['status'] ?? '') === 'sent') {
                $infoMessage = $result['message'] ?? 'A new verification email has been sent.';
            } else {
                $errors[] = $result['message'] ?? 'Unable to resend verification email right now.';
            }
        }
    }
}

$pendingState = getVerificationPendingState($email);

if (!empty($pendingState['verified'])) {
    unset($_SESSION['pending_verification_email']);
    redirect(SITE_URL . '/pages/verify_email.php?verified=1');
}

$remainingSeconds = (int) ($pendingState['remaining_seconds'] ?? 0);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="section-pad" aria-labelledby="verify-pending-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-6">
                <div class="form-wrapper text-center">
                    <h1 id="verify-pending-heading" class="display-5 mb-3">Verify Your Email</h1>
                    <p class="text-muted mb-4">
                        We sent a verification link to
                        <strong><?= e($email) ?></strong>.
                        Please check your inbox before logging in.
                    </p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger text-start" role="alert" aria-live="assertive">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($infoMessage !== ''): ?>
                        <div class="alert alert-success" role="alert">
                            <?= e($infoMessage) ?>
                        </div>
                    <?php endif; ?>

                    <div id="cooldown-box" class="alert alert-info mb-4" <?= $remainingSeconds > 0 ? '' : 'style="display:none;"' ?>>
                        You can resend the verification email in
                        <strong><span id="cooldown-seconds"><?= $remainingSeconds ?></span> seconds</strong>.
                    </div>

                    <div id="resend-box" <?= $remainingSeconds > 0 ? 'style="display:none;"' : '' ?>>
                        <form method="POST" action="verify_pending.php?email=<?= urlencode($email) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                            <?php if (hasRecaptchaConfig()): ?>
                                <div class="mb-3 text-center">
                                    <div class="d-inline-block">
                                        <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>"></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn-store w-100 py-3 rounded mb-3">
                                Resend Verification Email
                            </button>
                        </form>
                    </div>

                    <a href="<?= SITE_URL ?>/pages/login.php" class="btn btn-outline-secondary w-100 py-3 rounded">
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (hasRecaptchaConfig()): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<script>
(function () {
    var remaining = <?= $remainingSeconds ?>;
    var cooldownBox = document.getElementById('cooldown-box');
    var resendBox = document.getElementById('resend-box');
    var secondsEl = document.getElementById('cooldown-seconds');

    if (remaining <= 0) {
        return;
    }

    var timer = setInterval(function () {
        remaining--;

        if (secondsEl) {
            secondsEl.textContent = remaining;
        }

        if (remaining <= 0) {
            clearInterval(timer);
            if (cooldownBox) cooldownBox.style.display = 'none';
            if (resendBox) resendBox.style.display = '';
        }
    }, 1000);
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>