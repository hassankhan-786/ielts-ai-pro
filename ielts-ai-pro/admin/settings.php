<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $settings = [
        'site_title'     => trim((string)($_POST['site_title'] ?? 'IELTS AI Pro')),
        'ai_provider'    => in_array($_POST['ai_provider'] ?? '', ['offline', 'gemini', 'openai'], true) ? $_POST['ai_provider'] : 'offline',
        'gemini_api_key' => trim((string)($_POST['gemini_api_key'] ?? '')),
        'openai_api_key' => trim((string)($_POST['openai_api_key'] ?? '')),
    ];
    $stmt = $pdo->prepare('
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (:k, :v)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ');
    foreach ($settings as $k => $v) {
        $stmt->execute([':k' => $k, ':v' => $v]);
    }
    set_flash('success', 'AI provider and system settings updated.');
    header('Location: ' . BASE_URL . '/admin/settings.php');
    exit;
}

$currentProvider = get_setting('ai_provider', AI_PROVIDER);
$geminiKey       = get_setting('gemini_api_key', GEMINI_API_KEY);
$openaiKey       = get_setting('openai_api_key', OPENAI_API_KEY);
$siteTitle       = get_setting('site_title', APP_NAME);

$pageTitle = 'AI & System Settings - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:680px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2>AI Grading Engine &amp; System Configuration</h2>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/index.php" class="btn btn-sm btn-outline">&larr; Back to Admin</a>
    </div>
    <p class="text-muted">
        By default, <strong>Offline Linguistic Rubric Mode</strong> is active so Writing and Speaking evaluations work immediately on local XAMPP without requiring external paid API keys. You can switch to Google Gemini or OpenAI below at any time.
    </p>
    <form method="post" action="settings.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="site_title">Platform Title</label>
            <input type="text" id="site_title" name="site_title" value="<?= htmlspecialchars($siteTitle) ?>">
        </div>
        <div class="form-group">
            <label for="ai_provider">Active AI Evaluation Engine</label>
            <select id="ai_provider" name="ai_provider">
                <option value="offline" <?= $currentProvider === 'offline' ? 'selected' : '' ?>>Built-in Offline IELTS Rubric Analyzer (No API Key Required)</option>
                <option value="gemini" <?= $currentProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini API (gemini-2.5-flash)</option>
                <option value="openai" <?= $currentProvider === 'openai' ? 'selected' : '' ?>>OpenAI API (gpt-4o-mini)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="gemini_api_key">Google Gemini API Key (Optional)</label>
            <input type="password" id="gemini_api_key" name="gemini_api_key" value="<?= htmlspecialchars($geminiKey) ?>" placeholder="AIzaSy...">
        </div>
        <div class="form-group">
            <label for="openai_api_key">OpenAI API Key (Optional)</label>
            <input type="password" id="openai_api_key" name="openai_api_key" value="<?= htmlspecialchars($openaiKey) ?>" placeholder="sk-...">
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
