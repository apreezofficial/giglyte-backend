<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle search/filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$whereClauses = [];
$params = [];
$sql = "SELECT o.*, j.title AS job_title, uc.username AS client_username, uf.username AS freelancer_username 
        FROM orders o 
        LEFT JOIN jobs j ON o.job_id = j.id 
        LEFT JOIN users uc ON o.client_id = uc.id 
        LEFT JOIN users uf ON o.freelancer_id = uf.id";

if (!empty($search)) {
    $whereClauses[] = "j.title LIKE ?";
    $params[] = "%$search%";
}
if (!empty($status)) {
    $whereClauses[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'approve_delivery') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt->execute([$orderId]);
    } elseif ($action === 'cancel') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$orderId]);
    } elseif ($action === 'delete') {
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error deleting order: " . $e->getMessage();
        }
    }

    // Refresh page to reflect changes
    header("Location: orders.php" . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Fetch order details for viewing
$viewOrder = null;
$proposal = null;
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("
        SELECT o.*, j.title AS job_title, uc.username AS client_username, uf.username AS freelancer_username 
        FROM orders o 
        LEFT JOIN jobs j ON o.job_id = j.id 
        LEFT JOIN users uc ON o.client_id = uc.id 
        LEFT JOIN users uf ON o.freelancer_id = uf.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$_GET['view']]);
    $viewOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch related proposal
    $stmt = $conn->prepare("
        SELECT p.* 
        FROM proposals p 
        WHERE p.id = ?
    ");
    $stmt->execute([$viewOrder['proposal_id']]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include 'header.php'; // Include header ?>

<main class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-dark-blue dark:text-gray-100 mb-6">Order Management</h1>

    <!-- Error Message -->
    <?php if (isset($error)): ?>
        <div class="mb-4 p-3 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Form -->
    <form method="GET" class="mb-6 flex flex-col md:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
               placeholder="Search by job title"
               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Statuses</option>
            <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Filter</button>
    </form>

    <!-- View Order Modal -->
    <?php if ($viewOrder): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4">Order Details</h2>
                <div class="space-y-4">
                    <p><strong>Job Title:</strong> <?php echo htmlspecialchars($viewOrder['job_title']); ?></p>
                    <p><strong>Client:</strong> <?php echo htmlspecialchars($viewOrder['client_username'] ?? 'N/A'); ?></p>
                    <p><strong>Freelancer:</strong> <?php echo htmlspecialchars($viewOrder['freelancer_username'] ?? 'N/A'); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($viewOrder['status']); ?></p>
                    <?php if ($viewOrder['delivery_message']): ?>
                        <p><strong>Delivery Message:</strong> <?php echo htmlspecialchars($viewOrder['delivery_message']); ?></p>
                    <?php endif; ?>
                    <?php if ($viewOrder['delivery_file']): ?>
                        <p><strong>Delivery File:</strong> 
                            <a href="<?php echo htmlspecialchars($viewOrder['delivery_file']); ?>" class="text-light-blue hover:text-dark-blue" target="_blank">Download</a>
                        </p>
                    <?php endif; ?>
                    <?php if ($proposal): ?>
                        <h3 class="text-xl font-semibold text-dark-blue dark:text-gray-100 mt-6">Related Proposal</h3>
                        <p><strong>Cover Letter:</strong> <?php echo htmlspecialchars($proposal['cover_letter']); ?></p>
                        <p><strong>Proposed Amount:</strong> $<?php echo number_format($proposal['proposed_amount'], 2); ?></p>
                        <p><strong>Estimated Days:</strong> <?php echo htmlspecialchars($proposal['estimated_days']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($proposal['status']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <?php if ($viewOrder['status'] === 'in_progress' && $viewOrder['delivery_file']): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                            <input type="hidden" name="action" value="approve_delivery">
                            <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Approve Delivery</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($viewOrder['status'] === 'in_progress'): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-800">Cancel Order</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-800" onclick="return confirm('Are you sure you want to delete this order?');">Delete</button>
                    </form>
                    <a href="jobs.php?view=<?php echo $viewOrder['job_id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                       class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">View Job</a>
                    <a href="orders.php<?php echo !empty($_GET) ? '?' . http_build_query(array_diff_key($_GET, ['view' => ''])) : ''; ?>"
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-dark-blue dark:bg-gray-700 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Job Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Freelancer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['job_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['client_username'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['freelancer_username'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['status']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <a href="orders.php?view=<?php echo $order['id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                               class="text-light-blue hover:text-dark-blue">View</a>
                            <?php if ($order['status'] === 'in_progress' && $order['delivery_file']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="action" value="approve_delivery">
                                    <button type="submit" class="text-light-blue hover:text-dark-blue">Approve Delivery</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'in_progress'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="text-red-600 hover:text-red-800">Cancel</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this order?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>