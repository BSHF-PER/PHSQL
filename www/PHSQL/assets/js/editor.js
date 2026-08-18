/* PHSQL — SQL Editor: highlighting overlay, autocomplete, history, results */
'use strict';

App.editor = {
  last: null,           // last result payload
  init() {
    this.ta = $('#q-input');
    this.pre = $('#q-pre');
    this.gutter = $('#q-gutter');
    this.ac = $('#ac-popup');
    this.acIndex = 0; this.acItems = [];

    this.ta.value = localStorage.getItem('phsql.scratch') || 'SELECT * FROM ';
    this.highlight();

    this.ta.addEventListener('input', () => { this.highlight(); this.autocomplete(); this._persist(); });
    this.ta.addEventListener('scroll', () => this.syncScroll());
    this.ta.addEventListener('keydown', e => this.onKey(e));
    $('#q-run').addEventListener('click', () => this.run());
    $('#q-history').addEventListener('click', e => this.historyMenu(e));
    $('#q-csv').addEventListener('click', () => this.exportAs('csv'));
    $('#q-json').addEventListener('click', () => this.exportAs('json'));
    $('#q-sql').addEventListener('click', () => this.exportAs('sql'));
  },

  _persist: debounce(function () { localStorage.setItem('phsql.scratch', App.editor.ta.value); }, 500),

  highlight() {
    this.pre.innerHTML = hlSQL(this.ta.value) + '\n';
    const lines = this.ta.value.split('\n').length;
    this.gutter.textContent = Array.from({ length: lines }, (_, i) => i + 1).join('\n');
    this.syncScroll();
  },
  syncScroll() {
    this.pre.scrollTop = this.ta.scrollTop;
    this.pre.scrollLeft = this.ta.scrollLeft;
    this.gutter.scrollTop = this.ta.scrollTop;
  },

  onKey(e) {
    if (!this.ac.hidden) {
      if (e.key === 'ArrowDown') { e.preventDefault(); this.acMove(1); return; }
      if (e.key === 'ArrowUp')   { e.preventDefault(); this.acMove(-1); return; }
      if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); this.acAccept(); return; }
      if (e.key === 'Escape') { this.ac.hidden = true; return; }
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); this.run(); }
    if (e.key === 'Tab') { e.preventDefault(); this.ta.setRangeText('  ', this.ta.selectionStart, this.ta.selectionEnd, 'end'); this.highlight(); }
  },

  /* ---------- autocomplete ---------- */
  autocomplete() {
    const pos = this.ta.selectionStart;
    const text = this.ta.value.slice(0, pos);
    const m = text.match(/([\w`]+)(\.([\w`]*)?)?$/);
    if (!m) return this.hideAC();
    let prefix = null, word;
    if (m[2]) { prefix = m[1].replace(/`/g, ''); word = (m[3] || '').toLowerCase(); }
    else word = m[1].toLowerCase();
    if (!word) return this.hideAC();

    let items = [];
    if (prefix) {
      const t = (App.dbSchema?.tables || []).find(t => t.name.toLowerCase() === prefix.toLowerCase());
      if (t) items = t.columns.map(c => ({ label: c.name, kind: 'col' }));
    } else {
      items = [
        ...(App.dbSchema?.tables || []).map(t => ({ label: t.name, kind: 'table' })),
        ...SQL_KEYWORDS.map(k => ({ label: k, kind: 'kw' })),
      ].filter(i => i.label.toLowerCase().startsWith(word));
    }
    items = items.slice(0, 12);
    if (!items.length) return this.hideAC();
    this.acItems = items; this.acIndex = 0;
    this.ac.innerHTML = '';
    items.forEach((it, i) => this.ac.append(
      el('div', { class: 'ac-item' + (i === 0 ? ' active' : ''), onmousedown: e => { e.preventDefault(); this.acAccept(i); } },
        it.label, el('span', { class: 'ac-kind' }, it.kind))));
    const { top, left } = this.caretCoords();
    this.ac.style.top = top + 'px'; this.ac.style.left = Math.min(left, this.ac.parentElement.clientWidth - 230) + 'px';
    this.ac.hidden = false;
  },
  hideAC() { this.ac.hidden = true; },
  acMove(d) {
    this.acIndex = (this.acIndex + d + this.acItems.length) % this.acItems.length;
    $$('.ac-item', this.ac).forEach((n, i) => n.classList.toggle('active', i === this.acIndex));
  },
  acAccept(i = this.acIndex) {
    const it = this.acItems[i]; if (!it) return;
    const pos = this.ta.selectionStart;
    const before = this.ta.value.slice(0, pos);
    const m = before.match(/([\w`]+)(\.([\w`]*)?)?$/);
    const start = m ? pos - (m[3] ? m[3].length : m[1].length) : pos;
    const ins = (m && m[2]) ? it.label + ' ' : it.label + (it.kind === 'table' ? ' ' : ' ');
    this.ta.setRangeText(ins, start, pos, 'end');
    this.hideAC(); this.highlight();
  },
  caretCoords() {
    const mirror = el('div', {});
    const cs = getComputedStyle(this.ta);
    for (const p of ['fontFamily','fontSize','lineHeight','padding','whiteSpace','boxSizing']) mirror.style[p] = cs[p];
    mirror.style.cssText += ';position:absolute;visibility:hidden;overflow:hidden;width:' + this.ta.clientWidth + 'px';
    mirror.textContent = this.ta.value.slice(0, this.ta.selectionStart);
    const mark = el('span', {}, '|');
    mirror.append(mark);
    this.ta.parentElement.append(mirror);
    const top = mark.offsetTop - this.ta.scrollTop + this.ta.offsetTop + 22;
    const left = mark.offsetLeft - this.ta.scrollLeft + 4;
    mirror.remove();
    return { top, left };
  },

  /* ---------- run & results ---------- */
  async run() {
    const sql = this.ta.value.trim();
    if (!sql) return;
    const status = $('#res-status');
    status.className = 'res-status';
    status.innerHTML = '<span class="spinner"></span> running…';
    try {
      const r = await api('query', { sql });
      this.last = r;
      this._pushHistory(sql, r);
      if (r.type === 'result') {
        status.className = 'res-status ok';
        status.textContent = `${r.count} rows · ${r.ms} ms${r.truncated ? ' · truncated at 1000' : ''}`;
        this.renderTable(r);
      } else {
        status.className = 'res-status ok';
        status.textContent = `OK · ${r.affected} affected rows · ${r.ms} ms`;
        $('#res-table').innerHTML = `<div class="res-msg">✔ Query executed — ${r.affected} row(s) affected in ${r.ms} ms.</div>`;
        App.refreshSchema?.();
      }
    } catch (e) {
      this.last = null;
      status.className = 'res-status err';
      status.textContent = 'error';
      $('#res-table').innerHTML = `<div class="res-msg err">✕ ${esc(e.message)}</div>`;
      this._pushHistory(sql, { error: e.message });
    }
  },

  renderTable(r) {
    const wrap = $('#res-table');
    if (!r.rows.length) { wrap.innerHTML = '<div class="res-msg">Empty result set.</div>'; return; }
    const thead = el('thead', {}, el('tr', {}, r.columns.map(c => el('th', {}, c))));
    const tbody = el('tbody', {}, r.rows.slice(0, 500).map(row =>
      el('tr', {}, r.columns.map(c => {
        const v = row[c];
        return el('td', {}, v === null ? el('span', { class: 'null' }, 'NULL') : String(v));
      }))));
    wrap.innerHTML = '';
    wrap.append(el('table', { class: 'dgrid' }, thead, tbody));
    if (r.rows.length > 500) wrap.append(el('div', { class: 'res-msg' }, `… showing first 500 of ${r.count} rows. Export for full data.`));
  },

  exportAs(fmt) {
    if (!this.last || this.last.type !== 'result') return toast('Run a SELECT query first', 'warn');
    const { rows, columns } = this.last;
    if (fmt === 'csv')  download('result.csv', rowsToCSV(rows, columns), 'text/csv');
    if (fmt === 'json') download('result.json', JSON.stringify(rows, null, 2), 'application/json');
    if (fmt === 'sql') {
      promptBox('Export as INSERT statements', 'result', 'Target table name').then(name => {
        if (name) download(name + '.sql', rowsToInserts(rows, name), 'text/sql');
      });
      return;
    }
    toast(`Exported ${rows.length} rows`, 'ok');
  },

  /* ---------- history ---------- */
  _pushHistory(sql, r) {
    const h = JSON.parse(localStorage.getItem('phsql.qhist') || '[]');
    h.unshift({ sql, ts: Date.now(), ok: !r.error, ms: r.ms ?? null });
    localStorage.setItem('phsql.qhist', JSON.stringify(h.slice(0, 50)));
  },
  historyMenu(e) {
    const h = JSON.parse(localStorage.getItem('phsql.qhist') || '[]');
    if (!h.length) return toast('No history yet', 'info');
    const r = e.target.getBoundingClientRect();
    contextMenu(r.left, r.bottom + 4, h.slice(0, 12).map(it => ({
      label: `${it.ok ? '✓' : '✕'} ${it.sql.slice(0, 42)}${it.sql.length > 42 ? '…' : ''}`,
      onClick: () => { this.ta.value = it.sql; this.highlight(); this.ta.focus(); },
    })));
  },
};
