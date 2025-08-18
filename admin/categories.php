<?php
session_start();
require_once '../db_connect.php'; // Include database connection

// Protect page: redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Handle search/filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$whereClauses = [];
$params = [];
$sql = "SELECT js.skill, COUNT(js.id) AS job_count
        FROM job_skills js
        LEFT JOIN jobs j ON js.job_id = j.id
        WHERE 1=1";

if (!empty($search)) {
    $whereClauses[] = "js.skill LIKE ?";
    $params[] = "%$search%";
}
if (!empty($category)) {
    // Assuming categories are derived from a predefined list or user input
    // We'll filter by partial match on skill name for simplicity
    $whereClauses[] = "js.skill LIKE ?";
    $params[] = "%$category%";
}

if (!empty($whereClauses)) {
    $sql .= " AND " . implode(" AND ", $whereClauses);
}
$sql .= " GROUP BY js.skill ORDER BY js.skill";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all unique skills for filter dropdown (as a proxy for categories)
$stmt = $conn->query("SELECT DISTINCT skill FROM job_skills");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN); // Using skills as categories for filtering

// Handle skill actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $skillName = trim($_POST['skill_name'] ?? '');

    if ($action === 'add') {
        $newSkill = trim($_POST['name'] ?? '');

        if (!empty($newSkill)) {
            try {
                // Insert skill with a dummy job_id (0) for standalone skills
                $stmt = $conn->prepare("INSERT INTO job_skills (job_id, skill) VALUES (0, ?)");
                $stmt->execute([$newSkill]);
            } catch (PDOException $e) {
                $error = "Error adding skill: " . $e->getMessage();
            }
        } else {
            $error = "Skill name is required.";
        }
    } elseif ($action === 'edit') {
        $newSkill = trim($_POST['name'] ?? '');

        if (!empty($newSkill) && !empty($skillName)) {
            try {
                // Update all instances of the skill in job_skills
                $stmt = $conn->prepare("UPDATE job_skills SET skill = ? WHERE skill = ?");
                $stmt->execute([$newSkill, $skillName]);
            } catch (PDOException $e) {
                $error = "Error updating skill: " . $e->getMessage();
            }
        } else {
            $error = "Skill name is required.";
        }
    } elseif ($action === 'delete') {
        if (!empty($skillName)) {
            try {
                // Delete all instances of the skill
                $stmt = $conn->prepare("DELETE FROM job_skills WHERE skill = ?");
                $stmt->execute([$skillName]);
            } catch (PDOException $e) {
                $error = "Error deleting skill: " . $e->getMessage();
            }
        } else {
            $error = "Skill name is required.";
        }
    }

    // Refresh page to reflect changes
    header("Location: categories.php" . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Fetch skill details for editing
$editSkill = null;
if (isset($_GET['edit'])) {
    $editSkill = ['skill' => $_GET['edit']]; // Pass the skill name for editing
}

?>

<?php include 'header.php'; // Include header ?>

<main class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-dark-blue dark:text-gray-100 mb-6">Skills Management</h1>

    <!-- Error Message -->
    <?php if (isset($error)): ?>
        <div class="mb-4 p-3 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Form -->
    <form method="GET" class="mb-6 flex flex-col md:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
               placeholder="Search by skill name"
               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
        <select name="category"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Skills</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $category === $c ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Filter</button>
    </form>

    <!-- Add Skill Button -->
    <div class="mb-6">
        <a href="categories.php?add=true"
           class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">Add New Skill</a>
    </div>

    <!-- Skills Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600">
            <thead>
            <tr class="bg-gray-100 dark:bg-gray-700">
                <th class="px-4 py-2 border-b dark:border-gray-600 text-left">Skill Name</th>
                <th class="px-4 py-2 border-b dark:border-gray-600 text-left">Jobs Using Skill</th>
                <th class="px-4 py-2 border-b dark:border-gray-600 text-left">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($skills as $skill): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-2 border-b dark:border-gray-600"><?php echo htmlspecialchars($skill['skill']); ?></td>
                    <td class="px-4 py-2 border-b dark:border-gray-600"><?php echo $skill['job_count']; ?></td>
                    <td class="px-4 py-2 border-b dark:border-gray-600">
                        <a href="categories.php?edit=<?php echo urlencode($skill['skill']) . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                           class="text-blue-500 hover:text-blue-700">Edit</a>
                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this skill? This will remove it from all jobs.');">
                            <input type="hidden" name="skill_name" value="<?php echo htmlspecialchars($skill['skill']); ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="text-red-500 hover:text-red-700 ml-4">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Skill Modal -->
    <?php if (isset($_GET['add']) || $editSkill): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-lg">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4">
                    <?php echo isset($_GET['add']) ? 'Add New Skill' : 'Edit Skill'; ?>
                </h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="skill_name" value="<?php echo htmlspecialchars($editSkill['skill'] ?? ''); ?>">
                    <input type="hidden" name="action" value="<?php echo isset($_GET['add']) ? 'add' : 'edit'; ?>">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Skill Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($editSkill['skill'] ?? ''); ?>" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <a href="categories.php<?php echo !empty($_GET) ? '?' . http_build_query(array_diff_key($_GET, ['add' => '', 'edit' => ''])) : ''; ?>"
                           class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; // Include footer ?>