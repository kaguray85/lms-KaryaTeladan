function esc(value){return String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');}
function fileLink(path,label='Download'){return path ? `<a class="link" target="_blank" href="${APP_BASE}/${esc(path)}">${label}</a>` : '-';}
function badge(text,type='secondary'){return `<span class="badge badge-${type}">${esc(text)}</span>`;}
function statusType(status){return {active:'success',inactive:'danger',Hadir:'success',Izin:'warning',Sakit:'secondary',Alpha:'danger','Sudah dikumpulkan':'warning','Sudah dinilai':'success','Belum dikerjakan':'danger'}[status] || 'secondary';}
function alertPage(message,type='error'){const el=document.querySelector('[data-alert]'); if(!el)return; el.textContent=message; el.className=`alert alert-${type}`; el.hidden=false;}
async function loadData(endpoint){const res=await apiRequest(endpoint); return res.data || [];}
function renderEmpty(tbody,colspan,msg='Belum ada data.'){tbody.innerHTML=`<tr><td colspan="${colspan}" class="empty-state">${msg}</td></tr>`;}
async function fillSelect(selector, endpoint, mapper){const select=document.querySelector(selector); if(!select)return; const data=await loadData(endpoint); select.innerHTML='<option value="">Pilih data</option>'+data.map(mapper).join('');}
function formDataObj(form){return Object.fromEntries(new FormData(form).entries());}
function fdWithAction(form, action){const fd=new FormData(form); if(action) fd.set('action',action); return fd;}
