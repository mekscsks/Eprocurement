// user/assets/js/submissions.js
document.querySelectorAll('.db-nav-item').forEach(item => item.classList.remove('active'));
const submissionsLink = document.querySelector('.db-nav-item[href*="submissions"]');
if (submissionsLink) submissionsLink.classList.add('active');

const reuploadId  = document.getElementById('reuploadId');
const reuploadRef = document.getElementById('reuploadRef');
const officeUnits = window.ppmpOfficeUnits || {};

// Reupload Modal Handler
document.querySelectorAll('.reupload-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        reuploadId.value = btn.getAttribute('data-id') || '';
        reuploadRef.textContent = btn.getAttribute('data-ref') ? `Reference: ${btn.getAttribute('data-ref')}` : '';
        const file = document.getElementById('reuploadFile');
        if (file) file.value = '';
        const modalEl = document.getElementById('reuploadModal');
        if (modalEl && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });
});

// Edit PPMP Modal Handlers
const editOfficeSelect = document.getElementById('editOfficeSelect');
const editUnitSelect = document.getElementById('editUnitSelect');
const editPpmpId = document.getElementById('editPpmpId');
const editPpmpRef = document.getElementById('editPpmpRef');

const populateEditUnits = (officeValue, selectedUnit = '') => {
    if (!editUnitSelect) return;

    const units = officeUnits[officeValue] || [];
    editUnitSelect.innerHTML = '';
    editUnitSelect.append(new Option('Select Unit', '', !selectedUnit, !selectedUnit));

    units.forEach(unit => {
        editUnitSelect.append(new Option(unit, unit, false, unit === selectedUnit));
    });

    if (selectedUnit && !units.includes(selectedUnit)) {
        editUnitSelect.append(new Option(selectedUnit, selectedUnit, true, true));
    }
};

if (editOfficeSelect) {
    editOfficeSelect.addEventListener('change', () => populateEditUnits(editOfficeSelect.value, ''));
}

// Enhanced Edit PPMP Button Handler
document.querySelectorAll('.edit-ppmp-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        console.log('Edit button clicked - Loading PPMP data...');
        
        if (editPpmpId) editPpmpId.value = btn.getAttribute('data-id') || '';
        if (editPpmpRef) editPpmpRef.textContent = btn.getAttribute('data-ref') ? `Reference: ${btn.getAttribute('data-ref')}` : '';

        // Load Office and Unit
        const officeValue = btn.getAttribute('data-office') || '';
        if (editOfficeSelect) {
            if (officeValue && !Array.from(editOfficeSelect.options).some(option => option.value === officeValue)) {
                editOfficeSelect.append(new Option(officeValue, officeValue));
            }
            editOfficeSelect.value = officeValue;
            populateEditUnits(officeValue, btn.getAttribute('data-unit') || '');
        }

        // Field mappings: [element_id, data_attribute_key]
        const fieldMappings = [
            ['edit_ppmp_type', 'ppmp_type'],
            ['edit_project_type', 'project_type'],
            ['edit_description', 'description'],
            ['edit_quantity', 'quantity'],
            ['edit_procurement_mode', 'procurement_mode'],
            ['edit_preproc', 'preproc'],
            ['edit_start_date', 'start_date'],
            ['edit_end_date', 'end_date'],
            ['edit_delivery_period', 'delivery_period'],
            ['edit_source_funds', 'source_funds'],
            ['edit_budget', 'budget'],
            ['edit_remarks', 'remarks']
        ];

        // Populate all form fields from button data attributes
        fieldMappings.forEach(([elementId, dataKey]) => {
            const element = document.getElementById(elementId);
            if (element) {
                const value = btn.getAttribute(`data-${dataKey}`) || '';
                element.value = value;
                console.log(`Populated ${elementId}: ${value || '(empty)'}`);
            } else {
                console.warn(`Element not found: ${elementId}`);
            }
        });

        // Show modal
        const modalEl = document.getElementById('editPpmpModal');
        if (modalEl && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            console.log('Edit PPMP modal shown');
        }
    });
});

// Loading overlay for form submissions
const overlay   = document.getElementById('loadingOverlay');
const form      = document.getElementById('reupload_ppmp');
const submitBtn = document.getElementById('reuploadSubmitBtn');
if (form) {
    form.addEventListener('submit', () => {
        if (overlay) overlay.classList.remove('d-none');
        if (submitBtn) submitBtn.disabled = true;
    });
}

const editForm = document.getElementById('edit_ppmp_form');
const editSubmitBtn = document.getElementById('editPpmpSubmitBtn');
if (editForm) {
    editForm.addEventListener('submit', () => {
        if (overlay) overlay.classList.remove('d-none');
        if (editSubmitBtn) editSubmitBtn.disabled = true;
    });
}
