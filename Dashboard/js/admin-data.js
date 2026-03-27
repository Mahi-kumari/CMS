/* ============================================
   ICSS CRM — Admin Data Management
   ============================================ */

(() => {
  'use strict';

  const crmBase = window.CRM_BASE || '/CRM';
  const byId = (id) => document.getElementById(id);

  function fillUserForm(option) {
    if (!option) return;
    const name = option.getAttribute('data-full-name') || '';
    const email = option.getAttribute('data-email') || '';
    const phone = option.getAttribute('data-phone') || '';
    const emailNotif = option.getAttribute('data-email-notif') === '1';
    const soundAlert = option.getAttribute('data-sound-alert') === '1';

    const nameInput = byId('adminUserName');
    const emailInput = byId('adminUserEmail');
    const phoneInput = byId('adminUserPhone');
    const pwInput = byId('adminUserPassword');

    if (nameInput) nameInput.value = name;
    if (emailInput) emailInput.value = email;
    if (phoneInput) phoneInput.value = phone;
    void emailNotif;
    void soundAlert;
    if (pwInput) pwInput.value = '';
  }

  async function saveUserChanges() {
    const select = byId('adminUserSelect');
    const userId = select?.value || '';
    if (!userId) {
      App.showToast('warning', 'Select User', 'Please choose a user to update.');
      return;
    }

    const payload = new URLSearchParams({
      user_id: userId,
      full_name: byId('adminUserName')?.value.trim() || '',
      email: byId('adminUserEmail')?.value.trim() || '',
      phone: byId('adminUserPhone')?.value.trim() || '',
      email_notifications: '',
      sound_alerts: ''
    });

    const newPw = byId('adminUserPassword')?.value || '';
    if (newPw) payload.append('new_password', newPw);

    try {
      const res = await fetch(`${crmBase}/Dashboard/api/admin_update_user.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        App.showToast('error', 'Update Failed', data.message || 'Could not update user.');
        return;
      }
      App.showToast('success', 'User Updated', data.message || 'User profile updated.');

      const selectedOption = select.options[select.selectedIndex];
      if (selectedOption) {
        selectedOption.setAttribute('data-full-name', byId('adminUserName')?.value.trim() || '');
        selectedOption.setAttribute('data-email', byId('adminUserEmail')?.value.trim() || '');
        selectedOption.setAttribute('data-phone', byId('adminUserPhone')?.value.trim() || '');
        selectedOption.setAttribute('data-email-notif', '');
        selectedOption.setAttribute('data-sound-alert', '');
        selectedOption.textContent = `${byId('adminUserName')?.value.trim() || 'User'} (${byId('adminUserEmail')?.value.trim() || ''})`;
      }
    } catch (err) {
      App.showToast('error', 'Network Error', 'Please try again.');
    }
  }

  async function reassignLead() {
    const leadId = byId('adminLeadSelect')?.value || '';
    const userId = byId('adminAssigneeSelect')?.value || '';
    if (!leadId || !userId) {
      App.showToast('warning', 'Missing Fields', 'Select a lead and a user.');
      return;
    }

    try {
      const res = await fetch(`${crmBase}/Dashboard/api/admin_reassign_lead.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ lead_id: leadId, new_user_id: userId })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        App.showToast('error', 'Reassign Failed', data.message || 'Could not reassign lead.');
        return;
      }
      App.showToast('success', 'Lead Reassigned', data.message || 'Lead updated.');
    } catch (err) {
      App.showToast('error', 'Network Error', 'Please try again.');
    }
  }

  function bindEvents() {
    const userSelect = byId('adminUserSelect');
    if (userSelect) {
      userSelect.addEventListener('change', () => {
        fillUserForm(userSelect.options[userSelect.selectedIndex]);
      });
    }

    const saveBtn = byId('adminUserSaveBtn');
    if (saveBtn) saveBtn.addEventListener('click', (e) => {
      e.preventDefault();
      saveUserChanges();
    });

    const reassignBtn = byId('adminReassignBtn');
    if (reassignBtn) reassignBtn.addEventListener('click', (e) => {
      e.preventDefault();
      reassignLead();
    });

    const exportUsersBtn = byId('exportUsersBtn');
    if (exportUsersBtn) exportUsersBtn.addEventListener('click', () => {
      window.location.href = `${crmBase}/Dashboard/api/admin_export.php?type=users`;
    });

    const exportLeadsBtn = byId('exportLeadsBtn');
    if (exportLeadsBtn) exportLeadsBtn.addEventListener('click', () => {
      window.location.href = `${crmBase}/Dashboard/api/admin_export.php?type=leads`;
    });
  }

  function initAuditLog() {
    const searchInput = byId('auditSearch');
    const actionFilter = byId('auditActionFilter');
    const targetFilter = byId('auditTargetFilter');
    const countEl = byId('auditCount');
    const paginationEl = byId('auditPagination');
    const tbody = document.querySelector('#auditLogTable tbody') || document.querySelector('#tabData .data-table tbody') || document.querySelector('.data-table tbody');
    if (!tbody) return;

    let rows = Array.from(tbody.querySelectorAll('tr'));
    const emptyRow = rows.find(r => r.children.length === 1 || r.children.length === 8 && r.textContent.includes('No audit'));
    if (emptyRow && rows.length === 1) return;

    let currentPage = 1;
    const pageSize = 5;

    function matches(row, term, action, target) {
      const hay = [
        row.dataset.admin || '',
        row.dataset.action || '',
        row.dataset.target || '',
        row.dataset.field || '',
        row.dataset.old || '',
        row.dataset.new || ''
      ].join(' ').toLowerCase();
      const matchTerm = !term || hay.includes(term);
      const matchAction = !action || (row.dataset.action || '') === action;
      const matchTarget = !target || (row.dataset.target || '') === target;
      return matchTerm && matchAction && matchTarget;
    }

    function render() {
      const term = (searchInput?.value || '').toLowerCase().trim();
      const action = actionFilter?.value || '';
      const target = targetFilter?.value || '';
      const filtered = rows.filter(r => matches(r, term, action, target));

      const total = filtered.length;
      const totalPages = Math.max(1, Math.ceil(total / pageSize));
      if (currentPage > totalPages) currentPage = totalPages;

      const start = (currentPage - 1) * pageSize;
      const end = start + pageSize;

      rows.forEach(r => { r.style.display = 'none'; });
      filtered.slice(start, end).forEach(r => { r.style.display = ''; });

      if (countEl) {
        const shownStart = total === 0 ? 0 : start + 1;
        const shownEnd = Math.min(end, total);
        countEl.innerHTML = `Showing <strong>${shownStart}-${shownEnd}</strong> of <strong>${total}</strong> entries`;
      }

      if (paginationEl) {
        const buttons = [];
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        buttons.push(`<button class="btn btn-sm btn-secondary" data-page="${currentPage - 1}" ${prevDisabled}>Prev</button>`);
        for (let i = 1; i <= totalPages; i++) {
          const active = i === currentPage ? 'btn-primary' : 'btn-secondary';
          buttons.push(`<button class="btn btn-sm ${active}" data-page="${i}">${i}</button>`);
        }
        buttons.push(`<button class="btn btn-sm btn-secondary" data-page="${currentPage + 1}" ${nextDisabled}>Next</button>`);
        paginationEl.innerHTML = buttons.join(' ');

        paginationEl.querySelectorAll('button[data-page]').forEach(btn => {
          btn.addEventListener('click', () => {
            const p = parseInt(btn.getAttribute('data-page'), 10);
            if (!Number.isNaN(p) && p >= 1 && p <= totalPages) {
              currentPage = p;
              render();
            }
          });
        });
      }
    }

    if (searchInput) searchInput.addEventListener('input', () => { currentPage = 1; render(); });
    if (actionFilter) actionFilter.addEventListener('change', () => { currentPage = 1; render(); });
    if (targetFilter) targetFilter.addEventListener('change', () => { currentPage = 1; render(); });

    render();
  }

  document.addEventListener('DOMContentLoaded', bindEvents);
  document.addEventListener('DOMContentLoaded', initAuditLog);
})();

// Lead Ownership pagination (admin)
(() => {
  const table = document.getElementById('leadOwnershipTable');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  const paginationEl = document.getElementById('leadOwnershipPagination');
  const countEl2 = document.getElementById('leadOwnershipCount2');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  const emptyRow = rows.length === 1 && rows[0].children.length === 1;
  if (emptyRow) return;

  const ITEMS_PER_PAGE = 5;
  let currentPage = 1;

  function render() {
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
    if (currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;

    rows.forEach(r => { r.style.display = 'none'; });
    rows.slice(start, end).forEach(r => { r.style.display = ''; });

    if (countEl2) {
      const shownStart = total === 0 ? 0 : start + 1;
      const shownEnd = Math.min(end, total);
      countEl2.textContent = `Showing ${shownStart}-${shownEnd} of ${total} entries`;
    }

    if (!paginationEl) return;
    if (totalPages <= 1) {
      paginationEl.innerHTML = '';
      return;
    }

    let html = `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">‹</button>`;
    for (let i = 1; i <= totalPages; i++) {
      if (totalPages > 7) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
          html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
          html += `<span class="pagination-btn" style="pointer-events:none;">…</span>`;
        }
      } else {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
      }
    }
    html += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">›</button>`;
    paginationEl.innerHTML = html;
  }

  if (paginationEl) {
    paginationEl.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-page]');
      if (!btn) return;
      const p = parseInt(btn.getAttribute('data-page'), 10);
      const totalPages = Math.ceil(rows.length / ITEMS_PER_PAGE);
      if (!Number.isNaN(p) && p >= 1 && p <= totalPages) {
        currentPage = p;
        render();
      }
    });
  }

  render();
})();
