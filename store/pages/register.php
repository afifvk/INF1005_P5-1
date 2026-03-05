<?php
/**
 * pages/register.php
 */

// TEMPORARY: show errors to diagnose blank page — remove after fixing
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Register';

// Use absolute paths based on this file's location
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
}

$errors   = [];
$formData = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password']         ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $formData = ['username' => $username, 'email' => $email];

        $errors = validateRegistration($username, $email, $password, $confirm);

        if (empty($errors)) {
            $result = registerUser($username, $email, $password);

            if ($result === true) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Account created! Please log in.'];
                redirect(SITE_URL . '/pages/login.php');
            } else {
                $errors[] = $result;
            }
        }
    }
}
?>

<section class="section-pad" aria-labelledby="register-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-6">

                <div class="form-wrapper">
                    <h1 id="register-heading" class="h3 mb-1 text-center">Create Your Account</h1>
                    <p class="text-muted text-center small mb-4">Join us today — it's free!</p>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert" aria-live="assertive">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form id="register-form"
                          method="POST"
                          action="register.php"
                          novalidate
                          aria-label="Registration form">

                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Username <span aria-hidden="true" class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="username"
                                   name="username"
                                   class="form-control"
                                   value="<?= e($formData['username']) ?>"
                                   minlength="3"
                                   maxlength="50"
                                   autocomplete="username"
                                   required
                                   aria-required="true"
                                   aria-describedby="username-feedback"
                                   placeholder="3–50 characters">
                            <div class="invalid-feedback" id="username-feedback"></div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email Address <span aria-hidden="true" class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= e($formData['email']) ?>"
                                   autocomplete="email"
                                   required
                                   aria-required="true"
                                   aria-describedby="email-feedback"
                                   placeholder="you@example.com">
                            <div class="invalid-feedback" id="email-feedback"></div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <span aria-hidden="true" class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   minlength="8"
                                   autocomplete="new-password"
                                   required
                                   aria-required="true"
                                   aria-describedby="pw-feedback pw-hint">
                            <div class="invalid-feedback" id="pw-feedback"></div>
                            <div class="form-text" id="pw-hint">
                                Min. 8 characters, including one uppercase letter and one number.
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                Confirm Password <span aria-hidden="true" class="text-danger">*</span>
                            </label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="form-control"
                                   autocomplete="new-password"
                                   required
                                   aria-required="true"
                                   aria-describedby="confirm-feedback">
                            <div class="invalid-feedback" id="confirm-feedback"></div>
                        </div>

                        <button type="submit" class="btn-store w-100 py-3 rounded">
                            Create Account <i class="bi bi-person-check ms-1" aria-hidden="true"></i>
                        </button>
                    </form>

                    <hr class="divider-line">

                    <p class="text-center text-muted small mb-0">
                        Already have an account? <a href="login.php">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>