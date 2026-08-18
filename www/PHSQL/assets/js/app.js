/* PHSQL — shell: boot, connection manager, sidebar, routing, shortcuts, migrations */
'use strict';

/* ═══════════ schema normalization ═══════════ */
function normalizeSchema(rawTables) {
  return rawTables.map(t => ({
    name: t.name, comment: t.comment || '', engine: t.engine || 'InnoDB',
    columns: t.columns.map(c => ({
      name: c.name, type: (c.type || 'VARCHAR').toUpperCase(), length: c.length,
      unsigned: !!c.unsigned, nullable: !!c.nullable,
      default: c.default === null || c.default === undefined ? null : String(c.default),
      ai: !!c.ai, comment: c.comment || '',
    })),
    pk: t.primaryKey || [],
    indexes: (t.indexes || []).filter(ix => ix.name !== 'PRIMARY'
      && !(t.foreignKeys || []).some(fk => (fk.columns || []).join(',') === ix.columns.join(','))),
    fks: (t.foreignKeys || []).map(fk => ({
      name: fk.name, columns: fk.columns, refTable: fk.refTable, refColumns: fk.refColumns,
      column: fk.columns?.[0], refColumn: fk.refColumns?.[0],
      onDelete: fk.onDelete === 'RESTRICT' ? 'NO ACTION' : fk.onDelete,
      onUpdate: fk.onUpdate === 'RESTRICT' ? 'NO ACTION' : fk.onUpdate,
    })),
    pos: null,
  }));
}

/* ═══════════ boot ═══════════ */
document.addEventListener('DOMContentLoaded', async () => {
  applyTheme(App.theme);
  bindChrome();
  await renderSavedConnections();
  termTyping();
  setTimeout(() => $('#splash').classList.add('gone'), 700);
});

function applyTheme(t) {
  App.theme = t;
  document.documentElement.dataset.theme = t;
  localStorage.setItem('phsql.theme', t);
}
function termTyping() {
  const cmds = ['CREATE TABLE users (…)', 'link FK users → orders', 'SELECT * FROM orders ⚡ 12ms', 'migrate --preview ✓'];
  const node = $('#term-type'); if (!node) return;
  let ci = 0, i = 0, del = false;
  (function tick() {
    const c = cmds[ci];
    node.textContent = c.slice(0, i);
    if (!del && i++ >= c.length) { del = true; return setTimeout(tick, 1600); }
    if (del && i-- <= 0) { del = false; ci = (ci + 1) % cmds.length; }
    setTimeout(tick, del ? 22 : 55);
  })();
}

/* ═══════════ connection screen ═══════════ */
async function renderSavedConnections() {
  const list = $('#conn-list');
  const saved = await Vault.load('connections', []);
  $('#saved-count').textContent = saved.length ? saved.length : '';
  list.innerHTML = '';
  if (!saved.length) { list.append(el('div', { class: 'saved-empty' }, 'No saved connections yet — create your first one below.')); return; }
  for (const c of saved) {
    list.append(el('div', { class: 'saved-item', onclick: () => fillConnForm(c) },
      el('span', { class: 'si-dot' }),
      el('div', {},
        el('div', { class: 'si-name' }, c.name || c.host),
        el('div', { class: 'si-meta' }, `${c.user}@${c.host}:${c.port}/${c.db}`)),
      el('button', { class: 'si-x', title: 'Remove', onclick: e => { e.stopPropagation(); removeConnection(c.id); } }, '✕')));
  }
}
function fillConnForm(c) {
  $('#f-name').value = c.name || ''; $('#f-host').value = c.host; $('#f-port').value = c.port;
  $('#f-user').value = c.user; $('#f-db').value = c.db;
  $('#f-pass').value = c.remember ? (c.pass || '') : '';
  $('#f-remember').checked = !!c.remember;
  $('#f-name').dataset.id = c.id || '';
}
async function removeConnection(id) {
  const saved = (await Vault.load('connections', [])).filter(c => c.id !== id);
  await Vault.save('connections', saved);
  renderSavedConnections();
  toast('Connection removed', 'info');
}
async function persistConnection(cfg) {
  const saved = await Vault.load('connections', []);
  const id = cfg.id || uid();
  const entry = { id, name: cfg.name, host: cfg.host, port: cfg.port, user: cfg.user, db: cfg.db,
    remember: cfg.remember, pass: cfg.remember ? cfg.pass : null };
  const i = saved.findIndex(c => c.id === id || (c.host === cfg.host && c.port === cfg.port && c.db === cfg.db && c.user === cfg.user));
  if (i >= 0) saved[i] = entry; else saved.unshift(entry);
  await Vault.save('connections', saved.slice(0, 12));
}
function formConn() {
  return {
    id: $('#f-name').dataset.id || null,
    name: $('#f-name').value.trim(), host: $('#f-host').value.trim(), port: +$('#f-port').value || 3306,
    user: $('#f-user').value.trim(), pass: $('#f-pass').value, db: $('#f-db').value.trim(),
    remember: $('#f-remember').checked,
  };
}

function bindChrome() {
  $('#conn-form').addEventListener('submit', async e => {
    e.preventDefault();
    const cfg = formConn();
    const btn = $('#btn-connect');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Connecting…';
    try {
      const r = await api('connect', cfg);
      App.version = r.version;
      await persistConnection(cfg);
      App.conn = cfg;
      await enterApp(cfg);
    } catch (err) { toast(err.message, 'err', 6000); }
    finally { btn.disabled = false; btn.textContent = 'Connect →'; }
  });
  $('#btn-test').addEventListener('click', async () => {
    try {
      const r = await api('connect', formConn());
      toast(`Connection OK — server v${r.version}`, 'ok');
    } catch (e) { toast(e.message, 'err', 6000); }
  });
  $('#btn-theme').addEventListener('click', () => applyTheme(App.theme === 'dark' ? 'light' : 'dark'));
  $('#btn-disconnect').addEventListener('click', async () => {
    await api('disconnect').catch(() => {});
    location.reload();
  });
  $$('.view-tab').forEach(b => b.addEventListener('click', () => switchView(b.dataset.view)));
  $('#side-search').addEventListener('input', debounce(e => renderSidebar(e.target.value.trim().toLowerCase()), 150));
  $('#btn-refresh').addEventListener('click', () => refreshSchema(true));
  $('#btn-new-table').addEventListener('click', addTable);
  $('#btn-migrate').addEventListener('click', openMigration);
  $('#btn-schema-menu').addEventListener('click', e => {
    const r = e.currentTarget.getBoundingClientRect();
    contextMenu(r.left, r.bottom + 6, [
      { label: '⇧ Export schema JSON', onClick: exportSchemaJSON },
      { label: '⇩ Import schema JSON…', onClick: () => $('#file-import').click() },
      { label: '⇩ Export SQL dump', onClick: exportSQLDump },
      '-',
      { label: '⌨ Keyboard shortcuts', onClick: showHelp },
    ]);
  });
  $('#file-import').addEventListener('change', importSchemaJSON);
  $('#btn-side').addEventListener('click', () => { $('#sidebar').classList.toggle('open'); $('#side-backdrop').hidden = !$('#sidebar').classList.contains('open'); });
  $('#btn-insp')?.addEventListener('click', () => $('#inspector').classList.toggle('open'));
  $('#side-backdrop').addEventListener('click', () => $('#sidebar').classList.remove('open'));
  $('#insp-backdrop').addEventListener('click', () => $('#inspector').classList.remove('open'));

  /* global shortcuts */
  document.addEventListener('keydown', e => {
    if (e.target.matches('input,textarea,select') && !(e.altKey || e.ctrlKey)) return;
    if (e.altKey && ['1','2','3','4'].includes(e.key)) {
      e.preventDefault();
      switchView(['designer','erd','data','sql'][+e.key - 1]);
    }
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
      e.preventDefault();
      if (App.view === 'data') App.data.save();
      else openMigration();
    }
    if (e.altKey && e.key.toLowerCase() === 'l' && App.view === 'designer') { e.preventDefault(); App.designer.autoLayout(); }
    if (e.key === '/' && !e.target.matches('input,textarea')) { e.preventDefault(); $('#side-search').focus(); }
    if (e.key === '?' && !e.target.matches('input,textarea')) showHelp();
    if (e.altKey && e.key.toLowerCase() === 'n') { e.preventDefault(); addTable(); }
  });
}

/* ═══════════ enter app ═══════════ */
async function enterApp(cfg) {
  App.positionsKey = `phsql.pos.${cfg.host}:${cfg.port}/${cfg.db}`;
  const [tabRes, schRes] = await Promise.all([api('tables'), api('schema')]);
  App.tables = tabRes.tables;
  const model = normalizeSchema(schRes.schema);
  const positions = JSON.parse(localStorage.getItem(App.positionsKey) || '{}');
  let hadPositions = false;
  for (const t of model) if (positions[t.name]) { t.pos = { x: positions[t.name][0], y: positions[t.name][1] }; hadPositions = true; }
  if (!hadPositions && model.length) autoLayoutModels(model);
  App.dbSchema = { tables: model };
  App.schema = structuredClone(App.dbSchema);

  $('#connect-screen').classList.add('gone');
  const app = $('#app');
  app.hidden = false;
  requestAnimationFrame(() => app.classList.add('in'));
  $('#conn-chip').textContent = `${cfg.host}:${cfg.port} · ${cfg.db}`;
  $('#sb-conn').textContent = `● ${cfg.user}@${cfg.host}/${cfg.db} · v${App.version}`;

  renderSidebar();
  initViews();
  switchView('designer');
}

function autoLayoutModels(tables) {
  const tmp = { tables };
  const ordered = topoTables(tables);
  const level = new Map(); const byName = new Map(tables.map(t => [t.name, t]));
  for (const t of ordered) {
    let lv = 0;
    for (const fk of t.fks) if (fk.refTable !== t.name && byName.has(fk.refTable))
      lv = Math.max(lv, (level.get(fk.refTable) ?? 0) + 1);
    level.set(t.name, lv);
  }
  const cols = new Map();
  for (const t of ordered) { const lv = level.get(t.name); if (!cols.has(lv)) cols.set(lv, []); cols.get(lv).push(t); }
  [...cols.keys()].sort((a,b) => a-b).forEach((lv, ci) => {
    let y = 40;
    for (const t of cols.get(lv)) {
      t.pos = { x: 40 + ci * 340, y };
      y += HEAD_H + t.columns.length * ROW_H + NODE_PAD + 60;
    }
  });
}

function initViews() {
  App.designer = new NodeCanvas('d-canvas', 'd-svg', {
    editable: true,
    source: () => App.schema.tables,
    onSelect: sel => renderInspector(sel),
    onChanged: savePositions,
    onRename: t => renameTable(t),
    onLink: (from, col, to, toCol) => openFKDialog(from, col, to, toCol),
  });
  App.erd = new NodeCanvas('e-canvas', 'e-svg', {
    editable: false,
    source: () => App.dbSchema.tables,
    onSelect: sel => renderInspector(sel, true),
  });
  App.data.init();
  App.editor.init();

  $('#d-add').addEventListener('click', addTable);
  $('#d-layout').addEventListener('click', () => App.designer.autoLayout());
  $('#d-fit').addEventListener('click', () => App.designer.fit());
  $('#d-zin').addEventListener('click', () => App.designer.zoomBy(1.25));
  $('#d-zout').addEventListener('click', () => App.designer.zoomBy(0.8));
  $('#d-png').addEventListener('click', () => exportCanvasPNG($('#d-svg'), 'phsql-designer'));
  $('#e-layout').addEventListener('click', () => App.erd.autoLayout());
  $('#e-fit').addEventListener('click', () => App.erd.fit());
  $('#e-zin').addEventListener('click', () => App.erd.zoomBy(1.25));
  $('#e-zout').addEventListener('click', () => App.erd.zoomBy(0.8));
  $('#e-png').addEventListener('click', () => exportCanvasPNG($('#e-svg'), 'phsql-erd'));

  renderInspector({ table: null, col: null });
}

function savePositions() {
  const pos = {};
  for (const t of App.schema.tables) if (t.pos) pos[t.name] = [t.pos.x, t.pos.y];
  localStorage.setItem(App.positionsKey, JSON.stringify(pos));
}

function switchView(v) {
  App.view = v;
  $$('.view-tab').forEach(b => b.classList.toggle('active', b.dataset.view === v));
  $$('.view').forEach(s => s.classList.toggle('active', s.id === 'view-' + v));
  if (v === 'designer') { App.designer.render(); renderInspector(App.designer.sel); }
  if (v === 'erd') App.erd.render();
  if (v === 'data' && !App.data.table && App.selectedTable) App.data.open(App.selectedTable);
  if (v === 'sql') setTimeout(() => App.editor.ta.focus(), 80);
  $('#sb-info').textContent = `${App.schema?.tables.length ?? 0} tables in design · view: ${v}`;
}

/* ═══════════ sidebar ═══════════ */
function renderSidebar(filter = '') {
  const ul = $('#table-list');
  ul.innerHTML = '';
  const list = App.tables.filter(t => !filter || t.name.toLowerCase().includes(filter));
  list.forEach((t, i) => {
    const item = el('li', {
      class: 't-item' + (App.selectedTable === t.name ? ' active' : ''),
      style: `animation-delay:${Math.min(i * 20, 300)}ms`,
      onclick: () => openTable(t.name),
    },
      el('span', { class: 't-ico' }, '▤'),
      el('span', { class: 't-name', title: t.name }, t.name),
      el('span', { class: 't-rows' }, fmtNum(t.est_rows)),
      el('button', { class: 't-menu', onclick: e => { e.stopPropagation(); tableMenu(e, t.name); } }, '⋮'));
    ul.append(item);
  });
  $('#side-count').textContent = `${list.length} / ${App.tables.length} tables`;
}
const fmtNum = n => n == null ? '' : (+n >= 1000 ? (n / 1000).toFixed(1) + 'k' : n);

function tableMenu(e, name) {
  const r = e.target.getBoundingClientRect();
  contextMenu(r.right, r.bottom, [
    { label: '▤ Browse data', onClick: () => openTable(name) },
    { label: '▦ Focus in Designer', onClick: () => { switchView('designer'); App.designer.focusTable(name); renderInspector({ table: name, col: null }); } },
    { label: '◈ Focus in ERD', onClick: () => { switchView('erd'); App.erd.focusTable(name); } },
    '-',
    { label: '⇩ Dump table SQL', onClick: async () => {
      const r = await api('dump', { tables: [name] });
      download(name + '.sql', r.sql, 'text/sql');
    }},
    { label: '⌫ Truncate', danger: true, onClick: async () => {
      if (await confirmBox(`TRUNCATE TABLE ${name}? All rows will be deleted.`))
        try { await api('query', { sql: `TRUNCATE TABLE ${qi(name)}` }); toast('Table truncated', 'ok'); refreshSchema(); }
        catch (err) { toast(err.message, 'err'); }
    }},
    { label: '✕ Drop table', danger: true, onClick: async () => {
      const okName = await promptBox(`Type "${name}" to confirm DROP TABLE`, '', 'Confirm');
      if (okName !== name) return toast('Drop cancelled', 'warn');
      try { await api('query', { sql: `SET FOREIGN_KEY_CHECKS=0; DROP TABLE ${qi(name)}; SET FOREIGN_KEY_CHECKS=1` }); toast(`Table ${name} dropped`, 'ok'); refreshSchema(); }
      catch (err) { toast(err.message, 'err'); }
    }},
  ]);
}

function openTable(name) {
  App.selectedTable = name;
  renderSidebar($('#side-search').value.trim().toLowerCase());
  switchView('data');
  App.data.open(name);
  $('#sidebar').classList.remove('open');
}

async function refreshSchema(notify = false) {
  try {
    const [tabRes, schRes] = await Promise.all([api('tables'), api('schema')]);
    App.tables = tabRes.tables;
    const model = normalizeSchema(schRes.schema);
    const positions = JSON.parse(localStorage.getItem(App.positionsKey) || '{}');
    let had = false;
    for (const t of model) if (positions[t.name]) { t.pos = { x: positions[t.name][0], y: positions[t.name][1] }; had = true; }
    if (!had && model.length) autoLayoutModels(model);
    App.dbSchema = { tables: model };
    App.schema = structuredClone(App.dbSchema);
    renderSidebar($('#side-search').value.trim().toLowerCase());
    if (App.view === 'designer') { App.designer.render(); renderInspector(App.designer.sel); }
    if (App.view === 'erd') App.erd.render();
    if (notify) toast('Schema reloaded', 'ok');
  } catch (e) { toast(e.message, 'err'); }
}
App.refreshSchema = () => refreshSchema();

/* ═══════════ designer operations ═══════════ */
function addTable() {
  const n = App.schema.tables.length;
  const pos = App.designer.centerWorld();
  const t = {
    name: uniqueName('table_', App.schema.tables.map(x => x.name)),
    comment: '', engine: 'InnoDB',
    columns: [{ name: 'id', type: 'INT', length: null, unsigned: true, nullable: false, default: null, ai: true, comment: '' }],
    pk: ['id'], indexes: [], fks: [],
    pos: { x: Math.round(pos.x), y: Math.round(pos.y) },
  };
  App.schema.tables.push(t);
  switchView('designer');
  App.designer.render();
  App.designer.select(t.name);
  renderInspector({ table: t.name, col: null });
  savePositions();
  toast(`Table ${t.name} created — add columns in the panel →`, 'ok');
}
const uniqueName = (base, taken) => {
  let i = 1, n = base + i;
  while (taken.includes(n)) { i++; n = base + i; }
  return n;
};

async function renameTable(t) {
  if (!t) return;
  const name = await promptBox('Rename table', t.name, 'New table name');
  if (!name || name === t.name) return;
  if (!/^[\w$\u0600-\u06FF]+$/u.test(name)) return toast('Invalid identifier', 'err');
  const old = t.name;
  for (const x of App.schema.tables) for (const fk of x.fks) if (fk.refTable === old) fk.refTable = name;
  t.name = name;
  App.designer.render(); savePositions();
  renderInspector({ table: name, col: null });
  toast(`Renamed ${old} → ${name}`, 'ok');
}

function openFKDialog(from, col, to, toCol) {
  const sel = (val, opts) => {
    const s = el('select', {}, opts.map(o => el('option', { value: o, selected: o === val ? '' : null }, o)));
    return s;
  };
  const onDelete = sel('NO ACTION', ['NO ACTION','RESTRICT','CASCADE','SET NULL']);
  const onUpdate = sel('NO ACTION', ['NO ACTION','RESTRICT','CASCADE','SET NULL']);
  const targetPK = to.pk.includes(toCol);
  const body = el('div', { style: 'display:flex;flex-direction:column;gap:14px' },
    el('div', { class: 'fk-row' }, `${from.name}.${col}`, el('b', {}, ' → '), `${to.name}.${toCol}`),
    !targetPK ? el('span', { class: 'chip chip-danger' }, `⚠ ${to}.${toCol} is not a PK/UNIQUE column`) : null,
    el('label', { class: 'fld' }, el('span', {}, 'ON DELETE'), onDelete),
    el('label', { class: 'fld' }, el('span', {}, 'ON UPDATE'), onUpdate));
  modal({
    title: 'New foreign key', body,
    actions: [
      { label: 'Cancel' },
      { label: 'Create FK', kind: 'primary', onClick: () => {
        from.fks = from.fks.filter(f => f.column !== col);
        from.fks.push({
          name: `fk_${from.name}_${col}`.slice(0, 60),
          column: col, columns: [col], refTable: to.name, refColumn: toCol, refColumns: [toCol],
          onDelete: onDelete.value, onUpdate: onUpdate.value,
        });
        App.designer.render(); savePositions();
        toast(`FK ${from.name}.${col} → ${to.name}.${toCol} linked`, 'ok');
      }},
    ],
  });
}

/* ═══════════ inspector (right panel) ═══════════ */
function renderInspector(sel, readonly = false) {
  const head = $('#insp-head'), body = $('#insp-body');
  body.innerHTML = '';
  const t = App.schema.tables.find(x => x.name === sel.table);
  if (!t) {
    head.textContent = 'Properties';
    body.append(el('div', { class: 'insp-empty' },
      'Select a <b>table</b> or <b>column</b> on the canvas to edit it.<br><br>',
      '• Drag cards to arrange<br>• Drag from a port ● to another column to create a <b>foreign key</b><br>',
      '• Double-click a header to rename<br>• <kbd>Ctrl</kbd>+<kbd>S</kbd> to generate migration'));
    return;
  }
  if (sel.col) return renderColumnInspector(t, t.columns.find(c => c.name === sel.col), readonly);

  head.textContent = `TABLE · ${t.name}`;
  if (readonly) { body.append(structureSummary(t)); return; }

  const nameIn = el('input', { value: t.name });
  nameIn.addEventListener('change', () => {
    const v = nameIn.value.trim();
    if (v && v !== t.name) renameTable(t);
  });
  body.append(
    el('div', { class: 'insp-section' }, 'Table'),
    el('label', { class: 'fld' }, el('span', {}, 'Name'), nameIn),
    el('div', { class: 'kv' }, 'Columns', el('b', {}, t.columns.length)),
    el('div', { class: 'kv' }, 'Foreign keys', el('b', {}, t.fks.length)),
    el('div', { class: 'kv' }, 'In live DB', el('b', {}, App.dbSchema.tables.some(x => x.name === t.name) ? 'yes' : 'no (new)')),
    el('div', { class: 'pill-row' },
      el('button', { class: 'pill', onclick: () => { switchView('data'); openTable(t.name); } }, '▤ Data'),
      el('button', { class: 'pill', onclick: () => App.designer.focusTable(t.name) }, '⌖ Focus')),
    el('div', { class: 'insp-section' }, `Columns (${t.columns.length})`));

  const list = el('div', { class: 'col-list' });
  for (const c of t.columns) {
    list.append(el('div', { class: 'col-item', onclick: () => {
      App.designer.select(t.name, c.name);
      App.designer.render(); App.designer.select(t.name, c.name);
      renderColumnInspector(t, c, readonly);
    }},
      el('span', { class: 'ci-key' }, t.pk.includes(c.name) ? '◆' : '·'),
      c.name,
      el('span', { class: 'ci-type' }, c.type.toLowerCase() + (c.length && NEEDS_LEN.has(c.type) ? `(${c.length})` : '') + (c.ai ? ' ai' : ''))));
  }
  body.append(list,
    el('button', { class: 'btn btn-sm btn-ghost', onclick: () => {
      const c = { name: uniqueName('col_', t.columns.map(x => x.name)), type: 'VARCHAR', length: '255',
        unsigned: false, nullable: true, default: null, ai: false, comment: '' };
      t.columns.push(c);
      App.designer.render(); App.designer.select(t.name, c.name);
      renderColumnInspector(t, c, false);
    } }, '+ Add column'));

  if (t.fks.length) {
    body.append(el('div', { class: 'insp-section' }, 'Foreign keys'));
    for (const fk of t.fks) {
      body.append(el('div', { class: 'fk-row' },
        `${fk.column || (fk.columns||[]).join(',')}`, el('b', {}, '→'), `${fk.refTable}.${fk.refColumn || (fk.refColumns||[]).join(',')}`,
        el('button', { title: 'Drop FK', onclick: () => {
          t.fks = t.fks.filter(f => f !== fk);
          App.designer.render(); renderInspector(sel);
          toast('FK removed', 'ok');
        } }, '✕')));
    }
  }
  body.append(el('div', { class: 'insp-section' }, 'Danger zone'),
    el('button', { class: 'btn btn-sm btn-danger', onclick: async () => {
      if (await confirmBox(`Remove table "${t.name}" from the design? A DROP statement will be staged for the next migration.`)) {
        App.schema.tables = App.schema.tables.filter(x => x !== t);
        for (const x of App.schema.tables) x.fks = x.fks.filter(f => f.refTable !== t.name);
        App.designer.render(); renderInspector({ table: null, col: null });
      }
    } }, '🗑 Delete table'));
}

function renderColumnInspector(t, c, readonly) {
  if (!c) return;
  const head = $('#insp-head'), body = $('#insp-body');
  body.innerHTML = '';
  head.textContent = `COLUMN · ${t.name}.${c.name}`;
  if (readonly) { body.append(structureSummary(t, c.name)); return; }

  const refresh = () => { App.designer.render(); App.designer.select(t.name, c.name); };
  const nameIn = el('input', { value: c.name });
  nameIn.addEventListener('change', () => {
    const v = nameIn.value.trim();
    if (!v || v === c.name) return;
    const i = t.pk.indexOf(c.name); if (i >= 0) t.pk[i] = v;
    for (const ix of t.indexes) ix.columns = ix.columns.map(x => x === c.name ? v : x);
    for (const fk of t.fks) if (fk.column === c.name) { fk.column = v; fk.columns = [v]; }
    for (const x of App.schema.tables) for (const fk of x.fks)
      if (fk.refTable === t.name && fk.refColumn === c.name) { fk.refColumn = v; fk.refColumns = [v]; }
    c.name = v; refresh(); renderColumnInspector(t, c, readonly);
  });
  const typeSel = el('select', {}, COL_TYPES.map(x => el('option', { value: x, selected: x === c.type ? '' : null }, x)));
  typeSel.addEventListener('change', () => {
    c.type = typeSel.value;
    if (c.type === 'BOOLEAN') c.length = null;
    else if (NEEDS_LEN.has(c.type) && !c.length) c.length = DEFAULT_LEN[c.type] || '';
    refresh(); renderColumnInspector(t, c, readonly);
  });
  const lenIn = el('input', { value: c.length ?? '', placeholder: c.type === 'ENUM' ? "'a','b','c'" : 'length' });
  lenIn.addEventListener('change', () => { c.length = lenIn.value.trim() || null; refresh(); });
  const defIn = el('input', { value: c.default ?? '', placeholder: 'none / CURRENT_TIMESTAMP' });
  defIn.addEventListener('change', () => { c.default = defIn.value.trim() || null; refresh(); });
  const comIn = el('input', { value: c.comment ?? '', placeholder: 'comment' });
  comIn.addEventListener('change', () => { c.comment = comIn.value.trim(); });

  const sw = (label, val, on) => el('div', { class: 'switch' + (val ? ' on' : ''), onclick: e => {
    on(!val); refresh(); renderColumnInspector(t, c, readonly);
  }}, label, el('span', { class: 'sw' }));

  const isPk = t.pk.includes(c.name);
  const hasUq = t.indexes.some(ix => ix.unique && ix.columns.length === 1 && ix.columns[0] === c.name);
  const hasIx = t.indexes.some(ix => !ix.unique && ix.columns.length === 1 && ix.columns[0] === c.name);

  body.append(
    el('button', { class: 'link-btn', style: 'align-self:flex-start', onclick: () => {
      App.designer.select(t.name); renderInspector({ table: t.name, col: null });
    } }, '← back to table'),
    el('label', { class: 'fld' }, el('span', {}, 'Name'), nameIn),
    el('div', { class: 'fld-row' },
      el('label', { class: 'fld' }, el('span', {}, 'Type'), typeSel),
      el('label', { class: 'fld' }, el('span', {}, 'Length / Values'), lenIn)),
    sw('Unsigned', c.unsigned, v => c.unsigned = v),
    sw('Nullable (NULL allowed)', c.nullable, v => {
      c.nullable = v;
      if (!v && c.default === null) {} // keep
      if (t.pk.includes(c.name) && v) toast('PK columns should be NOT NULL', 'warn');
    }),
    sw('Auto Increment', c.ai, v => {
      c.ai = v;
      if (v) { c.nullable = false; if (!t.pk.length) t.pk = [c.name]; }
    }),
    el('label', { class: 'fld' }, el('span', {}, 'Default value'), defIn),
    el('div', { class: 'insp-section' }, 'Constraints'),
    el('div', { class: 'pill-row' },
      el('button', { class: 'pill' + (isPk ? ' on' : ''), onclick: () => {
        t.pk = isPk ? [] : [c.name];
        if (!isPk) c.nullable = false;
        refresh(); renderColumnInspector(t, c, readonly);
      } }, '◆ Primary Key'),
      el('button', { class: 'pill' + (hasUq ? ' on' : ''), onclick: () => {
        t.indexes = t.indexes.filter(ix => !(ix.unique && ix.columns.length === 1 && ix.columns[0] === c.name));
        if (!hasUq) t.indexes.push({ name: `uq_${t.name}_${c.name}`.slice(0, 60), unique: true, columns: [c.name] });
        refresh(); renderColumnInspector(t, c, readonly);
      } }, 'Unique'),
      el('button', { class: 'pill' + (hasIx ? ' on' : ''), onclick: () => {
        t.indexes = t.indexes.filter(ix => !(!ix.unique && ix.columns.length === 1 && ix.columns[0] === c.name));
        if (!hasIx) t.indexes.push({ name: `idx_${t.name}_${c.name}`.slice(0, 60), unique: false, columns: [c.name] });
        refresh(); renderColumnInspector(t, c, readonly);
      } }, 'Index')),
    el('label', { class: 'fld' }, el('span', {}, 'Comment'), comIn),
    el('div', { class: 'insp-section' }, 'Danger zone'),
    el('button', { class: 'btn btn-sm btn-danger', onclick: async () => {
      if (!(await confirmBox(`Drop column ${t.name}.${c.name}?`))) return;
      t.columns = t.columns.filter(x => x !== c);
      t.pk = t.pk.filter(p => p !== c.name);
      t.indexes = t.indexes.filter(ix => !ix.columns.includes(c.name));
      t.fks = t.fks.filter(f => f.column !== c.name);
      for (const x of App.schema.tables) x.fks = x.fks.filter(f => !(f.refTable === t.name && f.refColumn === c.name));
      App.designer.render(); renderInspector({ table: t.name, col: null });
    } }, '🗑 Delete column'));
}

function structureSummary(t, hiCol) {
  const box = el('div', { style: 'display:flex;flex-direction:column;gap:8px' });
  box.append(el('div', { class: 'kv' }, 'Engine', el('b', {}, t.engine || 'InnoDB')),
    el('div', { class: 'kv' }, 'PK', el('b', {}, t.pk.join(', ') || '—')));
  const list = el('div', { class: 'col-list' });
  for (const c of t.columns) list.append(el('div', { class: 'col-item' + (c.name === hiCol ? ' active' : '') },
    el('span', { class: 'ci-key' }, t.pk.includes(c.name) ? '◆' : '·'), c.name,
    el('span', { class: 'ci-type' }, c.type.toLowerCase() + (c.length && NEEDS_LEN.has(c.type) ? `(${c.length})` : ''))));
  box.append(list);
  return box;
}

/* ═══════════ migration ═══════════ */
function openMigration() {
  if (!App.schema) return;
  const stmts = migrationStatements(App.dbSchema, App.schema);
  if (!stmts.length) return toast('Schema is in sync with the database ✓', 'ok');

  const body = el('div', {});
  stmts.forEach((s, i) => body.append(el('div', { class: 'stmt', style: `animation-delay:${i * 60}ms` },
    el('div', { class: 'stmt-head' },
      el('span', { class: 'stmt-kind k-' + s.kind }, s.kind), s.label),
    el('pre', {}, hlSQL(s.sql)))));
  body.prepend(el('p', { style: 'margin-bottom:12px' },
    `${stmts.length} statement(s) will be executed against `, el('b', { style: 'color:var(--acc)' }, App.conn?.db), ':'));

  modal({
    title: '⚡ Migration preview', body, size: 'wide',
    actions: [
      { label: 'Cancel' },
      { label: `Execute ${stmts.length} statement(s)`, kind: 'primary', onClick: async close => {
        try {
          const sql = stmts.map(s => s.sql).join(';\n') + ';';
          const r = await api('exec_script', { sql });
          toast(`Migration applied — ${r.executed} statement(s) ✓`, 'ok', 5000);
          await refreshSchema();
          close();
        } catch (e) { toast(e.message, 'err', 9000); return false; }
      }},
    ],
  });
}

/* ═══════════ JSON import / export & dump ═══════════ */
function exportSchemaJSON() {
  const doc = {
    phsql: 1, db: App.conn?.db, generated: new Date().toISOString(),
    tables: App.schema.tables.map(({ pos, ...t }) => ({ ...t, position: pos })),
  };
  download(`${App.conn?.db || 'schema'}.phsql.json`, JSON.stringify(doc, null, 2), 'application/json');
  toast('Schema exported as JSON', 'ok');
}
function importSchemaJSON(e) {
  const file = e.target.files[0];
  e.target.value = '';
  if (!file) return;
  file.text().then(txt => {
    try {
      const doc = JSON.parse(txt);
      const tables = (doc.tables || []).map(t => ({
        name: t.name, comment: t.comment || '', engine: t.engine || 'InnoDB',
        columns: (t.columns || []).map(c => ({
          name: c.name, type: (c.type || 'VARCHAR').toUpperCase(), length: c.length ?? null,
          unsigned: !!c.unsigned, nullable: !!c.nullable, default: c.default ?? null,
          ai: !!c.ai || !!c.auto_increment, comment: c.comment || '',
        })),
        pk: t.primaryKey || t.pk || [],
        indexes: (t.indexes || []).map(ix => ({ name: ix.name, unique: !!ix.unique, columns: ix.columns })),
        fks: (t.foreignKeys || t.fks || []).map(f => ({
          name: f.name || `fk_${t.name}_${f.column || (f.columns||[])[0]}`,
          column: f.column || (f.columns||[])[0], columns: f.columns || [f.column],
          refTable: f.refTable, refColumn: f.refColumn || (f.refColumns||[])[0],
          refColumns: f.refColumns || [f.refColumn],
          onDelete: f.onDelete || 'NO ACTION', onUpdate: f.onUpdate || 'NO ACTION',
        })),
        pos: t.position || null,
      }));
      App.schema = { tables };
      let anyPos = tables.some(t => t.pos);
      if (!anyPos && tables.length) autoLayoutModels(tables);
      switchView('designer');
      App.designer.render(); App.designer.fit();
      toast(`Imported ${tables.length} table(s) — review the migration`, 'ok');
      openMigration();
    } catch (err) { toast('Invalid JSON schema: ' + err.message, 'err'); }
  });
}
async function exportSQLDump() {
  try {
    const r = await api('dump', {});
    download(`${App.conn?.db || 'schema'}.sql`, r.sql, 'text/sql');
    toast('SQL dump downloaded', 'ok');
  } catch (e) { toast(e.message, 'err'); }
}

/* ═══════════ help ═══════════ */
function showHelp() {
  const rows = [
    ['Alt + 1 / 2 / 3 / 4', 'Designer · ERD · Data · SQL Editor'],
    ['Ctrl + S', 'Generate migration (or save data changes)'],
    ['Ctrl + Enter', 'Run SQL query'],
    ['Alt + N', 'New table in designer'],
    ['Alt + L', 'Auto-layout canvas'],
    ['/', 'Focus table filter'],
    ['?', 'This help'],
    ['Drag port ● → column', 'Create foreign key'],
    ['Right-click edge', 'Drop foreign key'],
    ['Double-click node header', 'Rename table'],
    ['Type NULL in a cell', 'Set value to NULL'],
  ];
  modal({
    title: '⌨ Keyboard shortcuts',
    body: el('div', {}, rows.map(([k, d]) => el('div', { class: 'kv', style: 'padding:6px 0' },
      el('span', {}, el('kbd', {}, k)), el('b', { style: 'color:var(--tx1);font-family:var(--f-body);font-size:12.5px' }, d)))),
    actions: [{ label: 'Close', kind: 'primary' }],
  });
}
