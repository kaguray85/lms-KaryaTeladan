const muridState = {
  items: [],
  kelasOptions: [],
  searchTimer: null,
};

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function setPageAlert(message, type = 'error') {
  const alertBox = document.querySelector('[data-alert]');
  if (!alertBox) return;

  alertBox.textContent = message;
  alertBox.className = `alert alert-${type}`;
  alertBox.hidden = false;
}

function setSaveLoading(isLoading) {
  const button = document.querySelector('[data-save-button]');
  if (!button) return;

  button.disabled = isLoading;
  button.textContent = isLoading ? 'Menyimpan...' : 'Simpan';
}

function statusBadge(status) {
  const label = status === 'active' ? 'Aktif' : 'Nonaktif';
  const type = status === 'active' ? 'success' : 'danger';
  return `<span class="badge badge-${type}">${label}</span>`;
}

function renderKelasOptions() {
  const optionsMarkup = muridState.kelasOptions.map((kelas) => (
    `<option value="${kelas.id}">${escapeHtml(kelas.nama_kelas)} - ${escapeHtml(kelas.jurusan)} (${escapeHtml(kelas.tahun_ajaran)})</option>`
  )).join('');

  const filter = document.querySelector('[data-kelas-filter]');
  const select = document.querySelector('[data-kelas-select]');

  if (filter) {
    const current = filter.value;
    filter.innerHTML = `<option value="">Semua kelas</option>${optionsMarkup}`;
    filter.value = current;
  }

  if (select) {
    const current = select.value;
    select.innerHTML = `<option value="">Pilih kelas</option>${optionsMarkup}`;
    select.value = current;
  }
}

function renderMuridTable(items) {
  const tbody = document.querySelector('[data-murid-table]');
  if (!tbody) return;

  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state">Belum ada data murid.</td></tr>';
    return;
  }

  tbody.innerHTML = items.map((item, index) => `
    <tr>
      <td>${index + 1}</td>
      <td><strong>${escapeHtml(item.nama_murid)}</strong></td>
      <td>${escapeHtml(item.nis)}</td>
      <td>${escapeHtml(item.email)}</td>
      <td>${escapeHtml(item.nama_kelas || '-')}</td>
      <td>${escapeHtml(item.jurusan || '-')}</td>
      <td>${statusBadge(item.status)}</td>
      <td>
        <div class="action-group">
          <button class="btn btn-small btn-secondary" type="button" data-edit-id="${item.id}">Edit</button>
          <button class="btn btn-small btn-danger" type="button" data-delete-id="${item.id}" ${item.status === 'inactive' ? 'disabled' : ''}>Nonaktifkan</button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function loadMurid() {
  const loading = document.querySelector('[data-loading]');
  const search = document.querySelector('[data-search]')?.value || '';
  const status = document.querySelector('[data-status-filter]')?.value || '';
  const kelasId = document.querySelector('[data-kelas-filter]')?.value || '';
  const params = new URLSearchParams();

  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (kelasId) params.set('kelas_id', kelasId);

  try {
    if (window.authReady) await window.authReady;
    if (loading) {
      loading.hidden = false;
      loading.textContent = 'Memuat data murid...';
    }

    const query = params.toString() ? `?${params.toString()}` : '';
    const response = await apiRequest(`/admin/murid.php${query}`);
    muridState.items = response.data.items || [];
    muridState.kelasOptions = response.data.kelas_options || [];

    renderKelasOptions();
    renderMuridTable(muridState.items);

    if (loading) loading.hidden = true;
  } catch (error) {
    if (loading) loading.textContent = error.message || 'Data murid gagal dimuat.';
    renderMuridTable([]);
  }
}

function openModal(mode = 'create', item = null) {
  const modal = document.querySelector('[data-modal]');
  const form = document.querySelector('[data-murid-form]');
  const title = document.querySelector('[data-modal-title]');
  const passwordField = document.querySelector('[data-password-field]');

  if (!modal || !form) return;

  form.reset();
  form.action.value = mode;
  form.id.value = item?.id || '';
  renderKelasOptions();

  if (title) title.textContent = mode === 'create' ? 'Tambah Murid' : 'Edit Murid';
  if (passwordField) {
    passwordField.required = mode === 'create';
    passwordField.placeholder = mode === 'create' ? 'Minimal 6 karakter' : 'Kosongkan jika tidak diganti';
  }

  if (item) {
    form.nama_murid.value = item.nama_murid || '';
    form.nis.value = item.nis || '';
    form.email.value = item.email || '';
    form.kelas_id.value = item.kelas_id || '';
    form.jurusan.value = item.jurusan || '';
    form.no_hp.value = item.no_hp || '';
    form.status.value = item.status || 'active';
  }

  modal.hidden = false;
}

function closeModal() {
  const modal = document.querySelector('[data-modal]');
  if (modal) modal.hidden = true;
}

async function submitMurid(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const formData = new FormData(form);
  const payload = Object.fromEntries(formData.entries());

  if (payload.action === 'update' && !payload.password) {
    delete payload.password;
  }

  try {
    setSaveLoading(true);
    const response = await apiRequest('/admin/murid.php', {
      method: 'POST',
      body: payload,
    });

    setPageAlert(response.message, 'success');
    closeModal();
    await loadMurid();
  } catch (error) {
    const errors = error.response?.errors;
    const firstError = errors && Object.values(errors)[0];
    setPageAlert(firstError || error.message || 'Data murid gagal disimpan.');
  } finally {
    setSaveLoading(false);
  }
}

async function deleteMurid(id) {
  const item = muridState.items.find((murid) => Number(murid.id) === Number(id));
  const nama = item?.nama_murid || 'murid ini';

  if (!confirm(`Nonaktifkan data ${nama}? Akun login murid juga ikut dinonaktifkan.`)) {
    return;
  }

  try {
    const response = await apiRequest('/admin/murid.php', {
      method: 'POST',
      body: { action: 'delete', id },
    });

    setPageAlert(response.message, 'success');
    await loadMurid();
  } catch (error) {
    setPageAlert(error.message || 'Data murid gagal dinonaktifkan.');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.body.dataset.page !== 'admin-murid') return;

  loadMurid();

  document.querySelector('[data-open-create]')?.addEventListener('click', () => openModal('create'));
  document.querySelector('[data-refresh]')?.addEventListener('click', loadMurid);
  document.querySelector('[data-murid-form]')?.addEventListener('submit', submitMurid);
  document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));

  document.querySelector('[data-search]')?.addEventListener('input', () => {
    clearTimeout(muridState.searchTimer);
    muridState.searchTimer = setTimeout(loadMurid, 350);
  });

  document.querySelector('[data-status-filter]')?.addEventListener('change', loadMurid);
  document.querySelector('[data-kelas-filter]')?.addEventListener('change', loadMurid);

  document.querySelector('[data-murid-table]')?.addEventListener('click', (event) => {
    const editButton = event.target.closest('[data-edit-id]');
    const deleteButton = event.target.closest('[data-delete-id]');

    if (editButton) {
      const item = muridState.items.find((murid) => Number(murid.id) === Number(editButton.dataset.editId));
      if (item) openModal('update', item);
    }

    if (deleteButton) {
      deleteMurid(deleteButton.dataset.deleteId);
    }
  });
});
