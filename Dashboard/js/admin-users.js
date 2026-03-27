/* ============================================
   ICSS CRM — Admin User Management
   ============================================ */

(() => {
  'use strict';

  const tableBody = document.getElementById('adminUsersTbody');
  const countEl = document.getElementById('usersCount');
  const countEl2 = document.getElementById('usersCount2');
  const paginationEl = document.getElementById('usersPagination');
  if (!tableBody) return;

  const ITEMS_PER_PAGE = 5;
  let currentPage = 1;
  let allUsers = [];

  const formatDate = (val) => {
    if (!val) return '-';
    const d = new Date(val);
    if (Number.isNaN(d.getTime())) return val;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  };

  const crmBase = window.CRM_BASE || '/CRM';
  async function loadUsers() {
    try {
      const res = await fetch(`${crmBase}/Dashboard/api/admin_users.php`, { cache: 'no-store' });
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.message || 'Failed to load users');
      allUsers = Array.isArray(data.users) ? data.users : [];
      currentPage = 1;
      render();
    } catch (e) {
      tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--clr-text-secondary);">Failed to load users.</td></tr>`;
      if (paginationEl) paginationEl.innerHTML = '';
    }
  }

  function render() {
    if (!Array.isArray(allUsers) || allUsers.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--clr-text-secondary);">No users found.</td></tr>`;
      if (countEl) countEl.textContent = 'All users';
      if (countEl2) countEl2.textContent = '';
      if (paginationEl) paginationEl.innerHTML = '';
      return;
    }
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    const pageUsers = allUsers.slice(start, end);

    tableBody.innerHTML = pageUsers.map(u => `
        <tr data-user-id="${u.user_id}">
          <td>${u.full_name || '-'}</td>
          <td>${u.email || '-'}</td>
          <td>${u.phone || '-'}</td>
          <td>${formatDate(u.last_login)}</td>
          <td>${u.total_leads ?? 0}</td>
        </tr>
      `).join('');

    if (countEl) {
      const shownStart = allUsers.length === 0 ? 0 : start + 1;
      const shownEnd = Math.min(end, allUsers.length);
      countEl.innerHTML = `Showing <strong>${shownStart}-${shownEnd}</strong> of <strong>${allUsers.length}</strong> users`;
    }
    if (countEl2) {
      countEl2.textContent = `Total users: ${allUsers.length}`;
    }
    renderPagination();
  }

  function renderPagination() {
    if (!paginationEl) return;
    const totalPages = Math.ceil(allUsers.length / ITEMS_PER_PAGE);
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

  tableBody.addEventListener('click', () => {
    // no row actions
  });

  if (paginationEl) {
    paginationEl.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-page]');
      if (!btn) return;
      const p = parseInt(btn.getAttribute('data-page'), 10);
      const totalPages = Math.ceil(allUsers.length / ITEMS_PER_PAGE);
      if (!Number.isNaN(p) && p >= 1 && p <= totalPages) {
        currentPage = p;
        render();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', loadUsers);
})();
