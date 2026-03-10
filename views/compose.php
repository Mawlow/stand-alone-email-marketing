<?php
$templateSubject = 'Simplify your hiring process — Start with us';
$templateBody = '<p style="margin:0 0 16px; font-size:11px; font-weight:700; color:#ff8904;">Hire smarter. Hire faster.</p><h1 style="margin:0 0 20px; font-size:28px; font-weight:800; color:#0f172a;">Simplify your hiring process</h1><p style="margin:0 0 32px; font-size:15px; color:#64748b;">We streamline recruitment — from posting jobs to managing applicants — in one platform.</p><p><a href="#" style="display:inline-block; padding:14px 28px; background:#0f172a; color:#fff!important; text-decoration:none; font-weight:700; border-radius:12px;">Get started for free →</a></p>';
$composeGroups = $pdo->query('SELECT g.id, g.name, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as cnt FROM contact_groups g ORDER BY g.name')->fetchAll(PDO::FETCH_ASSOC);
$composeSenders = $pdo->query('SELECT id, name, email FROM sender_accounts WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
// Header and footer start empty; user loads them via Load template
$composeHeader = '';
$composeFooter = '';
$composeFooterBg = '#f1f5f9';
$composeBlockTextColor = '#1e293b';
$composeHeaderLogo = '';
$composeFooterLogo = '';
$composeBodyOutline = '';
$composeHeaderMode = 'text_only';
$composeFooterMode = 'text_only';
?>
<style>
    /* Hide the default title area from index.php */
    main > div > div.mb-4:first-child {
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

    .compose-banner {
        margin-bottom: 2rem;
    }

    /* Re-apply content constraints for the cards below the banner */
    .compose-content-wrapper {
        max-width: 72rem; /* 6xl */
        margin: 0 auto;
        padding: 0 1rem 2rem 1rem;
    }
    @media (max-width: 1023px) {
        .compose-content-wrapper {
            margin-top: 1.5rem;
        }
    }
    @media (min-width: 640px) { .compose-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
    @media (min-width: 1024px) { .compose-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Banner (Desktop) -->
<div class="compose-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Compose</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Create and send email campaigns</p>
        </div>
    </div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#02396E] px-4 py-4 text-white pb-6">
    <h1 class="text-xl font-bold">Compose</h1>
    <p class="text-blue-100/80 text-xs">Create campaigns</p>
</div>

<div class="compose-content-wrapper mt-6 lg:mt-0">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Compose campaign</h2></div>
    <form method="post" action="/compose" id="compose-form">
        <input type="hidden" name="action" value="send">
        <div class="p-6 space-y-4">
            <div class="flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-bold text-slate-700 mb-1">Subject *</label>
                    <input type="text" name="subject" id="compose-subject" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 md:py-2.5 focus:ring-2 focus:ring-[#02396E] text-base" placeholder="Email subject" value="<?= h($_POST['subject'] ?? '') ?>">
                </div>
                <div class="relative" id="load-template-wrap">
                    <button type="button" id="load-template-btn" class="w-full md:w-auto px-4 py-3 md:py-2.5 rounded-xl border-2 border-[#02396E] text-[#02396E] font-bold text-sm hover:bg-[#02396E] hover:text-white touch-manipulation">Load template</button>
                    <div id="load-template-dropdown" class="hidden absolute right-0 top-full mt-1 w-56 rounded-xl border border-slate-200 bg-white shadow-lg py-1 z-20">
                        <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">Saved templates</div>
                        <div id="load-template-list" class="max-h-64 overflow-y-auto"></div>
                        <div id="load-template-empty" class="hidden px-4 py-3 text-sm text-slate-500">No templates yet. Save a design with a template name in Design.</div>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <label class="block text-sm font-bold text-slate-700">Body *</label>
                    <div class="flex rounded-lg border border-slate-200 p-0.5 bg-slate-50">
                        <button type="button" id="body-mode-visual" class="px-3 py-1.5 text-sm font-medium rounded-md bg-[#02396E] text-white">Visual</button>
                        <button type="button" id="body-mode-html" class="px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:bg-slate-200">HTML</button>
                    </div>
                </div>
                <div id="compose-body-wysiwyg-wrap" class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                    <div class="border-b border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">Header (fixed)</div>
                    <div id="compose-header-preview" class="min-h-[40px] p-0 m-0"></div>
                    <div class="border-y border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">Your content</div>
                    <div class="max-w-[600px] w-full mx-auto">
                        <div id="compose-body-visual-wrap">
                            <div id="compose-body-outline-wrap" class="p-2">
                                <div id="compose-body-editor" class="min-h-[280px] text-slate-800" style="min-height:280px"></div>
                            </div>
                        </div>
                        <div id="compose-body-html-wrap" class="hidden p-2 bg-white">
                            <textarea name="body" id="compose-body" rows="12" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 font-mono text-sm" aria-required="true"><?= h($_POST['body'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase">Footer (fixed)</div>
                    <div id="compose-footer-preview" class="min-h-[40px] p-0 m-0"></div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Recipients</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 p-3 rounded-xl border-2 border-slate-200 hover:border-[#02396E] hover:bg-blue-50/30 cursor-pointer">
                        <input type="radio" name="recipient_filter" value="all" checked class="text-[#02396E]">
                        <span>All contacts — <?= $contactsCount ?> total</span>
                    </label>
                    <?php if (!empty($composeGroups)): ?>
                    <div class="p-3 rounded-xl border-2 border-slate-200">
                        <label class="flex items-center gap-2 cursor-pointer mb-2">
                            <input type="radio" name="recipient_filter" value="groups" class="text-[#02396E]">
                            <span class="font-medium">Select groups:</span>
                        </label>
                        <div class="flex flex-wrap gap-3 pl-6">
                            <?php foreach ($composeGroups as $cg): ?>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="recipient_groups[]" value="<?= (int)$cg['id'] ?>" class="rounded border-slate-300 text-[#02396E] recipient-group-cb">
                                <span class="text-sm"><?= h($cg['name']) ?> (<?= (int)$cg['cnt'] ?>)</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1" for="compose-sender">Sender (who sends this campaign)</label>
                <select id="compose-sender" name="compose_sender" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-800 font-bold focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                    <option value="all" class="font-bold">All active senders (rotate)</option>
                    <?php foreach ($composeSenders as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" class="font-bold"><?= h($s['name']) ?> (<?= h($s['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="rotate_senders" id="rotate_senders" value="1" checked class="rounded border-slate-300 text-[#02396E]">
                <label for="rotate_senders" class="text-sm font-medium text-slate-700">Rotate sender accounts</label>
            </div>
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex flex-col sm:flex-row gap-3">
            <button type="submit" class="px-6 py-3 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c] touch-manipulation w-full sm:w-auto">Send campaign</button>
            <a href="<?= url('index') ?>" class="px-6 py-3 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300 text-center touch-manipulation w-full sm:w-auto">Cancel</a>
        </div>
    </form>
</div>
<script>
    var composeDesignHeader = <?= json_encode($composeHeader) ?>;
    var composeDesignFooter = <?= json_encode($composeFooter) ?>;
    var composeDesignFooterBg = <?= json_encode($composeFooterBg) ?>;
    var composeBlockTextColor = <?= json_encode($composeBlockTextColor) ?>;
    var composeHeaderLogo = <?= json_encode($composeHeaderLogo) ?>;
    var composeFooterLogo = <?= json_encode($composeFooterLogo) ?>;
    var composeHeaderMode = <?= json_encode($composeHeaderMode) ?>;
    var composeFooterMode = <?= json_encode($composeFooterMode) ?>;
    var composeBodyOutline = <?= json_encode($composeBodyOutline) ?>;
    var logoBaseUrl = <?= json_encode(trackingBaseUrl()) ?>;
</script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function() {
    var wrap = document.getElementById('compose-body-visual-wrap');
    var htmlWrap = document.getElementById('compose-body-html-wrap');
    var ta = document.getElementById('compose-body');
    var visualBtn = document.getElementById('body-mode-visual');
    var htmlBtn = document.getElementById('body-mode-html');
    var form = ta.closest('form');
    var headerPreview = document.getElementById('compose-header-preview');
    var footerPreview = document.getElementById('compose-footer-preview');
    var isVisualMode = true;
    function escapeHtmlAndBreaks(t) {
        if (t == null || t === '') return '';
        var div = document.createElement('div');
        div.textContent = t;
        return div.innerHTML.replace(/\n/g, '<br>');
    }
    function escapeAttr(s) {
        if (s == null || s === '') return '';
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML.replace(/"/g, '&quot;').replace(/\n/g, ' ');
    }
    function normalizeTemplateHtml(html) {
        if (!html) return '';
        return String(html)
            .replace(/<head\b[^>]*>[\s\S]*?<\/head>/gi, '')
            .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '')
            .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '')
            .replace(/<link\b[^>]*>/gi, '')
            .replace(/<\/?(html|body)\b[^>]*>/gi, '')
            .trim();
    }
    function makeAbsoluteUrls(html, baseUrl) {
        if (!html || !baseUrl) return html || '';
        var base = String(baseUrl).replace(/\/$/, '');
        return html
            .replace(/\b(src|href)=(["\'])(?!\/\/|https?:|data:)([^"\']*)\2/gi, function(m, attr, q, url) {
                var u = url.trim();
                if (!u) return m;
                if (u.indexOf('/') === 0) return attr + '=' + q + base + u + q;
                return attr + '=' + q + base + '/' + u.replace(/^\//, '') + q;
            })
            .replace(/\burl\s*\(\s*(["\']?)(?!\/\/|https?:|data:)([^"\')\s]*)\1\s*\)/gi, function(m, q, url) {
                var u = url.trim();
                if (!u) return m;
                var outQuote = q || '"';
                if (u.indexOf('/') === 0) return 'url(' + outQuote + base + u + outQuote + ')';
                return 'url(' + outQuote + base + '/' + u.replace(/^\//, '') + outQuote + ')';
            });
    }
    function buildBlock(logoUrl, text, bg, textColor, mode) {
        mode = mode || 'text_only';
        if (['logo_only', 'text_only', 'logo_and_text'].indexOf(mode) === -1) mode = 'text_only';
        var showLogo = (mode === 'logo_only' || mode === 'logo_and_text') && logoUrl && String(logoUrl).trim();
        var showText = (mode === 'text_only' || mode === 'logo_and_text') && text && String(text).trim();
        if (!showLogo && !showText) return '';
        var altText = (showText ? String(text).trim().split('\n')[0] : 'Logo').substring(0, 100);
        var inner = '';
        if (showLogo) {
            var logoSrc = (logoUrl.indexOf('http') === 0) ? logoUrl : (logoBaseUrl ? logoBaseUrl + '/' + logoUrl.replace(/^\//, '') : '/' + logoUrl.replace(/^\//, ''));
            logoSrc = String(logoSrc).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;');
            var altEsc = escapeAttr(altText);
            var fallbackEsc = escapeHtmlAndBreaks(altText);
            inner += '<span style="display:inline-block; min-height:40px;"><img src="' + logoSrc + '" alt="' + altEsc + '" style="max-width:180px; height:auto; display:block; margin:0 auto 8px;" onerror="this.style.display=\'none\'; var n=this.nextElementSibling; if(n) n.style.display=\'block\';" /><span style="display:none; font-size:15px; font-weight:600;">' + fallbackEsc + '</span></span>';
        }
        if (showText) inner += '<div style="margin:0; font-size:15px; line-height:1.4;">' + text + '</div>';
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse; margin:0;"><tr><td align="center" style="padding:0; margin:0; text-align:center;">' + inner + '</td></tr></table>';
    }
    function buildBlockWithAbsoluteUrls(logoUrl, text, bg, textColor, mode) {
        var cleanText = normalizeTemplateHtml(text || '');
        var absText = makeAbsoluteUrls(cleanText, typeof logoBaseUrl !== 'undefined' ? logoBaseUrl : '');
        return buildBlock(logoUrl, absText, bg, textColor, mode);
    }
    if (headerPreview && typeof composeDesignFooterBg !== 'undefined') {
        var headerBlock = buildBlockWithAbsoluteUrls(typeof composeHeaderLogo !== 'undefined' ? composeHeaderLogo : '', typeof composeDesignHeader !== 'undefined' ? composeDesignHeader : '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
        if (headerBlock) headerPreview.innerHTML = headerBlock;
        else headerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add header</span>';
    }
    if (footerPreview && typeof composeDesignFooterBg !== 'undefined') {
        var footerBlock = buildBlockWithAbsoluteUrls(typeof composeFooterLogo !== 'undefined' ? composeFooterLogo : '', typeof composeDesignFooter !== 'undefined' ? composeDesignFooter : '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
        if (footerBlock) { footerPreview.innerHTML = footerBlock; }
        else { footerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add footer</span>'; }
    }
    function refreshDesignPreviews() {
        var hBlock = buildBlockWithAbsoluteUrls(typeof composeHeaderLogo !== 'undefined' ? composeHeaderLogo : '', typeof composeDesignHeader !== 'undefined' ? composeDesignHeader : '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
        if (headerPreview) {
            if (hBlock) headerPreview.innerHTML = hBlock;
            else headerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add header</span>';
        }
        var fBlock = buildBlockWithAbsoluteUrls(typeof composeFooterLogo !== 'undefined' ? composeFooterLogo : '', typeof composeDesignFooter !== 'undefined' ? composeDesignFooter : '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
        if (footerPreview) {
            if (fBlock) { footerPreview.innerHTML = fBlock; }
            else { footerPreview.innerHTML = '<span class="text-slate-400 text-sm">Load a template to add footer</span>'; }
        }
    }
    var outlineWrap = document.getElementById('compose-body-outline-wrap');
    var loadTemplateBtn = document.getElementById('load-template-btn');
    var loadTemplateDropdown = document.getElementById('load-template-dropdown');
    var loadTemplateList = document.getElementById('load-template-list');
    var loadTemplateEmpty = document.getElementById('load-template-empty');
    var loadedTemplatesList = [];
    function composeBasePath() {
        var base = (window.location.pathname.indexOf('/compose') !== -1) ? window.location.pathname.replace(/\/compose.*$/, '') : window.location.pathname.replace(/\/[^/]*$/, '');
        if (!base) base = '';
        return base;
    }
    function applyLoadedTemplate(d) {
        if (!d) return;
        composeDesignHeader = d.header_html || '';
        composeDesignFooter = d.footer_html || '';
        composeDesignFooterBg = d.footer_bg_color || '#f1f5f9';
        composeBlockTextColor = d.block_text_color || '#1e293b';
        composeHeaderLogo = d.header_logo_url || '';
        composeFooterLogo = d.footer_logo_url || '';
        composeHeaderMode = d.header_mode || 'text_only';
        composeFooterMode = d.footer_mode || 'text_only';
        composeBodyOutline = '';
        refreshDesignPreviews();
        if (outlineWrap) { outlineWrap.style.border = ''; outlineWrap.style.borderRadius = ''; outlineWrap.style.background = ''; }
    }
    function renderLoadTemplateList() {
        if (!loadTemplateList) return;
        loadTemplateList.innerHTML = '';
        if (loadTemplateEmpty) loadTemplateEmpty.classList.add('hidden');
        if (loadedTemplatesList.length === 0) {
            if (loadTemplateEmpty) loadTemplateEmpty.classList.remove('hidden');
            return;
        }
        loadedTemplatesList.forEach(function(tpl, idx) {
            var row = document.createElement('div');
            row.className = 'flex items-center gap-0.5 px-2 py-1';

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'load-template-option flex-1 text-left px-2 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 rounded-lg hover:bg-slate-50';
            btn.textContent = tpl.name;
            btn.setAttribute('data-idx', String(idx));
            btn.onclick = function() {
                loadTemplateDropdown.classList.add('hidden');
                applyLoadedTemplate(loadedTemplatesList[idx]);
            };

            var editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'shrink-0 p-1.5 text-slate-500 rounded-lg hover:bg-slate-100 hover:text-[#02396E]';
            editBtn.setAttribute('aria-label', 'Edit template ' + tpl.name);
            editBtn.setAttribute('title', 'Edit template');
            editBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
            editBtn.onclick = function(e) {
                e.stopPropagation();
                var current = loadedTemplatesList[idx];
                if (!current || !current.id) return;
                window.location.href = composeBasePath() + '/design?edit_template=' + encodeURIComponent(String(current.id));
            };

            var delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'shrink-0 p-1.5 text-red-600 rounded-lg hover:bg-red-50';
            delBtn.setAttribute('aria-label', 'Delete template ' + tpl.name);
            delBtn.setAttribute('title', 'Delete template');
            delBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
            delBtn.onclick = function(e) {
                e.stopPropagation();
                var current = loadedTemplatesList[idx];
                if (!current || !current.id) return;
                if (!window.confirm('Delete template "' + current.name + '"?')) return;
                delBtn.disabled = true;
                fetch(composeBasePath() + '/api/v1/design/templates/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: current.id })
                }).then(function(r) {
                    return r.json().then(function(data) {
                        if (!r.ok) throw new Error((data && data.error) ? data.error : 'Could not delete template.');
                        return data;
                    });
                }).then(function() {
                    loadedTemplatesList = loadedTemplatesList.filter(function(item) { return item.id !== current.id; });
                    renderLoadTemplateList();
                }).catch(function(err) {
                    alert(err && err.message ? err.message : 'Could not delete template.');
                    delBtn.disabled = false;
                });
            };

            row.appendChild(btn);
            row.appendChild(editBtn);
            row.appendChild(delBtn);
            loadTemplateList.appendChild(row);
        });
    }
    if (loadTemplateBtn && loadTemplateDropdown) {
        loadTemplateBtn.onclick = function(e) {
            e.stopPropagation();
            loadTemplateDropdown.classList.toggle('hidden');
            if (!loadTemplateDropdown.classList.contains('hidden') && loadTemplateList) {
                fetch(composeBasePath() + '/api/v1/design/templates').then(function(r) { return r.json(); }).then(function(data) {
                    loadedTemplatesList = data.templates || [];
                    renderLoadTemplateList();
                }).catch(function() { if (loadTemplateList) loadTemplateList.innerHTML = ''; if (loadTemplateEmpty) loadTemplateEmpty.classList.remove('hidden'); });
            }
        };
        document.addEventListener('click', function() { loadTemplateDropdown.classList.add('hidden'); });
        loadTemplateDropdown.addEventListener('click', function(e) { e.stopPropagation(); });
    }
    if (outlineWrap) { outlineWrap.style.border = ''; outlineWrap.style.borderRadius = ''; outlineWrap.style.background = ''; }
    var quill = new Quill('#compose-body-editor', { theme: 'snow', modules: { toolbar: [[{header:[1,2,3,false]}], ['bold','italic','underline'], [{list:'ordered'},{list:'bullet'}], ['link'], ['clean']] } });
    window.quill = quill;
    if (ta.value) quill.root.innerHTML = ta.value;
    quill.on('text-change', function() { ta.value = quill.root.innerHTML; });
    function setVisual(v) {
        isVisualMode = !!v;
        if (v) {
            quill.root.innerHTML = ta.value;
            if (wrap) wrap.classList.remove('hidden');
            if (htmlWrap) htmlWrap.classList.add('hidden');
            visualBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md bg-[#02396E] text-white';
            htmlBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:bg-slate-200';
        } else {
            ta.value = quill.root.innerHTML;
            if (wrap) wrap.classList.add('hidden');
            if (htmlWrap) htmlWrap.classList.remove('hidden');
            htmlBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md bg-[#02396E] text-white';
            visualBtn.className = 'px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:bg-slate-200';
        }
    }
    visualBtn.onclick = function() { setVisual(true); };
    htmlBtn.onclick = function() { ta.value = quill.root.innerHTML; setVisual(false); };
    setVisual(true);
    form.onsubmit = function() {
        var middle = isVisualMode ? quill.root.innerHTML : ta.value;
        if (!middle || middle.replace(/<[^>]*>|&nbsp;/g, '').trim() === '') {
            alert('Please enter a message in the body.');
            return false;
        }
        var headerBlock = buildBlockWithAbsoluteUrls(composeHeaderLogo || '', composeDesignHeader || '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
        var footerBlock = buildBlockWithAbsoluteUrls(composeFooterLogo || '', composeDesignFooter || '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
        var bodyWrapped = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:600px; margin:0 auto; border-collapse:collapse;"><tr><td style="padding:16px 20px; font-family:Arial, Helvetica, sans-serif;">' + middle + '</td></tr></table>';
        ta.value = headerBlock + bodyWrapped + footerBlock;
        return true;
    };
})();
</script>
