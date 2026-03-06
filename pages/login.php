<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        $email    = normalizeEmail(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';
        $emailVal = $email;

        if (empty($email) || empty($password)) {
            $errors[] = 'Please enter your email and password.';
        } else {
            $result = loginUser($email, $password);

            if ($result === true) {
                $name = trim((string) ($_SESSION['first_name'] ?? '') . ' ' . (string) ($_SESSION['last_name'] ?? ''));
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . trim($name) . '!'];
                redirect(SITE_URL . '/index.php');
            } else {
                $errors[] = $result;
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
                                   aria-required="true"
                                   aria-describedby="email-hint">
                            <div class="invalid-feedback" id="email-hint">Please enter a valid email.</div>
                        </div>

                        <div class="mb-3">
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
