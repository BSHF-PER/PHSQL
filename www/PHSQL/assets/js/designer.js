/* PHSQL — Visual Designer & ERD canvas engine + SQL generator */
'use strict';

const NODE_W = 240, HEAD_H = 36, ROW_H = 25, NODE_PAD = 7;

const COL_TYPES = ['INT','BIGINT','SMALLINT','TINYINT','MEDIUMINT','DECIMAL','FLOAT','DOUBLE','VARCHAR','CHAR','TEXT',
  'TINYTEXT','MEDIUMTEXT','LONGTEXT','BLOB','DATE','DATETIME','TIMESTAMP','TIME','YEAR','JSON','BOOLEAN','ENUM','SET'];
const NEEDS_LEN  = new Set(['VARCHAR','CHAR','DECIMAL','FLOAT','DOUBLE','VARBINARY','BINARY']);
const DEFAULT_LEN = { VARCHAR: '255', CHAR: '1', DECIMAL: '10,2', TINYINT: '', INT: '', BIGINT: '' };
const NUMERIC = new Set(['INT','BIGINT','SMALLINT','TINYINT','MEDIUMINT','DECIMAL','FLOAT','DOUBLE','BOOLEAN']);

/* styles embedded inside the SVG so PNG export keeps them */
const SVG_STYLE = `
.node-body{fill:var(--bg2);stroke:var(--line);stroke-width:1;rx:12px;filter:drop-shadow(0 5px 12px rgba(0,0,0,.3))}
.node-head{fill:var(--bg3)}
.node-title{font-family:var(--f-disp);font-weight:600;font-size:13px;fill:var(--tx0)}
.node-count{font-family:var(--f-mono);font-size:9.5px;fill:var(--tx2)}
.row-bg{fill:var(--acc);opacity:0}
.node-row.selected .row-bg{opacity:.14}
.col-name{font-family:var(--f-mono);font-size:11px;fill:var(--tx1)}
.node-row.is-pk .col-name{fill:var(--acc);font-weight:600}
.col-type{font-family:var(--f-mono);font-size:9.5px;fill:var(--tx2)}
.pk-dot{fill:var(--acc)}
.port{fill:var(--bg0);stroke:var(--tx2);stroke-width:1.2;opacity:.55}
.port.link-hot{opacity:1;stroke:var(--cyan)}
.edge{fill:none;stroke:var(--cyan);stroke-width:1.7;opacity:.75}
.edge-temp{fill:none;stroke:var(--green);stroke-width:2;stroke-dasharray:6 5}
`;

/* ══════════════ SQL generator ══════════════ */

function typeSQL(c) {
  let t = c.type.toUpperCase();
  if (t === 'BOOLEAN') return 'TINYINT(1)';
  if ((t === 'ENUM' || t === 'SET') && c.length) return `${t}(${c.length})`;
  if (c.length && (NEEDS_LEN.has(t) || t === 'TINYINT')) return `${t}(${c.length})`;
  return t;
}
function defaultClause(c) {
  if (c.default === null || c.default === undefined || c.default === '') return '';
  const d = String(c.default);
  const up = d.toUpperCase();
  if (['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()', 'NOW()'].includes(up)) return ' DEFAULT CURRENT_TIMESTAMP';
  if (NUMERIC.has(c.type.toUpperCase()) && /^-?\d+(\.\d+)?$/.test(d)) return ` DEFAULT ${d}`;
  return ` DEFAULT '${d.replace(/'/g, "''")}'`;
}
function columnDef(c) {
  return `${qi(c.name)} ${typeSQL(c)}${c.unsigned ? ' UNSIGNED' : ''}${c.nullable ? ' NULL' : ' NOT NULL'}${defaultClause(c)}${c.ai ? ' AUTO_INCREMENT' : ''}${c.comment ? ` COMMENT '${c.comment.replace(/'/g,"''")}'` : ''}`;
}
function topoTables(tables) {
  const byName = new Map(tables.map(t => [t.name, t]));
  const indeg = new Map(tables.map(t => [t.name, 0]));
  const adj = new Map(tables.map(t => [t.name, []]));
  for (const t of tables) for (const fk of t.fks) {
    if (fk.refTable !== t.name && byName.has(fk.refTable)) {
      indeg.set(t.name, indeg.get(t.name) + 1);
      adj.get(fk.refTable).push(t.name);
    }
  }
  const q = tables.filter(t => !indeg.get(t.name)).map(t => t.name);
  const out = [];
  while (q.length) {
    const n = q.shift(); out.push(byName.get(n));
    for (const m of adj.get(n)) { indeg.set(m, indeg.get(m) - 1); if (!indeg.get(m)) q.push(m); }
  }
  for (const t of tables) if (!out.includes(t)) out.push(t); // cycles fallback
  return out;
}
function createTableSQL(t) {
  const lines = t.columns.map(c => '  ' + columnDef(c));
  const pkCols = t.pk.filter(p => t.columns.some(c => c.name === p));
  if (pkCols.length) lines.push(`  PRIMARY KEY (${pkCols.map(qi).join(', ')})`);
  for (const ix of t.indexes)
    lines.push(`  ${ix.unique ? 'UNIQUE ' : ''}INDEX ${qi(ix.name)} (${ix.columns.map(qi).join(', ')})`);
  for (const fk of t.fks) {
    const cols = fk.columns || [fk.column], refs = fk.refColumns || [fk.refColumn];
    lines.push(`  CONSTRAINT ${qi(fk.name)} FOREIGN KEY (${cols.map(qi).join(', ')}) REFERENCES ${qi(fk.refTable)} (${refs.map(qi).join(', ')})`
      + ` ON DELETE ${fk.onDelete || 'NO ACTION'} ON UPDATE ${fk.onUpdate || 'NO ACTION'}`);
  }
  return `CREATE TABLE ${qi(t.name)} (\n${lines.join(',\n')}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`;
}

/** diff old(live) → new(design) and produce labelled statements */
function migrationStatements(oldS, newS) {
  const stmts = [];
  const oldMap = new Map(oldS.tables.map(t => [t.name, t]));
  const newMap = new Map(newS.tables.map(t => [t.name, t]));

  for (const t of oldS.tables) if (!newMap.has(t.name))
    stmts.push({ kind: 'drop', label: `Drop table ${t.name}`, sql: `DROP TABLE ${qi(t.name)}` });

  for (const t of topoTables(newS.tables)) if (!oldMap.has(t.name))
    stmts.push({ kind: 'add', label: `Create table ${t.name}`, sql: createTableSQL(t) });

  for (const t of newS.tables) {
    const o = oldMap.get(t.name);
    if (!o) continue;
    const alt = [];
    const oCols = new Map(o.columns.map(c => [c.name, c]));
    const nCols = new Map(t.columns.map(c => [c.name, c]));

    for (const c of t.columns) if (!oCols.has(c.name)) alt.push({ s: `ADD COLUMN ${columnDef(c)}`, kind: 'add' });
    for (const c of o.columns) if (!nCols.has(c.name)) alt.push({ s: `DROP COLUMN ${qi(c.name)}`, kind: 'drop' });

    const sig = c => [typeSQL(c), !!c.unsigned, !!c.nullable, c.default ?? '', !!c.ai].join('|');
    for (const c of t.columns) {
      const oc = oCols.get(c.name);
      if (oc && sig(oc) !== sig(c)) alt.push({ s: `MODIFY COLUMN ${columnDef(c)}`, kind: 'alter' });
    }
    if ((o.pk || []).join(',') !== (t.pk || []).join(',')) {
      if (o.pk?.length) alt.push({ s: 'DROP PRIMARY KEY', kind: 'drop' });
      if (t.pk?.length) alt.push({ s: `ADD PRIMARY KEY (${t.pk.map(qi).join(', ')})`, kind: 'add' });
    }
    const ixSig = ix => (ix.unique ? 'U:' : 'I:') + ix.columns.join(',');
    const oIx = new Map(o.indexes.map(ix => [ixSig(ix), ix]));
    const nIx = new Map(t.indexes.map(ix => [ixSig(ix), ix]));
    for (const [s, ix] of oIx) if (!nIx.has(s)) alt.push({ s: `DROP INDEX ${qi(ix.name)}`, kind: 'drop' });
    for (const [s, ix] of nIx) if (!oIx.has(s))
      alt.push({ s: `ADD ${ix.unique ? 'UNIQUE ' : ''}INDEX ${qi(ix.name)} (${ix.columns.map(qi).join(', ')})`, kind: 'add' });

    const fkSig = f => `${f.column || (f.columns||[]).join(',')}->${f.refTable}.${f.refColumn || (f.refColumns||[]).join(',')}/${f.onDelete}/${f.onUpdate}`;
    const oFk = new Map(o.fks.map(f => [fkSig(f), f]));
    const nFk = new Map(t.fks.map(f => [fkSig(f), f]));
    for (const [s, f] of oFk) if (!nFk.has(s)) alt.push({ s: `DROP FOREIGN KEY ${qi(f.name)}`, kind: 'drop' });
    for (const [s, f] of nFk) if (!oFk.has(s)) {
      const cols = f.columns || [f.column], refs = f.refColumns || [f.refColumn];
      alt.push({ s: `ADD CONSTRAINT ${qi(f.name)} FOREIGN KEY (${cols.map(qi).join(', ')}) REFERENCES ${qi(f.refTable)} (${refs.map(qi).join(', ')}) ON DELETE ${f.onDelete || 'NO ACTION'} ON UPDATE ${f.onUpdate || 'NO ACTION'}`, kind: 'add' });
    }
    const kind = alt.some(a => a.kind === 'drop') ? 'drop' : alt.some(a => a.kind === 'add') ? 'add' : 'alter';
    if (alt.length) stmts.push({ kind, label: `Alter table ${t.name}`,
      sql: `ALTER TABLE ${qi(t.name)}\n  ` + alt.map(a => a.s).join(',\n  ') });
  }
  return stmts;
}

/* ══════════════ Canvas engine ══════════════ */

class NodeCanvas {
  constructor(wrapId, svgId, opts) {
    this.wrap = $('#' + wrapId);
    this.svg  = $('#' + svgId);
    this.opts = opts;               // { editable, source(), onSelect(), onChanged() }
    this.px = 60; this.py = 60; this.z = 1;
    this.sel = { table: null, col: null };
    this.nodeEls = new Map();
    this.drag = null; this.link = null;
    this.viewport = svgEl('g', { class: 'viewport' });
    this.svg.append(svgEl('style', {}, SVG_STYLE), this.viewport);
    this._bind();
  }
  get tables() { return this.opts.source(); }

  _bind() {
    const svg = this.svg;
    svg.addEventListener('wheel', e => {
      e.preventDefault();
      const r = svg.getBoundingClientRect();
      const sx = e.clientX - r.left, sy = e.clientY - r.top;
      const z2 = clamp(this.z * Math.exp(-e.deltaY * 0.0012), 0.2, 2.5);
      this.px = sx - (sx - this.px) * (z2 / this.z);
      this.py = sy - (sy - this.py) * (z2 / this.z);
      this.z = z2; this._apply();
    }, { passive: false });

    svg.addEventListener('pointerdown', e => {
      if (e.button === 1 || e.button === 2) return;
      const port = e.target.closest('.port');
      const head = e.target.closest('.node-head');
      const row  = e.target.closest('.node-row');
      const node = e.target.closest('.node');

      if (port && this.opts.editable) {
        const t = this.tables.find(x => x.name === port.closest('.node').dataset.table);
        const colName = port.closest('.node-row').dataset.col;
        this.link = { from: t, col: colName };
        this.tempPath = svgEl('path', { class: 'edge-temp' });
        this.viewport.append(this.tempPath);
        e.preventDefault(); return;
      }
      if (head && this.opts.editable) {
        const t = this.tables.find(x => x.name === head.closest('.node').dataset.table);
        this.drag = { type: 'node', t, start: this._world(e), orig: { ...t.pos }, moved: false };
        e.preventDefault(); return;
      }
      if (node) {
        const name = node.dataset.table;
        const colName = row ? row.dataset.col : null;
        this.sel = { table: name, col: colName };
        this._markSelected();
        this.opts.onSelect?.(this.sel);
        if (!this.opts.editable) return;
        if (!row && !head) { this.drag = { type: 'pan', sx: e.clientX, sy: e.clientY, opx: this.px, opy: this.py }; }
        return;
      }
      this.sel = { table: null, col: null };
      this._markSelected();
      this.opts.onSelect?.(this.sel);
      this.drag = { type: 'pan', sx: e.clientX, sy: e.clientY, opx: this.px, opy: this.py };
    });

    window.addEventListener('pointermove', e => {
      if (this.link) {
        const w = this._world(e);
        const from = this._portXY(this.link.from, this.link.col, w.x > this.link.from.pos.x + NODE_W / 2 ? 'r' : 'l');
        const dx = clamp(Math.abs(w.x - from.x) * .5, 40, 170);
        const dir = w.x >= from.x ? 1 : -1;
        this.tempPath.setAttribute('d', `M${from.x},${from.y} C${from.x + dx * dir},${from.y} ${w.x - dx * dir},${w.y} ${w.x},${w.y}`);
        $$('.port.link-hot', this.svg).forEach(p => p.classList.remove('link-hot'));
        const under = document.elementFromPoint(e.clientX, e.clientY)?.closest?.('.port');
        if (under && under.closest('.node').dataset.table !== this.link.from.name) under.classList.add('link-hot');
        return;
      }
      if (!this.drag) return;
      if (this.drag.type === 'pan') {
        this.px = this.drag.opx + (e.clientX - this.drag.sx);
        this.py = this.drag.opy + (e.clientY - this.drag.sy);
        this._apply();
      } else if (this.drag.type === 'node') {
        const w = this._world(e);
        this.drag.moved = true;
        this.drag.t.pos.x = Math.round(this.drag.orig.x + (w.x - this.drag.start.x));
        this.drag.t.pos.y = Math.round(this.drag.orig.y + (w.y - this.drag.start.y));
        this.nodeEls.get(this.drag.t.name)?.setAttribute('transform', `translate(${this.drag.t.pos.x},${this.drag.t.pos.y})`);
        this._rafEdges();
      }
    });
    window.addEventListener('pointerup', e => {
      if (this.link) {
        const under = document.elementFromPoint(e.clientX, e.clientY)?.closest?.('.port');
        this.tempPath?.remove();
        if (under) {
          const toTable = this.tables.find(x => x.name === under.closest('.node').dataset.table);
          const toCol = under.closest('.node-row').dataset.col;
          if (toTable && toTable.name !== this.link.from.name)
            this.opts.onLink?.(this.link.from, this.link.col, toTable, toCol);
        }
        $$('.port.link-hot', this.svg).forEach(p => p.classList.remove('link-hot'));
        this.link = null;
      }
      if (this.drag?.type === 'node' && this.drag.moved) this.opts.onChanged?.();
      this.drag = null;
      this.svg.classList.remove('panning');
    });

    svg.addEventListener('dblclick', e => {
      const node = e.target.closest('.node');
      if (node && this.opts.editable) this.opts.onRename?.(this.tables.find(t => t.name === node.dataset.table));
    });
  }

  _world(e) {
    const r = this.svg.getBoundingClientRect();
    return { x: (e.clientX - r.left - this.px) / this.z, y: (e.clientY - r.top - this.py) / this.z };
  }
  _apply() {
    this.viewport.setAttribute('transform', `translate(${this.px},${this.py}) scale(${this.z})`);
    const g = 24 * this.z;
    this.wrap.style.backgroundSize = `${g}px ${g}px`;
    this.wrap.style.backgroundPosition = `${this.px}px ${this.py}px`;
    const zv = $('#' + this.svg.id.replace('-svg', '-zoom'));
    if (zv) zv.textContent = Math.round(this.z * 100) + '%';
  }
  _rafEdges() {
    if (this._edgeRaf) return;
    this._edgeRaf = requestAnimationFrame(() => { this._edgeRaf = null; this.drawEdges(); });
  }

  /* ---------- rendering ---------- */
  render() {
    this.viewport.querySelectorAll('.layer-edges,.layer-nodes,.empty-hint').forEach(n => n.remove());
    const gE = svgEl('g', { class: 'layer-edges' });
    const gN = svgEl('g', { class: 'layer-nodes' });
    this.viewport.append(gE, gN);
    this.edgeLayer = gE;
    this.nodeEls = new Map();
    for (const t of this.tables) {
      if (!t.pos) t.pos = { x: 40, y: 40 };
      gN.append(this._buildNode(t));
    }
    this.drawEdges();
    this._markSelected();
    if (!this.tables.length) {
      this.viewport.append(svgEl('text', { class: 'node-count', x: 20, y: 0, 'font-size': 14 },
        'Canvas is empty — add a table to start designing.'));
    }
    this._apply();
  }

  _buildNode(t) {
    const H = HEAD_H + t.columns.length * ROW_H + NODE_PAD;
    const g = svgEl('g', { class: 'node', transform: `translate(${t.pos.x},${t.pos.y})`, 'data-table': t.name });
    g.append(svgEl('rect', { class: 'node-body', width: NODE_W, height: H, rx: 12 }));
    const headPath = `M0,${HEAD_H} V12 Q0,0 12,0 H${NODE_W - 12} Q${NODE_W},0 ${NODE_W},12 V${HEAD_H} Z`;
    g.append(svgEl('path', { class: 'node-head', d: headPath }));
    g.append(svgEl('text', { class: 'node-title', x: 14, y: 23 }, t.name));
    g.append(svgEl('text', { class: 'node-count', x: NODE_W - 12, y: 22, 'text-anchor': 'end' }, `${t.columns.length} cols`));

    t.columns.forEach((c, i) => {
      const y0 = HEAD_H + i * ROW_H, mid = y0 + ROW_H / 2;
      const isPk = t.pk.includes(c.name);
      const row = svgEl('g', { class: 'node-row' + (isPk ? ' is-pk' : ''), 'data-col': c.name });
      row.append(svgEl('rect', { class: 'row-bg', x: 3, y: y0 + 1, width: NODE_W - 6, height: ROW_H - 2, rx: 6 }));
      if (isPk) row.append(svgEl('circle', { class: 'pk-dot', cx: 15, cy: mid, r: 3.4 }));
      row.append(svgEl('text', { class: 'col-name', x: isPk ? 26 : 15, y: mid + 3.5 }, c.name));
      let ty = c.type.toLowerCase();
      if (c.length && NEEDS_LEN.has(c.type.toUpperCase())) ty += `(${c.length})`;
      if (c.ai) ty += ' ai';
      row.append(svgEl('text', { class: 'col-type', x: NODE_W - 15, y: mid + 3.5, 'text-anchor': 'end' }, ty));
      row.append(svgEl('circle', { class: 'port', cx: 0, cy: mid, r: 4.5 }));
      row.append(svgEl('circle', { class: 'port', cx: NODE_W, cy: mid, r: 4.5 }));
      g.append(row);
    });
    return g;
  }

  _portXY(t, colName, side) {
    const i = Math.max(0, t.columns.findIndex(c => c.name === colName));
    return { x: t.pos.x + (side === 'r' ? NODE_W : 0), y: t.pos.y + HEAD_H + i * ROW_H + ROW_H / 2 };
  }

  drawEdges() {
    if (!this.edgeLayer) return;
    this.edgeLayer.innerHTML = '';
    const byName = new Map(this.tables.map(t => [t.name, t]));
    for (const t of this.tables) {
      for (const fk of t.fks) {
        const child = t, parent = byName.get(fk.refTable);
        const col = fk.column || (fk.columns || [])[0];
        const refCol = fk.refColumn || (fk.refColumns || [])[0];
        if (!parent || !col || !refCol) continue;
        const side = (child.pos.x + NODE_W / 2) <= (parent.pos.x + NODE_W / 2)
          ? ['r', 'l'] : ['l', 'r'];
        const p1 = this._portXY(child, col, side[0]);
        const p2 = this._portXY(parent, refCol, side[1]);
        const dir = side[0] === 'r' ? 1 : -1;
        const dx = clamp(Math.abs(p2.x - p1.x) * .5, 45, 170);
        const d = `M${p1.x},${p1.y} C${p1.x + dx * dir},${p1.y} ${p2.x - dx * dir},${p2.y} ${p2.x},${p2.y}`;
        const hit = svgEl('path', { class: 'edge-hit', d });
        hit.append(svgEl('title', {}, `${t.name}.${col} → ${parent.name}.${refCol}  ·  FK ${fk.name}\nON DELETE ${fk.onDelete || 'NO ACTION'} · ON UPDATE ${fk.onUpdate || 'NO ACTION'}`));
        if (this.opts.editable) {
          hit.addEventListener('contextmenu', e => {
            e.preventDefault();
            contextMenu(e.clientX, e.clientY, [
              { label: `✕ Drop FK ${fk.name}`, danger: true, onClick: () => {
                t.fks = t.fks.filter(f => f !== fk);
                this.render(); this.opts.onChanged?.();
                toast(`Foreign key ${fk.name} removed`, 'ok');
              }},
            ]);
          });
        }
        this.edgeLayer.append(svgEl('path', { class: 'edge', d }), hit);
      }
    }
  }

  _markSelected() {
    $$('.node', this.svg).forEach(n => n.classList.toggle('selected', n.dataset.table === this.sel.table));
    $$('.node-row', this.svg).forEach(r => {
      const tName = r.closest('.node').dataset.table;
      r.classList.toggle('selected', tName === this.sel.table && r.dataset.col === this.sel.col);
    });
  }

  select(table, col = null) {
    this.sel = { table, col };
    this._markSelected();
  }
  focusTable(name) {
    const t = this.tables.find(x => x.name === name);
    if (!t) return;
    const r = this.wrap.getBoundingClientRect();
    this.z = 1;
    this.px = r.width / 2 - t.pos.x - NODE_W / 2;
    this.py = r.height / 2 - t.pos.y - 60;
    this.select(name);
    this._apply(); this.drawEdges();
  }
  zoomBy(f) {
    const r = this.wrap.getBoundingClientRect();
    const sx = r.width / 2, sy = r.height / 2;
    const z2 = clamp(this.z * f, 0.2, 2.5);
    this.px = sx - (sx - this.px) * (z2 / this.z);
    this.py = sy - (sy - this.py) * (z2 / this.z);
    this.z = z2; this._apply();
  }
  fit() {
    if (!this.tables.length) return;
    let minX = 1e9, minY = 1e9, maxX = -1e9, maxY = -1e9;
    for (const t of this.tables) {
      minX = Math.min(minX, t.pos.x); minY = Math.min(minY, t.pos.y);
      maxX = Math.max(maxX, t.pos.x + NODE_W);
      maxY = Math.max(maxY, t.pos.y + HEAD_H + t.columns.length * ROW_H);
    }
    const r = this.wrap.getBoundingClientRect();
    const bw = maxX - minX + 120, bh = maxY - minY + 120;
    this.z = clamp(Math.min(r.width / bw, r.height / bh), 0.2, 1.3);
    this.px = (r.width - (maxX - minX) * this.z) / 2 - minX * this.z;
    this.py = (r.height - (maxY - minY) * this.z) / 2 - minY * this.z;
    this._apply();
  }
  autoLayout() {
    const ordered = topoTables(this.tables);
    const level = new Map();
    const byName = new Map(this.tables.map(t => [t.name, t]));
    for (const t of ordered) {
      let lv = 0;
      for (const fk of t.fks) {
        if (fk.refTable !== t.name && byName.has(fk.refTable))
          lv = Math.max(lv, (level.get(fk.refTable) ?? 0) + 1);
      }
      level.set(t.name, lv);
    }
    const cols = new Map();
    for (const t of ordered) {
      const lv = level.get(t.name);
      if (!cols.has(lv)) cols.set(lv, []);
      cols.get(lv).push(t);
    }
    [...cols.keys()].sort((a, b) => a - b).forEach((lv, ci) => {
      let y = 40;
      for (const t of cols.get(lv)) {
        t.pos = { x: 40 + ci * 340, y };
        y += HEAD_H + t.columns.length * ROW_H + NODE_PAD + 60;
      }
    });
    this.render(); this.fit(); this.opts.onChanged?.();
  }
  centerWorld() {
    const r = this.wrap.getBoundingClientRect();
    return { x: (r.width / 2 - this.px) / this.z - NODE_W / 2, y: (r.height / 2 - this.py) / this.z - 40 };
  }
}

/* PNG export (inlines computed theme colors) */
function exportCanvasPNG(svg, name) {
  const clone = svg.cloneNode(true);
  const r = svg.getBoundingClientRect();
  clone.setAttribute('width', r.width); clone.setAttribute('height', r.height);
  clone.setAttribute('xmlns', SVGNS);
  const css = getComputedStyle(document.documentElement);
  const style = clone.querySelector('style');
  if (style) style.textContent = style.textContent.replace(/var\((--[\w-]+)\)/g,
    (_, v) => css.getPropertyValue(v).trim() || '#888');
  const bg = svgEl('rect', { width: '100%', height: '100%', fill: css.getPropertyValue('--bg0').trim() || '#0b0e12' });
  clone.insertBefore(bg, clone.firstChild);
  const data = new XMLSerializer().serializeToString(clone);
  const img = new Image();
  img.onload = () => {
    const c = document.createElement('canvas');
    c.width = r.width * 2; c.height = r.height * 2;
    const ctx = c.getContext('2d');
    ctx.scale(2, 2); ctx.drawImage(img, 0, 0);
    c.toBlob(b => {
      const a = el('a', { href: URL.createObjectURL(b), download: name + '.png' });
      a.click();
    });
  };
  img.onerror = () => toast('PNG export failed', 'err');
  img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(data);
}
