<?php
$pageTitle = 'Reset Password';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$errors = [];
$successMessage = '';
$record = getPasswordResetRecord($token);
$isTokenUsable = is_array($record) && ($record['status'] ?? '') === 'valid';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $result = resetPasswordWithToken($token, $password, $confirm);

        if (($result['status'] ?? '') === 'success') {
            $successMessage = $result['message'];
            $isTokenUsable = false;
        } elseif (($result['status'] ?? '') === 'validation_error') {
            $errors = $result['errors'] ?? ['Unable to reset password.'];
            $isTokenUsable = true;
        } else {
            $errors[] = $result['message'] ?? 'Unable to reset password.';
            $isTokenUsable = false;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-pad" aria-labelledby="reset-password-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5">
                <div class="form-wrapper">
                    <h1 id="reset-password-heading" class="h3 mb-1 text-center">Reset Password</h1>
                    <p class="text-muted text-center small mb-4">Choose a new password for your account.</p>

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
                    <a class="btn-store w-100 py-3 rounded" href="<?= SITE_URL ?>/pages/login.php">Go to Login</a>
                    <?php elseif ($isTokenUsable): ?>
                    <form method="POST" action="reset_password.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="token" value="<?= e($token) ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <p class="form-text mb-2">
                                Must contain at least 10 characters, one uppercase letter, one lowercase letter, one number, and one special character.
                            </p>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   minlength="10"
                                   required
                                   autocomplete="new-password"
                                   placeholder="New Password">
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="form-control"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Confirm New Password">
                        </div>

                        <button type="submit" class="btn-store w-100 py-3 rounded">Reset Password</button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        This password reset link is invalid or has expired. Please request a new one.
                    </div>
                    <a class="btn-store w-100 py-3 rounded" href="<?= SITE_URL ?>/pages/forgot_password.php">Request a New Link</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
