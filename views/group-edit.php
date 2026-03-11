<?php
$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$group = null;
$members = [];
$nonMembers = [];

if ($groupId) {
    $st = $pdo->prepare('SELECT * FROM contact_groups WHERE id = ?');
    $st->execute([$groupId]);
    $group = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($group) {
        $stMembers = $pdo->prepare('SELECT c.* FROM marketing_contacts c INNER JOIN contact_group_members m ON m.contact_id = c.id WHERE m.group_id = ? ORDER BY c.email');
        $stMembers->execute([$groupId]);
        $members = $stMembers->fetchAll(PDO::FETCH_ASSOC);

        // Fetch contacts not in this group for the dropdown
        $stNonMembers = $pdo->prepare('SELECT * FROM marketing_contacts WHERE id NOT IN (SELECT contact_id FROM contact_group_members WHERE group_id = ?) ORDER BY email');
        $stNonMembers->execute([$groupId]);
        $nonMembers = $stNonMembers->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<style>
    /* Force the parent container to be full width */
    main > div.max-w-6xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Style flash messages */
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem; /* 6xl */
        margin-left: auto;
        margin-right: auto;
        margin-top: 1.5rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* Success notification as toast at the bottom right */
    .toast-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .toast-message {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        border: 2px solid #10b981; /* emerald-500 */
        color: #065f46; /* emerald-800 */
        font-weight: 500;
        min-width: 300px;
        animation: toastIn 0.3s ease-out forwards;
    }

    .toast-message.error {
        border-color: #ef4444; /* red-500 */
        color: #991b1b; /* red-800 */
    }

    /* Target the central index.php flash message if it exists */
    main > div.max-w-6xl > div.bg-emerald-50 {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
        width: auto;
        min-width: 300px;
        margin: 0;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        border-width: 2px;
        animation: toastIn 0.3s ease-out forwards;
    }

    @keyframes toastIn {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .toast-fade-out {
        animation: toastOut 0.5s ease-in forwards !important;
    }

    @keyframes toastOut {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(100%); opacity: 0; }
    }

    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }

    .group-edit-banner {
        margin-bottom: 2rem;
    }

    /* Modal Animation */
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .modal-animate {
        animation: modalFadeIn 0.2s ease-out forwards;
    }
</style>

<!-- Group Edit Banner -->
<div class="group-edit-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="mb-4">
            <a href="<?= url('groups') ?>" class="text-[#ff8904] hover:text-orange-600 text-sm font-bold flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back
            </a>
        </div>
        <div>
            <h1 class="text-[2.5rem] font-bold leading-tight"><?= $group ? 'Edit Group' : 'Add New Group' ?></h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Define and organize your contact segments.</p>
        </div>
    </div>
    <!-- Decorative element -->
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const showToast = (message, isError = false) => {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast-message ${isError ? 'error' : ''}`;
        toast.textContent = message;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('toast-fade-out');
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    };

    // Remove Member Modal Logic
    const removeModal = document.getElementById('removeModal');
    const removeContactEmail = document.getElementById('removeContactEmail');
    const confirmRemoveBtn = document.getElementById('confirmRemoveBtn');
    const cancelRemoveBtn = document.getElementById('cancelRemoveBtn');
    let removeFormToSubmit = null;

    // Delegate remove trigger click
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-trigger')) {
            const button = e.target;
            removeFormToSubmit = button.closest('form');
            const email = button.getAttribute('data-email');
            removeContactEmail.textContent = email;
            removeModal.classList.remove('hidden');
            removeModal.classList.add('flex');
        }
    });

    const closeRemoveModal = () => {
        removeModal.classList.add('hidden');
        removeModal.classList.remove('flex');
        removeFormToSubmit = null;
    };

    if (cancelRemoveBtn) cancelRemoveBtn.addEventListener('click', closeRemoveModal);
    if (removeModal) {
        removeModal.addEventListener('click', (e) => {
            if (e.target === removeModal) closeRemoveModal();
        });
    }

    if (confirmRemoveBtn) {
        confirmRemoveBtn.addEventListener('click', () => {
            if (removeFormToSubmit) removeFormToSubmit.submit();
        });
    }

    // Quick Create AJAX
    const quickCreateForm = document.getElementById('quickCreateForm');
    if (quickCreateForm) {
        quickCreateForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(quickCreateForm);
            
            try {
                const response = await fetch(quickCreateForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    quickCreateForm.reset();
                    
                    // Add new row to table
                    const tbody = document.getElementById('membersTableBody');
                    const emptyRow = tbody.querySelector('tr td[colspan]');
                    if (emptyRow) tbody.innerHTML = ''; // Clear "No contacts" message
                    
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-slate-50 transition-colors group';
                    row.innerHTML = `
                        <td class="px-6 py-4 text-sm font-semibold text-slate-900">${result.data.email}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">${result.data.company_name}</td>
                        <td class="px-6 py-4 text-right">
                            <form method="post" action="${quickCreateForm.action}" class="inline remove-member-form">
                                <input type="hidden" name="action" value="group-remove-member">
                                <input type="hidden" name="group_id" value="${formData.get('group_id')}">
                                <input type="hidden" name="contact_id" value="${result.data.id}">
                                <button type="button" class="text-red-600 hover:text-red-800 text-xs font-bold uppercase tracking-wider transition-colors remove-trigger" data-email="${result.data.email}">Remove</button>
                            </form>
                        </td>
                    `;
                    tbody.prepend(row);
                } else {
                    showToast(result.message, true);
                }
            } catch (err) {
                showToast('An error occurred. Please try again.', true);
            }
        });
    }

    // Auto-hide success notification (toast) after 3 seconds
    const successToast = document.querySelector('main > div.max-w-6xl > div.bg-emerald-50');
    if (successToast) {
        setTimeout(() => {
            successToast.classList.add('toast-fade-out');
            setTimeout(() => successToast.remove(), 500); // Remove after animation ends
        }, 3000);
    }
});
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 space-y-8">
    <!-- Group Details Card -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden text-left">
        <div class="bg-[#02396E] px-6 py-6 border-b border-white/10">
            <h3 class="text-base md:text-lg font-bold text-white uppercase tracking-wide">Group Details</h3>
        </div>
        <form method="post" action="<?= url('groups') ?>">
            <input type="hidden" name="action" value="group-save">
            <?php if ($group): ?><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><?php endif; ?>
            
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Group Name *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:outline-none transition-all" value="<?= h($group['name'] ?? '') ?>" placeholder="e.g. Newsletter, VIP, Customers">
                    <p class="mt-2 text-xs text-slate-400">Choose a unique name to easily identify this segment.</p>
                </div>
            </div>

            <div class="bg-slate-50 px-8 py-4 flex gap-3 border-t border-slate-100">
                <button type="submit" class="px-5 py-2 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-[#f54a00] shadow-lg shadow-orange-900/10 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    <?= $group ? 'Update Group' : 'Create Group' ?>
                </button>
                <a href="<?= url('groups') ?>" class="px-5 py-2 bg-white text-slate-600 text-sm font-bold rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <?php if ($group): ?>
    <!-- Group Membership Card -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden text-left">
        <div class="bg-[#02396E] px-6 py-4 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-base md:text-lg font-bold text-white uppercase tracking-wide">Contacts in this Group</h3>
        </div>

        <!-- Membership Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 bg-slate-50 border-b border-slate-100">
            <!-- Add Existing Section -->
            <div class="p-6">
                <h4 class="text-[10px] font-black text-slate-700 uppercase tracking-widest mb-4">Add Existing Contact</h4>
                <form method="post" action="<?= url('group-edit', ['id' => $groupId]) ?>" class="flex gap-2">
                    <input type="hidden" name="action" value="group-add-member">
                    <input type="hidden" name="group_id" value="<?= (int)$group['id'] ?>">
                    <select name="contact_id" class="flex-1 rounded-xl border border-slate-200 px-4 py-[5px] text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:outline-none transition-all bg-white text-sm">
                        <option value="">-- Select existing --</option>
                        <?php foreach ($nonMembers as $nm): ?>
                            <option value="<?= (int)$nm['id'] ?>"><?= h($nm['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 py-[5px] bg-[#ff8904] text-white font-bold rounded-xl hover:bg-[#f54a00] transition-all text-sm">Add</button>
                </form>
            </div>
            <!-- Quick Create New Section -->
            <div class="p-6">
                <h4 class="text-[10px] font-black text-slate-700 uppercase tracking-widest mb-4">Quick Create & Add</h4>
                <form id="quickCreateForm" method="post" action="<?= url('group-edit', ['id' => $groupId]) ?>" class="flex flex-col sm:flex-row gap-2">
                    <input type="hidden" name="action" value="group-create-add-member">
                    <input type="hidden" name="group_id" value="<?= (int)$group['id'] ?>">
                    <input type="email" name="email" required placeholder="New email address" class="flex-1 rounded-xl border border-slate-200 px-4 py-[5px] text-sm focus:ring-2 focus:ring-[#ff8904] outline-none transition-all">
                    <input type="text" name="company_name" placeholder="Name/Company" class="flex-1 rounded-xl border border-slate-200 px-4 py-[5px] text-sm focus:ring-2 focus:ring-[#ff8904] outline-none transition-all">
                    <button type="submit" class="px-4 py-1.5 bg-[#ff8904] text-white font-bold rounded-lg hover:bg-[#f54a00] transition-all text-xs whitespace-nowrap">Create & Add</button>
                </form>
            </div>
        </div>
        
        <!-- Members Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-1/2 px-6 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="w-1/3 px-6 py-3 text-xs font-black text-slate-500 uppercase tracking-wider">Company</th>
                        <th class="px-6 py-3 text-right text-xs font-black text-slate-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody id="membersTableBody" class="divide-y divide-slate-100">
                    <?php foreach ($members as $member): ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-900"><?= h($member['email']) ?></td>
                        <td class="px-6 py-4 text-sm text-slate-600"><?= h($member['company_name'] ?: '—') ?></td>
                        <td class="px-6 py-4 text-right">
                            <form method="post" action="<?= url('group-edit', ['id' => $groupId]) ?>" class="inline remove-member-form">
                                <input type="hidden" name="action" value="group-remove-member">
                                <input type="hidden" name="group_id" value="<?= (int)$group['id'] ?>">
                                <input type="hidden" name="contact_id" value="<?= (int)$member['id'] ?>">
                                <button type="button" class="text-red-600 hover:text-red-800 text-xs font-bold uppercase tracking-wider transition-colors remove-trigger" data-email="<?= h($member['email']) ?>">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-slate-400 font-medium">
                            No contacts in this group yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-slate-50 px-8 py-6 border-t border-slate-100 flex justify-between items-center">
            <p class="text-xs text-slate-400 italic">Manage detailed info by editing contacts directly.</p>
            <a href="<?= url('contacts') ?>" class="text-[#02396E] text-xs font-black uppercase tracking-wider hover:underline flex items-center gap-1">
                Manage All Contacts
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Remove Contact Confirmation Modal -->
<div id="removeModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden modal-animate">
        <div class="p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600 mb-4 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Remove from Group?</h3>
            <p class="text-slate-600">Are you sure you want to remove <span id="removeContactEmail" class="font-bold text-slate-900"></span> from this group? The contact will still remain in your main list.</p>
        </div>
        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3">
            <button id="confirmRemoveBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200">Remove Contact</button>
            <button id="cancelRemoveBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 font-bold rounded-xl border border-slate-200 hover:bg-slate-100 transition-colors">Cancel</button>
        </div>
    </div>
</div>
