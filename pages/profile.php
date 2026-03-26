<?php
/**
 * pages/profile.php
 * ALL processing happens before any HTML output.
 * This prevents "headers already sent" errors on redirect.
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/profile_helpers.php';

// Auth check — before any output
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please log in to view your profile.'];
    redirect(SITE_URL . '/pages/login.php');
}

$userId = (int)$_SESSION['user_id'];
$errors = [];

// ── ALL POST HANDLING BEFORE header.php ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {

        $action = isset($_POST['action']) ? $_POST['action'] : '';

        // UPDATE
        if ($action === 'update') {
            $firstName = trim(isset($_POST['first_name'])      ? $_POST['first_name']       : '');
            $lastName  = trim(isset($_POST['last_name'])       ? $_POST['last_name']        : '');
            $address   = trim(isset($_POST['address'])         ? $_POST['address']          : '');
            $currentUser = getUserById($userId);
            
            $newPass   = isset($_POST['new_password'])         ? $_POST['new_password']     : '';
            $confirmP  = isset($_POST['confirm_password'])     ? $_POST['confirm_password'] : '';

            $errors = validateProfileUpdate($firstName, $lastName, $newPass, $confirmP);

            if (empty($errors)) {
                $result = updateUserProfile($userId, $firstName, $lastName, $address, $newPass);
                if ($result === true) {
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name']  = $lastName;
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
                    redirect(SITE_URL . '/pages/profile.php'); // safe — no HTML output yet
                } else {
                    $errors[] = $result;
                }
            }
        }

        // DELETE
        if ($action === 'delete') {
            $confirmText = isset($_POST['confirm_delete']) ? trim($_POST['confirm_delete']) : '';
            if ($confirmText !== 'DELETE') {
                $errors[] = 'You must type DELETE exactly to confirm account deletion.';
            } else {
                deleteUser($userId);
                logoutUser();
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Your account has been deleted.'];
                redirect(SITE_URL . '/index.php'); // safe — no HTML output yet
            }
        }
    }
}

// Fetch user data for display
$user = getUserById($userId);
if (!$user) {
    logoutUser();
    redirect(SITE_URL . '/index.php');
}

// ── NOW safe to output HTML ──────────────────────────────────
$pageTitle = 'My Profile';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="section-pad" aria-labelledby="profile-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Header -->
                <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                    <div>
                        <h1 id="profile-heading" class="mb-1">My Profile</h1>
                        <p class="text-muted small mb-0">
                            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                            &nbsp;·&nbsp;
                            <span class="badge bg-secondary"><?= e(ucfirst($user['role'])) ?></span>
                        </p>
                    </div>
                    <a href="<?= SITE_URL ?>/pages/cart.php" class="btn-store-outline btn-sm">
                        <i class="bi bi-cart3 me-1" aria-hidden="true"></i> View Cart
                    </a>
                </div>

                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert" aria-live="assertive">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- PROFILE FORM -->
                <div class="form-wrapper mb-4">
                    <h2 class="h5 mb-4">
                        <i class="bi bi-person-circle me-2" aria-hidden="true"></i>
                        Account Details
                    </h2>

                    <form method="POST" action="profile.php" aria-label="Edit profile form">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="update">

                        <!-- First (required) + Last (required) -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="first_name" class="form-label">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="first_name" name="first_name"
                                       class="form-control"
                                       value="<?= e($user['first_name'] ?? '') ?>"
                                       maxlength="80"
                                       required>
                            </div>
                            <div class="col-sm-6">
                                <label for="last_name" class="form-label">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="last_name" name="last_name"
                                       class="form-control"
                                       value="<?= e($user['last_name'] ?? '') ?>"
                                       maxlength="80"
                                       required>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email"
                                class="form-control"
                                value="<?= e($user['email']) ?>"
                                disabled>
                                <div class="form-text">Email address cannot be changed.</div>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <textarea id="address" name="address"
                                      class="form-control" rows="3"
                                      placeholder="123 Main Street, City, Postcode"><?= e($user['address'] ?? '') ?></textarea>
                        </div>

                        <hr class="divider-line">

                        <!-- Change Password -->
                        <h3 class="h6 text-muted text-uppercase mb-3" style="letter-spacing:.06em;">
                            Change Password
                            <span class="fw-normal text-lowercase">(leave blank to keep current)</span>
                        </h3>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="form-control" autocomplete="new-password"
                                       aria-describedby="pw-note">
                                <div class="form-text" id="pw-note">Min 10 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character.</div>
                            </div>
                            <div class="col-sm-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="form-control" autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn-store px-4 py-2 rounded">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- DANGER ZONE -->
                <div class="mt-5">
                    <button class="btn btn-sm btn-outline-danger"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#danger-zone"
                            aria-expanded="false"
                            aria-controls="danger-zone">
                        <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                        Delete Account
                    </button>

                    <div class="collapse mt-3" id="danger-zone">
                        <div class="p-4 rounded border border-danger"
                             style="background:rgba(192,57,43,0.04);">
                            <h3 class="h6 text-danger mb-1">Delete Account</h3>
                            <p class="text-muted small mb-3">
                                Permanently deletes your account, cart, and all data.
                                <strong>This cannot be undone.</strong>
                            </p>

                            <form method="POST" action="profile.php"
                                  aria-label="Delete account form"
                                  onsubmit="return confirm('Are you absolutely sure? This cannot be undone.')">

                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="delete">

                                <div class="mb-3">
                                    <label for="confirm_delete" class="form-label small fw-semibold">
                                        Type <code>DELETE</code> to confirm
                                    </label>
                                    <input type="text"
                                           id="confirm_delete"
                                           name="confirm_delete"
                                           class="form-control form-control-sm"
                                           placeholder="DELETE"
                                           autocomplete="off"
                                           style="max-width:200px;">
                                </div>

                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash3 me-1" aria-hidden="true"></i>
                                    Permanently Delete My Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>