<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

$pdo = getDB();
$pageTitle = 'Manage Inventory';

$search = trim((string)($_GET['search'] ?? ''));
$status = (string)($_GET['status'] ?? 'all');
$sort = (string)($_GET['sort'] ?? 'newest');

$allowedStatuses = ['all', 'in_stock', 'out_of_stock'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$sortOptions = [
    'newest' => 'id DESC',
    'oldest' => 'id ASC',
    'name_asc' => 'name ASC',
    'name_desc' => 'name DESC',
    'price_low_high' => 'price ASC',
    'price_high_low' => 'price DESC',
    'stock_low_high' => 'stock ASC',
    'stock_high_low' => 'stock DESC',
];

if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'newest';
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(name LIKE ? OR description LIKE ?)';
    $likeSearch = '%' . $search . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if ($status === 'in_stock') {
    $where[] = 'stock > 0';
} elseif ($status === 'out_of_stock') {
    $where[] = 'stock = 0';
}

$sql = 'SELECT * FROM products';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $sortOptions[$sort];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$returnParams = [
    'search' => $search,
    'status' => $status,
    'sort' => $sort,
];

$returnParams = array_filter(
    $returnParams,
    static fn($value) => $value !== '' && $value !== 'all' && $value !== 'newest'
);

$returnTo = http_build_query($returnParams);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Manage Inventory</h1>
            <p class="text-muted mb-0">Filter, sort, and update your product inventory from one place.</p>
        </div>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Filter &amp; Sort Inventory</div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="inventory-search" class="form-label">Search</label>
                    <input type="text"
                           id="inventory-search"
                           name="search"
                           class="form-control"
                           placeholder="Search by product name or description"
                           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-3">
                    <label for="inventory-status" class="form-label">Filter</label>
                    <select id="inventory-status" name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Products</option>
                        <option value="in_stock" <?= $status === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                        <option value="out_of_stock" <?= $status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="inventory-sort" class="form-label">Sort By</label>
                    <select id="inventory-sort" name="sort" class="form-select">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name A to Z</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name Z to A</option>
                        <option value="price_low_high" <?= $sort === 'price_low_high' ? 'selected' : '' ?>>Price Low to High</option>
                        <option value="price_high_low" <?= $sort === 'price_high_low' ? 'selected' : '' ?>>Price High to Low</option>
                        <option value="stock_low_high" <?= $sort === 'stock_low_high' ? 'selected' : '' ?>>Stock Low to High</option>
                        <option value="stock_high_low" <?= $sort === 'stock_high_low' ? 'selected' : '' ?>>Stock High to Low</option>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center pt-1">
                    <small class="text-muted">Showing <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?>.</small>
                    <a href="<?= SITE_URL ?>/admin/inventory.php" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-4">
        <button type="button"
                class="btn btn-success"
                data-bs-toggle="modal"
                data-bs-target="#addInventoryModal">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
            Add Inventory
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Current Inventory</div>
        <div class="card-body">
            <div class="table-responsive inventory-table-scroll">
                <table class="table table-bordered table-hover align-middle inventory-table mb-0">
                    <thead class="table-dark inventory-table-head">
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Inventory</th>
                            <th>Status</th>
                            <th>Save</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $formId = 'update-product-' . (int)$product['id'];
                        $inStock = ((int)$product['stock']) > 0;
                        $productName = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                        $productImg = !empty($product['image'])
                            ? SITE_URL . '/assets/images/' . htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8')
                            : SITE_URL . '/assets/images/placeholder.svg';
                        ?>
                        <tr>
                            <td>
                                <?= (int)$product['id'] ?>
                                <form id="<?= $formId ?>" action="<?= SITE_URL ?>/admin/update_product.php" method="POST" class="d-none">
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
                                </form>
                            </td>

                            <td>
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <img src="<?= $productImg ?>"
                                         alt="<?= $productName ?>"
                                         width="70"
                                         class="img-thumbnail"
                                         onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'"
                                         style="cursor:pointer;"
                                         role="button"
                                         tabindex="0"
                                         aria-label="View larger image of <?= $productName ?>"
                                         onclick="openImageModal(this.src, '<?= $productName ?>')"
                                         onkeydown="if(event.key==='Enter')this.click()">
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            style="font-size: 0.7rem;"
                                            aria-label="Change image for <?= $productName ?>"
                                            onclick="openChangeImageModal(<?= (int)$product['id'] ?>, '<?= $productName ?>', '<?= $productImg ?>')">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i> Change
                                    </button>
                                </div>
                            </td>

                            <td>
                                <input type="text"
                                       name="name"
                                       value="<?= $productName ?>"
                                       class="form-control form-control-sm"
                                       form="<?= $formId ?>"
                                       aria-label="Product name for product <?= (int)$product['id'] ?>"
                                       required>
                            </td>

                            <td>
                                <textarea name="description"
                                          class="form-control form-control-sm"
                                          rows="2"
                                          form="<?= $formId ?>"
                                          aria-label="Description for product <?= (int)$product['id'] ?>"
                                          required><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </td>

                            <td>
                                <input type="number"
                                       name="price"
                                       step="0.01"
                                       min="0"
                                       value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>"
                                       class="form-control form-control-sm"
                                       form="<?= $formId ?>"
                                       aria-label="Price for product <?= (int)$product['id'] ?>"
                                       required>
                            </td>

                            <td>
                                <input type="number"
                                       name="stock"
                                       min="0"
                                       value="<?= (int)$product['stock'] ?>"
                                       class="form-control form-control-sm"
                                       form="<?= $formId ?>"
                                       aria-label="Stock for product <?= (int)$product['id'] ?>"
                                       required>
                            </td>

                            <td>
                                <span class="badge <?= $inStock ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $inStock ? 'In Stock' : 'Out of Stock' ?>
                                </span>
                            </td>

                            <td>
                                <button type="submit" class="btn btn-primary btn-sm" form="<?= $formId ?>">Save</button>
                            </td>

                            <td>
                                <a href="<?= SITE_URL ?>/admin/delete_product.php?id=<?= (int)$product['id'] ?><?= $returnTo !== '' ? '&return_to=' . urlencode($returnTo) : '' ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this product?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">No products found for the selected filters.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInventoryModalLabel">Add Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= SITE_URL ?>/admin/add_product.php" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="col-12">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" step="0.01" min="0" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Inventory Amount</label>
                        <input type="number" name="stock" min="0" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="image" accept="image/jpeg,image/png" class="form-control" required>
                        <div class="form-text">Accepted formats: JPG and PNG. Max size: 5 MB.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-labelledby="imageLightboxLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageLightboxLabel">Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="lightbox-img"
                     src=""
                     alt=""
                     class="img-fluid rounded shadow"
                     style="max-height: 70vh; object-fit: contain;"
                     onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">
            </div>
        </div>
    </div>
</div>

<!-- Change Image Modal -->
<div class="modal fade" id="changeImageModal" tabindex="-1" aria-labelledby="changeImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeImageModalLabel">Change Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= SITE_URL ?>/admin/update_product_image.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="changeImageProductId" value="">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="changeImagePreview"
                             src=""
                             alt=""
                             class="img-fluid rounded shadow-sm"
                             style="max-height: 200px; object-fit: contain;"
                             onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">
                    </div>
                    <div>
                        <label for="changeImageFile" class="form-label">Select new image</label>
                        <input type="file"
                               id="changeImageFile"
                               name="image"
                               accept="image/jpeg,image/png"
                               class="form-control"
                               required
                               onchange="if(this.files[0]){document.getElementById('changeImagePreview').src=URL.createObjectURL(this.files[0])}">
                        <div class="form-text">Accepted formats: JPG and PNG. Max size: 5 MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openImageModal(src, name) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-img').alt = name;
    document.getElementById('imageLightboxLabel').textContent = name;
    new bootstrap.Modal(document.getElementById('imageLightboxModal')).show();
}

function openChangeImageModal(productId, productName, currentImgSrc) {
    document.getElementById('changeImageProductId').value = productId;
    document.getElementById('changeImageModalLabel').textContent = 'Change Image — ' + productName;
    document.getElementById('changeImagePreview').src = currentImgSrc;
    document.getElementById('changeImagePreview').alt = productName;
    document.getElementById('changeImageFile').value = '';
    new bootstrap.Modal(document.getElementById('changeImageModal')).show();
}
</script>

<?php require_once __DIR__ . '/accessibility_landmarks.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
