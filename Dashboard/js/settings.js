/* ============================================
   ICSS CRM — Settings Logic
   ============================================ */

const Settings = (() => {
    'use strict';

    function initToggles() {
        document.querySelectorAll('.toggle-switch input').forEach(toggle => {
            toggle.addEventListener('change', () => {
                const key = toggle.id || toggle.name;
                const label = toggle.closest('.settings-row')?.querySelector('h4')?.textContent || key;
                App.showToast(
                    'success',
                    'Setting Updated',
                    `"${label}" has been ${toggle.checked ? 'enabled' : 'disabled'}.`
                );
            });
        });
    }

    function initProfileForm() {
        const saveBtn = document.getElementById('saveProfileBtn');
        if (!saveBtn) return;

        saveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            saveBtn.classList.add('loading');
            saveBtn.disabled = true;

            setTimeout(() => {
                saveBtn.classList.remove('loading');
                saveBtn.disabled = false;
                App.showToast('success', 'Profile Updated', 'Your profile information has been saved.');
            }, 1200);
        });
    }

    function initPasswordForm() {
        const changeBtn = document.getElementById('changePasswordBtn');
        if (!changeBtn) return;

        changeBtn.addEventListener('click', (e) => {
            e.preventDefault();

            const current = document.getElementById('currentPassword');
            const newPw = document.getElementById('newPassword');
            const confirm = document.getElementById('confirmPassword');

            if (!current?.value || !newPw?.value || !confirm?.value) {
                App.showToast('error', 'Error', 'Please fill in all password fields.');
                return;
            }

            if (newPw.value !== confirm.value) {
                App.showToast('error', 'Mismatch', 'New password and confirmation do not match.');
                return;
            }

            if (newPw.value.length < 8) {
                App.showToast('warning', 'Weak Password', 'Password must be at least 8 characters.');
                return;
            }

            changeBtn.classList.add('loading');
            changeBtn.disabled = true;

            setTimeout(() => {
                changeBtn.classList.remove('loading');
                changeBtn.disabled = false;
                current.value = '';
                newPw.value = '';
                confirm.value = '';
                App.showToast('success', 'Password Changed', 'Your password has been updated successfully.');
            }, 1200);
        });
    }

    function init() {
        initToggles();
        initProfileForm();
        initPasswordForm();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', Settings.init);
