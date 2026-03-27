/* ============================================
   ICSS CRM - Create Lead Form Logic
   ============================================ */

const CreateLead = (() => {
    'use strict';

    // Lookup Data
    const roleParam = window.CRM_ROLE === 'admin' ? 'role=admin' : '';
    const crmBase = window.CRM_BASE || '/CRM';
    const apiBase = window.CRM_ROLE === 'admin' ? `${crmBase}/Dashboard/` : '';
    const withRole = (url) => {
        const baseUrl = url.startsWith('api/') ? `${apiBase}${url}` : url;
        return roleParam ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${roleParam}` : baseUrl;
    };

    async function loadLookups() {
        try {
            let res = await fetch(withRole('api/lookups.php'), { cache: 'no-store' });
            if (!res.ok) {
                const fallback = withRole(window.location.origin + `${crmBase}/Dashboard/api/lookups.php`);
                res = await fetch(fallback, { cache: 'no-store' });
            }
            if (!res.ok) {
                console.warn('Lookup fetch failed:', res.status);
                return;
            }
            const data = await res.json();

            populateSelect('courseApplied', data.courses, 'Select Course');
            populateSelect('admittedCourseName', data.courses, 'Select Course');
            populateSelect('nextCourseSuggested', data.courses, 'Select Course');
            populateSelect('studentState', data.locations, 'Select State');
            populateSelect('centerLocation', data.locations, 'Select Location');
            populateSelect('sourceOfLead', data.sources, 'Select Source');

        } catch (e) {
            console.warn('Failed to load lookups:', e);
        }
    }

    /* ---------- INLINE MESSAGE ---------- */
    function showInlineMessage(type, message) {
        const el = document.getElementById('formMessage');
        if (!el) return;

        el.textContent = message;
        el.className = 'form-message ' + type;
        el.style.display = 'block';

        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            el.style.display = 'none';
        }, 4000);
    }

    /* ---------- TOAST ---------- */
    function showToast(icon, title) {
        ensureSwal().then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon,
                title,
                showConfirmButton: false,
                timer: 3000
            });
        });
    }

    function populateSelect(id, items, placeholder) {
        const select = document.getElementById(id);
        if (!select) return;

        select.innerHTML = '';

        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = placeholder;
        select.appendChild(defaultOpt);

        const list = Array.isArray(items) ? [...items] : [];

        if (id === 'sourceOfLead' && !list.includes('Other')) list.push('Other');
        if (id === 'centerLocation' && !list.includes('Other')) list.push('Other');

        list.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            select.appendChild(opt);
        });
    }

    /* ---------- UI FEATURES ---------- */

    function initSections() {
        document.querySelectorAll('.form-section-header').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.closest('.form-section');
                if (section) section.classList.toggle('collapsed');
            });
        });
    }

    function initWorkingDetailsToggle() {
        const professionSelect = document.getElementById('priorKnowledge');
        const workingGroup = document.getElementById('workingDetailsGroup');

        if (!professionSelect || !workingGroup) return;

        const toggle = () => {
            workingGroup.style.display = professionSelect.value === 'Working' ? '' : 'none';
        };

        toggle();
        professionSelect.addEventListener('change', toggle);
    }

    function initCounselingDefaults() {
        const inquiryDate = document.getElementById('inquiryDate');

        if (inquiryDate && !inquiryDate.value) {
            const today = new Date();
            inquiryDate.value = today.toISOString().split('T')[0];
        }
    }

    function initFeeToggles() {
        const tokenStatus = document.getElementById('tokenStatus');
        const tokenGroup = document.getElementById('tokenAmountGroup');

        if (tokenStatus && tokenGroup) {
            const toggle = () => {
                tokenGroup.style.display = tokenStatus.value === 'Yes' ? '' : 'none';
            };
            toggle();
            tokenStatus.addEventListener('change', toggle);
        }
    }

    function initFeeCalculation() {
        const courseFee = document.getElementById('courseFee');
        const discount = document.getElementById('discountApplied');
        const finalFee = document.getElementById('finalFee');
        if (!courseFee || !discount || !finalFee) return;

        const toNumber = (value) => {
            const num = parseFloat(value);
            return Number.isFinite(num) ? num : 0;
        };

        const clamp = (val, min, max) => Math.min(Math.max(val, min), max);

        const updateFinalFee = () => {
            const base = toNumber(courseFee.value);
            const pct = clamp(toNumber(discount.value), 0, 100);
            if (discount.value !== '' && discount.value != pct) {
                discount.value = pct;
            }
            const discounted = base - (base * pct / 100);
            finalFee.value = discounted ? discounted.toFixed(2) : '';
        };

        courseFee.addEventListener('input', updateFinalFee);
        discount.addEventListener('input', updateFinalFee);
        updateFinalFee();
    }

    function initPhoneNumberFilters() {
        const phoneInput = document.getElementById('phoneNumber');
        const whatsappInput = document.getElementById('whatsappNumber');

        const bindFilter = (input) => {
            if (!input) return;
            input.addEventListener('input', () => {
                const digits = input.value.replace(/\D/g, '').slice(0, 10);
                if (input.value !== digits) {
                    input.value = digits;
                }
            });
        };

        bindFilter(phoneInput);
        bindFilter(whatsappInput);
    }

    /* ---------- VALIDATION ---------- */

    function validateForm() {
        let valid = true;
        const form = document.getElementById('createLeadForm');
        if (!form) return true;

        form.querySelectorAll('.form-group.has-error').forEach(g => {
            g.classList.remove('has-error');
        });

        form.querySelectorAll('[required]').forEach(input => {
            const group = input.closest('.form-group');
            if (!input.value.trim()) {
                if (group) group.classList.add('has-error');
                valid = false;
            }
        });

        return valid;
    }

    /* ---------- SWEETALERT ---------- */

    function ensureSwal() {
        if (window.Swal) return Promise.resolve(window.Swal);

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'js/sweetalert2.min.js';
            script.onload = () => resolve(window.Swal);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /* ---------- SUBMIT ---------- */

    async function submitLead(form, resetAfter) {
        const formData = new FormData(form);

        try {
            const res = await fetch(withRole('api/save_lead.php'), {
                method: 'POST',
                body: formData
            });

            const data = await res.json().catch(() => null);

            if (!res.ok) {
                const msg = data?.message || 'Failed to save lead.';
                showInlineMessage('error', msg);
                showToast('error', 'Save Failed');
                return false;
            }

            // ✅ SUCCESS FIX
            showInlineMessage('success', data?.message || 'Lead saved successfully.');
            showToast('success', 'Lead Saved');

            if (resetAfter) resetForm();

            return true;

        } catch (err) {
            showInlineMessage('error', 'Network error. Please try again.');
            return false;
        }
    }

    /* ---------- HANDLERS ---------- */

    function handleSave(e) {
        e.preventDefault();

        if (!validateForm()) {
            showInlineMessage('error', 'Please fill all required fields.');
            return;
        }

        const btn = document.getElementById('saveBtn');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('loading');
        }

        const form = document.getElementById('createLeadForm');

        submitLead(form, true).finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        });
    }

    function handleSaveAndContinue(e) {
        e.preventDefault();

        if (!validateForm()) {
            showInlineMessage('error', 'Please fill all required fields.');
            return;
        }

        const form = document.getElementById('createLeadForm');
        submitLead(form, true);
    }

    function resetForm() {
        const form = document.getElementById('createLeadForm');
        if (!form) return;

        form.reset();
        window.scrollTo({ top: 0 });
    }

    /* ---------- INIT ---------- */

    function init() {
        initSections();
        initWorkingDetailsToggle();
        initCounselingDefaults();
        initFeeToggles();
        initFeeCalculation();
        initPhoneNumberFilters();
        loadLookups();

        const saveBtn = document.getElementById('saveBtn');
        const saveContinueBtn = document.getElementById('saveContinueBtn');

        if (saveBtn) saveBtn.addEventListener('click', handleSave);
        if (saveContinueBtn) saveContinueBtn.addEventListener('click', handleSaveAndContinue);

        // Auto hide message on typing
        const form = document.getElementById('createLeadForm');
        if (form) {
            form.addEventListener('input', () => {
                const msg = document.getElementById('formMessage');
                if (msg) msg.style.display = 'none';
            });
        }
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', CreateLead.init);
console.log('CreateLead JS loaded');
