function escapeHtml(value) {
  return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
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
function closeModal() { const modal = document.querySelector('[data-modal]'); if (modal) modal.hidden = true; }
function firstError(error) { const errors = error.response?.errors; return errors ? Object.values(errors)[0] : null; }
async function getJson(endpoint) { const response = await apiRequest(endpoint); return response.data || []; }

const jadwalState = { items: [], guru: [], kelas: [], mapel: [], searchTimer: null };
async function loadOptions(){
  [jadwalState.guru, jadwalState.kelas, jadwalState.mapel] = await Promise.all([getJson('/admin/guru.php?status=active'), getJson('/admin/kelas.php?mode=options'), getJson('/admin/mapel.php?mode=options')]);
  document.querySelector('[data-guru-options]').innerHTML='<option value="">Pilih guru</option>'+jadwalState.guru.map(g=>`<option value="${g.id}">${escapeHtml(g.nama_guru)}</option>`).join('');
  document.querySelector('[data-kelas-options]').innerHTML='<option value="">Pilih kelas</option>'+jadwalState.kelas.map(k=>`<option value="${k.id}">${escapeHtml(k.nama_kelas)} - ${escapeHtml(k.jurusan)}</option>`).join('');
  document.querySelector('[data-mapel-options]').innerHTML='<option value="">Pilih mapel</option>'+jadwalState.mapel.map(m=>`<option value="${m.id}" data-guru="${m.guru_id}" data-kelas="${m.kelas_id}">${escapeHtml(m.nama_mapel)} - ${escapeHtml(m.nama_kelas)}</option>`).join('');
}
function syncFromMapel(){ const form=document.querySelector('[data-jadwal-form]'); const option=form?.mapel_id?.selectedOptions?.[0]; if(!form||!option)return; const guru=option.dataset.guru; const kelas=option.dataset.kelas; if(kelas) form.kelas_id.value=kelas; if(guru) form.guru_id.value=guru; }
function renderJadwalTable(items){ const tbody=document.querySelector('[data-jadwal-table]'); if(!tbody)return; if(!items.length){tbody.innerHTML='<tr><td colspan="9" class="empty-state">Belum ada data jadwal.</td></tr>';return;} tbody.innerHTML=items.map((item,i)=>`<tr><td>${i+1}</td><td><strong>${escapeHtml(item.hari)}</strong></td><td>${escapeHtml(item.jam_mulai)} - ${escapeHtml(item.jam_selesai)}</td><td>${escapeHtml(item.nama_kelas)}</td><td>${escapeHtml(item.nama_mapel)}</td><td>${escapeHtml(item.nama_guru)}</td><td>${escapeHtml(item.ruangan||'-')}</td><td>${statusBadge(item.status)}</td><td><div class="action-group"><button class="btn btn-small btn-secondary" data-edit-id="${item.id}">Edit</button><button class="btn btn-small btn-danger" data-delete-id="${item.id}" ${item.status==='inactive'?'disabled':''}>Nonaktifkan</button></div></td></tr>`).join(''); }
async function loadJadwal(){ const loading=document.querySelector('[data-loading]'); const params=new URLSearchParams(); const search=document.querySelector('[data-search]')?.value||''; const status=document.querySelector('[data-status-filter]')?.value||''; const hari=document.querySelector('[data-hari-filter]')?.value||''; if(search)params.set('search',search); if(status)params.set('status',status); if(hari)params.set('hari',hari); try{ if(window.authReady) await window.authReady; if(loading)loading.hidden=false; const q=params.toString()?`?${params}`:''; const res=await apiRequest(`/admin/jadwal.php${q}`); jadwalState.items=res.data||[]; renderJadwalTable(jadwalState.items); if(loading)loading.hidden=true; }catch(error){ if(loading) loading.textContent=error.message||'Data jadwal gagal dimuat.'; renderJadwalTable([]); }}
function openModal(mode='create', item=null){ const modal=document.querySelector('[data-modal]'); const form=document.querySelector('[data-jadwal-form]'); const title=document.querySelector('[data-modal-title]'); if(!modal||!form)return; form.reset(); form.action.value=mode; form.id.value=item?.id||''; if(title) title.textContent=mode==='create'?'Tambah Jadwal':'Edit Jadwal'; if(item){ form.hari.value=item.hari||'Senin'; form.kelas_id.value=item.kelas_id||''; form.mapel_id.value=item.mapel_id||''; form.guru_id.value=item.guru_id||''; form.jam_mulai.value=(item.jam_mulai||'').slice(0,5); form.jam_selesai.value=(item.jam_selesai||'').slice(0,5); form.ruangan.value=item.ruangan||''; form.status.value=item.status||'active'; } modal.hidden=false; }
async function submitJadwal(e){ e.preventDefault(); const payload=Object.fromEntries(new FormData(e.currentTarget).entries()); try{ setSaveLoading(true); const res=await apiRequest('/admin/jadwal.php',{method:'POST',body:payload}); setPageAlert(res.message,'success'); closeModal(); await loadJadwal(); }catch(error){ setPageAlert(firstError(error)||error.message||'Data jadwal gagal disimpan.'); }finally{ setSaveLoading(false); }}
async function deleteJadwal(id){ if(!confirm('Nonaktifkan jadwal pelajaran ini?'))return; try{ const res=await apiRequest('/admin/jadwal.php',{method:'POST',body:{action:'delete',id}}); setPageAlert(res.message,'success'); await loadJadwal(); }catch(error){ setPageAlert(error.message||'Jadwal gagal dinonaktifkan.'); }}
document.addEventListener('DOMContentLoaded', async()=>{ if(document.body.dataset.page!=='admin-jadwal')return; await loadOptions().catch(()=>setPageAlert('Pilihan kelas/mapel/guru gagal dimuat.')); loadJadwal(); document.querySelector('[data-open-create]')?.addEventListener('click',()=>openModal('create')); document.querySelector('[data-refresh]')?.addEventListener('click',loadJadwal); document.querySelector('[data-jadwal-form]')?.addEventListener('submit',submitJadwal); document.querySelector('[data-mapel-options]')?.addEventListener('change',syncFromMapel); document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',closeModal)); document.querySelector('[data-search]')?.addEventListener('input',()=>{clearTimeout(jadwalState.searchTimer); jadwalState.searchTimer=setTimeout(loadJadwal,350);}); document.querySelector('[data-status-filter]')?.addEventListener('change',loadJadwal); document.querySelector('[data-hari-filter]')?.addEventListener('change',loadJadwal); document.querySelector('[data-jadwal-table]')?.addEventListener('click',e=>{ const edit=e.target.closest('[data-edit-id]'); const del=e.target.closest('[data-delete-id]'); if(edit){ const item=jadwalState.items.find(x=>Number(x.id)===Number(edit.dataset.editId)); if(item) openModal('update',item);} if(del) deleteJadwal(del.dataset.deleteId); }); });


document.addEventListener('DOMContentLoaded', async()=>{
  if(!['guru-jadwal','murid-jadwal'].includes(document.body.dataset.page)) return;
  if(window.authReady) await window.authReady;
  const role=document.body.dataset.role; const tbody=document.querySelector('[data-table]');
  try{
    const data=await loadData(`/${role}/jadwal.php`);
    if(!data.length){ renderEmpty(tbody,8,'Belum ada jadwal.'); return; }
    tbody.innerHTML=data.map((j,i)=>`<tr><td>${i+1}</td><td>${esc(j.hari)}</td><td>${esc(j.jam_mulai)}-${esc(j.jam_selesai)}</td><td>${esc(j.nama_kelas)}</td><td>${esc(j.nama_mapel)}</td><td>${esc(j.nama_guru)}</td><td>${esc(j.ruangan||'-')}</td><td>${badge(j.status,statusType(j.status))}</td></tr>`).join('');
  }catch(e){ alertPage(e.message||'Jadwal gagal dimuat.'); }
});
