<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $title    = trim((string)($_POST['title'] ?? ''));
    $module   = in_array($_POST['module'] ?? '', ['reading', 'listening', 'writing', 'speaking'], true) ? $_POST['module'] : 'reading';
    $examType = in_array($_POST['exam_type'] ?? '', ['academic', 'general', 'both'], true) ? $_POST['exam_type'] : 'both';
    $duration = max(5, (int)($_POST['duration_minutes'] ?? 60));
    $desc     = trim((string)($_POST['description'] ?? ''));
    $passage  = trim((string)($_POST['passage_or_script'] ?? ''));

    if ($title !== '') {
        $stmt = $pdo->prepare('
            INSERT INTO tests (title, module, exam_type, duration_minutes, description, passage_or_script, is_active)
            VALUES (:title, :mod, :etype, :dur, :desc, :pass, 1)
        ');
        $stmt->execute([
            ':title' => $title,
            ':mod'   => $module,
            ':etype' => $examType,
            ':dur'   => $duration,
            ':desc'  => $desc,
            ':pass'  => $passage,
        ]);
        set_flash('success', 'New IELTS practice test created.');
    }
    header('Location: ' . BASE_URL . '/admin/tests.php');
    exit;
}

$tests = $pdo->query('
    SELECT t.*, COUNT(q.id) AS question_count
    FROM tests t
    LEFT JOIN questions q ON q.test_id = t.id
    GROUP BY t.id
    ORDER BY t.id ASC
')->fetchAll();

$pageTitle = 'Manage IELTS Tests - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2>IELTS Test Bank</h2>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/index.php" class="btn btn-sm btn-outline">&larr; Back to Admin</a>
    </div>
    <table class="data-table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Module</th>
                <th>Track</th>
                <th>Duration</th>
                <th>Questions</th>
                <th>Preview</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tests as $t): ?>
                <tr>
                    <td>#<?= (int)$t['id'] ?></td>
                    <td><?= htmlspecialchars($t['title']) ?></td>
                    <td><span class="pill"><?= ucfirst(htmlspecialchars($t['module'])) ?></span></td>
                    <td><?= ucfirst(htmlspecialchars($t['exam_type'])) ?></td>
                    <td><?= (int)$t['duration_minutes'] ?> mins</td>
                    <td><?= (int)$t['question_count'] ?></td>
                    <td><a href="<?= htmlspecialchars(BASE_URL . '/' . $t['module'] . '.php?test_id=' . (int)$t['id']) ?>" class="btn btn-sm btn-outline">Open</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:1.5rem;">
    <h3>Create New Practice Test</h3>
    <form method="post" action="tests.php">
        <?= csrf_field() ?>
        <div class="grid-2">
            <div class="form-group">
                <label for="title">Test Title</label>
                <input type="text" id="title" name="title" required placeholder="e.g., Academic Reading Practice Test 2">
            </div>
            <div class="form-group">
                <label for="module">Module</label>
                <select id="module" name="module">
                    <option value="reading">Reading</option>
                    <option value="listening">Listening</option>
                    <option value="writing">Writing</option>
                    <option value="speaking">Speaking</option>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="exam_type">Exam Track</label>
                <select id="exam_type" name="exam_type">
                    <option value="academic">Academic</option>
                    <option value="general">General Training</option>
                    <option value="both">Both</option>
                </select>
            </div>
            <div class="form-group">
                <label for="duration_minutes">Duration (Minutes)</label>
                <input type="number" id="duration_minutes" name="duration_minutes" value="60" min="5" max="180">
            </div>
        </div>
        <div class="form-group">
            <label for="description">Instructions / Description</label>
            <input type="text" id="description" name="description" placeholder="Short instructions for candidates...">
        </div>
        <div class="form-group">
            <label for="passage_or_script">Reading Passage HTML / Listening Script / Writing Prompt</label>
            <textarea id="passage_or_script" name="passage_or_script" rows="6"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Test</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
