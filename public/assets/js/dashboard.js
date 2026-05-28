async function loadDashboard() {
  const dashboardType = document.body.dataset.dashboard;
  if (!dashboardType) return;

  const loading = document.querySelector('[data-loading]');
  const grid = document.querySelector('[data-dashboard-grid]');
  const logs = document.querySelector('[data-logs]');

  try {
    if (window.authReady) await window.authReady;

    const response = await apiRequest(`/${dashboardType}/dashboard.php`);
    const data = response.data;
    const summary = data.summary || {};

    if (loading) loading.hidden = true;
    if (grid) {
      grid.innerHTML = Object.entries(summary)
        .map(([key, value]) => `
          <article class="stat-card">
            <span>${key.replaceAll('_', ' ')}</span>
            <strong>${value}</strong>
          </article>
        `)
        .join('');
    }

    if (logs) {
      const activities = data.aktivitas_terbaru || [];
      if (!activities.length) {
        logs.innerHTML = '<li>Belum ada aktivitas terbaru.</li>';
        return;
      }
      logs.innerHTML = activities.map((item) => `
        <li>
          <strong>${item.role}</strong> - ${item.aktivitas}
          <small>${item.created_at}</small>
        </li>
      `).join('');
    }
  } catch (error) {
    if (loading) loading.textContent = error.message || 'Dashboard gagal dimuat.';
  }
}

document.addEventListener('DOMContentLoaded', loadDashboard);
