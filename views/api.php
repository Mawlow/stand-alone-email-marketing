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
    main > div > div.mb-4:first-child { display: none; }
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem;
        margin-left: auto;
        margin-right: auto;
        margin-top: 1rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }

    .api-banner { margin-bottom: 2rem; }

    /* Content Wrapper matching compose.php */
    .api-content-wrapper {
        max-width: 72rem; /* 6xl */
        margin: 0 auto;
        padding: 0 1rem 2rem 1rem;
    }
    @media (max-width: 1023px) { .api-content-wrapper { margin-top: 1.5rem; } }
    @media (min-width: 640px) { .api-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
    @media (min-width: 1024px) { .api-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Banner (The "Identity") -->
<div class="api-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">API</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Integrate with external systems</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white pb-6">
    <h1 class="text-xl font-bold">API</h1>
    <p class="text-blue-100/80 text-xs">Integrate systems</p>
</div>

<div class="api-content-wrapper mt-6 lg:mt-0">
    <!-- Main API Card - Less Padding -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h2 class="text-lg md:text-2xl font-bold text-white">API Integration</h2>
        </div>
        
        <div class="p-4 md:p-6 space-y-6">
            <p class="text-slate-700 text-sm font-medium leading-relaxed border-b border-slate-50 pb-4">
                Connect external websites to this system using an API key. Trigger campaigns using your senders and optional design templates.
            </p>

            <?php if ($newKey): ?>
            <!-- Alert for New Key -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                <p class="font-bold text-slate-700 text-sm mb-2 uppercase tracking-wide">Key Successfully Generated:</p>
                <div class="flex flex-col sm:flex-row items-stretch gap-2">
                    <code id="new-api-key" class="flex-1 p-3 bg-white border border-slate-200 rounded-lg text-sm font-mono font-bold text-slate-800 break-all shadow-inner"><?= h($newKey) ?></code>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-api-key').textContent); this.textContent='Copied!';" class="inline-flex items-center px-6 py-2.5 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-[#f54a00] transition-colors whitespace-nowrap">Copy Token</button>
                </div>
                <p class="text-slate-500 text-[10px] mt-2 font-bold uppercase tracking-tighter">This token will not be shown again.</p>
            </div>
            <?php endif; ?>

            <!-- List Section -->
            <div>
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Active Credentials</h3>
                <?php if (empty($apiKeys)): ?>
                <div class="py-8 text-center bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-slate-400 text-xs italic">No active API keys discovered.</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Name</th>
                                <th class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Created</th>
                                <th class="px-4 py-2 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php foreach ($apiKeys as $k): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-bold text-slate-800"><?= h($k['name']) ?></td>
                            <td class="px-4 py-3 text-xs text-slate-500 font-medium"><?= h(date('M d, Y', strtotime($k['created_at']))) ?></td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" action="<?= url('api') ?>" class="inline delete-form" data-key-name="<?= h($k['name']) ?>">
                                    <input type="hidden" name="action" value="api-key-delete">
                                    <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                                    <button type="button" class="delete-btn text-red-600 hover:text-red-800 font-bold text-xs uppercase tracking-tighter hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Create New Key Form -->
            <div>
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Request New Access</h3>
                <div id="api-key-form" class="bg-slate-50 p-4 md:p-6 rounded-2xl border border-slate-200 mb-8">
                    <form method="post" action="<?= url('api') ?>" class="flex flex-col md:flex-row md:items-end gap-3">
                        <input type="hidden" name="action" value="api-key-create">
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Application Name</label>
                            <input type="text" name="api_key_name" required maxlength="255" placeholder="e.g. My Website Signup" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-[#02396E] outline-none transition-all bg-white">
                        </div>
                        <button type="submit" class="inline-flex items-center px-8 py-2.5 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-[#f54a00] transition-colors shadow-md">Generate Key</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Documentation -->
        <div class="bg-slate-900 p-6 md:p-8 text-white border-t border-slate-800">
            <h3 class="text-xs font-bold uppercase tracking-widest mb-6 border-b border-white/5 pb-2">Quick Integration Guide</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Header</label>
                        <code class="block p-3 bg-white/5 border border-white/10 rounded-xl text-blue-100 font-mono text-xs">X-API-Key: YOUR_TOKEN</code>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Endpoint</label>
                        <code class="block p-3 bg-white/5 border border-white/10 rounded-xl text-blue-100 font-mono text-xs break-all">POST <?= h($apiBaseUrl) ?>/api/v1/send</code>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">JSON Payload</label>
                    <pre class="bg-black/40 border border-white/10 p-4 rounded-xl font-mono text-[10px] text-blue-100 overflow-x-auto leading-relaxed">{
  "subject": "System Campaign",
  "body": "&lt;p&gt;HTML Content&lt;/p&gt;",
  "recipients": ["user@domain.com"],
  "use_design": true
}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-[1px] flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 transform transition-all">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Delete API Key</h3>
                    <p class="text-sm text-slate-500">This action cannot be undone</p>
                </div>
            </div>
            <p class="text-slate-700 mb-6">Are you sure you want to delete the API key for <span id="keyName" class="font-semibold text-slate-900"></span>? External integrations using this key will stop working.</p>
            <div class="flex gap-3">
                <button type="button" id="cancelDelete" class="flex-1 px-4 py-2 bg-white text-slate-600 font-medium rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="button" id="confirmDelete" class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">Delete Key</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize modal functionality immediately
(function() {
    const deleteModal = document.getElementById('deleteModal');
    const keyNameSpan = document.getElementById('keyName');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    let currentForm = null;

    // Handle delete button clicks - use event delegation
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            e.preventDefault();
            const form = deleteBtn.closest('.delete-form');
            const keyName = form.dataset.keyName;
            
            currentForm = form;
            keyNameSpan.textContent = keyName;
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
            return false;
        }
    });

    // Handle cancel button
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
            currentForm = null;
            return false;
        });
    }

    // Handle confirm delete
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentForm) {
                currentForm.submit();
            }
            return false;
        });
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !deleteModal.classList.contains('hidden')) {
            cancelDeleteBtn.click();
        }
    });

    // Close modal on backdrop click
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            cancelDeleteBtn.click();
        }
    });
})();
</script>
