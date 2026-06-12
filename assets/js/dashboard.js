

'use strict';


const Schema = {
  string(v, min = 1, max = 200) {
    if (typeof v !== 'string') return 'Must be a string';
    const t = v.trim();
    if (t.length < min) return `Minimum ${min} character${min > 1 ? 's' : ''} required`;
    if (t.length > max) return `Maximum ${max} characters allowed`;
    return null;
  },
  float(v, lo, hi) {
    const n = parseFloat(v);
    if (isNaN(n)) return 'Must be a valid number';
    if (lo !== undefined && n < lo) return `Minimum value is ${lo}`;
    if (hi !== undefined && n > hi) return `Maximum value is ${hi}`;
    return null;
  },
  int(v, lo, hi) {
    const n = parseInt(v, 10);
    if (isNaN(n) || String(n) !== String(v).trim()) return 'Must be a whole number';
    if (lo !== undefined && n < lo) return `Minimum is ${lo}`;
    if (hi !== undefined && n > hi) return `Maximum is ${hi}`;
    return null;
  },
  enum(v, options) {
    if (!options.includes(v)) return `Must be one of: ${options.join(', ')}`;
    return null;
  },
  sanitize(str) {
    return String(str).replace(/<[^>]*>/g, '').trim();
  }
};


const AuditTrail = {
  _log: [],
  record(action, payload, userId, userName) {
    const entry = {
      action,
      actorId:   userId,
      actorName: userName,
      timestamp: new Date().toISOString(),
      delta:     JSON.stringify(payload).slice(0, 300),
    };
    this._log.unshift(entry);
    if (this._log.length > 50) this._log.pop();
    return entry;
  },
  getAll() { return [...this._log]; },
  formatTs(iso) {
    return new Date(iso).toLocaleString('en-GB', {
      day:'2-digit', month:'short', year:'numeric',
      hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
  }
};


const Toast = {
  wrap: null,
  init() { this.wrap = document.getElementById('toastWrap'); },
  show(msg, type = 'info', duration = 3500) {
    const icons = { success: 'OK', error: 'ERR', info: 'INFO' };
    const t = document.createElement('div');
    t.className = `dash-toast ${type}`;
    t.innerHTML = `
      <span class="toast-icon">${icons[type] ?? 'INFO'}</span>
      <span class="toast-msg">${msg}</span>
      <button class="toast-close" aria-label="Dismiss">x</button>`;
    t.querySelector('.toast-close').addEventListener('click', () => this._remove(t));
    this.wrap.appendChild(t);
    setTimeout(() => this._remove(t), duration);
  },
  _remove(el) {
    el.classList.add('removing');
    el.addEventListener('animationend', () => el.remove(), { once: true });
  }
};


const Confirm = {
  overlay: null,
  okBtn:   null,
  _resolve: null,
  init() {
    this.overlay = document.getElementById('confirmOverlay');
    this.okBtn   = document.getElementById('confirmOk');
    document.getElementById('confirmCancel').addEventListener('click', () => this._close(false));
    this.okBtn.addEventListener('click', () => this._close(true));
    this.overlay.addEventListener('click', e => { if (e.target === this.overlay) this._close(false); });
  },
  ask(title, text) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmText').textContent  = text;
    this.overlay.classList.add('open');
    return new Promise(res => { this._resolve = res; });
  },
  _close(val) {
    this.overlay.classList.remove('open');
    if (this._resolve) { this._resolve(val); this._resolve = null; }
  }
};


const API = {
  base: '../api.php?action=',
  async request(action, method = 'GET', body = null) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(this.base + action, opts);
    const json = await res.json().catch(() => ({ success: false, message: 'Invalid server response' }));
    return json;
  },
  getIncidents()       { return this.request('get_incidents'); },
  createIncident(data) { return this.request('create_incident', 'POST', data); },
  updateIncident(id, data) { return this.request(`update_incident&id=${id}`, 'POST', data); },
  deleteIncident(id)   { return this.request(`delete_incident&id=${id}`, 'POST'); },
};


const State = {
  incidents:    [],
  filtered:     [],
  selected:     new Set(),
  activeId:     null,
  filterStatus: 'all',
  filterType:   'all',
  searchQuery:  '',
  sortCol:      'created_at',
  sortDir:      'desc',
  drawerMode:   null,
  editId:       null,
  drawerStep:   1,
  formData:     {},
};


const SEV_COLORS = ['','#3fb950','#d2a624','#d29922','#f0883e','#f85149'];
const SEV_LABELS = ['','Low','Guarded','Elevated','High','Critical'];

function renderSevPips(sev) {
  let html = '<div class="dash-sev-bar">';
  for (let i = 1; i <= 5; i++) {
    const fill = i <= sev ? SEV_COLORS[sev] : '';
    html += `<div class="dash-sev-pip" style="${fill ? `background:${fill}` : ''}"></div>`;
  }
  html += '</div>';
  return html;
}


function applyFilters() {
  let list = [...State.incidents];

  if (State.searchQuery) {
    const q = State.searchQuery.toLowerCase();
    list = list.filter(i =>
      i.title.toLowerCase().includes(q) ||
      i.description?.toLowerCase().includes(q) ||
      String(i.id).includes(q)
    );
  }
  if (State.filterStatus !== 'all') list = list.filter(i => i.status === State.filterStatus);
  if (State.filterType   !== 'all') list = list.filter(i => i.incident_type === State.filterType);
  list.sort((a, b) => {
    let av = a[State.sortCol], bv = b[State.sortCol];
    if (typeof av === 'string') av = av.toLowerCase(), bv = bv.toLowerCase();
    if (av < bv) return State.sortDir === 'asc' ? -1 : 1;
    if (av > bv) return State.sortDir === 'asc' ?  1 : -1;
    return 0;
  });

  State.filtered = list;
}

function renderTable() {
  applyFilters();
  const tbody = document.getElementById('tableBody');
  const emptyState = document.getElementById('tableEmpty');
  const total  = State.incidents.length;
  const active = State.incidents.filter(i => i.status === 'active').length;
  const disp   = State.incidents.filter(i => i.status === 'dispatched').length;
  const res    = State.incidents.filter(i => i.status === 'resolved').length;
  document.getElementById('statTotal').textContent  = total;
  document.getElementById('statActive').textContent = active;
  document.getElementById('statDisp').textContent   = disp;
  document.getElementById('statRes').textContent    = res;
  document.getElementById('toolbarCount').textContent = State.filtered.length;

  if (State.filtered.length === 0) {
    tbody.innerHTML = '';
    emptyState.hidden = false;
    return;
  }
  emptyState.hidden = true;

  tbody.innerHTML = State.filtered.map(inc => {
    const checked  = State.selected.has(inc.id);
    const isActive = State.activeId === inc.id;
    const typeIcons = { police: '', fire: '', medical: '' };
    const statusLabel = { active: 'Active', dispatched: 'Dispatched', resolved: 'Resolved' };
    const lat = parseFloat(inc.latitude).toFixed(6);
    const lng = parseFloat(inc.longitude).toFixed(6);
    const date = new Date(inc.created_at).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'2-digit' });

    return `
    <tr data-id="${inc.id}" class="${isActive ? 'selected' : ''}"
        role="row" tabindex="0"
        aria-selected="${isActive}">
      <td class="dash-cb-cell">
        <input type="checkbox" class="dash-cb row-cb" data-id="${inc.id}"
               aria-label="Select incident ${inc.id}"
               ${checked ? 'checked' : ''}>
      </td>
      <td class="numeric">#${String(inc.id).padStart(4,'0')}</td>
      <td>
        <div style="font-weight:500;max-width:180px;overflow:hidden;text-overflow:ellipsis">${escHtml(inc.title)}</div>
      </td>
      <td>
        <span class="dash-type-pill ${inc.incident_type}">
          ${typeIcons[inc.incident_type] ?? ''} ${inc.incident_type}
        </span>
      </td>
      <td>
        <div class="dash-sev">${renderSevPips(inc.severity)}</div>
      </td>
      <td>
        <span class="dash-status-pill ${inc.status}">${statusLabel[inc.status] ?? inc.status}</span>
      </td>
      <td class="numeric">${lat}, ${lng}</td>
      <td class="numeric">${date}</td>
      <td>
        <div class="dash-row-actions">
          <button class="dash-row-btn edit-btn" data-id="${inc.id}" aria-label="Edit incident ${inc.id}">Edit</button>
          <button class="dash-row-btn del del-btn" data-id="${inc.id}" aria-label="Delete incident ${inc.id}">Del</button>
        </div>
      </td>
    </tr>`;
  }).join('');
  tbody.querySelectorAll('tr[data-id]').forEach(tr => {
    const id = parseInt(tr.dataset.id, 10);

    tr.addEventListener('click', e => {
      if (e.target.closest('.dash-cb-cell') || e.target.closest('.dash-row-actions')) return;
      openDetail(id);
    });

    tr.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDetail(id); }
    });

    tr.querySelector('.row-cb')?.addEventListener('change', e => {
      e.stopPropagation();
      if (e.target.checked) State.selected.add(id); else State.selected.delete(id);
      updateBulkBar();
      tr.classList.toggle('highlighted', e.target.checked);
    });

    tr.querySelector('.edit-btn')?.addEventListener('click', e => {
      e.stopPropagation();
      openDrawer('edit', id);
    });

    tr.querySelector('.del-btn')?.addEventListener('click', e => {
      e.stopPropagation();
      deleteSingle(id);
    });
  });
  State.selected.forEach(id => {
    const tr = tbody.querySelector(`tr[data-id="${id}"]`);
    if (tr) tr.classList.add('highlighted');
  });
}


function updateBulkBar() {
  const bar    = document.getElementById('bulkBar');
  const normal = document.getElementById('normalToolbar');
  const hdr    = document.getElementById('masterCb');
  const count  = State.selected.size;

  if (count > 0) {
    bar.classList.add('visible');
    normal.style.display = 'none';
    document.getElementById('bulkCount').textContent = `${count} incident${count > 1 ? 's' : ''} selected`;
  } else {
    bar.classList.remove('visible');
    normal.style.display = '';
  }

  const all = State.filtered.length > 0 && State.selected.size === State.filtered.length;
  if (hdr) hdr.checked = all;
}


function openDetail(id) {
  const inc = State.incidents.find(i => i.id === id);
  if (!inc) return;
  State.activeId = id;
  renderTable();

  const body = document.getElementById('detailBody');
  const lat  = parseFloat(inc.latitude).toFixed(6);
  const lng  = parseFloat(inc.longitude).toFixed(6);
  const typeIcons = { police: '', fire: '', medical: '' };
  const statusLabel = { active: 'Active', dispatched: 'Dispatched', resolved: 'Resolved' };
  const createdAt = new Date(inc.created_at).toLocaleString('en-GB', { dateStyle:'medium', timeStyle:'short' });
  const updatedAt = new Date(inc.updated_at).toLocaleString('en-GB', { dateStyle:'medium', timeStyle:'short' });
  const mapUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${parseFloat(inc.longitude)-.005},${parseFloat(inc.latitude)-.005},${parseFloat(inc.longitude)+.005},${parseFloat(inc.latitude)+.005}&layer=mapnik&marker=${inc.latitude},${inc.longitude}`;
  const auditEntries = AuditTrail.getAll().filter(e => e.delta.includes(`"id":${id}`) || (e.action === 'CREATE' && e.delta.includes(inc.title?.slice(0,10))));
  const lastAudit    = AuditTrail.getAll().find(e => e.delta.includes(`"id":${id}`));

  body.innerHTML = `
    <div class="dash-map-wrap">
      <iframe src="${mapUrl}" title="Incident location on map" loading="lazy"></iframe>
    </div>

    <div class="dash-detail-field">
      <div class="dash-detail-label">Title</div>
      <div class="dash-detail-value" style="font-weight:600">${escHtml(inc.title)}</div>
    </div>
    <div class="dash-detail-field">
      <div class="dash-detail-label">Description</div>
      <div class="dash-detail-value" style="color:var(--txt-secondary)">${escHtml(inc.description || '-')}</div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="dash-detail-field">
        <div class="dash-detail-label">Type</div>
        <div class="dash-detail-value">
          <span class="dash-type-pill ${inc.incident_type}">
            ${typeIcons[inc.incident_type] ?? ''} ${inc.incident_type}
          </span>
        </div>
      </div>
      <div class="dash-detail-field">
        <div class="dash-detail-label">Status</div>
        <div class="dash-detail-value">
          <span class="dash-status-pill ${inc.status}">${statusLabel[inc.status]}</span>
        </div>
      </div>
      <div class="dash-detail-field">
        <div class="dash-detail-label">Severity</div>
        <div class="dash-detail-value">
          ${renderSevPips(inc.severity)}
          <span style="font-size:11px;color:var(--txt-secondary);margin-top:3px;display:block">
            Level ${inc.severity} - ${SEV_LABELS[inc.severity]}
          </span>
        </div>
      </div>
      <div class="dash-detail-field">
        <div class="dash-detail-label">Incident ID</div>
        <div class="dash-detail-value mono">#${String(inc.id).padStart(4,'0')}</div>
      </div>
    </div>

    <div class="dash-detail-field">
      <div class="dash-detail-label">Coordinates</div>
      <div class="dash-detail-value mono">${lat}, ${lng}</div>
    </div>

    ${inc.dispatch_unit ? `
    <div class="dash-detail-field">
      <div class="dash-detail-label">Dispatch</div>
      <div class="dash-detail-value">
        <span class="dash-type-pill ${inc.dispatch_unit}">${typeIcons[inc.dispatch_unit] ?? ''} ${inc.dispatch_unit}</span>
        <span style="font-size:11px;color:var(--txt-muted);margin-left:6px">${inc.dispatch_status ?? ''}</span>
      </div>
    </div>` : ''}

    <div class="dash-detail-meta">
      <div class="dash-detail-meta-title">Audit Metadata</div>
      <div class="dash-detail-meta-row">
        <span>Created</span><span>${createdAt}</span>
      </div>
      <div class="dash-detail-meta-row">
        <span>Last modified</span><span>${updatedAt}</span>
      </div>
      ${lastAudit ? `
      <div class="dash-detail-meta-row">
        <span>Modified by</span><span>${escHtml(lastAudit.actorName)}</span>
      </div>
      <div class="dash-detail-meta-row">
        <span>Audit action</span><span>${lastAudit.action}</span>
      </div>` : ''}
    </div>

    <div class="dash-detail-actions">
      <button class="dash-detail-btn" id="detailEditBtn" aria-label="Edit this incident">Edit</button>
      <button class="dash-detail-btn danger" id="detailDelBtn" aria-label="Delete this incident">Delete</button>
    </div>`;

  document.getElementById('detailHeader').textContent = `Incident #${String(id).padStart(4,'0')}`;
  document.getElementById('detailEditBtn').addEventListener('click', () => openDrawer('edit', id));
  document.getElementById('detailDelBtn').addEventListener('click', () => deleteSingle(id));
}

function closeDetail() {
  State.activeId = null;
  renderTable();
  document.getElementById('detailHeader').textContent = 'Incident Detail';
  document.getElementById('detailBody').innerHTML = `
    <div class="dash-detail-placeholder">
      <div class="dash-detail-placeholder-icon"></div>
      <p>Select a row to view incident details and its location on the map.</p>
    </div>`;
}


function openDrawer(mode, id = null) {
  State.drawerMode = mode;
  State.editId     = id;
  State.drawerStep = 1;
  State.formData   = {};

  const title = document.getElementById('drawerTitle');
  title.textContent = mode === 'create' ? 'New Incident' : `Edit Incident #${String(id).padStart(4,'0')}`;
  document.querySelectorAll('.dform-input, .dform-textarea, .dform-select').forEach(el => {
    el.classList.remove('error');
  });
  document.querySelectorAll('.dform-error').forEach(el => el.textContent = '');

  if (mode === 'edit' && id) {
    const inc = State.incidents.find(i => i.id === id);
    if (inc) prefillForm(inc);
  } else {
    clearForm();
  }

  goToStep(1);
  document.getElementById('drawerOverlay').classList.add('open');
  setTimeout(() => document.getElementById('f-title')?.focus(), 320);
}

function closeDrawer() {
  document.getElementById('drawerOverlay').classList.remove('open');
  State.drawerMode = null;
  State.editId     = null;
}

function prefillForm(inc) {
  setVal('f-title',       inc.title);
  setVal('f-description', inc.description || '');
  setVal('f-status',      inc.status);
  setVal('f-latitude',    inc.latitude);
  setVal('f-longitude',   inc.longitude);
  setVal('f-notes',       '');
  selectSeverity(inc.severity);
  selectType(inc.incident_type);
}

function clearForm() {
  ['f-title','f-description','f-status','f-latitude','f-longitude','f-notes'].forEach(id => setVal(id, ''));
  selectSeverity(null);
  selectType(null);
}

function setVal(id, v) {
  const el = document.getElementById(id);
  if (el) el.value = v ?? '';
}

function selectSeverity(sev) {
  document.querySelectorAll('.dform-sev-btn').forEach(btn => {
    const n = parseInt(btn.dataset.sev, 10);
    btn.className = 'dform-sev-btn' + (n === sev ? ` sel-${sev}` : '');
  });
}

function selectType(type) {
  document.querySelectorAll('.dform-type-label').forEach(lbl => {
    const val = lbl.querySelector('input')?.value;
    lbl.className = 'dform-type-label' + (val === type ? ` ${type}-sel` : '');
    if (lbl.querySelector('input')) lbl.querySelector('input').checked = val === type;
  });
}

function goToStep(step) {
  State.drawerStep = step;

  document.querySelectorAll('.dash-step').forEach(el => {
    const n = parseInt(el.dataset.step, 10);
    el.className = 'dash-step' + (n < step ? ' done' : n === step ? ' active' : '');
  });

  document.querySelectorAll('.dash-step-panel').forEach(el => {
    el.classList.toggle('active', parseInt(el.dataset.step, 10) === step);
  });

  const prevBtn = document.getElementById('drawerPrev');
  const nextBtn = document.getElementById('drawerNext');
  const saveBtn = document.getElementById('drawerSave');

  prevBtn.style.display = step > 1 ? '' : 'none';
  nextBtn.style.display = step < 3 ? '' : 'none';
  saveBtn.style.display = step === 3 ? '' : 'none';
  if (step === 3) renderAuditPreview();
}

function renderAuditPreview() {
  const box = document.getElementById('auditPreviewBox');
  if (!box) return;
  const userId   = window.__ADMIN_ID   ?? '-';
  const userName = window.__ADMIN_NAME ?? '-';
  const now      = new Date().toLocaleString('en-GB', { dateStyle:'medium', timeStyle:'short' });
  const action   = State.drawerMode === 'create' ? 'CREATE' : 'UPDATE';
  box.innerHTML = `
    <div class="dash-audit-box-title">Audit Trail Preview</div>
    <div class="dash-audit-row"><span>Action</span><span>${action}</span></div>
    <div class="dash-audit-row"><span>Actor ID</span><span>${userId}</span></div>
    <div class="dash-audit-row"><span>Actor</span><span>${escHtml(userName)}</span></div>
    <div class="dash-audit-row"><span>Timestamp</span><span>${now}</span></div>
    <div class="dash-audit-row"><span>Target ID</span><span>${State.editId ?? 'pending'}</span></div>`;
}


function validateStep(step) {
  let valid = true;

  function fieldErr(id, msg) {
    const el  = document.getElementById(id);
    const err = document.getElementById(id + '-err');
    if (msg) { el?.classList.add('error'); if (err) err.textContent = msg; valid = false; }
    else      { el?.classList.remove('error'); if (err) err.textContent = ''; }
  }

  if (step === 1) {
    fieldErr('f-title',  Schema.string(document.getElementById('f-title')?.value, 3, 200));
    const activeSev = document.querySelector('.dform-sev-btn[class*="sel-"]');
    if (!activeSev) { document.getElementById('sev-err').textContent = 'Select a severity level'; valid = false; }
    else            { document.getElementById('sev-err').textContent = ''; }
    const activeType = document.querySelector('input[name="f-type"]:checked');
    if (!activeType) { document.getElementById('type-err').textContent = 'Select an incident type'; valid = false; }
    else             { document.getElementById('type-err').textContent = ''; }
    const status = document.getElementById('f-status')?.value;
    if (Schema.enum(status, ['active','dispatched','resolved'])) {
      fieldErr('f-status', 'Select a valid status');
    }
  }

  if (step === 2) {
    fieldErr('f-latitude',  Schema.float(document.getElementById('f-latitude')?.value,  -90,  90));
    fieldErr('f-longitude', Schema.float(document.getElementById('f-longitude')?.value, -180, 180));
  }

  return valid;
}


function collectPayload() {
  const sev  = document.querySelector('.dform-sev-btn[class*="sel-"]');
  const type = document.querySelector('input[name="f-type"]:checked');
  return {
    title:         Schema.sanitize(document.getElementById('f-title')?.value ?? ''),
    description:   Schema.sanitize(document.getElementById('f-description')?.value ?? ''),
    severity:      sev  ? parseInt(sev.dataset.sev, 10)  : null,
    incident_type: type ? type.value : null,
    status:        document.getElementById('f-status')?.value ?? 'active',
    latitude:      parseFloat(document.getElementById('f-latitude')?.value),
    longitude:     parseFloat(document.getElementById('f-longitude')?.value),
    notes:         Schema.sanitize(document.getElementById('f-notes')?.value ?? ''),
  };
}


async function saveDrawer() {
  const payload  = collectPayload();
  const saveBtn  = document.getElementById('drawerSave');
  const userId   = window.__ADMIN_ID   ?? 0;
  const userName = window.__ADMIN_NAME ?? 'Admin';

  saveBtn.disabled = true;
  saveBtn.innerHTML = '<span class="btn-spinner"></span> Saving...';

  try {
    let res;
    if (State.drawerMode === 'create') {
      res = await API.createIncident(payload);
      if (res.success) {
        AuditTrail.record('CREATE', { ...payload, id: res.incident_id }, userId, userName);
        Toast.show('Incident created successfully', 'success');
      }
    } else {
      res = await API.updateIncident(State.editId, payload);
      if (res.success) {
        AuditTrail.record('UPDATE', { ...payload, id: State.editId }, userId, userName);
        Toast.show('Incident updated successfully', 'success');
      }
    }

    if (!res.success) {
      Toast.show(res.message ?? 'Operation failed', 'error');
    } else {
      closeDrawer();
      await loadIncidents();
      if (State.drawerMode === 'edit' && State.editId) openDetail(State.editId);
    }
  } catch (err) {
    Toast.show('Network error. Please try again.', 'error');
    console.error('[Dashboard] save error:', err);
  } finally {
    saveBtn.disabled = false;
    saveBtn.innerHTML = 'Save Incident';
  }
}


async function deleteSingle(id) {
  const inc = State.incidents.find(i => i.id === id);
  const ok  = await Confirm.ask(
    'Delete Incident',
    `Are you sure you want to permanently delete "${inc?.title ?? `#${id}`}"? This cannot be undone.`
  );
  if (!ok) return;

  const res = await API.deleteIncident(id);
  if (res.success) {
    AuditTrail.record('DELETE', { id }, window.__ADMIN_ID ?? 0, window.__ADMIN_NAME ?? 'Admin');
    Toast.show('Incident deleted', 'success');
    State.selected.delete(id);
    if (State.activeId === id) closeDetail();
    await loadIncidents();
  } else {
    Toast.show(res.message ?? 'Delete failed', 'error');
  }
}


async function bulkDelete() {
  const ids = [...State.selected];
  if (!ids.length) return;
  const ok = await Confirm.ask('Bulk Delete', `Permanently delete ${ids.length} selected incident${ids.length > 1 ? 's' : ''}?`);
  if (!ok) return;

  let failed = 0;
  for (const id of ids) {
    const res = await API.deleteIncident(id);
    if (res.success) {
      AuditTrail.record('DELETE', { id }, window.__ADMIN_ID ?? 0, window.__ADMIN_NAME ?? 'Admin');
    } else { failed++; }
  }

  State.selected.clear();
  if (failed > 0) Toast.show(`${failed} deletion${failed > 1 ? 's' : ''} failed`, 'error');
  else Toast.show(`${ids.length} incidents deleted`, 'success');
  closeDetail();
  await loadIncidents();
}

async function bulkResolve() {
  const ids = [...State.selected];
  if (!ids.length) return;
  let done = 0;
  for (const id of ids) {
    const inc = State.incidents.find(i => i.id === id);
    if (!inc || inc.status === 'resolved') continue;
    const res = await API.updateIncident(id, { ...inc, status: 'resolved' });
    if (res.success) {
      AuditTrail.record('UPDATE', { id, status: 'resolved' }, window.__ADMIN_ID ?? 0, window.__ADMIN_NAME ?? 'Admin');
      done++;
    }
  }
  State.selected.clear();
  Toast.show(`${done} incident${done !== 1 ? 's' : ''} resolved`, 'success');
  await loadIncidents();
}

function bulkExportCSV() {
  const ids  = [...State.selected];
  const rows = ids.length > 0
    ? State.incidents.filter(i => ids.includes(i.id))
    : State.filtered;
  if (!rows.length) { Toast.show('Nothing to export', 'info'); return; }

  const headers = ['ID','Title','Type','Severity','Status','Latitude','Longitude','Created'];
  const csv = [
    headers.join(','),
    ...rows.map(i => [
      i.id,
      `"${(i.title ?? '').replace(/"/g,'""')}"`,
      i.incident_type,
      i.severity,
      i.status,
      i.latitude,
      i.longitude,
      i.created_at,
    ].join(','))
  ].join('\n');

  const blob = new Blob([csv], { type: 'text/csv' });
  const a    = Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(blob),
    download: `incidents_export_${Date.now()}.csv`
  });
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(a.href);
  Toast.show(`Exported ${rows.length} records`, 'success');
}


async function loadIncidents() {
  try {
    const res = await API.getIncidents();
    if (res.success) {
      State.incidents = res.data ?? [];
      renderTable();
    } else {
      Toast.show('Failed to load incidents', 'error');
    }
  } catch (err) {
    Toast.show('Network error loading incidents', 'error');
    console.error('[Dashboard] load error:', err);
  }
}


function bindSortHeaders() {
  document.querySelectorAll('.dash-table thead th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (State.sortCol === col) {
        State.sortDir = State.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        State.sortCol = col;
        State.sortDir = 'asc';
      }
      document.querySelectorAll('.dash-table thead th').forEach(h => h.classList.remove('sorted'));
      th.classList.add('sorted');
      th.querySelector('.sort-icon').textContent = State.sortDir === 'asc' ? 'asc' : 'desc';
      renderTable();
    });
  });
}


function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}


document.addEventListener('DOMContentLoaded', async () => {

  Toast.init();
  Confirm.init();

  
  document.getElementById('searchInput').addEventListener('input', e => {
    State.searchQuery = e.target.value;
    renderTable();
  });

  
  document.querySelectorAll('.dash-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.group;
      const val   = btn.dataset.val;
      document.querySelectorAll(`.dash-filter-btn[data-group="${group}"]`)
        .forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      if (group === 'status') State.filterStatus = val;
      if (group === 'type')   State.filterType   = val;
      renderTable();
    });
  });

  
  document.getElementById('masterCb').addEventListener('change', e => {
    if (e.target.checked) State.filtered.forEach(i => State.selected.add(i.id));
    else State.selected.clear();
    renderTable();
    updateBulkBar();
  });

  
  document.getElementById('addBtn').addEventListener('click', () => openDrawer('create'));

  
  document.getElementById('bulkDeleteBtn').addEventListener('click',  bulkDelete);
  document.getElementById('bulkResolveBtn').addEventListener('click', bulkResolve);
  document.getElementById('bulkExportBtn').addEventListener('click',  bulkExportCSV);
  document.getElementById('bulkClearBtn').addEventListener('click', () => {
    State.selected.clear();
    updateBulkBar();
    renderTable();
  });

  
  document.getElementById('exportAllBtn')?.addEventListener('click', () => {
    State.selected.clear();
    bulkExportCSV();
  });

  
  document.getElementById('detailCloseBtn').addEventListener('click', closeDetail);

  
  document.getElementById('drawerOverlay').addEventListener('click', e => {
    if (e.target === document.getElementById('drawerOverlay')) closeDrawer();
  });
  document.getElementById('drawerCloseBtn').addEventListener('click', closeDrawer);

  
  document.getElementById('drawerNext').addEventListener('click', () => {
    if (!validateStep(State.drawerStep)) return;
    if (State.drawerStep < 3) goToStep(State.drawerStep + 1);
  });
  document.getElementById('drawerPrev').addEventListener('click', () => {
    if (State.drawerStep > 1) goToStep(State.drawerStep - 1);
  });
  document.getElementById('drawerSave').addEventListener('click', saveDrawer);

  
  document.querySelectorAll('.dform-sev-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const sev = parseInt(btn.dataset.sev, 10);
      selectSeverity(sev);
      document.getElementById('sev-err').textContent = '';
    });
  });

  
  document.querySelectorAll('.dform-type-label').forEach(lbl => {
    lbl.addEventListener('click', () => {
      const val = lbl.querySelector('input')?.value;
      selectType(val);
      document.getElementById('type-err').textContent = '';
    });
  });

  
  bindSortHeaders();

  
  await loadIncidents();

  
  setInterval(async () => {
    if (!document.hidden) await loadIncidents();
  }, 30000);
});

