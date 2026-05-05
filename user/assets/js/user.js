// user/assets/js/user.js
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('forceChangePasswordModal');
    if (modal && modal.dataset.show === '1') {
        new bootstrap.Modal(modal, { backdrop: 'static', keyboard: false }).show();
    }
});
