<?php
$templateSubject = 'Simplify your hiring process — Start with us';
$templateBody = '<p style="margin:0 0 16px; font-size:11px; font-weight:700; color:#ff8904;">Hire smarter. Hire faster.</p><h1 style="margin:0 0 20px; font-size:28px; font-weight:800; color:#0f172a;">Simplify your hiring process</h1><p style="margin:0 0 32px; font-size:15px; color:#64748b;">We streamline recruitment — from posting jobs to managing applicants — in one platform.</p><p><a href="#" style="display:inline-block; padding:14px 28px; background:#0f172a; color:#fff!important; text-decoration:none; font-weight:700; border-radius:12px;">Get started for free →</a></p>';
$composeGroupsStmt = $pdo->prepare('SELECT g.id, g.name, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as cnt FROM contact_groups g WHERE g.user_id = ? ORDER BY g.name');
$composeGroupsStmt->execute([$userId]);
$composeGroups = $composeGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
$composeSenders = $pdo->query('SELECT id, name, email FROM sender_accounts WHERE is_active=1 ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
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
    main > div > div.mb-4:first-child { display: none; }
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem;
        margin-left: auto;
        margin-right: auto;
        margin-top: 1.5rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }

    .compose-banner { margin-bottom: 2rem; }

    .compose-content-wrapper {
        max-width: 72rem;
        margin: 0 auto;
        padding: 0 1rem 2rem 1rem;
    }
    @media (max-width: 1023px) { .compose-content-wrapper { margin-top: 1.5rem; } }
    @media (min-width: 640px) { .compose-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
    @media (min-width: 1024px) { .compose-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Banner (Desktop) - Restored Dark Design -->
<div class="compose-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Compose</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Create and send email campaigns</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Sub-Navigation Bar - Floating Segmented Control -->
<div class="sticky top-4 z-20 flex justify-start mb-8 px-4 sm:px-24">
    <div class="inline-flex p-1.5 bg-white/90 backdrop-blur-xl rounded-2xl border border-slate-200 shadow-2xl">
        <a href="<?= url('compose') ?>" class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?= currentPage() === 'compose' ? 'bg-[#f54a00] text-white shadow-lg' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Compose
        </a>
        <a href="<?= url('senders') ?>" class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?= currentPage() === 'senders' ? 'bg-[#f54a00] text-white shadow-lg' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Senders
        </a>
        <a href="<?= url('contacts') ?>" class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all <?= currentPage() === 'contacts' ? 'bg-[#f54a00] text-white shadow-lg' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Contacts
        </a>
    </div>
</div>

<div class="compose-content-wrapper mt-6 lg:mt-0">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <!-- Dashboard-Style Section Header -->
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h2 class="text-lg md:text-2xl font-bold text-white">Compose Campaign</h2>
            <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                <button type="button" id="load-template-btn" class="flex-1 sm:flex-none inline-flex items-center px-4 py-2 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-[#f54a00] transition-colors justify-center shadow-lg">Load Template</button>
            </div>
        </div>

        <div class="relative" id="load-template-wrap">
            <div id="load-template-dropdown" class="hidden absolute right-8 top-2 w-64 rounded-xl border border-slate-200 bg-white shadow-2xl py-1 z-50">
                <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase border-b border-slate-100">Saved templates</div>
                <div id="load-template-list" class="max-h-64 overflow-y-auto"></div>
                <div id="load-template-empty" class="hidden px-4 py-3 text-sm text-slate-500">No templates yet.</div>
            </div>
        </div>

        <form method="post" action="/compose" id="compose-form">
            <input type="hidden" name="action" value="send">
            <div class="p-6 md:p-8 space-y-6">
                <!-- Subject -->
                <div>
                    <label class="block text-lg font-bold text-slate-800 mb-1">Campaign Subject <span class="text-red-600">*</span></label>
                    <input type="text" name="subject" id="compose-subject" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 font-bold focus:ring-2 focus:ring-[#02396E] outline-none transition-all" placeholder="Email subject" value="<?= h($_POST['subject'] ?? '') ?>">
                </div>

                <!-- Body -->
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <label class="block text-lg font-bold text-slate-800">Message Content <span class="text-red-600">*</span></label>
                        <div class="flex gap-1 rounded-lg border border-slate-200 p-0.5 bg-slate-50 -mt-1.5">
                            <button type="button" id="body-mode-visual" class="px-3 py-1.5 text-xs font-bold rounded-md bg-[#02396E] text-white">Visual</button>
                            <button type="button" id="body-mode-html" class="px-3 py-1.5 text-xs font-bold rounded-md text-slate-600 hover:bg-slate-200">HTML</button>
                        </div>
                    </div>
                    <div id="compose-body-wysiwyg-wrap" class="rounded-2xl border-2 border-slate-100 overflow-hidden bg-white shadow-sm">
                        <div class="bg-slate-50 px-6 py-3 text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] border-b border-slate-100">Header Preview</div>
                        <div id="compose-header-preview" class="min-h-[40px]"></div>
                        
                        <div class="bg-slate-50 px-6 py-3 text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] border-y border-slate-100">Body Editor</div>
                        <div class="max-w-[600px] w-full mx-auto">
                            <div id="compose-body-visual-wrap">
                                <div id="compose-body-outline-wrap" class="p-4">
                                    <div class="flex justify-end mb-2">
                                        <button type="button" id="ai-generate-btn" class="px-3 py-1.5 text-xs font-bold rounded-lg border-2 border-[#02396E] text-[#02396E] hover:bg-[#02396E] hover:text-white transition-colors">AI generate</button>
                                    </div>
                                    <div id="compose-body-editor" class="min-h-[350px] text-slate-800" style="min-height:350px"></div>
                                </div>
                            </div>
                            <div id="compose-body-html-wrap" class="hidden p-4 bg-white">
                                <textarea name="body" id="compose-body" rows="15" class="w-full rounded-xl border border-slate-200 px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-[#02396E] outline-none" placeholder="Compose your marketing masterpiece..."><?= h($_POST['body'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-3 text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] border-t border-slate-100">Footer Preview</div>
                        <div id="compose-footer-preview" class="min-h-[40px]"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-4">
                    <!-- Recipients -->
                    <div>
                        <label class="block text-lg font-bold text-slate-800 mb-3">Recipients</label>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 hover:border-[#02396E] hover:bg-blue-50/30 cursor-pointer transition-all">
                                <input type="radio" name="recipient_filter" value="all" checked class="w-5 h-5 text-[#02396E] border-slate-300 focus:ring-[#02396E]">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900 text-sm">All Contacts</span>
                                    <span class="text-xs text-slate-500"><?= $contactsCount ?> total subscribers</span>
                                </div>
                            </label>
                            <?php if (!empty($composeGroups)): ?>
                            <div class="p-4 rounded-xl border-2 border-slate-100">
                                <label class="flex items-center gap-3 cursor-pointer mb-4">
                                    <input type="radio" name="recipient_filter" value="groups" class="w-5 h-5 text-[#02396E] border-slate-300 focus:ring-[#02396E]">
                                    <span class="font-bold text-slate-900 text-sm">Target Specific Groups:</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-8">
                                    <?php foreach ($composeGroups as $cg): ?>
                                    <label class="inline-flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="recipient_groups[]" value="<?= (int)$cg['id'] ?>" class="rounded border-slate-300 text-[#02396E] focus:ring-[#02396E] recipient-group-cb">
                                        <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors"><?= h($cg['name']) ?> <span class="text-xs text-slate-400">(<?= (int)$cg['cnt'] ?>)</span></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sending Configuration -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-lg font-bold text-slate-800 mb-2" for="compose-sender">Select Sender Identity</label>
                            <select id="compose-sender" name="compose_sender" class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 text-slate-900 font-bold focus:ring-2 focus:ring-[#02396E] outline-none transition-all bg-white">
                                <option value="all">All Active Senders (Recommended: Rotating)</option>
                                <?php foreach ($composeSenders as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= h($s['name']) ?> (<?= h($s['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <input type="checkbox" name="rotate_senders" id="rotate_senders" value="1" checked class="w-5 h-5 rounded border-slate-300 text-[#02396E] focus:ring-[#02396E]">
                            <label for="rotate_senders" class="text-sm font-bold text-slate-700 cursor-pointer">Rotate sender accounts automatically</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Action Bar -->
            <div class="bg-slate-50 px-6 py-4 flex items-center justify-start gap-3 border-t border-slate-100">
                <button type="submit" class="px-10 py-3 bg-[#ff8904] text-white font-black rounded-xl hover:bg-[#f54a00] transition-all shadow-lg uppercase tracking-widest text-sm">Send Campaign</button>
                <a href="<?= url('index') ?>" class="px-6 py-3 bg-slate-200 text-slate-700 text-sm font-bold rounded-xl hover:bg-slate-300 transition-colors">Cancel</a>
            </div>
        </form>

        <!-- AI Generate modal -->
        <div id="ai-generate-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 hidden">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900">AI generate</h3>
                    <p class="text-sm text-slate-500 mt-1">Describe the email content you want (e.g. &ldquo;professional welcome email for new subscribers&rdquo;).</p>
                </div>
                <div class="p-6 flex-1 overflow-auto">
                    <textarea id="ai-generate-prompt" rows="4" class="w-full rounded-xl border-2 border-slate-200 px-4 py-3 text-slate-800 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] outline-none resize-none" placeholder="e.g. Professional welcome email for new subscribers..."></textarea>
                    <p id="ai-generate-status" class="mt-2 text-sm text-slate-500 hidden"></p>
                </div>
                <div class="p-6 border-t border-slate-100 flex gap-3 justify-end">
                    <button type="button" id="ai-generate-cancel" class="px-4 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300">Cancel</button>
                    <button type="button" id="ai-generate-submit" class="px-4 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Generate</button>
                </div>
            </div>
        </div>
    </div>
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
    var form = document.getElementById('compose-form');
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
            inner += '<span style="display:inline-block; min-height:40px;"><img src="' + logoSrc + '" alt="' + altEsc + '" style="max-width:180px; height:auto; display:block; margin:0 auto 8px;" onerror="this.style.display=\'none\'; var n=this.nextElementSibling; if(n) n.style.display=\'block\';" /><span style="display:none; font-size:15px; font-weight:600; color:' + (textColor || '#1e293b') + ';">' + fallbackEsc + '</span></span>';
        }
        if (showText) inner += '<div style="margin:0; font-size:15px; line-height:1.4; color:' + (textColor || '#1e293b') + ';">' + text + '</div>';
        return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; border-collapse:collapse; margin:0; background-color:' + (bg || 'transparent') + ';"><tr><td align="center" style="padding:24px 20px; margin:0; text-align:center;">' + inner + '</td></tr></table>';
    }
    function buildBlockWithAbsoluteUrls(logoUrl, text, bg, textColor, mode) {
        var cleanText = normalizeTemplateHtml(text || '');
        var absText = makeAbsoluteUrls(cleanText, typeof logoBaseUrl !== 'undefined' ? logoBaseUrl : '');
        return buildBlock(logoUrl, absText, bg, textColor, mode);
    }
    function refreshDesignPreviews() {
        var previewBg = '#f1f5f9'; /* light gray so preview is not so white */
        var hBlock = buildBlockWithAbsoluteUrls(typeof composeHeaderLogo !== 'undefined' ? composeHeaderLogo : '', typeof composeDesignHeader !== 'undefined' ? composeDesignHeader : '', previewBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
        if (headerPreview) {
            headerPreview.innerHTML = hBlock || '<div class="p-4 text-center text-slate-300 text-xs italic">No header template loaded</div>';
        }
        var fBlock = buildBlockWithAbsoluteUrls(typeof composeFooterLogo !== 'undefined' ? composeFooterLogo : '', typeof composeDesignFooter !== 'undefined' ? composeDesignFooter : '', previewBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
        if (footerPreview) {
            footerPreview.innerHTML = fBlock || '<div class="p-4 text-center text-slate-300 text-xs italic">No footer template loaded</div>';
        }
        if (outlineWrap) {
            if (typeof composeBodyOutline !== 'undefined' && composeBodyOutline) {
                outlineWrap.style.border = '2px solid ' + composeBodyOutline;
                outlineWrap.style.borderRadius = '12px';
            } else {
                outlineWrap.style.border = '';
                outlineWrap.style.borderRadius = '';
            }
        }
    }
    var outlineWrap = document.getElementById('compose-body-outline-wrap');
    refreshDesignPreviews();

    var loadTemplateBtn = document.getElementById('load-template-btn');
    var loadTemplateDropdown = document.getElementById('load-template-dropdown');
    var loadTemplateList = document.getElementById('load-template-list');
    var loadTemplateEmpty = document.getElementById('load-template-empty');
    var loadedTemplatesList = [];

    function composeBasePath() {
        var base = (window.location.pathname.indexOf('/compose') !== -1) ? window.location.pathname.replace(/\/compose.*$/, '') : window.location.pathname.replace(/\/[^/]*$/, '');
        return base || '';
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
        composeBodyOutline = d.body_outline_color || '';
        refreshDesignPreviews();
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
            btn.className = 'load-template-option flex-1 text-left px-3 py-2 text-sm font-bold text-slate-700 rounded-lg hover:bg-blue-50 transition-colors';
            btn.textContent = tpl.name;
            btn.onclick = function() {
                loadTemplateDropdown.classList.add('hidden');
                applyLoadedTemplate(loadedTemplatesList[idx]);
            };
            row.appendChild(btn);
            loadTemplateList.appendChild(row);
        });
    }
    if (loadTemplateBtn) {
        loadTemplateBtn.onclick = function(e) {
            e.stopPropagation();
            loadTemplateDropdown.classList.toggle('hidden');
            if (!loadTemplateDropdown.classList.contains('hidden')) {
                fetch(composeBasePath() + '/api/v1/design/templates').then(r => r.json()).then(data => {
                    loadedTemplatesList = data.templates || [];
                    renderLoadTemplateList();
                });
            }
        };
        document.addEventListener('click', () => loadTemplateDropdown.classList.add('hidden'));
    }

    var quill = new Quill('#compose-body-editor', { 
        theme: 'snow', 
        placeholder: 'Compose your marketing masterpiece...',
        modules: { toolbar: [[{header:[1,2,3,false]}], ['bold','italic','underline'], [{list:'ordered'},{list:'bullet'}], ['link'], ['clean']] } 
    });
    if (ta.value && ta.value.indexOf("<h1>Hello there!</h1>") === -1 && ta.value !== '<p><br></p>') {
        quill.root.innerHTML = ta.value;
    }
    quill.on('text-change', () => ta.value = quill.root.innerHTML);

    function setVisual(v) {
        isVisualMode = !!v;
        if (v) {
            quill.root.innerHTML = ta.value;
            wrap.classList.remove('hidden');
            htmlWrap.classList.add('hidden');
            visualBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-md bg-[#02396E] text-white';
            htmlBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-md text-slate-600 hover:bg-slate-200';
        } else {
            ta.value = quill.root.innerHTML;
            wrap.classList.add('hidden');
            htmlWrap.classList.remove('hidden');
            htmlBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-md bg-[#02396E] text-white';
            visualBtn.className = 'px-3 py-1.5 text-xs font-bold rounded-md text-slate-600 hover:bg-slate-200';
        }
    }
    visualBtn.onclick = () => setVisual(true);
    htmlBtn.onclick = () => setVisual(false);

    form.onsubmit = function() {
        var middle = isVisualMode ? quill.root.innerHTML : ta.value;
        if (!middle || middle.replace(/<[^>]*>|&nbsp;/g, '').trim() === '') {
            alert('Please enter a message.');
            return false;
        }
        var headerBlock = buildBlockWithAbsoluteUrls(composeHeaderLogo || '', composeDesignHeader || '', composeDesignFooterBg, composeBlockTextColor, typeof composeHeaderMode !== 'undefined' ? composeHeaderMode : 'text_only');
        var footerBlock = buildBlockWithAbsoluteUrls(composeFooterLogo || '', composeDesignFooter || '', composeDesignFooterBg, composeBlockTextColor, typeof composeFooterMode !== 'undefined' ? composeFooterMode : 'text_only');
        var bodyWrapped = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:600px; margin:0 auto; border-collapse:collapse;"><tr><td style="padding:16px 20px; font-family:Arial, Helvetica, sans-serif;">' + middle + '</td></tr></table>';
        ta.value = headerBlock + bodyWrapped + footerBlock;
        return true;
    };

    var aiModal = document.getElementById('ai-generate-modal');
    var aiPrompt = document.getElementById('ai-generate-prompt');
    var aiStatus = document.getElementById('ai-generate-status');
    var aiSubmit = document.getElementById('ai-generate-submit');
    var aiCancel = document.getElementById('ai-generate-cancel');
    if (document.getElementById('ai-generate-btn')) {
        document.getElementById('ai-generate-btn').onclick = function() {
            aiPrompt.value = '';
            aiStatus.classList.add('hidden');
            aiStatus.textContent = '';
            aiModal.classList.remove('hidden');
            aiPrompt.focus();
        };
    }
    if (aiCancel) {
        aiCancel.onclick = function() { aiModal.classList.add('hidden'); };
    }
    aiModal.onclick = function(e) {
        if (e.target === aiModal) aiModal.classList.add('hidden');
    };
    if (aiSubmit) {
        aiSubmit.onclick = function() {
            var promptText = (aiPrompt.value || '').trim();
            if (!promptText) {
                aiStatus.classList.remove('hidden');
                aiStatus.textContent = 'Please enter a prompt.';
                aiStatus.className = 'mt-2 text-sm text-amber-600';
                return;
            }
            aiStatus.classList.remove('hidden');
            aiStatus.textContent = 'Generating...';
            aiStatus.className = 'mt-2 text-sm text-slate-500';
            aiSubmit.disabled = true;
            fetch('/api/v1/compose/ai-generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: promptText })
            }).then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); }).then(function(result) {
                aiSubmit.disabled = false;
                if (result.ok && result.data.content) {
                    quill.root.innerHTML = result.data.content;
                    ta.value = quill.root.innerHTML;
                    aiModal.classList.add('hidden');
                } else {
                    aiStatus.textContent = result.data.error || 'Something went wrong.';
                    aiStatus.className = 'mt-2 text-sm text-red-600';
                }
            }).catch(function() {
                aiSubmit.disabled = false;
                aiStatus.textContent = 'Network error. Try again.';
                aiStatus.className = 'mt-2 text-sm text-red-600';
            });
        };
    }
})();
</script>
