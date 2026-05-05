// user/assets/js/ppmp.js
document.querySelectorAll('.db-nav-item').forEach(i => i.classList.remove('active'));
const ppmpLink = document.querySelector('.db-nav-item[href*="ppmp"]');
if (ppmpLink) ppmpLink.classList.add('active');

const officeUnits = {
    'Office of the Schools Division Superintendent': [
        'OSDS Proper',
        'ASDS',
        'ICT Services',
        'Administrative Services - Cashier',
        'Administrative Services - Personnel',
        'Administrative Services - Records',
        'Administrative Services - Procurement',
        'Administrative Services - Supply and Property',
        'Legal Services',
        'Finance Services - Budget',
        'Finance Services - Accounting',
    ],
    'Curriculum Implementation Division': [
        'LR',
        'EPS',
        'PSDS',
        'ALS',
    ],
    'School Governance and Operations Division': [
        'Planning & Research',
        'HRD',
        'SMNE',
        'SMME',
        'HSNU',
        'Facilities',
        'Education Facilities Section',
        'YFD',
        'Private',
    ],
};

const officeSelect = document.getElementById('officeSelect');
const unitSelect   = document.getElementById('unitSelect');

officeSelect?.addEventListener('change', () => {
    const opts = officeUnits[officeSelect.value] || [];
    unitSelect.innerHTML = '<option value="">Select Unit</option>';
    opts.forEach(u => {
        const o = document.createElement('option');
        o.value = u; o.textContent = u;
        unitSelect.appendChild(o);
    });
    unitSelect.disabled = opts.length === 0;
    if (opts.length === 0) unitSelect.required = false;
    else unitSelect.required = true;
});
