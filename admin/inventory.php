<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

if (!isAdmin()) {
    die("Access denied.");
}

$pdo = getDB();
$pageTitle = 'Manage Inventory';

$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Manage Inventory</h2>

    <div class="card mb-4">
        <div class="card-header">Add New Product</div>
        <div class="card-body">
            <form action="<?= SITE_URL ?>/admin/add_product.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" name="price" step="0.01" min="0" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Inventory Amount</label>
                    <input type="number" name="stock" min="0" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Product Image</label>
                    <input type="file" name="image" accept="image/*" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-success">Add Product</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Current Inventory</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
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
                        <tr>
                            <form action="<?= SITE_URL ?>/admin/update_product.php" method="POST">
                                <td>
                                    <?= (int)$product['id'] ?>
                                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                                </td>

                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($product['image']) ?>"
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             width="70">
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <input type="text"
                                           name="name"
                                           value="<?= htmlspecialchars($product['name']) ?>"
                                           class="form-control"
                                           required>
                                </td>

                                <td>
                                    <textarea name="description"
                                              class="form-control"
                                              rows="2"
                                              required><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                </td>

                                <td>
                                    <input type="number"
                                           name="price"
                                           step="0.01"
                                           min="0"
                                           value="<?= htmlspecialchars($product['price']) ?>"
                                           class="form-control"
                                           required>
                                </td>

                                <td>
                                    <input type="number"
                                           name="stock"
                                           min="0"
                                           value="<?= (int)$product['stock'] ?>"
                                           class="form-control"
                                           required>
                                </td>

                                <td>
                                    <select name="is_active" class="form-select" required>
                                        <option value="1" <?= !empty($product['is_active']) ? 'selected' : '' ?>>
                                            In Stock
                                        </option>
                                        <option value="0" <?= empty($product['is_active']) ? 'selected' : '' ?>>
                                            Out of Stock
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Save
                                    </button>
                                </td>

                                <td>
                                    <a href="<?= SITE_URL ?>/admin/delete_product.php?id=<?= (int)$product['id'] ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this product?');">
                                        Delete
                                    </a>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No products found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>