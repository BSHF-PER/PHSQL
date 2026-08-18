/* PHSQL — core: utilities, API client, encrypted vault, UI primitives */
'use strict';

const App = {
  conn: null, theme: localStorage.getItem('phsql.theme') || 'dark',
  tables: [], dbSchema: null, schema: null, view: 'designer',
  selectedTable: null, positionsKey: '', version: '',
  designer: null, erd: null, data: null, editor: null,
};

/* ---------- dom helpers ---------- */
const $  = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

function el(tag, attrs = {}, ...kids) {
  const n = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (v == null) continue;
    if (k === 'class') n.className = v;
    else if (k.startsWith('on')) n.addEventListener(k.slice(2), v);
    else n.setAttribute(k, v);
  }
  for (const k of kids.flat(Infinity)) {
    if (k == null) continue;
    n.append(k.nodeType ? k : document.createTextNode(k));
  }
  return n;
}
const SVGNS = 'http://www.w3.org/2000/svg';
function svgEl(tag, attrs = {}, ...kids) {
  const n = document.createElementNS(SVGNS, tag);
  for (const [k, v] of Object.entries(attrs)) { if (v != null) n.setAttribute(k, v); }
  for (const k of kids.flat(Infinity)) { if (k != null) n.append(k.nodeType ? k : document.createTextNode(k)); }
  return n;
}

const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
const qi = s => '`' + String(s).replace(/`/g, '``') + '`';
const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

/* ---------- API client ---------- */
async function api(action, data = {}) {
  const res = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...data }),
  });
  let json;
  try { json = await res.json(); } catch { throw new Error('Invalid server response (HTTP ' + res.status + ')'); }
  if (!json.ok) throw new Error(json.error || 'Request failed');
  return json;
}

/* ---------- encrypted local vault (AES-GCM) ---------- */
const Vault = (() => {
  const enc = new TextEncoder(), dec = new TextDecoder();
  const b64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf)));
  const unb64 = s => Uint8Array.from(atob(s), c => c.charCodeAt(0));
  async function key() {
    const km = await crypto.subtle.importKey('raw', enc.encode('phsql::local::vault'), 'PBKDF2', false, ['deriveKey']);
    return crypto.subtle.deriveKey(
      { name: 'PBKDF2', salt: enc.encode('phsql.v1'), iterations: 60000, hash: 'SHA-256' },
      km, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
  }
  return {
    async save(name, value) {
      const iv = crypto.getRandomValues(new Uint8Array(12));
      const ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, await key(), enc.encode(JSON.stringify(value)));
      localStorage.setItem('phsql:' + name, b64(iv.buffer) + '.' + b64(ct));
    },
    async load(name, fallback = null) {
      const raw = localStorage.getItem('phsql:' + name);
      if (!raw) return fallback;
      try {
        const [iv, ct] = raw.split('.');
        const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: unb64(iv) }, await key(), unb64(ct));
        return JSON.parse(dec.decode(pt));
      } catch { return fallback; }
    },
    remove(name) { localStorage.removeItem('phsql:' + name); },
  };
})();

/* ---------- toasts ---------- */
function toast(msg, kind = 'info', ms = 3800) {
  const icons = { ok: '✓', err: '✕', warn: '⚠', info: 'ℹ' };
  const t = el('div', { class: 'toast ' + kind },
    el('span', { class: 't-ic' }, icons[kind] || 'ℹ'),
    el('span', {}, msg));
  $('#toasts').append(t);
  setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 320); }, ms);
}

/* ---------- modals ---------- */
function modal({ title, body, actions = [], size = '' }) {
  const root = $('#modal-root');
  const closeBtn = el('button', { class: 'modal-x', onclick: () => close() }, '✕');
  const bodyBox = el('div', { class: 'modal-body' });
  if (typeof body === 'string') bodyBox.innerHTML = body; else if (body) bodyBox.append(body);
  const foot = el('div', { class: 'modal-foot' });
  const box = el('div', { class: 'modal ' + size },
    el('div', { class: 'modal-head' }, title, closeBtn), bodyBox, foot);
  const ov = el('div', { class: 'modal-overlay' }, box);
  function close() {
    ov.classList.remove('show');
    setTimeout(() => ov.remove(), 200);
    document.removeEventListener('keydown', onKey);
  }
  function onKey(e) { if (e.key === 'Escape') close(); }
  for (const a of actions) {
    foot.append(el('button', {
      class: 'btn ' + (a.kind === 'danger' ? 'btn-danger' : a.kind === 'primary' ? 'btn-primary' : 'btn-ghost'),
      onclick: async () => {
        const keep = await a.onClick?.(close);
        if (keep !== false) close();
      },
    }, a.label));
  }
  ov.addEventListener('mousedown', e => { if (e.target === ov) close(); });
  root.append(ov);
  requestAnimationFrame(() => ov.classList.add('show'));
  document.addEventListener('keydown', onKey);
  return { close, el: box };
}
const confirmBox = (msg, { title = 'Are you sure?', danger = true } = {}) => new Promise(res => {
  modal({ title, body: `<p>${esc(msg)}</p>`, actions: [
    { label: 'Cancel' , onClick: () => res(false) },
    { label: danger ? 'Yes, do it' : 'OK', kind: danger ? 'danger' : 'primary', onClick: () => res(true) },
  ]});
});
const promptBox = (title, value = '', label = 'Name') => new Promise(res => {
  const input = el('input', { value, style: 'width:100%' });
  modal({ title, body: el('label', { class: 'fld' }, el('span', {}, label), input), actions: [
    { label: 'Cancel', onClick: () => res(null) },
    { label: 'OK', kind: 'primary', onClick: () => res(input.value.trim()) },
  ]});
  setTimeout(() => { input.focus(); input.select(); }, 60);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') res(input.value.trim()); });
});

/* ---------- context menu ---------- */
function contextMenu(x, y, items) {
  $$('.menu-pop').forEach(m => m.remove());
  const pop = el('div', { class: 'menu-pop' });
  for (const it of items) {
    if (it === '-') { pop.append(el('hr')); continue; }
    pop.append(el('button', {
      class: it.danger ? 'danger' : '',
      onclick: () => { pop.remove(); it.onClick(); },
    }, it.label));
  }
  document.body.append(pop);
  const r = pop.getBoundingClientRect();
  pop.style.left = Math.min(x, innerWidth - r.width - 8) + 'px';
  pop.style.top  = Math.min(y, innerHeight - r.height - 8) + 'px';
  setTimeout(() => document.addEventListener('click', () => pop.remove(), { once: true }), 0);
}

/* ---------- downloads & export helpers ---------- */
function download(name, content, type = 'text/plain') {
  const a = el('a', { href: URL.createObjectURL(new Blob([content], { type })), download: name });
  a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 3000);
}
const csvCell = v => v == null ? '' : /[",\n]/.test(v) ? '"' + String(v).replace(/"/g, '""') + '"' : String(v);
function rowsToCSV(rows, cols) {
  return [cols.map(csvCell).join(','), ...rows.map(r => cols.map(c => csvCell(r[c])).join(','))].join('\n');
}
function rowsToInserts(rows, table) {
  if (!rows.length) return '';
  const cols = Object.keys(rows[0]);
  const lit = v => v == null ? 'NULL' : typeof v === 'number' ? v : `'${String(v).replace(/\\/g,'\\\\').replace(/'/g,"\\'")}'`;
  return rows.map(r => `INSERT INTO ${qi(table)} (${cols.map(qi).join(', ')}) VALUES (${cols.map(c => lit(r[c])).join(', ')};`).join('\n');
}

/* ---------- SQL syntax highlighting ---------- */
const SQL_KEYWORDS = ['SELECT','FROM','WHERE','INSERT','INTO','VALUES','UPDATE','SET','DELETE','CREATE','TABLE','ALTER',
  'DROP','ADD','COLUMN','MODIFY','CHANGE','PRIMARY','KEY','FOREIGN','REFERENCES','CONSTRAINT','UNIQUE','INDEX','JOIN',
  'LEFT','RIGHT','INNER','OUTER','FULL','CROSS','ON','AS','AND','OR','NOT','NULL','IN','IS','LIKE','BETWEEN','ORDER',
  'GROUP','BY','HAVING','LIMIT','OFFSET','UNION','ALL','DISTINCT','EXISTS','CASE','WHEN','THEN','ELSE','END','IF',
  'DEFAULT','AUTO_INCREMENT','ENGINE','CHARSET','COLLATE','CASCADE','RESTRICT','NO','ACTION','TRUNCATE','RENAME','TO',
  'SHOW','DESCRIBE','EXPLAIN','USE','DATABASE','DATABASES','TABLES','BEGIN','COMMIT','ROLLBACK'];

function hlSQL(src) {
  const re = /(\/\*[\s\S]*?\*\/|--[^\n]*|#[^\n]*)|('(?:\\.|[^'\\])*'?|"(?:\\.|[^"\\])*"?)|(\b\d+(?:\.\d+)?\b)|(\b(?:SELECT|FROM|WHERE|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|TABLE|ALTER|DROP|ADD|COLUMN|MODIFY|CHANGE|PRIMARY|KEY|FOREIGN|REFERENCES|CONSTRAINT|UNIQUE|INDEX|JOIN|LEFT|RIGHT|INNER|OUTER|FULL|CROSS|ON|AS|AND|OR|NOT|NULL|IN|IS|LIKE|BETWEEN|ORDER|GROUP|BY|HAVING|LIMIT|OFFSET|UNION|ALL|DISTINCT|EXISTS|CASE|WHEN|THEN|ELSE|END|IF|DEFAULT|AUTO_INCREMENT|ENGINE|CHARSET|COLLATE|CASCADE|RESTRICT|NO|ACTION|TRUNCATE|RENAME|TO|SHOW|DESCRIBE|EXPLAIN|USE|DATABASE|DATABASES|TABLES|BEGIN|COMMIT|ROLLBACK|INT|INTEGER|BIGINT|SMALLINT|TINYINT|MEDIUMINT|DECIMAL|NUMERIC|FLOAT|DOUBLE|VARCHAR|CHAR|TEXT|TINYTEXT|MEDIUMTEXT|LONGTEXT|BLOB|DATE|DATETIME|TIMESTAMP|TIME|YEAR|JSON|BOOLEAN|ENUM|UNSIGNED|SIGNED)\b)|(\b(?:COUNT|SUM|AVG|MIN|MAX|CONCAT|LENGTH|UPPER|LOWER|NOW|CURDATE|COALESCE|NULLIF|CAST|SUBSTRING|TRIM|ROUND|ABS|IFNULL|FORMAT|DATE_FORMAT|GROUP_CONCAT)\b)|(`[^`\n]*`?)/gi;
  let out = '', last = 0, m;
  while ((m = re.exec(src))) {
    out += esc(src.slice(last, m.index));
    const cls = m[1] ? 'c-com' : m[2] ? 'c-str' : m[3] ? 'c-num' : m[4] ? 'c-key' : m[5] ? 'c-fn' : 'c-id';
    out += `<span class="${cls}">${esc(m[0])}</span>`;
    last = m.index + m[0].length;
  }
  return out + esc(src.slice(last));
}
