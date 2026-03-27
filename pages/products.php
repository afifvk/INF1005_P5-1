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

<style>
/* ── Filter Sidebar ─────────────────────────────────── */
.filter-sidebar {
    background: #fff;
    border: 1px solid #e8e2d9;
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 20px;
}
.filter-sidebar__header {
    background: #3d6b4f;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.filter-sidebar__title {
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
    margin: 0;
}
.filter-clear-btn {
    font-size: .75rem;
    color: rgba(255,255,255,.75);
    background: none;
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 20px;
    padding: 3px 10px;
    cursor: pointer;
    transition: all .2s;
}
.filter-clear-btn:hover { background: rgba(255,255,255,.15); color: #fff; }

.filter-section {
    padding: 16px 18px;
    border-bottom: 1px solid #f0ece4;
}
.filter-section:last-child { border-bottom: none; }
.filter-section__label {
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7c5c3e;
    margin-bottom: 10px;
    display: block;
}

.filter-search {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #ddd6c9;
    border-radius: 8px;
    font-size: .88rem;
    outline: none;
    transition: border-color .2s;
}
.filter-search:focus { border-color: #3d6b4f; }

.filter-sort {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #ddd6c9;
    border-radius: 8px;
    font-size: .88rem;
    background: #faf8f4;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%233d6b4f' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    cursor: pointer;
}
.filter-sort:focus { outline: none; border-color: #3d6b4f; }

.filter-pills { display: flex; flex-wrap: wrap; gap: 6px; }

/* Accessible visually-hidden checkbox — still focusable by keyboard */
.filter-pill input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
}
.filter-pill label {
    display: inline-block;
    padding: 4px 11px;
    border-radius: 20px;
    border: 1.5px solid #ddd6c9;
    font-size: .78rem;
    color: #555;
    cursor: pointer;
    transition: all .2s;
    user-select: none;
}
.filter-pill input:checked + label {
    background: #3d6b4f;
    border-color: #3d6b4f;
    color: #fff;
    font-weight: 500;
}
.filter-pill label:hover { border-color: #3d6b4f; color: #3d6b4f; }

/* Keyboard focus ring on pill labels */
.filter-pill input:focus-visible + label {
    outline: 2px solid #3d6b4f;
    outline-offset: 2px;
}

.caffeine-none   input:checked + label { background: #6aaa6a; border-color: #6aaa6a; }
.caffeine-low    input:checked + label { background: #89b04a; border-color: #89b04a; }
.caffeine-medium input:checked + label { background: #c9a84c; border-color: #c9a84c; }
.caffeine-high   input:checked + label { background: #c0572a; border-color: #c0572a; }

.results-count { font-size: .88rem; color: #777; margin-bottom: 16px; min-height: 1.2em; }
.results-count strong { color: #1e2d24; }

.filter-loading { display: none; text-align: center; padding: 48px 0; }
.filter-loading.is-active { display: block; }
.filter-spinner {
    width: 36px; height: 36px;
    border: 3px solid #e0d9ce;
    border-top-color: #3d6b4f;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.caffeine-badge {
    display: inline-block;
    font-size: .68rem;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 20px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 6px;
}
.caffeine-badge--none   { background: #6aaa6a; }
.caffeine-badge--low    { background: #89b04a; }
.caffeine-badge--medium { background: #c9a84c; }
.caffeine-badge--high   { background: #c0572a; }

.tea-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
.tea-tag {
    font-size: .68rem;
    background: #f5f0e8;
    color: #7c5c3e;
    padding: 2px 8px;
    border-radius: 20px;
}

@media (max-width: 767px) {
    .filter-sidebar { position: static; margin-bottom: 24px; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-card {
    animation: fadeInUp 0.3s ease both;
}
</style>

<section class="section-pad" aria-labelledby="catalogue-heading">
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
                <aside class="filter-sidebar" aria-labelledby="filter-heading">
                    <div class="filter-sidebar__header">
                        <h2 class="filter-sidebar__title" id="filter-heading">Filters</h2>
                        <button class="filter-clear-btn" id="clear-filters" type="button">Clear all</button>
                    </div>

                    <div class="filter-section">
                        <label for="tea-search" class="filter-section__label">Search</label>
                        <input type="search" id="tea-search" class="filter-search"
                               placeholder="Search teas…" autocomplete="off">
                    </div>

                    <div class="filter-section">
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
                                <input type="checkbox" id="fl-<?= e($f) ?>" name="flavours[]" value="<?= e($f) ?>">
                                <label for="fl-<?= e($f) ?>"><?= e($f) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="caffeine-group-label">Caffeine Level</p>
                        <div class="filter-pills" role="group" aria-labelledby="caffeine-group-label">
                            <?php foreach ($caffeine as $c): ?>
                            <div class="filter-pill caffeine-<?= strtolower(e($c)) ?>">
                                <input type="checkbox" id="caf-<?= e($c) ?>" name="caffeine[]" value="<?= e($c) ?>">
                                <label for="caf-<?= e($c) ?>"><?= e($c) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="benefits-group-label">Health Benefits</p>
                        <div class="filter-pills" role="group" aria-labelledby="benefits-group-label">
                            <?php foreach ($benefits as $b): ?>
                            <div class="filter-pill">
                                <input type="checkbox" id="ben-<?= e($b) ?>" name="benefits[]" value="<?= e($b) ?>">
                                <label for="ben-<?= e($b) ?>"><?= e($b) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <p class="filter-section__label" id="origin-group-label">Origin</p>
                        <div class="filter-pills" role="group" aria-labelledby="origin-group-label">
                            <?php foreach ($origins as $o): ?>
                            <div class="filter-pill">
                                <input type="checkbox" id="ori-<?= e($o) ?>" name="origins[]" value="<?= e($o) ?>">
                                <label for="ori-<?= e($o) ?>"><?= e($o) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </aside>
            </div>

            <!-- Results -->
            <div class="col-md-9">
                <p class="results-count"
                   id="results-count"
                   aria-live="polite"
                   aria-atomic="true"></p>

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
</section>

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