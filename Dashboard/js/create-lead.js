/* ============================================
   ICSS CRM — Create Lead Form Logic
   ============================================ */

const CreateLead = (() => {
    'use strict';

    const STORAGE_KEY = 'crm_lead_draft';
    let autoSaveTimer = null;

    // ─── Collapsible Sections ───
    function initSections() {
        document.querySelectorAll('.form-section-header').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.closest('.form-section');
                section.classList.toggle('collapsed');
            });
        });
    }

    // ─── Searchable Selects ───
    function initSearchableSelects() {
        document.querySelectorAll('.searchable-select').forEach(wrapper => {
            const trigger = wrapper.querySelector('.searchable-select-trigger');
            const dropdown = wrapper.querySelector('.searchable-select-dropdown');
            const searchInput = wrapper.querySelector('.searchable-select-search input');
            const options = wrapper.querySelectorAll('.searchable-select-option');
            const triggerText = trigger.querySelector('.trigger-text');
            const hiddenInput = wrapper.querySelector('input[type="hidden"]');

            // Toggle
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other selects
                document.querySelectorAll('.searchable-select.open').forEach(s => {
                    if (s !== wrapper) s.classList.remove('open');
                });
                wrapper.classList.toggle('open');
                if (wrapper.classList.contains('open') && searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
            });

            // Search
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.toLowerCase();
                    options.forEach(opt => {
                        const text = opt.textContent.toLowerCase();
                        opt.classList.toggle('hidden', !text.includes(query));
                    });
                });
            }

            // Select option
            options.forEach(opt => {
                opt.addEventListener('click', () => {
                    options.forEach(o => o.classList.remove('selected'));
                    opt.classList.add('selected');
                    if (triggerText) triggerText.textContent = opt.textContent;
                    if (hiddenInput) hiddenInput.value = opt.dataset.value || opt.textContent;
                    wrapper.classList.remove('open');
                    triggerAutoSave();
                });
            });
        });

        // Close on outside click
        document.addEventListener('click', () => {
            document.querySelectorAll('.searchable-select.open').forEach(s => {
                s.classList.remove('open');
            });
        });
    }

    // ─── Form Validation ───
    function validateForm() {
        let valid = true;
        const form = document.getElementById('createLeadForm');
        if (!form) return true;

        // Clear previous errors
        form.querySelectorAll('.form-group.has-error').forEach(g => {
            g.classList.remove('has-error');
        });

        // Required fields
        form.querySelectorAll('[required]').forEach(input => {
            const group = input.closest('.form-group');
            if (!input.value.trim()) {
                if (group) group.classList.add('has-error');
                valid = false;
            }
        });

        // Phone validation
        const phoneInput = form.querySelector('#phoneNumber');
        if (phoneInput && phoneInput.value.trim()) {
            const phoneRegex = /^[+]?[\d\s\-()]{10,15}$/;
            if (!phoneRegex.test(phoneInput.value.trim())) {
                const group = phoneInput.closest('.form-group');
                if (group) {
                    group.classList.add('has-error');
                    const errorEl = group.querySelector('.form-error');
                    if (errorEl) errorEl.textContent = 'Enter a valid phone number';
                }
                valid = false;
            }
        }

        // Email validation
        const emailInput = form.querySelector('#emailAddress');
        if (emailInput && emailInput.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value.trim())) {
                const group = emailInput.closest('.form-group');
                if (group) {
                    group.classList.add('has-error');
                    const errorEl = group.querySelector('.form-error');
                    if (errorEl) errorEl.textContent = 'Enter a valid email address';
                }
                valid = false;
            }
        }

        return valid;
    }

    // ─── Auto-save Draft ───
    function triggerAutoSave() {
        const indicator = document.querySelector('.autosave-indicator');
        const dot = indicator ? indicator.querySelector('.autosave-dot') : null;

        if (dot) dot.classList.add('saving');
        if (indicator) {
            const text = indicator.querySelector('.autosave-text');
            if (text) text.textContent = 'Saving...';
        }

        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            saveDraft();
            if (dot) dot.classList.remove('saving');
            if (indicator) {
                const text = indicator.querySelector('.autosave-text');
                if (text) text.textContent = 'Draft saved';
            }
        }, 1000);
    }

    function saveDraft() {
        const form = document.getElementById('createLeadForm');
        if (!form) return;

        const data = {};
        form.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                if (input.checked) {
                    data[input.name || input.id] = input.value;
                }
            } else {
                data[input.name || input.id] = input.value;
            }
        });

        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function loadDraft() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (!saved) return;

        try {
            const data = JSON.parse(saved);
            const form = document.getElementById('createLeadForm');
            if (!form) return;

            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"], #${key}`);
                if (!input) return;

                if (input.type === 'checkbox' || input.type === 'radio') {
                    const inputs = form.querySelectorAll(`[name="${key}"]`);
                    inputs.forEach(inp => {
                        if (inp.value === data[key]) inp.checked = true;
                    });
                } else {
                    input.value = data[key];
                }
            });

            App.showToast('info', 'Draft Restored', 'Your previously saved draft has been loaded.');
        } catch (e) {
            console.warn('Failed to load draft:', e);
        }
    }

    function clearDraft() {
        localStorage.removeItem(STORAGE_KEY);
    }

    // ─── Form Submission ───
    function handleSave(e) {
        e.preventDefault();

        if (!validateForm()) {
            App.showToast('error', 'Validation Error', 'Please fill in all required fields correctly.');
            // Scroll to first error
            const firstError = document.querySelector('.form-group.has-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Simulate save
        const btn = e.target.closest('.btn') || document.getElementById('saveBtn');
        if (btn) {
            btn.classList.add('loading');
            btn.disabled = true;
        }

        setTimeout(() => {
            if (btn) {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
            clearDraft();
            App.showToast('success', 'Lead Created', 'The lead has been saved successfully.');
        }, 1500);
    }

    function handleSaveAndContinue(e) {
        e.preventDefault();

        if (!validateForm()) {
            App.showToast('error', 'Validation Error', 'Please fill in all required fields correctly.');
            return;
        }

        const btn = e.target.closest('.btn');
        if (btn) {
            btn.classList.add('loading');
            btn.disabled = true;
        }

        setTimeout(() => {
            if (btn) {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
            clearDraft();
            App.showToast('success', 'Lead Created', 'The lead has been saved. You can create another one.');
            resetForm();
        }, 1500);
    }

    function handleCancel() {
        if (confirm('Are you sure you want to cancel? Unsaved changes will be lost.')) {
            clearDraft();
            window.location.href = 'index.html';
        }
    }

    function handleViewChangeLog() {
        App.openModal('changeLogModal');
    }

    function resetForm() {
        const form = document.getElementById('createLeadForm');
        if (form) {
            form.reset();
            form.querySelectorAll('.form-group.has-error').forEach(g => {
                g.classList.remove('has-error');
            });
            // Reset searchable selects
            form.querySelectorAll('.searchable-select-option.selected').forEach(opt => {
                opt.classList.remove('selected');
            });
            form.querySelectorAll('.trigger-text').forEach(t => {
                t.textContent = t.dataset.placeholder || 'Select...';
            });
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ─── Init ───
    function init() {
        initSections();
        initSearchableSelects();
        loadDraft();

        // Auto-save on input
        const form = document.getElementById('createLeadForm');
        if (form) {
            form.addEventListener('input', triggerAutoSave);
            form.addEventListener('change', triggerAutoSave);
        }

        // Button handlers
        const saveBtn = document.getElementById('saveBtn');
        const saveContinueBtn = document.getElementById('saveContinueBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const changeLogBtn = document.getElementById('changeLogBtn');

        if (saveBtn) saveBtn.addEventListener('click', handleSave);
        if (saveContinueBtn) saveContinueBtn.addEventListener('click', handleSaveAndContinue);
        if (cancelBtn) cancelBtn.addEventListener('click', handleCancel);
        if (changeLogBtn) changeLogBtn.addEventListener('click', handleViewChangeLog);
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', CreateLead.init);
