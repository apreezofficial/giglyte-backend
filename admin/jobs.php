<?php
session_start();
require_once '../db_connect.php'; // Include database connection

// Protect page: redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Add is_approved column to jobs table if not exists
try {
    $conn->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS is_approved ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
} catch (PDOException $e) {
    // Ignore if column already exists
}

// Handle search/filter
$search = $_GET['search'] ?? '';
$skill = $_GET['skill'] ?? '';
$status = $_GET['status'] ?? '';
$approval = $_GET['approval'] ?? '';

$whereClauses = [];
$params = [];
$sql = "SELECT j.*, u.username AS client_username, COUNT(o.id) AS order_count 
        FROM jobs j 
        LEFT JOIN users u ON j.client_id = u.id 
        LEFT JOIN orders o ON j.id = o.job_id";

if (!empty($search)) {
    $whereClauses[] = "j.title LIKE ?";
    $params[] = "%$search%";
}
if (!empty($skill)) {
    $sql .= " LEFT JOIN job_skills js ON j.id = js.job_id";
    $whereClauses[] = "js.skill = ?";
    $params[] = $skill;
}
if (!empty($status)) {
    $whereClauses[] = "j.status = ?";
    $params[] = $status;
}
if (!empty($approval)) {
    $whereClauses[] = "j.is_approved = ?";
    $params[] = $approval;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " GROUP BY j.id";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all unique skills for filter dropdown
$stmt = $conn->query("SELECT DISTINCT skill FROM job_skills");
$skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle job and proposal actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = $_POST['job_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'approve' || $action === 'reject') {
        $newApproval = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE jobs SET is_approved = ? WHERE id = ?");
        $stmt->execute([$newApproval, $jobId]);
    } elseif ($action === 'delete') {
        // Soft delete: set status to cancelled
        $stmt = $conn->prepare("UPDATE jobs SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$jobId]);
    } elseif ($action === 'hard_delete') {
        // Hard delete: remove job and related data
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("DELETE FROM job_skills WHERE job_id = ?");
            $stmt->execute([$jobId]);
            $stmt = $conn->prepare("DELETE FROM orders WHERE job_id = ?");
            $stmt->execute([$jobId]);
            $stmt = $conn->prepare("DELETE FROM proposals WHERE job_id = ?");
            $stmt->execute([$jobId]);
            $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error deleting job: " . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $budget = $_POST['budget'] ?? 0.00;
        $status = $_POST['status'] ?? 'open';
        $skillsInput = $_POST['skills'] ?? '';

        if (!empty($title) && !empty($description)) {
            $conn->beginTransaction();
            try {
                // Update job
                $stmt = $conn->prepare("UPDATE jobs SET title = ?, description = ?, budget = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, floatval($budget), $status, $jobId]);

                // Update skills
                $stmt = $conn->prepare("DELETE FROM job_skills WHERE job_id = ?");
                $stmt->execute([$jobId]);
                if (!empty($skillsInput)) {
                    $skillsArray = array_map('trim', explode(',', $skillsInput));
                    foreach ($skillsArray as $skill) {
                        if (!empty($skill)) {
                            $stmt = $conn->prepare("INSERT INTO job_skills (job_id, skill) VALUES (?, ?)");
                            $stmt->execute([$jobId, $skill]);
                        }
                    }
                }
                $conn->commit();
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = "Error updating job: " . $e->getMessage();
            }
        } else {
            $error = "Title and description are required.";
        }
    } elseif ($action === 'accept_proposal' || $action === 'reject_proposal') {
        $proposalId = $_POST['proposal_id'] ?? '';
        $newStatus = $action === 'accept_proposal' ? 'accepted' : 'rejected';
        $stmt = $conn->prepare("UPDATE proposals SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $proposalId]);

        // If accepting, create an order
        if ($action === 'accept_proposal') {
            $stmt = $conn->prepare("SELECT job_id, freelancer_id FROM proposals WHERE id = ?");
            $stmt->execute([$proposalId]);
            $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $conn->prepare("SELECT client_id FROM jobs WHERE id = ?");
            $stmt->execute([$proposal['job_id']]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("
                INSERT INTO orders (job_id, proposal_id, client_id, freelancer_id, status)
                VALUES (?, ?, ?, ?, 'in_progress')
            ");
            $stmt->execute([$proposal['job_id'], $proposalId, $job['client_id'], $proposal['freelancer_id']]);
        }
    } elseif ($action === 'delete_proposal') {
        $proposalId = $_POST['proposal_id'] ?? '';
        $stmt = $conn->prepare("DELETE FROM proposals WHERE id = ?");
        $stmt->execute([$proposalId]);
    } elseif ($action === 'edit_proposal') {
        $proposalId = $_POST['proposal_id'] ?? '';
        $coverLetter = $_POST['cover_letter'] ?? '';
        $proposedAmount = $_POST['proposed_amount'] ?? 0.00;
        $estimatedDays = $_POST['estimated_days'] ?? 0;

        if (!empty($coverLetter) && $proposedAmount > 0 && $estimatedDays > 0) {
            $stmt = $conn->prepare("
                UPDATE proposals 
                SET cover_letter = ?, proposed_amount = ?, estimated_days = ?
                WHERE id = ?
            ");
            $stmt->execute([$coverLetter, floatval($proposedAmount), intval($estimatedDays), $proposalId]);
        } else {
            $error = "Cover letter, proposed amount, and estimated days are required.";
        }
    }

    // Refresh page to reflect changes
    header("Location: jobs.php" . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Fetch job details for editing
$editJob = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editJob = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch skills for the job
    $stmt = $conn->prepare("SELECT skill FROM job_skills WHERE job_id = ?");
    $stmt->execute([$editJob['id']]);
    $editJob['skills'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch job details for viewing
$viewJob = null;
$proposals = [];
$assignedFreelancer = null;
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("SELECT j.*, u.username AS client_username FROM jobs j LEFT JOIN users u ON j.client_id = u.id WHERE j.id = ?");
    $stmt->execute([$_GET['view']]);
    $viewJob = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch skills for the job
    $stmt = $conn->prepare("SELECT skill FROM job_skills WHERE job_id = ?");
    $stmt->execute([$viewJob['id']]);
    $viewJob['skills'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch proposals
    $stmt = $conn->prepare("
        SELECT p.*, u.username AS freelancer_username 
        FROM proposals p 
        LEFT JOIN users u ON p.freelancer_id = u.id 
        WHERE p.job_id = ?
    ");
    $stmt->execute([$viewJob['id']]);
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check for assigned freelancer (from accepted proposal with order)
    $stmt = $conn->prepare("
        SELECT u.username 
        FROM orders o 
        LEFT JOIN users u ON o.freelancer_id = u.id 
        WHERE o.job_id = ? AND o.status IN ('in_progress', 'completed')
        LIMIT 1
    ");
    $stmt->execute([$viewJob['id']]);
    $assignedFreelancer = $stmt->fetchColumn();
}

// Fetch proposal details for editing
$editProposal = null;
if (isset($_GET['edit_proposal'])) {
    $stmt = $conn->prepare("
        SELECT p.*, u.username AS freelancer_username 
        FROM proposals p 
        LEFT JOIN users u ON p.freelancer_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$_GET['edit_proposal']]);
    $editProposal = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<?php include 'header.php'; // Include header ?>

<main class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-dark-blue dark:text-gray-100 mb-6">Service (Gig) Management</h1>

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
        <select name="skill" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Skills</option>
            <?php foreach ($skills as $s): ?>
                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $skill === $s ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Statuses</option>
            <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
            <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
        <select name="approval" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Approval Statuses</option>
            <option value="pending" <?php echo $approval === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo $approval === 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $approval === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Filter</button>
    </form>

    <!-- Edit Job Modal -->
    <?php if ($editJob): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4">Edit Job</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="job_id" value="<?php echo $editJob['id']; ?>">
                    <input type="hidden" name="action" value="edit">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editJob['title']); ?>" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea id="description" name="description" required
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100"><?php echo htmlspecialchars($editJob['description']); ?></textarea>
                    </div>
                    <div>
                        <label for="budget" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Budget ($)</label>
                        <input type="number" id="budget" name="budget" value="<?php echo htmlspecialchars($editJob['budget']); ?>" step="0.01" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                            <option value="open" <?php echo $editJob['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $editJob['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $editJob['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $editJob['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label for="skills" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Skills (comma-separated)</label>
                        <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars(implode(', ', $editJob['skills'])); ?>"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <a href="jobs.php<?php echo !empty($_GET) ? '?' . http_build_query(array_diff_key($_GET, ['edit' => ''])) : ''; ?>"
                           class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Proposal Modal -->
    <?php if ($editProposal): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4">Edit Proposal</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="job_id" value="<?php echo $editProposal['job_id']; ?>">
                    <input type="hidden" name="proposal_id" value="<?php echo $editProposal['id']; ?>">
                    <input type="hidden" name="action" value="edit_proposal">
                    <div>
                        <label for="freelancer" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Freelancer</label>
                        <input type="text" id="freelancer" value="<?php echo htmlspecialchars($editProposal['freelancer_username']); ?>" disabled
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <label for="cover_letter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cover Letter</label>
                        <textarea id="cover_letter" name="cover_letter" required
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100"><?php echo htmlspecialchars($editProposal['cover_letter']); ?></textarea>
                    </div>
                    <div>
                        <label for="proposed_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Proposed Amount ($)</label>
                        <input type="number" id="proposed_amount" name="proposed_amount" value="<?php echo htmlspecialchars($editProposal['proposed_amount']); ?>" step="0.01" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div>
                        <label for="estimated_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estimated Days</label>
                        <input type="number" id="estimated_days" name="estimated_days" value="<?php echo htmlspecialchars($editProposal['estimated_days']); ?>" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <a href="jobs.php?view=<?php echo $editProposal['job_id'] . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['edit_proposal' => ''])) : ''); ?>"
                           class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- View Job Modal -->
    <?php if ($viewJob): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-3xl max-h-[80vh] overflow-y-auto">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4">Job Details</h2>
                <div class="space-y-4">
                    <p><strong>Title:</strong> <?php echo htmlspecialchars($viewJob['title']); ?></p>
                    <p><strong>Client:</strong> <?php echo htmlspecialchars($viewJob['client_username'] ?? 'N/A'); ?></p>
                    <p><strong>Budget:</strong> $<?php echo number_format($viewJob['budget'], 2); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($viewJob['status']); ?></p>
                    <p><strong>Approval Status:</strong> <?php echo htmlspecialchars($viewJob['is_approved']); ?></p>
                    <p><strong>Skills:</strong> <?php echo htmlspecialchars(implode(', ', $viewJob['skills'])); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($viewJob['description']); ?></p>
                    <?php if ($assignedFreelancer): ?>
                        <p><strong>Assigned Freelancer:</strong> <?php echo htmlspecialchars($assignedFreelancer); ?></p>
                    <?php else: ?>
                        <p><strong>Assigned Freelancer:</strong> None</p>
                    <?php endif; ?>
                    <h3 class="text-xl font-semibold text-dark-blue dark:text-gray-100 mt-6">Proposals</h3>
                    <?php if ($proposals): ?>
                        <div class="space-y-4">
                            <?php foreach ($proposals as $proposal): ?>
                                <div class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <p><strong>Freelancer:</strong> <?php echo htmlspecialchars($proposal['freelancer_username']); ?></p>
                                    <p><strong>Cover Letter:</strong> <?php echo htmlspecialchars($proposal['cover_letter']); ?></p>
                                    <p><strong>Proposed Amount:</strong> $<?php echo number_format($proposal['proposed_amount'], 2); ?></p>
                                    <p><strong>Estimated Days:</strong> <?php echo htmlspecialchars($proposal['estimated_days']); ?></p>
                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($proposal['status']); ?></p>
                                    <div class="mt-2 space-x-2">
                                        <?php if ($proposal['status'] === 'pending'): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="job_id" value="<?php echo $viewJob['id']; ?>">
                                                <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                                <input type="hidden" name="action" value="accept_proposal">
                                                <button type="submit" class="text-light-blue hover:text-dark-blue">Accept</button>
                                            </form>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="job_id" value="<?php echo $viewJob['id']; ?>">
                                                <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                                <input type="hidden" name="action" value="reject_proposal">
                                                <button type="submit" class="text-light-blue hover:text-dark-blue">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="jobs.php?edit_proposal=<?php echo $proposal['id'] . '&view=' . $viewJob['id'] . (!empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['view' => '', 'edit_proposal' => ''])) : ''); ?>"
                                           class="text-light-blue hover:text-dark-blue">Edit</a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="job_id" value="<?php echo $viewJob['id']; ?>">
                                            <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                            <input type="hidden" name="action" value="delete_proposal">
                                            <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this proposal?');">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No proposals for this job.</p>
                    <?php endif; ?>
                </div>
                <div class="mt-6 flex justify-end">
                    <a href="jobs.php<?php echo !empty($_GET) ? '?' . http_build_query(array_diff_key($_GET, ['view' => '', 'edit_proposal' => ''])) : ''; ?>"
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Jobs Table -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-dark-blue dark:bg-gray-700 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Budget</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Approval</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($jobs as $job): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($job['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($job['client_username'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($job['budget'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($job['status']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($job['is_approved']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $job['order_count']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <a href="jobs.php?view=<?php echo $job['id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                               class="text-light-blue hover:text-dark-blue">View</a>
                            <a href="jobs.php?edit=<?php echo $job['id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                               class="text-light-blue hover:text-dark-blue">Edit</a>
                            <?php if ($job['is_approved'] === 'pending'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="text-light-blue hover:text-dark-blue">Approve</button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="text-light-blue hover:text-dark-blue">Reject</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="text-red-600 hover:text-red-800">Cancel</button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <input type="hidden" name="action" value="hard_delete">
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to permanently delete this job?');">Delete</button>
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