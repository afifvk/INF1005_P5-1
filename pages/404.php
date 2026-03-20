<?php
define('SKIP_PAGE_RATE_LIMIT', true);
http_response_code(404);
$pageTitle = '404 Not Found';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section-pad">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-6">
                <div class="form-wrapper text-center">
                    <h1 class="display-5 mb-3">404 Not Found</h1>
                    <p class="text-muted mb-4">The page you are looking for could not be found.</p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="<?= e(SITE_URL) ?>/index.php" class="btn-store py-3 px-4 rounded">Return Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
