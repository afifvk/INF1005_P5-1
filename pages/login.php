<?php
/**
 * pages/login.php
 * Handles GET (display form) and POST (process login).
 *
 * SECURITY:
 * - CSRF token validated on POST
 * - password_verify() used (timing-safe)
 * - Session regenerated on successful login
 * - Generic error message prevents username enumeration
 */

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        // Sanitize inputs — trim and filter
        $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $emailVal = $email;

        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter your email and password.';
        } else {
            $result = loginUser($email, $password);

            if ($result === true) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . e($_SESSION['username']) . '!'];
                redirect(SITE_URL . '/index.php');
            } else {
                $errors[] = $result; // Generic: "Invalid email or password."
            }
        }
    }
}
?>

<section class="section-pad" aria-labelledby="login-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5">

                <div class="form-wrapper">
                    <h1 id="login-heading" class="h3 mb-1 text-center">Welcome Back</h1>
                    <p class="text-muted text-center small mb-4">Sign in to your account</p>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert" aria-live="assertive">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form id="login-form"
                          method="POST"
                          action="login.php"
                          novalidate
                          aria-label="Login form">

                        <!-- CSRF Token (hidden) -->
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= e($emailVal) ?>"
                                   autocomplete="email"
                                   required
                                   aria-required="true"
                                   aria-describedby="email-hint">
                            <div class="invalid-feedback" id="email-hint">Please enter a valid email.</div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   autocomplete="current-password"
                                   required
                                   aria-required="true"
                                   aria-describedby="pw-hint">
                            <div class="invalid-feedback" id="pw-hint">Please enter your password.</div>
                        </div>

                        <button type="submit" class="btn-store w-100 py-3 rounded">
                            Sign In <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </button>
                    </form>

                    <hr class="divider-line">

                    <p class="text-center text-muted small mb-0">
                        Don't have an account?
                        <a href="register.php">Create one here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
