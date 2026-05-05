<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/localdb.php';
include 'includes/auth.php';
?>
<?php include 'includes/header.php' ?>
<?php if (isset($_SESSION['alert'])):
    $alert = $_SESSION['alert'];
    $icon  = $alert['type'] ?? 'info';
    $title = $alert['title'] ?? ucfirst($icon);
    $text  = $alert['msg'] ?? '';
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: <?= json_encode($icon) ?>,
        title: <?= json_encode($title) ?>,
        text: <?= json_encode($text) ?>,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
});
</script>
<?php unset($_SESSION['alert']); endif; ?>
<link rel="stylesheet" href="assets/css/css.css">

<div class="db-layout">
    <?php include 'includes/sidebar.php'; ?>

    <main class="db-main">
        <div class="ppmp-page-wrap">
        <div class="db-card">
            <div class="db-card-head">
                <div>
                    <div class="db-card-title">Submit PPMP</div>
                    <div class="db-card-subtitle">Project Procurement Management Plan</div>
                </div>
                <div class="db-card-badge"><i class="bi bi-shield-check"></i> RA 12009</div>
            </div>

            <div class="db-card-body">
                <form method="POST" action="code.php" enctype="multipart/form-data">

                    <div class="db-row">
                        <div class="db-field">
                            <label>Email Address</label>
                            <div class="db-field-inner">
                                <i class="bi bi-envelope db-ficon"></i>
                                <input type="email" name="email"
                                       value="<?= htmlspecialchars($_SESSION['auth_user']['email'] ?? '') ?>"
                                       required>
                            </div>
                        </div>

                        <div class="db-field">
                            <label>PPMP Type</label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-tag db-ficon"></i>
                                <select name="ppmp_type">
                                    <option value="Indicative">Indicative</option>
                                    <option value="Final">Final</option>
                                    <option value="Supplemental">Supplemental</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Office <span style="color:var(--red)">*</span></label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-building db-ficon"></i>
                                <select name="office" id="officeSelect" required>
                                    <option value=""> Select Office </option>
                                    <option value="Office of the Schools Division Superintendent">Office of the Schools Division Superintendent</option>
                                    <option value="Curriculum Implementation Division">Curriculum Implementation Division</option>
                                    <option value="School Governance and Operations Division">School Governance and Operations Division</option>
                                </select>
                            </div>
                        </div>

                        <div class="db-field">
                            <label>Unit / Section <span style="color:var(--red)">*</span></label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-diagram-3 db-ficon"></i>
                                <select name="unit" id="unitSelect" required>
                                    <option value=""> Select Unit </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Fiscal Year</label>
                            <div class="db-field-inner">
                                <i class="bi bi-calendar2 db-ficon"></i>
                                <input type="number" name="fiscal_year" min="2020" max="2099" placeholder="e.g. <?= date('Y') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="db-field">
                        <label>General Description and Objective</label>
                        <div class="db-field-inner">
                            <textarea name="description" rows="3" style="width:100%;padding:.7rem 1rem;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--text);background:var(--white);outline:none;resize:vertical;" required></textarea>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Type of Project</label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-folder db-ficon"></i>
                                <select name="project_type">
                                    <option value="Goods">Goods</option>
                                    <option value="Infrastructure">Infrastructure</option>
                                    <option value="Consulting Services">Consulting Services</option>
                                </select>
                            </div>
                        </div>

                        <div class="db-field">
                            <label>Quantity / Size of Project</label>
                            <div class="db-field-inner">
                                <i class="bi bi-123 db-ficon"></i>
                                <input type="text" name="quantity">
                            </div>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Recommended Mode of Procurement</label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-card-checklist db-ficon"></i>
                                <select name="procurement_mode">
                                    <option value=""> Select Mode </option>
                                    <option value="Section 27 – Competitive Bidding">Section 27  Competitive Bidding</option>
                                    <option value="Section 28 – Limited Source Bidding">Section 28 Limited Source Bidding</option>
                                    <option value="Section 29 – Competitive Dialogue">Section 29  Competitive Dialogue</option>
                                    <option value="Section 30 – Unsolicited Offer with Bid Matching">Section 30  Unsolicited Offer with Bid Matching</option>
                                    <option value="Section 31 – Direct Contracting">Section 31  Direct Contracting</option>
                                    <option value="Section 32 – Direct Acquisition">Section 32  Direct Acquisition</option>
                                    <option value="Section 33 – Repeat Order">Section 33  Repeat Order</option>
                                    <option value="Section 34 – Small Value Procurement">Section 34  Small Value Procurement</option>
                                    <option value="Section 35 – Negotiated Procurement">Section 35  Negotiated Procurement</option>
                                    <option value="Section 36 – Direct Sales">Section 36  Direct Sales</option>
                                    <option value="Section 37 – Direct Procurement for Science, Technology and Innovation">Section 37  Direct Procurement for Science, Technology and Innovation</option>
                                </select>
                            </div>
                        </div>

                        <div class="db-field">
                            <label>Pre-Procurement Conference</label>
                            <div class="db-field-inner is-select">
                                <i class="bi bi-people db-ficon"></i>
                                <select name="preproc">
                                    <option value=""> N/A </option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Start of Procurement</label>
                            <div class="db-field-inner">
                                <i class="bi bi-calendar db-ficon"></i>
                                <input type="date" name="start_date">
                            </div>
                        </div>

                        <div class="db-field">
                            <label>End of Procurement</label>
                            <div class="db-field-inner">
                                <i class="bi bi-calendar-check db-ficon"></i>
                                <input type="date" name="end_date">
                            </div>
                        </div>
                    </div>

                    <div class="db-row">
                        <div class="db-field">
                            <label>Expected Delivery / Implementation</label>
                            <div class="db-field-inner">
                                <i class="bi bi-truck db-ficon"></i>
                                <input type="date" name="delivery_period">
                            </div>
                        </div>

                        <div class="db-field">
                            <label>Source of Funds</label>
                            <div class="db-field-inner">
                                <i class="bi bi-bank db-ficon"></i>
                                <input type="text" name="source_funds">
                            </div>
                        </div>
                    </div>

                    <div class="db-field">
                        <label>Estimated Budget (₱)</label>
                        <div class="db-field-inner">
                            <i class="bi bi-currency-exchange db-ficon"></i>
                            <input type="number" step="0.01" name="budget">
                        </div>
                    </div>

                    <div class="db-field">
                        <label>Upload Supporting Document <span style="color:var(--muted);font-weight:400;font-size:.78rem;">(PDF, Word, Excel &mdash; max 10MB)</span></label>
                        <label class="ppmp-upload-box" id="ppmpUploadBox">
                            <input type="file" name="supporting_doc" id="supportingDocInput"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx"
                                   style="display:none;">
                            <div class="ppmp-upload-icon" id="ppmpUploadIcon">
                                <i class="bi bi-cloud-arrow-up"></i>
                            </div>
                            <div class="ppmp-upload-text" id="ppmpUploadText">
                                <span class="ppmp-upload-cta">Click to browse</span> or drag &amp; drop
                                <div class="ppmp-upload-hint">PDF, DOC, DOCX, XLS, XLSX</div>
                            </div>
                            <div class="ppmp-upload-chosen" id="ppmpUploadChosen" style="display:none;">
                                <i class="bi bi-file-earmark-check"></i>
                                <span id="ppmpFileName"></span>
                                <button type="button" class="ppmp-upload-clear" id="ppmpClearBtn" title="Remove file">&times;</button>
                            </div>
                        </label>
                        <div class="ppmp-upload-error" id="ppmpUploadError" style="display:none;">
                            <i class="bi bi-exclamation-circle"></i> <span id="ppmpUploadErrorMsg"></span>
                        </div>
                    </div>
                    <div class="db-field">
                        <label>Remarks</label>
                        <div class="db-field-inner">
                            <textarea name="remarks" rows="2" style="width:100%;padding:.7rem 1rem;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--text);background:var(--white);outline:none;resize:vertical;"></textarea>
                        </div>
                    </div>

                    <div style="text-align:center;margin-top:1.5rem;">
                        <button type="submit" name="PPMPSUB" class="db-btn-submit">
                            <i class="bi bi-send"></i> Submit PPMP
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <aside class="ppmp-resources">
            <div class="ppmp-res-title"><i class="bi bi-play-circle"></i> Resources &amp; References</div>

            <div class="ppmp-res-item">
                <div class="ppmp-res-label">Video 1: How to conduct Market Scoping</div>
                <a href="https://www.facebook.com/watch/?v=614350731538858&ref=sharing&share_url=https%3A%2F%2Ffb.watch%2FCpAN9W8eWw%2F" target="_blank" class="ppmp-res-link">
                    <i class="bi bi-facebook"></i> Watch on Facebook
                </a>
            </div>

            <div class="ppmp-res-item">
                <div class="ppmp-res-label">Video 2: How to make PPMP</div>
                <a href="https://www.facebook.com/watch/?v=1454888729280790&ref=sharing" target="_blank" class="ppmp-res-link">
                    <i class="bi bi-facebook"></i> Watch on Facebook
                </a>
            </div>

            <div class="ppmp-res-divider"></div>

            <div class="ppmp-res-item">
                <div class="ppmp-res-label">Mode of Procurement</div>
                <div class="ppmp-res-desc">Use the GPPB Decision Tree to determine the appropriate mode of procurement for your project.</div>
                <a href="https://www.gppb.gov.ph/ngpa-decision-tree/" target="_blank" class="ppmp-res-link ppmp-res-link--green">
                    <i class="bi bi-diagram-3"></i> GPPB Decision Tree
                </a>
            </div>
        </aside>

        </div>
    </main>
</div>

<style>
.ppmp-page-wrap {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}
.ppmp-page-wrap .db-card {
    flex: 1;
    min-width: 0;
}
.ppmp-resources {
    width: 280px;
    flex-shrink: 0;
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 14px;
    padding: 1.25rem;
    position: sticky;
    top: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: .9rem;
}
.ppmp-res-title {
    font-size: .95rem;
    font-weight: 700;
    color: var(--navy-mid);
    display: flex;
    align-items: center;
    gap: .4rem;
    padding-bottom: .75rem;
    border-bottom: 1.5px solid var(--border);
}
.ppmp-res-item {
    display: flex;
    flex-direction: column;
    gap: .35rem;
}
.ppmp-res-label {
    font-size: .82rem;
    font-weight: 600;
    color: var(--text);
    line-height: 1.4;
}
.ppmp-res-desc {
    font-size: .78rem;
    color: var(--muted);
    line-height: 1.45;
}
.ppmp-res-link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .8rem;
    font-weight: 600;
    color: #1877f2;
    text-decoration: none;
    padding: .35rem .7rem;
    border-radius: 7px;
    background: #e7f0fd;
    transition: background .2s, color .2s;
    width: fit-content;
}
.ppmp-res-link:hover { background: #1877f2; color: #fff; }
.ppmp-res-link--green { color: var(--navy-mid); background: var(--navy-light); }
.ppmp-res-link--green:hover { background: var(--navy-mid); color: #fff; }
.ppmp-res-divider {
    border-top: 1.5px solid var(--border);
    margin: .1rem 0;
}
@media (max-width: 900px) {
    .ppmp-page-wrap { flex-direction: column; }
    .ppmp-resources { width: 100%; position: static; }
}
.ppmp-upload-box {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    border: 2px dashed var(--border);
    border-radius: 12px;
    background: var(--bg);
    cursor: pointer;
    transition: border-color .2s, background .2s;
    position: relative;
}
.ppmp-upload-box:hover { border-color: var(--navy-mid); background: var(--navy-light); }
.ppmp-upload-box.has-file { border-color: var(--green); background: var(--green-light, #ecfdf5); border-style: solid; }
.ppmp-upload-icon {
    width: 44px; height: 44px; flex-shrink: 0;
    border-radius: 10px;
    background: var(--navy-light);
    color: var(--navy-mid);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    transition: background .2s, color .2s;
}
.ppmp-upload-box.has-file .ppmp-upload-icon { background: var(--green-light, #ecfdf5); color: var(--green); }
.ppmp-upload-text { line-height: 1.5; font-size: .87rem; color: var(--muted); }
.ppmp-upload-cta { color: var(--navy-mid); font-weight: 600; }
.ppmp-upload-hint { font-size: .75rem; color: var(--muted); margin-top: .1rem; }
.ppmp-upload-chosen {
    display: flex; align-items: center; gap: .5rem;
    font-size: .87rem; font-weight: 600; color: var(--green);
    flex: 1; min-width: 0;
}
.ppmp-upload-chosen i { font-size: 1.1rem; flex-shrink: 0; }
.ppmp-upload-chosen span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ppmp-upload-clear {
    margin-left: auto; flex-shrink: 0;
    background: none; border: none;
    font-size: 1.2rem; line-height: 1;
    color: var(--red); cursor: pointer;
    padding: 0 .25rem;
}
.ppmp-upload-error {
    display: flex; align-items: center; gap: .35rem;
    margin-top: .35rem;
    font-size: .8rem; color: var(--red);
}
</style>

<script src="assets/js/ppmp.js"></script>
<script>
(function () {
    const input    = document.getElementById('supportingDocInput');
    const box      = document.getElementById('ppmpUploadBox');
    const icon     = document.getElementById('ppmpUploadIcon');
    const text     = document.getElementById('ppmpUploadText');
    const chosen   = document.getElementById('ppmpUploadChosen');
    const nameEl   = document.getElementById('ppmpFileName');
    const clearBtn = document.getElementById('ppmpClearBtn');
    const errBox   = document.getElementById('ppmpUploadError');
    const errMsg   = document.getElementById('ppmpUploadErrorMsg');

    const ALLOWED = ['pdf','doc','docx','xls','xlsx'];
    const MAX_MB  = 10;

    function showFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!ALLOWED.includes(ext)) {
            showError('Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX.');
            input.value = '';
            return;
        }
        if (file.size > MAX_MB * 1024 * 1024) {
            showError('File too large. Maximum size is 10MB.');
            input.value = '';
            return;
        }
        clearError();
        nameEl.textContent = file.name;
        text.style.display   = 'none';
        chosen.style.display = 'flex';
        box.classList.add('has-file');
    }

    function clearFile() {
        input.value = '';
        nameEl.textContent   = '';
        text.style.display   = '';
        chosen.style.display = 'none';
        box.classList.remove('has-file');
        clearError();
    }

    function showError(msg) { errMsg.textContent = msg; errBox.style.display = 'flex'; }
    function clearError()   { errBox.style.display = 'none'; }

    input.addEventListener('change', () => { if (input.files[0]) showFile(input.files[0]); });

    clearBtn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); clearFile(); });

    // Drag & drop
    box.addEventListener('dragover',  e => { e.preventDefault(); box.style.borderColor = 'var(--navy-mid)'; });
    box.addEventListener('dragleave', () => { box.style.borderColor = ''; });
    box.addEventListener('drop', e => {
        e.preventDefault();
        box.style.borderColor = '';
        const file = e.dataTransfer.files[0];
        if (!file) return;
        // Assign to input via DataTransfer
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showFile(file);
    });
})();
</script>

