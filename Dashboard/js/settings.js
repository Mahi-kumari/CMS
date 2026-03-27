/* ============================================
   ICSS CRM — Settings Logic
   ============================================ */

const Settings = (() => {
    'use strict';

    const roleParam = window.CRM_ROLE === 'admin' ? 'role=admin' : '';
    const crmBase = window.CRM_BASE || '/CRM';
    const apiBase = window.CRM_ROLE === 'admin' ? `${crmBase}/Dashboard/` : '';
    const withRole = (url) => {
        const baseUrl = url.startsWith('api/') ? `${apiBase}${url}` : url;
        return roleParam ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${roleParam}` : baseUrl;
    };

    function initToggles() {
        document.querySelectorAll('.toggle-switch input').forEach(toggle => {
            toggle.addEventListener('change', async () => {
                const key = toggle.id || toggle.name;
                const label = toggle.closest('.settings-row')?.querySelector('h4')?.textContent || key;

                if (toggle.id === 'emailNotif' || toggle.id === 'soundAlert') {
                    try {
                        const emailToggle = document.getElementById('emailNotif');
                        const soundToggle = document.getElementById('soundAlert');
                        const res = await fetch(withRole('api/update_notifications.php'), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                email_notifications: emailToggle && emailToggle.checked ? '1' : '0',
                                sound_alerts: soundToggle && soundToggle.checked ? '1' : '0'
                            })
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            App.showToast('error', 'Update Failed', data.message || 'Could not update setting.');
                        } else {
                            App.showToast('success', 'Setting Updated', data.message || `"${label}" updated.`);
                        }
                    } catch (err) {
                        App.showToast('error', 'Network Error', 'Please try again.');
                    }
                    return;
                }

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

        saveBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            saveBtn.classList.add('loading');
            saveBtn.disabled = true;

            const fullName = document.getElementById('settingFullName')?.value.trim();
            const email = document.getElementById('settingEmail')?.value.trim();
            const phone = document.getElementById('settingPhone')?.value.trim();

            try {
                const res = await fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        full_name: fullName || '',
                        email: email || '',
                        phone: phone || ''
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    App.showToast('error', 'Update Failed', data.message || 'Could not update profile.');
                } else {
                    App.showToast('success', 'Profile Updated', data.message || 'Your profile information has been saved.');
                    if (fullName) {
                        document.querySelectorAll('.profile-name').forEach(el => {
                            el.textContent = fullName;
                        });
                        const metaName = document.querySelector('.profile-meta h3');
                        if (metaName) metaName.textContent = fullName;
                    }
                    if (email) {
                        const emailEls = document.querySelectorAll('.profile-email');
                        emailEls.forEach(el => { el.textContent = email; });
                        const metaEmail = document.querySelector('.profile-meta p');
                        if (metaEmail) metaEmail.textContent = `${email} · User`;
                    }
                }
            } catch (err) {
                App.showToast('error', 'Network Error', 'Please try again.');
            } finally {
                saveBtn.classList.remove('loading');
                saveBtn.disabled = false;
            }
        });
    }

    function initPasswordForm() {
        const changeBtn = document.getElementById('changePasswordBtn');
        if (!changeBtn) return;

        changeBtn.addEventListener('click', async (e) => {
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

            if (newPw.value.length < 6) {
                App.showToast('warning', 'Weak Password', 'Password must be at least 6 characters.');
                return;
            }

            changeBtn.classList.add('loading');
            changeBtn.disabled = true;

            try {
                const res = await fetch('api/update_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        current_password: current.value,
                        new_password: newPw.value,
                        confirm_password: confirm.value
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) {
                    App.showToast('error', 'Update Failed', data.message || 'Could not update password.');
                } else {
                    App.showToast('success', 'Password Changed', data.message || 'Your password has been updated successfully.');
                    current.value = '';
                    newPw.value = '';
                    confirm.value = '';
                }
            } catch (err) {
                App.showToast('error', 'Network Error', 'Please try again.');
            } finally {
                changeBtn.classList.remove('loading');
                changeBtn.disabled = false;
            }
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
