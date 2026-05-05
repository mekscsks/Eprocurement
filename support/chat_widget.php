<?php
/**
 * support/chat_widget.php
 * Drop-in floating chat widget for user & admin roles.
 * Include this file on ANY page:
 *   <?php include __DIR__ . '/../support/chat_widget.php'; ?>
 *
 * Requires: active session with $_SESSION['auth_user']['account_id'] & ['role']
 * Superadmin is skipped (they use superadmin_support.php instead).
 */

if (session_status() === PHP_SESSION_NONE) session_start();

$_cw_role = $_SESSION['auth_user']['role'] ?? '';
if (empty($_SESSION['auth_user']['account_id']) || $_cw_role === 'superadmin') return;

// Resolve path to support/ endpoints relative to this file
$_cw_base = rtrim(
    str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])),
    '/'
);
// Always point to /support/ regardless of which page includes this
$_cw_api = '/support';
?>

<!-- ═══════════════════════════════════════════════════════════ CHAT WIDGET -->
<style>
/* ── Floating button ── */
#cwBtn{
    position:fixed;bottom:28px;right:28px;z-index:9999;
    width:56px;height:56px;border-radius:50%;
    background:linear-gradient(135deg,#0D2B55,#1A4080);
    color:#fff;border:none;cursor:pointer;
    box-shadow:0 4px 18px rgba(13,43,85,.45);
    display:flex;align-items:center;justify-content:center;
    font-size:1.4rem;transition:transform .2s,box-shadow .2s;
}
#cwBtn:hover{transform:scale(1.08);box-shadow:0 6px 24px rgba(13,43,85,.55);}
#cwUnread{
    position:absolute;top:-4px;right:-4px;
    background:#ef4444;color:#fff;
    font-size:.6rem;font-weight:700;
    border-radius:999px;padding:.15rem .42rem;
    min-width:18px;text-align:center;
    display:none;border:2px solid #fff;
}

/* ── Chat panel ── */
#cwPanel{
    position:fixed;bottom:96px;right:28px;z-index:9998;
    width:360px;height:520px;
    background:#fff;border-radius:20px;
    box-shadow:0 8px 40px rgba(13,43,85,.22);
    display:flex;flex-direction:column;overflow:hidden;
    transform:scale(.85) translateY(20px);
    transform-origin:bottom right;
    opacity:0;pointer-events:none;
    transition:transform .25s cubic-bezier(.34,1.56,.64,1), opacity .2s;
}
#cwPanel.open{
    transform:scale(1) translateY(0);
    opacity:1;pointer-events:all;
}

/* ── Header ── */
.cw-head{
    background:linear-gradient(135deg,#0D2B55,#1A4080);
    padding:.85rem 1rem;
    display:flex;align-items:center;gap:.75rem;
    border-bottom:3px solid #F5A623;flex-shrink:0;
}
.cw-head-avatar{
    width:36px;height:36px;border-radius:50%;
    background:#F5A623;color:#0D2B55;
    display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:.9rem;flex-shrink:0;
}
.cw-head-info{flex:1;}
.cw-head-info strong{color:#fff;font-size:.9rem;display:block;}
.cw-head-info small{color:rgba(255,255,255,.5);font-size:.72rem;}
.cw-head-close{
    background:none;border:none;color:rgba(255,255,255,.6);
    font-size:1.1rem;cursor:pointer;padding:.2rem .4rem;border-radius:6px;
    transition:color .15s;
}
.cw-head-close:hover{color:#fff;}
#cwStatusDot{
    width:9px;height:9px;border-radius:50%;
    background:#22c55e;display:inline-block;margin-right:.3rem;
    box-shadow:0 0 0 2px rgba(34,197,94,.25);
}
#cwStatusDot.closed{background:#ef4444;box-shadow:0 0 0 2px rgba(239,68,68,.25);}

/* ── Body ── */
.cw-body{
    flex:1;overflow-y:auto;padding:1rem;
    display:flex;flex-direction:column;gap:.55rem;
    background:#f8fafd;
}
.cw-body::-webkit-scrollbar{width:4px;}
.cw-body::-webkit-scrollbar-thumb{background:#d0d9ea;border-radius:4px;}

/* ── Bubbles ── */
.cw-bwrap{display:flex;flex-direction:column;max-width:80%;}
.cw-bwrap.me{align-self:flex-end;align-items:flex-end;}
.cw-bwrap.them{align-self:flex-start;align-items:flex-start;}
.cw-bubble{
    padding:.55rem .85rem;border-radius:16px;
    font-size:.84rem;line-height:1.5;word-break:break-word;
}
.cw-bubble.me{background:#1A4080;color:#fff;border-bottom-right-radius:3px;}
.cw-bubble.them{background:#e9eef6;color:#1a2b40;border-bottom-left-radius:3px;}
.cw-meta{font-size:.67rem;color:#a0aec0;margin-top:.18rem;}
.cw-bubble img{max-width:160px;border-radius:8px;margin-top:.35rem;display:block;}
.cw-bubble a{color:inherit;text-decoration:underline;}

/* ── Typing ── */
#cwTyping{font-size:.74rem;color:#a0aec0;min-height:1.1rem;padding:0 .2rem;}

/* ── Footer ── */
.cw-foot{padding:.7rem .85rem;border-top:1px solid #e8eef6;background:#fff;flex-shrink:0;}
.cw-input-row{display:flex;gap:.4rem;align-items:flex-end;}
.cw-input-row textarea{
    flex:1;resize:none;border-radius:12px;
    border:1px solid #d6e1ef;padding:.5rem .75rem;
    font-size:.84rem;font-family:inherit;outline:none;
    transition:border .2s;max-height:100px;overflow-y:auto;
}
.cw-input-row textarea:focus{border-color:#1A4080;}
.cw-input-row textarea:disabled{background:#f4f7fc;}
.cw-btn-send{
    background:#1A4080;color:#fff;border:none;
    border-radius:12px;padding:.5rem .9rem;
    cursor:pointer;transition:background .2s;flex-shrink:0;
}
.cw-btn-send:hover{background:#0D2B55;}
.cw-btn-send:disabled{opacity:.45;cursor:not-allowed;}
.cw-attach-lbl{
    cursor:pointer;color:#a0aec0;font-size:1rem;
    padding:.45rem;flex-shrink:0;transition:color .15s;
}
.cw-attach-lbl:hover{color:#1A4080;}
#cwAttachPreview{font-size:.72rem;color:#718096;margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

/* ── Closed notice ── */
#cwClosedNotice{
    display:none;text-align:center;
    font-size:.78rem;color:#b91c1c;
    background:#fee2e2;border-radius:8px;
    padding:.4rem .75rem;margin:.5rem 0 0;
}

/* ── Empty state ── */
.cw-empty{
    flex:1;display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    color:#b0bec5;gap:.4rem;font-size:.82rem;
    text-align:center;padding:1rem;
}
.cw-empty i{font-size:2.2rem;}

/* ── Mobile ── */
@media(max-width:480px){
    #cwPanel{width:calc(100vw - 24px);right:12px;bottom:88px;height:70vh;}
    #cwBtn{right:16px;bottom:16px;}
}
</style>

<!-- Toggle button -->
<button id="cwBtn" title="Technical Support" onclick="cwToggle()">
    <i class="bi bi-headset"></i>
    <span id="cwUnread"></span>
</button>

<!-- Chat panel -->
<div id="cwPanel">
    <div class="cw-head">
        <div class="cw-head-avatar">S</div>
        <div class="cw-head-info">
            <strong>Technical Support</strong>
            <small><span id="cwStatusDot"></span><span id="cwStatusTxt">Online</span></small>
        </div>
        <button class="cw-head-close" onclick="cwToggle()" title="Minimize"><i class="bi bi-dash-lg"></i></button>
    </div>

    <div class="cw-body" id="cwBody">
        <div class="cw-empty" id="cwEmpty">
            <i class="bi bi-chat-dots"></i>
            <span>Loading…</span>
        </div>
    </div>

    <div class="cw-foot">
        <div id="cwTyping"></div>
        <form id="cwForm" enctype="multipart/form-data" onsubmit="cwSend(event)">
            <input type="hidden" id="cwThreadId" name="thread_id" value="">
            <div class="cw-input-row">
                <label class="cw-attach-lbl" title="Attach file (jpg/png/pdf, max 5MB)">
                    <i class="bi bi-paperclip"></i>
                    <input type="file" name="attachment" id="cwAttach" accept=".jpg,.jpeg,.png,.pdf" hidden onchange="cwPreviewFile()">
                </label>
                <textarea name="message" id="cwMsg" rows="1"
                    placeholder="Type a message…" maxlength="2000"
                    onkeydown="cwEnterSend(event)" oninput="cwAutoResize(this);cwOnType()"></textarea>
                <button type="submit" class="cw-btn-send" id="cwSendBtn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
            <div id="cwAttachPreview"></div>
            <div id="cwClosedNotice">This thread is closed. Superadmin will reopen it.</div>
        </form>
    </div>
</div>

<script>
(function(){
const API    = <?= json_encode($_cw_api) ?>;
const ME_ID  = <?= (int)($_SESSION['auth_user']['account_id'] ?? 0) ?>;
let threadId = 0, lastId = 0, isOpen = false, threadStatus = 'open';
let typingTimer, pollTimer, typingPollTimer;

// ── Toggle ────────────────────────────────────────────────────────────────────
window.cwToggle = function() {
    isOpen = !isOpen;
    document.getElementById('cwPanel').classList.toggle('open', isOpen);
    if (isOpen) {
        clearUnread();
        cwScrollBottom();
    }
};

// ── Boot ──────────────────────────────────────────────────────────────────────
async function boot() {
    const r = await apiFetch(API + '/get_thread.php').catch(()=>null);
    if (r?.thread_id) {
        threadId = r.thread_id;
        document.getElementById('cwThreadId').value = threadId;
    }
    await cwPoll();
    pollTimer       = setInterval(cwPoll, 2500);
    typingPollTimer = setInterval(cwPollTyping, 2000);
    setInterval(cwPollUnread, 4000); // unread badge even when closed
}

// ── Poll messages ─────────────────────────────────────────────────────────────
async function cwPoll() {
    if (!threadId) return;
    const r = await apiFetch(`${API}/fetch_messages.php?thread_id=${threadId}&after_id=${lastId}`).catch(()=>null);
    if (!r) return;

    threadStatus = r.thread_status || 'open';
    updateStatusUI();

    if (r.messages?.length) {
        const empty = document.getElementById('cwEmpty');
        if (empty) empty.remove();
        r.messages.forEach(appendBubble);
        lastId = r.messages.at(-1).id;
        if (isOpen) { cwScrollBottom(); markRead(); }
        else         { bumpUnread(r.messages.filter(m => parseInt(m.sender_id) !== ME_ID).length); }
    } else if (lastId === 0) {
        const empty = document.getElementById('cwEmpty');
        if (empty) { empty.querySelector('span').textContent = 'No messages yet. Say hello! 👋'; }
    }
}

// ── Unread badge (poll even when closed) ─────────────────────────────────────
let unreadCount = 0;
function bumpUnread(n) {
    if (!n) return;
    unreadCount += n;
    const el = document.getElementById('cwUnread');
    el.textContent = unreadCount > 99 ? '99+' : unreadCount;
    el.style.display = 'flex';
    el.style.alignItems = 'center';
    el.style.justifyContent = 'center';
}
function clearUnread() {
    unreadCount = 0;
    document.getElementById('cwUnread').style.display = 'none';
    markRead();
}
async function cwPollUnread() {
    if (isOpen || !threadId) return;
    // Count unread from server
    const r = await apiFetch(`${API}/fetch_messages.php?thread_id=${threadId}&after_id=${lastId}`).catch(()=>null);
    if (r?.messages?.length) {
        const newMsgs = r.messages.filter(m => parseInt(m.sender_id) !== ME_ID);
        if (newMsgs.length) bumpUnread(newMsgs.length);
    }
}

// ── Append bubble ─────────────────────────────────────────────────────────────
function appendBubble(m) {
    const isMe = parseInt(m.sender_id) === ME_ID;
    const wrap = document.createElement('div');
    wrap.className = 'cw-bwrap ' + (isMe ? 'me' : 'them');
    wrap.dataset.id = m.id;

    let html = m.message ? esc(m.message) : '';
    if (m.attachment) {
        const ext = m.attachment.split('.').pop().toLowerCase();
        const url = `${API}/uploads/${m.attachment}`;
        html += ['jpg','jpeg','png'].includes(ext)
            ? `<br><a href="${url}" target="_blank"><img src="${url}"></a>`
            : `<br><a href="${url}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> ${esc(m.attachment)}</a>`;
    }

    wrap.innerHTML = `
        <div class="cw-bubble ${isMe?'me':'them'}">${html}</div>
        <div class="cw-meta">${isMe?'You':esc(m.sender_name)} · ${fmtTime(m.created_at)}</div>`;
    document.getElementById('cwBody').appendChild(wrap);
}

// ── Send ──────────────────────────────────────────────────────────────────────
window.cwSend = async function(e) {
    e.preventDefault();
    if (threadStatus === 'closed') return;
    const msg  = document.getElementById('cwMsg').value.trim();
    const file = document.getElementById('cwAttach').files[0];
    if (!msg && !file) return;

    const fd = new FormData(document.getElementById('cwForm'));
    fd.set('thread_id', threadId);

    document.getElementById('cwSendBtn').disabled = true;
    const r = await apiFetch(API + '/send_message.php', {method:'POST', body:fd}).catch(()=>null);
    document.getElementById('cwSendBtn').disabled = false;

    if (r?.success) {
        if (!threadId && r.thread_id) {
            threadId = r.thread_id;
            document.getElementById('cwThreadId').value = threadId;
        }
        document.getElementById('cwMsg').value = '';
        document.getElementById('cwMsg').style.height = 'auto';
        document.getElementById('cwAttach').value = '';
        document.getElementById('cwAttachPreview').textContent = '';
        sendTyping(0);
        await cwPoll();
    } else {
        alert(r?.error || 'Failed to send');
    }
};

// ── Typing ────────────────────────────────────────────────────────────────────
window.cwOnType = function() {
    clearTimeout(typingTimer);
    sendTyping(1);
    typingTimer = setTimeout(()=>sendTyping(0), 3000);
};
window.cwEnterSend = function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('cwForm').dispatchEvent(new Event('submit'));
    }
};
window.cwAutoResize = function(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
};
function sendTyping(val) {
    if (!threadId) return;
    const fd = new FormData();
    fd.append('thread_id', threadId);
    fd.append('is_typing', val);
    fetch(API + '/typing.php', {method:'POST', body:fd});
}
async function cwPollTyping() {
    if (!threadId || !isOpen) return;
    const r = await apiFetch(`${API}/typing.php?thread_id=${threadId}`).catch(()=>null);
    document.getElementById('cwTyping').textContent =
        r?.typing?.length ? r.typing.join(', ') + ' is typing…' : '';
}

// ── Status UI ─────────────────────────────────────────────────────────────────
function updateStatusUI() {
    const isClosed = threadStatus === 'closed';
    const dot      = document.getElementById('cwStatusDot');
    const txt      = document.getElementById('cwStatusTxt');
    const notice   = document.getElementById('cwClosedNotice');
    const msg      = document.getElementById('cwMsg');
    const btn      = document.getElementById('cwSendBtn');

    dot.className  = isClosed ? 'closed' : '';
    txt.textContent = isClosed ? 'Closed' : 'Online';
    notice.style.display = isClosed ? 'block' : 'none';
    msg.disabled   = isClosed;
    btn.disabled   = isClosed;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
window.cwPreviewFile = function() {
    document.getElementById('cwAttachPreview').textContent =
        document.getElementById('cwAttach').files[0]?.name || '';
};
function markRead() {
    if (!threadId) return;
    const fd = new FormData();
    fd.append('thread_id', threadId);
    fetch(API + '/mark_as_read.php', {method:'POST', body:fd});
}
function cwScrollBottom() {
    const b = document.getElementById('cwBody');
    requestAnimationFrame(()=>{ b.scrollTop = b.scrollHeight; });
}
function esc(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}
function fmtTime(ts) {
    const d = new Date(ts.replace(' ','T'));
    return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
}
async function apiFetch(url, opts={}) {
    const res = await fetch(url, opts);
    return res.json();
}

boot();
})();
</script>
<!-- ══════════════════════════════════════════════════════ END CHAT WIDGET -->
