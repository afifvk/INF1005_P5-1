<?php
$pageTitle = 'My Saved Teas';

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/recommendation_helpers.php';

if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please log in to view your saved teas.'];
    redirect(SITE_URL . '/pages/login.php');
}

$userId = (int)$_SESSION['user_id'];
$recommendations = getRecommendationsByUser($userId);

function quizAnswerLabel($value) {
    $map = [
        'light' => 'Light & floral',
        'earthy' => 'Earthy & robust',
        'sweet' => 'Sweet & aromatic',
        'morning' => 'Morning boost',
        'afternoon' => 'Afternoon calm',
        'evening' => 'Evening relaxation',
        'adventurous' => 'Adventurous',
        'cozy' => 'Cozy',
        'focused' => 'Focused',
        'citrus' => 'Citrus',
        'vanilla' => 'Vanilla',
        'smoky' => 'Smoky'
    ];
    return $map[$value] ?? $value;
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="section-pad" aria-labelledby="saved-teas-heading">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h1 id="saved-teas-heading" class="mb-1">My Saved Teas</h1>
                <p class="text-muted mb-0">Your Personalitea recommendations, saved in one place.</p>
            </div>
            <a href="<?= SITE_URL ?>/index.php#quiz-section" class="btn-store-outline btn-sm">
                <i class="bi bi-stars me-1" aria-hidden="true"></i>Take quiz again
            </a>
        </div>

        <?php if (empty($recommendations)): ?>
            <div class="form-wrapper text-center py-4">
                <h2 class="h5">No saved teas yet</h2>
                <p class="text-muted mb-3">Take the Personalitea quiz and save your favourite suggestions.</p>
                <a href="<?= SITE_URL ?>/index.php#quiz-section" class="btn-store">Go to quiz</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($recommendations as $item): ?>
                    <?php
                    $displayName = $item['product_name'] ?: ($item['product_title'] ?: 'Tea recommendation');
                    $answers = json_decode($item['answers_json'] ?? '', true);
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="product-card h-100">
                            <?php if (!empty($item['product_image'])): ?>
                                <div class="card-img-wrapper">
                                    <img src="<?= SITE_URL ?>/assets/images/<?= e($item['product_image']) ?>"
                                         alt="<?= e($displayName) ?>"
                                         loading="lazy"
                                         onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h3 class="product-name"><?= e($displayName) ?></h3>
                                <p class="text-muted small mb-2">Saved on <?= date('d M Y, h:i A', strtotime($item['created_at'])) ?></p>

                                <?php if (is_array($answers) && !empty($answers)): ?>
                                    <p class="small mb-2"><strong>Your quiz picks:</strong></p>
                                    <ul class="small text-muted ps-3 mb-3">
                                        <?php foreach ($answers as $value): ?>
                                            <li><?= e(quizAnswerLabel((string)$value)) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <div class="mt-auto d-flex gap-2 flex-wrap">
                                    <?php if (!empty($item['product_id'])): ?>
                                        <a href="<?= SITE_URL ?>/pages/product_detail.php?id=<?= (int)$item['product_id'] ?>" class="btn-store-outline btn-sm">View</a>
                                        <form method="POST" action="<?= SITE_URL ?>/pages/cart_action.php" data-ajax-cart="true">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <button type="submit" class="btn-store btn-sm">
                                                <i class="bi bi-cart-plus" aria-hidden="true"></i> Add to cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= SITE_URL ?>/pages/products.php" class="btn-store-outline btn-sm">Browse products</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
