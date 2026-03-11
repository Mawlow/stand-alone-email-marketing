<?php
$searchQuery = $_GET['search'] ?? '';
$q = 'SELECT g.*, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as member_count FROM contact_groups g';
$params = [];
if ($searchQuery !== '') {
    $q .= ' WHERE g.name LIKE ?';
    $params[] = "%$searchQuery%";
}
$q .= ' ORDER BY g.name';
$stmt = $pdo->prepare($q);
$stmt->execute($params);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    .groups-banner {
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

<!-- Groups Banner -->
<div class="groups-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Contact Groups</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Organize your contacts for more targeted campaigns.</p>
        </div>
        <!-- Search Bar -->
        <form method="get" action="<?= url('groups') ?>" class="relative z-10 w-full md:w-[400px]">
            <input type="hidden" name="page" value="groups">
            <div class="relative group">
                <input type="text" name="search" id="groupsSearch" placeholder="Search groups..." value="<?= h($searchQuery) ?>" class="w-full bg-white rounded-xl py-3 pl-12 pr-20 text-slate-900 text-base placeholder-slate-400 focus:outline-none transition-all shadow-inner border-none">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                
                <!-- Clear Button (X) -->
                <button type="button" id="clearGroupsSearch" class="absolute right-12 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 <?= empty($searchQuery) ? 'hidden' : '' ?>" title="Clear search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Search Arrow Button -->
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-[#02396E] text-white rounded-full shadow-md" title="Search now">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </button>
            </div>
        </form>
    </div>
    <!-- Decorative element -->
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('groupsSearch');
    const clearBtn = document.getElementById('clearGroupsSearch');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (searchInput.value.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        });

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            clearBtn.classList.add('hidden');
            searchInput.closest('form').submit();
        });
    }

    // Modal Logic
    const deleteModal = document.getElementById('deleteModal');
    const deleteGroupName = document.getElementById('deleteGroupName');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    let formToSubmit = null;

    document.querySelectorAll('.delete-trigger').forEach(button => {
        button.addEventListener('click', (e) => {
            formToSubmit = button.closest('form');
            const groupName = button.getAttribute('data-group-name');
            deleteGroupName.textContent = groupName;
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
        });
    });

    const closeModal = () => {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
        formToSubmit = null;
    };

    cancelDeleteBtn.addEventListener('click', closeModal);
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeModal();
    });

    confirmDeleteBtn.addEventListener('click', () => {
        if (formToSubmit) formToSubmit.submit();
    });

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

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 text-left">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-6 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-xl md:text-2xl font-bold text-white">Groups</h2>
            <a href="<?= url('group-edit') ?>" class="inline-flex items-center px-3.5 py-1.5 bg-white text-[#02396E] text-sm font-bold rounded-xl hover:bg-[#ff8904] hover:text-white transition-colors">+ Add Group</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Name</th>
                        <th class="px-4 md:px-8 py-3 text-center text-xs font-black text-slate-700 uppercase">Contacts</th>
                        <th class="pl-4 md:pl-8 pr-6 md:pr-14 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $g): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold text-slate-900"><?= h($g['name']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-center text-slate-600 font-medium"><?= (int)($g['member_count'] ?? 0) ?></td>
                        <td class="px-4 md:px-8 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="<?= url('group-edit', ['id' => $g['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form method="post" action="<?= url('groups') ?>" class="inline">
                                    <input type="hidden" name="action" value="group-delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                    <button type="button" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors delete-trigger" data-group-name="<?= h($g['name']) ?>" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($groups)): ?>
                    <tr><td colspan="3" class="px-4 md:px-8 py-12 text-center text-slate-500 font-bold">No groups found. <a href="<?= url('group-edit') ?>" class="text-[#ff8904] hover:underline">Create your first group</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden modal-animate">
        <div class="p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600 mb-4 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Delete Group?</h3>
            <p class="text-slate-600">Are you sure you want to delete <span id="deleteGroupName" class="font-bold text-slate-900"></span>? Contacts in this group will not be removed from your list.</p>
        </div>
        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3">
            <button id="confirmDeleteBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200">Delete Group</button>
            <button id="cancelDeleteBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 font-bold rounded-xl border border-slate-200 hover:bg-slate-100 transition-colors">Cancel</button>
        </div>
    </div>
</div>
