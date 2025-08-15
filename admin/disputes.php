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
$sql = "SELECT d.*, j.title AS job_title, uc.username AS client_username, uf.username AS freelancer_username 
        FROM disputes d 
        LEFT JOIN orders o ON d.order_id = o.id 
        LEFT JOIN jobs j ON o.job_id = j.id 
        LEFT JOIN users uc ON d.client_id = uc.id 
        LEFT JOIN users uf ON d.freelancer_id = uf.id";

if (!empty($search)) {
    $whereClauses[] = "j.title LIKE ?";
    $params[] = "%$search%";
}
if (!empty($status)) {
    $whereClauses[] = "d.status = ?";
    $params[] = $status;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle dispute actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disputeId = $_POST['dispute_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'resolve') {
        $resolution = $_POST['resolution'] ?? '';
        if (!empty($resolution)) {
            $stmt = $conn->prepare("
                UPDATE disputes 
                SET status = 'resolved', resolved_at = NOW(), resolution = ? 
                WHERE id = ?
            ");
            $stmt->execute([$resolution, $disputeId]);
        } else {
            $error = "Resolution description is required.";
        }
    } elseif ($action === 'close') {
        $stmt = $conn->prepare("UPDATE disputes SET status = 'closed', resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$disputeId]);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM disputes WHERE id = ?");
        $stmt->execute([$disputeId]);
    }

    // Refresh page to reflect changes
    header("Location: disputes.php" . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Fetch dispute details for viewing
$viewDispute = null;
$order = null;
$proposal = null;
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("
        SELECT d.*, j.title AS job_title, uc.username AS client_username, uf.username AS freelancer_username 
        FROM disputes d 
        LEFT JOIN orders o ON d.order_id = o.id 
        LEFT JOIN jobs j ON o.job_id = j.id 
        LEFT JOIN users uc ON d.client_id = uc.id 
        LEFT JOIN users uf ON d.freelancer_id = uf.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$_GET['view']]);
    $viewDispute = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch related order
    $stmt = $conn->prepare("
        SELECT o.*, j.title AS job_title 
        FROM orders o 
        LEFT JOIN jobs j ON o.job_id = j.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$viewDispute['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch related proposal
    $stmt = $conn->prepare("SELECT p.* FROM proposals p WHERE p.id = ?");
    $stmt->execute([$order['proposal_id']]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include 'header.php'; // Include header ?>

<main class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-dark-blue dark:text-gray-100 mb-6">Dispute Management</h1>

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
            <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
            <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Filter</button>
    </form>

    <!-- View Dispute Modal -->
    <?php if ($viewDispute): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4">Dispute Details</h2>
                <div class="space-y-4">
                    <p><strong>Job Title:</strong> <?php echo htmlspecialchars($viewDispute['job_title']); ?></p>
                    <p><strong>Client:</strong> <?php echo htmlspecialchars($viewDispute['client_username'] ?? 'N/A'); ?></p>
                    <p><strong>Freelancer:</strong> <?php echo htmlspecialchars($viewDispute['freelancer_username'] ?? 'N/A'); ?></p>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($viewDispute['reason']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($viewDispute['status']); ?></p>
                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($viewDispute['created_at']); ?></p>
                    <?php if ($viewDispute['resolved_at']): ?>
                        <p><strong>Resolved At:</strong> <?php echo htmlspecialchars($viewDispute['resolved_at']); ?></p>
                    <?php endif; ?>
                    <?php if ($viewDispute['resolution']): ?>
                        <p><strong>Resolution:</strong> <?php echo htmlspecialchars($viewDispute['resolution']); ?></p>
                    <?php endif; ?>
                    <?php if ($order): ?>
                        <h3 class="text-xl font-semibold text-dark-blue dark:text-gray-100 mt-6">Related Order</h3>
                        <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                        <?php if ($order['delivery_message']): ?>
                            <p><strong>Delivery Message:</strong> <?php echo htmlspecialchars($order['delivery_message']); ?></p>
                        <?php endif; ?>
                        <?php if ($order['delivery_file']): ?>
                            <p><strong>Delivery File:</strong> 
                                <a href="<?php echo htmlspecialchars($order['delivery_file']); ?>" class="text-light-blue hover:text-dark-blue" target="_blank">Download</a>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($proposal): ?>
                        <h3 class="text-xl font-semibold text-dark-blue dark:text-gray-100 mt-6">Related Proposal</h3>
                        <p><strong>Cover Letter:</strong> <?php echo htmlspecialchars($proposal['cover_letter']); ?></p>
                        <p><strong>Proposed Amount:</strong> $<?php echo number_format($proposal['proposed_amount'], 2); ?></p>
                        <p><strong>Estimated Days:</strong> <?php echo htmlspecialchars($proposal['estimated_days']); ?></p>
                    <?php endif; ?>
                    <?php if ($viewDispute['status'] === 'open'): ?>
                        <h3 class="text-xl font-semibold text-dark-blue dark:text-gray-100 mt-6">Resolve Dispute</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="dispute_id" value="<?php echo $viewDispute['id']; ?>">
                            <input type="hidden" name="action" value="resolve">
                            <div>
                                <label for="resolution" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resolution</label>
                                <textarea id="resolution" name="resolution" required
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100"></textarea>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Resolve</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <?php if ($viewDispute['status'] === 'open' || $viewDispute['status'] === 'resolved'): ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="dispute_id" value="<?php echo $viewDispute['id']; ?>">
                            <input type="hidden" name="action" value="close">
                            <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Close Dispute</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="dispute_id" value="<?php echo $viewDispute['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-800" onclick="return confirm('Are you sure you want to delete this dispute?');">Delete</button>
                    </form>
                    <a href="jobs.php?view=<?php echo $order['job_id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                       class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">View Job</a>
                    <a href="orders.php?view=<?php echo $viewDispute['order_id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                       class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">View Order</a>
                    <a href="disputes.php<?php echo !empty($_GET) ? '?' . http_build_query(array_diff_key($_GET, ['view' => ''])) : ''; ?>"
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Disputes Table -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-dark-blue dark:bg-gray-700 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Job Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Freelancer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($disputes as $dispute): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($dispute['job_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($dispute['client_username'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($dispute['freelancer_username'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars(substr($dispute['reason'], 0, 50)) . (strlen($dispute['reason']) > 50 ? '...' : ''); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($dispute['status']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <a href="disputes.php?view=<?php echo $dispute['id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                               class="text-light-blue hover:text-dark-blue">View</a>
                            <?php if ($dispute['status'] === 'open'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                                    <input type="hidden" name="action" value="resolve">
                                    <input type="hidden" name="resolution" value="Resolved by admin">
                                    <button type="submit" class="text-light-blue hover:text-dark-blue">Resolve</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($dispute['status'] === 'open' || $dispute['status'] === 'resolved'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                                    <input type="hidden" name="action" value="close">
                                    <button type="submit" class="text-light-blue hover:text-dark-blue">Close</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this dispute?');">Delete</button>
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