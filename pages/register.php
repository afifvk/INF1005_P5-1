<?php
$pageTitle = 'Register';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/pages/login.php');
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
        $lastName  = trim(isset($_POST['last_name'])  ? $_POST['last_name']  : '');
        $address   = trim(isset($_POST['address'])    ? $_POST['address']    : '');
        $email     = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password  = isset($_POST['password'])         ? $_POST['password']         : '';
        $confirm   = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $formData = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'address'    => $address,
            'email'      => $email,
        ];

        $errors = validateRegistration($firstName, $lastName, $email, $password, $confirm);

        if (empty($errors)) {
            $result = registerUser($firstName, $lastName, $address, $email, $password);

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

                        <!-- First Name (optional) + Last Name (required) -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text"
                                       id="first_name"
                                       name="first_name"
                                       class="form-control"
                                       value="<?= e($formData['first_name']) ?>"
                                       maxlength="80"
                                       autocomplete="given-name"
                                       placeholder="Optional">
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
                                       aria-required="true"
                                       placeholder="Smith">
                            </div>
                        </div>

                        <!-- Email -->
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
                                   aria-required="true"
                                   aria-describedby="email-feedback"
                                   placeholder="you@example.com">
                            <div class="invalid-feedback" id="email-feedback"></div>
                        </div>

                        <!-- Address (optional) -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <textarea id="address"
                                      name="address"
                                      class="form-control"
                                      rows="3"
                                      autocomplete="street-address"
                                      placeholder="Optional — 123 Main Street, City, Postcode"><?= e($formData['address']) ?></textarea>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <span class="text-danger" aria-hidden="true">*</span>
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
                                Min. 8 characters, one uppercase letter, one number.
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                Confirm Password <span class="text-danger" aria-hidden="true">*</span>
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