/* PHSQL — Data Viewer: grid, inline editing, filters, pagination */
'use strict';

App.data = {
  table: null, rows: [], cols: [], pk: [], total: 0,
  page: 1, per: 50, sort: null, dir: 'asc', q: '',
  filters: [], dirty: new Map(), selected: new Set(), pendingNew: null,

  init() { this.root = $('#data-root'); },

  async open(table) {
    this.table = table; this.page = 1; this.sort = null; this.dir = 'asc';
    this.q = ''; this.filters = []; this.dirty.clear(); this.selected.clear(); this.pendingNew = null;
    await this.fetch();
  },

  async fetch() {
    if (!this.table) return;
    try {
      const r = await api('table_data', {
        table: this.table, page: this.page, per: this.per,
        sort: this.sort, dir: this.dir, q: this.q, filters: this.filters,
      });
      this.rows = r.rows; this.cols = r.columns; this.pk = r.pk; this.total = r.total;
      this.dirty.clear(); this.selected.clear();
      this.render();
    } catch (e) { toast(e.message, 'err'); }
  },

  canEdit() { return this.pk.length > 0; },

  render() {
    const hasPK = this.canEdit();
    /* toolbar */
    const search = el('input', { type: 'search', placeholder: 'Search all columns…', value: this.q });
    search.addEventListener('input', debounce(() => { this.q = search.value.trim(); this.page = 1; this.fetch(); }, 350));

    const saveBtn = el('button', { class: 'btn btn-sm btn-primary', onclick: () => this.save() }, '💾 Save changes (0)');
    this.saveBtn = saveBtn;

    const perSel = el('select', { onchange: e => { this.per = +e.target.value; this.page = 1; this.fetch(); } });
    for (const n of [25, 50, 100, 250]) perSel.append(el('option', { value: n, selected: n === this.per ? '' : null }, n + ' / page'));
    if (this.per !== 25 && this.per !== 50 && this.per !== 100 && this.per !== 250) perSel.append(el('option', { value: this.per, selected: '' }, this.per + ' / page'));

    const toolbar = el('div', { class: 'data-toolbar' },
      el('span', { class: 'dt-name' }, this.table),
      search,
      el('button', { class: 'btn btn-sm btn-ghost', onclick: () => this.addRow() }, '+ Insert row'),
      el('button', { class: 'btn btn-sm btn-ghost', onclick: () => this.deleteSelected() }, '🗑 Delete'),
      el('button', { class: 'btn btn-sm btn-ghost', onclick: () => this.fetch() }, '⟳'),
      el('button', { class: 'btn btn-sm btn-ghost', onclick: () => this.exportCSV() }, '⇩ CSV'),
      saveBtn,
      el('span', { class: 'tb-sep' }), perSel,
      !hasPK ? el('span', { class: 'chip chip-danger' }, 'no PK — editing disabled') : null,
    );

    /* filters bar */
    const chips = el('span', {});
    this.filters.forEach((f, i) => chips.append(
      el('span', { class: 'fchip' }, `${f.col} ${f.op} ${f.op.startsWith('IS') ? '' : f.val}`,
        el('button', { onclick: () => { this.filters.splice(i, 1); this.page = 1; this.fetch(); } }, '✕'))));
    const fCol = el('select', {}, this.cols.map(c => el('option', { value: c.c }, c.c)));
    const fOp = el('select', {}, ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'IS NULL', 'IS NOT NULL'].map(o => el('option', {}, o)));
    const fVal = el('input', { placeholder: 'value', style: 'width:120px' });
    const fBar = el('div', { class: 'filters-bar' },
      el('span', { style: 'font-size:11.5px;color:var(--tx2);font-weight:600' }, 'FILTER'),
      fCol, fOp, fVal,
      el('button', { class: 'btn btn-sm btn-ghost', onclick: () => {
        const f = { col: fCol.value, op: fOp.value, val: fVal.value };
        if (!f.op.startsWith('IS') && f.val === '') return toast('Enter a filter value', 'warn');
        this.filters.push(f); this.page = 1; this.fetch();
      } }, '+ Add'),
      chips);

    /* grid */
    const thead = el('thead', {}, el('tr', {},
      el('th', { class: 'row-check' }),
      this.cols.map(c => {
        const th = el('th', { class: 'sortable' }, c.c);
        if (this.sort === c.c) th.append(el('span', { class: 'arr' }, this.dir === 'asc' ? '▲' : '▼'));
        th.addEventListener('click', () => {
          if (this.sort === c.c) { this.dir = this.dir === 'asc' ? 'desc' : 'asc'; }
          else { this.sort = c.c; this.dir = 'asc'; }
          this.fetch();
        });
        return th;
      })));

    const tbody = el('tbody', {});
    if (this.pendingNew) tbody.append(this._newRowTR());
    this.rows.forEach((row, ri) => {
      const tr = el('tr', {});
      const cb = el('input', { type: 'checkbox' });
      cb.addEventListener('change', () => { cb.checked ? this.selected.add(ri) : this.selected.delete(ri); });
      tr.append(el('td', { class: 'row-check' }, cb));
      for (const c of this.cols) {
        const val = row[c.c];
        const td = el('td', { title: val == null ? 'NULL' : String(val) },
          val === null ? el('span', { class: 'null' }, 'NULL') : String(val));
        if (this.dirty.get(ri)?.has(c.c)) td.classList.add('dirty');
        if (hasPK) td.addEventListener('click', () => this._editCell(td, ri, c.c));
        tr.append(td);
      }
      tbody.append(tr);
    });

    const gridWrap = el('div', { class: 'data-grid-wrap' },
      el('table', { class: 'dgrid' }, thead, tbody));

    /* status + pager */
    const pages = Math.max(1, Math.ceil(this.total / this.per));
    const pager = el('div', { class: 'pager' },
      el('button', { onclick: () => { this.page = 1; this.fetch(); }, disabled: this.page <= 1 ? '' : null }, '«'),
      el('button', { onclick: () => { this.page--; this.fetch(); }, disabled: this.page <= 1 ? '' : null }, '‹'),
      el('span', { class: 'pg-info' }, `${this.page} / ${pages}`),
      el('button', { onclick: () => { this.page++; this.fetch(); }, disabled: this.page >= pages ? '' : null }, '›'),
      el('button', { onclick: () => { this.page = pages; this.fetch(); }, disabled: this.page >= pages ? '' : null }, '»'));
    const status = el('div', { class: 'data-status' },
      el('span', {}, `${this.total.toLocaleString()} rows · PK: ${this.pk.join(', ') || '—'}`), pager);

    this.root.innerHTML = '';
    this.root.append(toolbar, fBar, gridWrap, status);
  },

  _newRowTR() {
    const tr = el('tr', { class: 'row-new' });
    tr.append(el('td', { class: 'row-check' }, '✚'));
    for (const c of this.cols) {
      const td = el('td', { class: 'editing' });
      const inp = el('input', { placeholder: 'NULL' });
      inp.value = this.pendingNew[c.c] ?? '';
      inp.addEventListener('input', () => { this.pendingNew[c.c] = inp.value; });
      td.append(inp); tr.append(td);
    }
    return tr;
  },

  _editCell(td, ri, col) {
    if (td.classList.contains('editing')) return;
    const orig = this.rows[ri][col];
    td.classList.add('editing');
    td.innerHTML = '';
    const inp = el('input', {});
    inp.value = orig === null ? '' : String(orig);
    if (orig === null) inp.placeholder = 'NULL';
    td.append(inp); inp.focus(); inp.select();
    const commit = () => {
      td.classList.remove('editing');
      let v = inp.value;
      let parsed = (v === '' && orig === null) ? null : (v.toUpperCase() === 'NULL' && v.length === 4 ? null : v);
      const changed = !(parsed === orig || String(parsed ?? '') === String(orig ?? ''));
      if (changed) {
        if (!this.dirty.has(ri)) this.dirty.set(ri, new Map());
        this.dirty.get(ri).set(col, parsed);
        this.rows[ri][col] = parsed;
      } else this.dirty.get(ri)?.delete(col);
      this.render();
    };
    inp.addEventListener('blur', commit);
    inp.addEventListener('keydown', e => {
      if (e.key === 'Enter') { e.preventDefault(); inp.removeEventListener('blur', commit); commit(); }
      if (e.key === 'Escape') { inp.removeEventListener('blur', commit); td.classList.remove('editing'); this.render(); }
    });
  },

  _updateSaveBtn() {
    let n = 0; this.dirty.forEach(m => n += m.size);
    if (this.pendingNew) n++;
    if (this.saveBtn) this.saveBtn.textContent = `💾 Save changes (${n})`;
  },

  addRow() {
    if (!this.canEdit()) return toast('Table has no primary key', 'warn');
    this.pendingNew = {};
    this.render();
    const first = this.root.querySelector('.row-new input');
    first?.focus();
  },

  async save() {
    try {
      let done = 0;
      if (this.pendingNew && Object.values(this.pendingNew).some(v => v !== '')) {
        const values = {};
        for (const [k, v] of Object.entries(this.pendingNew)) {
          if (v === '') continue;
          values[k] = v.toUpperCase() === 'NULL' ? null : v;
        }
        if (Object.keys(values).length) { await api('row_insert', { table: this.table, values }); done++; }
        this.pendingNew = null;
      }
      for (const [ri, cols] of [...this.dirty.entries()]) {
        const row = this.rows[ri]; if (!row || !cols.size) continue;
        const pk = this.pk.map(c => ({ col: c, val: row[c] ?? null }));
        const set = Object.fromEntries(cols);
        const r = await api('row_save', { table: this.table, pk, set });
        done += r.affected || 1;
      }
      toast(`Saved ${done} change${done === 1 ? '' : 's'} ✓`, 'ok');
      await this.fetch();
    } catch (e) { toast(e.message, 'err'); }
  },

  async deleteSelected() {
    if (!this.selected.size) return toast('Select rows first (checkboxes)', 'warn');
    if (!this.canEdit()) return toast('Table has no primary key', 'warn');
    const yes = await confirmBox(`Delete ${this.selected.size} row(s) from ${this.table}? This cannot be undone.`,
      { title: 'Delete rows' });
    if (!yes) return;
    try {
      for (const ri of this.selected) {
        const row = this.rows[ri]; if (!row) continue;
        await api('row_delete', { table: this.table, pk: this.pk.map(c => ({ col: c, val: row[c] ?? null })) });
      }
      toast('Rows deleted', 'ok');
      await this.fetch();
    } catch (e) { toast(e.message, 'err'); }
  },

  exportCSV() {
    const cols = this.cols.map(c => c.c);
    download(`${this.table}.csv`, rowsToCSV(this.rows, cols), 'text/csv');
  },
};

/* keep save button fresh while editing */
const _origEdit = App.data._editCell.bind(App.data);
App.data._editCell = function (td, ri, col) {
  _origEdit(td, ri, col);
  td.querySelector('input')?.addEventListener('input', () => this._updateSaveBtn());
};
