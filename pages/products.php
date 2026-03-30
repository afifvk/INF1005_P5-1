<?php
$pageTitle = 'Products';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/product_helpers.php';
require_once dirname(__DIR__) . '/includes/tea_helpers.php';
require_once dirname(__DIR__) . '/includes/header.php';

// Fetch distinct filter options for sidebar
$flavours = getDistinctTeaValues('flavour');
$origins  = getDistinctTeaValues('origin');
$benefits = getAllTeaBenefits();
$caffeine = ['None', 'Low', 'Medium', 'High'];

$products = getAllProducts();
?>

<!-- W3C/AXE fix: <section> changed to <div> so <aside> is not nested inside a landmark element -->
<div class="section-pad">
    <div class="container">

        <div class="row mb-4">
            <div class="col">
                <nav aria-label="Breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Products</li>
                    </ol>
                </nav>
                <h1 id="catalogue-heading" class="mb-1">Our Teas</h1>
                <p class="text-muted">Browse and filter our full collection</p>
            </div>
        </div>

        <div class="row g-4">

            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <!-- AXE fix: aria-labelledby links aside to its visible heading -->
                <aside class="filter-sidebar" aria-labelledby="filter-heading">
                    <div class="filter-sidebar__header">
                        <!-- AXE fix: id added so aside can be labelled by this heading -->
                        <h2 class="filter-sidebar__title" id="filter-heading">Filters</h2>
                        <button class="filter-clear-btn" id="clear-filters" type="button">Clear all</button>
                    </div>

                    <div class="filter-section">
                        <!-- W3C fix: explicit <label> associated to input via for/id -->
                        <label for="tea-search" class="filter-section__label">Search</label>
                        <input type="search" id="tea-search" class="filter-search"
                               placeholder="Search teas…" autocomplete="off">
                    </div>

                    <div class="filter-section">
                        <!-- W3C fix: explicit <label> associated to select via for/id -->
                        <label for="sort-select" class="filter-section__label">Sort by</label>
                        <select id="sort-select" class="filter-sort">
                            <option value="name_asc">Name A–Z</option>
                            <option value="name_desc">Name Z–A</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                        </select>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="flavour-group-label">Flavour</p>
                        <div class="filter-pills" role="group" aria-labelledby="flavour-group-label">
                            <?php foreach ($flavours as $f): ?>
                            <div class="filter-pill">
                                <input type="checkbox" id="fl-<?= e(str_replace(' ', '-', $f)) ?>" name="flavours[]" value="<?= e($f) ?>">
                                <label for="fl-<?= e(str_replace(' ', '-', $f)) ?>"><?= e($f) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="caffeine-group-label">Caffeine Level</p>
                        <div class="filter-pills" role="group" aria-labelledby="caffeine-group-label">
                            <?php foreach ($caffeine as $c): ?>
                            <div class="filter-pill caffeine-<?= strtolower(e($c)) ?>">
                                <input type="checkbox" id="caf-<?= e(str_replace(' ', '-', $c)) ?>" name="caffeine[]" value="<?= e($c) ?>">
                                <label for="caf-<?= e(str_replace(' ', '-', $c)) ?>"><?= e($c) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="benefits-group-label">Health Benefits</p>
                        <div class="filter-pills" role="group" aria-labelledby="benefits-group-label">
                            <?php foreach ($benefits as $b): ?>
                            <div class="filter-pill">
                                <!-- W3C fix: spaces replaced with hyphens in id/for attributes -->
                                <input type="checkbox" id="ben-<?= e(str_replace(' ', '-', $b)) ?>" name="benefits[]" value="<?= e($b) ?>">
                                <label for="ben-<?= e(str_replace(' ', '-', $b)) ?>"><?= e($b) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="origin-group-label">Origin</p>
                        <div class="filter-pills" role="group" aria-labelledby="origin-group-label">
                            <?php foreach ($origins as $o): ?>
                            <div class="filter-pill">
                                <!-- W3C fix: spaces replaced with hyphens in id/for attributes -->
                                <input type="checkbox" id="ori-<?= e(str_replace(' ', '-', $o)) ?>" name="origins[]" value="<?= e($o) ?>">
                                <label for="ori-<?= e(str_replace(' ', '-', $o)) ?>"><?= e($o) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </aside>
            </div>

            <!-- Results -->
            <div class="col-md-9">
                <!-- AXE fix: aria-live announces result count changes to screen readers -->
                <p class="results-count"
                   id="results-count"
                   aria-live="polite"
                   aria-atomic="true"></p>

                <!-- AXE fix: aria-hidden hides decorative spinner from screen readers -->
                <div class="filter-loading"
                     id="filter-loading"
                     aria-hidden="true">
                    <div class="filter-spinner"></div>
                    <p class="text-muted" style="font-size:.85rem;">Loading teas…</p>
                </div>

                <div id="results-container"></div>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const container   = document.getElementById('results-container');
    const countEl     = document.getElementById('results-count');
    const loadingEl   = document.getElementById('filter-loading');
    const clearBtn    = document.getElementById('clear-filters');
    const sortSelect  = document.getElementById('sort-select');
    const searchInput = document.getElementById('tea-search');

    let debounceTimer = null;

    function getParams() {
        const params = new URLSearchParams();
        document.querySelectorAll('input[name="flavours[]"]:checked').forEach(el => params.append('flavours[]', el.value));
        document.querySelectorAll('input[name="caffeine[]"]:checked').forEach(el => params.append('caffeine[]', el.value));
        document.querySelectorAll('input[name="benefits[]"]:checked').forEach(el => params.append('benefits[]', el.value));
        document.querySelectorAll('input[name="origins[]"]:checked').forEach(el  => params.append('origins[]',  el.value));
        params.set('sort',   sortSelect.value);
        params.set('search', searchInput.value.trim());
        return params;
    }

    async function fetchResults() {
        loadingEl.classList.add('is-active');
        loadingEl.removeAttribute('aria-hidden');
        container.innerHTML = '';
        countEl.textContent = '';

        try {
            const res  = await fetch('filter_ajax.php?' + getParams().toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('Network error');
            const html = await res.text();

            loadingEl.classList.remove('is-active');
            loadingEl.setAttribute('aria-hidden', 'true');
            container.innerHTML = html;

            container.querySelectorAll('.product-card').forEach(function(card, i) {
                card.style.animationDelay = (i * 0.05) + 's';
            });

            const cards = container.querySelectorAll('.product-card');
            countEl.innerHTML = cards.length > 0
                ? `Showing <strong>${cards.length}</strong> tea${cards.length !== 1 ? 's' : ''}`
                : 'No teas match your filters.';

        } catch (err) {
            loadingEl.classList.remove('is-active');
            loadingEl.setAttribute('aria-hidden', 'true');
            container.innerHTML = '<p class="text-danger">Could not load products. Please try again.</p>';
        }
    }

    function triggerFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchResults, 300);
    }

    document.querySelectorAll('input[type="checkbox"]').forEach(el => el.addEventListener('change', triggerFilter));
    sortSelect.addEventListener('change', triggerFilter);
    searchInput.addEventListener('input', triggerFilter);

    clearBtn.addEventListener('click', function () {
        document.querySelectorAll('input[type="checkbox"]').forEach(el => el.checked = false);
        sortSelect.value  = 'name_asc';
        searchInput.value = '';
        fetchResults();
    });

    // Pre-tick filters from URL params (e.g. from quiz)
    const urlParams = new URLSearchParams(window.location.search);
    const preSelected = urlParams.getAll('flavours[]');
    preSelected.forEach(function(val) {
        const cb = document.querySelector('input[name="flavours[]"][value="' + val + '"]');
        if (cb) cb.checked = true;
    });

    fetchResults();
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>