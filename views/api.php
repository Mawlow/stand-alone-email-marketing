<?php
$apiKeys = $pdo->query('SELECT id, name, created_at FROM api_keys ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$newKey = $_SESSION['new_api_key'] ?? null;
$newKeyName = $_SESSION['new_api_key_name'] ?? '';
if ($newKey !== null) {
    unset($_SESSION['new_api_key'], $_SESSION['new_api_key_name']);
}
$apiBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (dirname($_SERVER['SCRIPT_NAME'] ?? '') !== '/' ? rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') : '');
?>
<style>
    /* Hide the default title area from index.php */
    main > div > div.mb-6:first-child {
        display: none;
    }

    /* Force the parent container to be full width and remove padding */
    main > div.max-w-6xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Style flash messages to stay centered */
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem; /* 6xl */
        margin-left: auto;
        margin-right: auto;
        margin-top: 1.5rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }

    .api-banner {
        margin-bottom: 2rem;
    }

    /* Re-apply content constraints for the cards below the banner */
    .api-content-wrapper {
        max-width: 72rem; /* 6xl */
        margin: 0 auto;
        padding: 0 1rem 2rem 1rem;
    }
    @media (min-width: 640px) { .api-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
    @media (min-width: 1024px) { .api-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Banner (Dashboard Style) -->
<div class="api-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">API</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Manage your email marketing</p>
        </div>
    </div>
</div>

<div class="api-content-wrapper">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">API for external websites</h2></div>
    <p class="p-4 text-slate-600 text-sm border-b border-slate-100">Other sites can send email campaigns to this system using an API key. They send subject, body, and a list of recipient emails; campaigns are sent using your senders and (optionally) your header/footer design.</p>
    <?php if ($newKey): ?>
    <div class="mx-4 mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
        <p class="font-bold text-emerald-800 mb-1">API key created (copy it now — it won’t be shown again):</p>
        <div class="flex flex-wrap items-center gap-2">
            <code id="new-api-key" class="block flex-1 min-w-0 p-3 bg-white border border-emerald-300 rounded-lg text-sm break-all"><?= h($newKey) ?></code>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-api-key').textContent); this.textContent='Copied!';" class="px-4 py-2 bg-[#02396E] text-white rounded-lg font-medium text-sm">Copy</button>
        </div>
        <p class="text-emerald-700 text-xs mt-2">Use in requests: <code>X-API-Key: &lt;key&gt;</code> or <code>Authorization: Bearer &lt;key&gt;</code></p>
    </div>
    <?php endif; ?>
    <div class="p-6">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Create API key</h3>
        <form method="post" action="<?= url('api') ?>" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="action" value="api-key-create">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Name (e.g. website or company)</label>
                <input type="text" name="api_key_name" required maxlength="255" placeholder="e.g. Acme Corp Website" class="rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E] w-64">
            </div>
            <button type="submit" class="px-4 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Create key</button>
        </form>
    </div>
    <div class="border-t border-slate-200 p-6">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Your API keys</h3>
        <?php if (empty($apiKeys)): ?>
        <p class="text-slate-500">No API keys yet. Create one above and give it to the external site.</p>
        <?php else: ?>
        <table class="w-full text-left">
            <thead class="bg-slate-50"><tr><th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase">Name</th><th class="px-4 py-2 text-xs font-bold text-slate-600 uppercase">Created</th><th class="px-4 py-2 text-right text-xs font-bold text-slate-600 uppercase">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($apiKeys as $k): ?>
            <tr class="border-t border-slate-100"><td class="px-4 py-3 font-medium"><?= h($k['name']) ?></td><td class="px-4 py-3 text-slate-500 text-sm"><?= h($k['created_at']) ?></td><td class="px-4 py-3 text-right"><form method="post" action="<?= url('api') ?>" class="inline" onsubmit="return confirm('Delete this API key? The external site will no longer be able to send campaigns.');"><input type="hidden" name="action" value="api-key-delete"><input type="hidden" name="id" value="<?= (int)$k['id'] ?>"><button type="submit" class="text-red-600 hover:underline text-sm">Delete</button></form></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <div class="border-t border-slate-200 p-6 bg-slate-50">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-2">Endpoint</h3>
        <p class="text-slate-600 text-sm mb-2"><strong>POST</strong> <code class="bg-white px-2 py-1 rounded border border-slate-200"><?= h($apiBaseUrl) ?>/api/v1/send</code></p>
        <p class="text-slate-600 text-sm mb-2">Headers: <code>Content-Type: application/json</code>, <code>X-API-Key: &lt;your-key&gt;</code></p>
        <p class="text-slate-600 text-sm mb-2">Body (JSON):</p>
        <pre class="bg-white p-4 rounded-xl border border-slate-200 text-xs overflow-x-auto">{
  "subject": "Your email subject",
  "body": "&lt;p&gt;HTML content or plain text&lt;/p&gt;",
  "recipients": ["email1@example.com", "email2@example.com"],
  "use_design": true
}</pre>
        <p class="text-slate-500 text-xs mt-2"><code>use_design</code> (optional): if <code>true</code>, wraps the body with your header and footer from Design.</p>
    </div>
</div>
