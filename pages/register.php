<?php
$pageTitle = 'Register';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors   = [];
$formData = [
    'first_name' => '',
    'last_name'  => '',
    'address'    => '',
    'email'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Invalid form token. Please refresh and try again.';
    } else {
        $firstName = trim(isset($_POST['first_name']) ? $_POST['first_name'] : '');
        $lastName  = trim(isset($_POST['last_name']) ? $_POST['last_name'] : '');
        $address   = trim(isset($_POST['address']) ? $_POST['address'] : '');
        $email     = normalizeEmail(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password  = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm   = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $formData = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'address'    => $address,
            'email'      => $email,
        ];

        $errors = validateRegistration($firstName, $lastName, $email, $password, $confirm);

        if (empty($errors) && hasRecaptchaConfig()) {
            $recaptchaResult = verifyRecaptchaToken($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '');
            if (!$recaptchaResult['success']) {
                $errors[] = $recaptchaResult['message'];
            }
        }

        if (empty($errors)) {
            $result = registerUser($firstName, $lastName, $address, $email, $password);

            if (($result['status'] ?? '') === 'pending') {
                $_SESSION['pending_verification_email'] = $result['email'] ?? $email;
                redirect(SITE_URL . '/pages/verify_pending.php?email=' . urlencode($result['email'] ?? $email));
            } else {
                $errors[] = $result['message'] ?? 'Unable to create account.';
            }
        }
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
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

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="first_name" class="form-label">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       id="first_name"
                                       name="first_name"
                                       class="form-control"
                                       placeholder="John"
                                       value="<?= e($formData['first_name']) ?>"
                                       required>
                            </div>

                            <div class="col-sm-6">
                                <label for="last_name" class="form-label">
                                    Last Name <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       id="last_name"
                                       name="last_name"
                                       class="form-control"
                                       value="<?= e($formData['last_name']) ?>"
                                       maxlength="80"
                                       autocomplete="family-name"
                                       required
                                       placeholder="Smith">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email Address <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control"
                                   value="<?= e($formData['email']) ?>"
                                   autocomplete="email"
                                   required
                                   aria-describedby="email-feedback"
                                   placeholder="you@example.com">
                            <div class="invalid-feedback" id="email-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <textarea id="address"
                                      name="address"
                                      class="form-control"
                                      rows="3"
                                      autocomplete="street-address"
                                      placeholder="Optional — 123 Main Street, City, Postcode"><?= e($formData['address']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <p class="form-text mb-2" id="password-help">
                                Must contain at least 10 characters, one uppercase letter, one lowercase letter, one number, and one special character.
                            </p>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control"
                                   minlength="10"
                                   required
                                   autocomplete="new-password"
                                   aria-describedby="password-help"
                                   placeholder="Password">
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                Confirm Password <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="form-control"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Confirm Password">
                        </div>

                        <?php if (hasRecaptchaConfig()): ?>
                        <div class="mb-3 text-center">
                            <div class="d-inline-block">
                                <div class="g-recaptcha" data-sitekey="<?= e(RECAPTCHA_SITE_KEY) ?>"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-store w-100 py-3 rounded">
                            Create Account <i class="bi bi-person-plus ms-1" aria-hidden="true"></i>
                        </button>
                    </form>

                    <hr class="divider-line">

                    <p class="text-center text-muted small mb-0">
                        Already have an account?
                        <a href="login.php" class="text-decoration-underline">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (hasRecaptchaConfig()): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>