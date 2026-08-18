<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PHSQL — Visual Database Studio</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23ffb454'/><ellipse cx='16' cy='11' rx='8' ry='3.4' fill='%23151a21'/><path d='M8 11v9c0 1.9 3.6 3.4 8 3.4s8-1.5 8-3.4v-9' stroke='%23151a21' stroke-width='2.6' fill='none'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- splash -->
<div id="splash">
  <div class="splash-logo">PH<span>SQL</span></div>
  <div class="splash-bar"><i></i></div>
</div>

<!-- ══════════ CONNECTION SCREEN ══════════ -->
<section id="connect-screen" class="screen">
  <aside class="conn-brand">
    <div class="brand-mark">
      <div class="brand-db"></div>
      <h1>PH<em>SQL</em></h1>
    </div>
    <p class="tagline">Visual Database Studio for MySQL &amp; MariaDB — a modern, drag-and-drop alternative to phpMyAdmin.</p>
    <div class="term-demo" aria-hidden="true">
      <div class="term-line"><span class="t-p">phsql&gt;</span> <span id="term-type"></span><span class="caret"></span></div>
      <div class="term-line t-dim">✓ schema synced · 3 tables · 2 relations</div>
    </div>
    <ul class="brand-feats">
      <li>◆ Visual table designer — nodes &amp; drag-and-drop FKs</li>
      <li>◆ Live ERD with infinite zoom / pan canvas</li>
      <li>◆ Inline data editing, SQL editor &amp; migrations</li>
    </ul>
  </aside>

  <div class="conn-main">
    <h2 class="conn-title">Connect to database</h2>

    <div class="saved-block">
      <div class="saved-head"><span>Saved connections</span><span class="saved-count" id="saved-count"></span></div>
      <div id="conn-list" class="saved-list"></div>
    </div>

    <form id="conn-form" autocomplete="off">
      <div class="form-grid">
        <label class="field span2"><span>Connection name</span>
          <input id="f-name" placeholder="My Project — dev"></label>
        <label class="field span2"><span>Host</span>
          <input id="f-host" value="127.0.0.1" required></label>
        <label class="field"><span>Port</span>
          <input id="f-port" value="3306" inputmode="numeric"></label>
        <label class="field"><span>Username</span>
          <input id="f-user" value="root" required></label>
        <label class="field span2"><span>Password</span>
          <input id="f-pass" type="password" placeholder="••••••••"></label>
        <label class="field span2"><span>Database</span>
          <input id="f-db" placeholder="my_app" required></label>
      </div>
      <label class="remember"><input type="checkbox" id="f-remember" checked> Remember password on this device (AES-encrypted)</label>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" id="btn-test">Test connection</button>
        <button type="submit" class="btn btn-primary" id="btn-connect">Connect →</button>
      </div>
    </form>
    <p class="conn-note">Credentials are kept in your browser session only. Saved connections use AES-GCM encryption in LocalStorage.</p>
    <p class="conn-note">Behzad Shahbazi Fard <span><a style="color: #ffb454;" href="https://github.com/BSHF-PER">My GitHub</a></span></p>
    <p calss="conn-note">GitHub for <span><a style="color: #ffb454;" href="https://github.com/BSHF-PER/PHSQL">this project</a></span></p>
  </div>
</section>

<!-- ══════════ APP SHELL ══════════ -->
<div id="app" hidden>
  <header id="topbar">
    <button class="icon-btn only-mobile" id="btn-side" title="Tables">☰</button>
    <div class="brand">PH<span>SQL</span></div>
    <div class="conn-chip" id="conn-chip" title="Active connection"></div>
    <nav class="views" id="view-tabs">
      <button class="view-tab active" data-view="designer"><i class="vt-ico">▦</i>Designer <kbd>1</kbd></button>
      <button class="view-tab" data-view="erd"><i class="vt-ico">◈</i>ERD <kbd>2</kbd></button>
      <button class="view-tab" data-view="data"><i class="vt-ico">▤</i>Data <kbd>3</kbd></button>
      <button class="view-tab" data-view="sql"><i class="vt-ico">⌘</i>SQL Editor <kbd>4</kbd></button>
    </nav>
    <div class="top-actions">
      <button class="btn btn-primary btn-sm" id="btn-migrate" title="Generate migration (Ctrl+S)">⚡ Migrate</button>
      <button class="icon-btn" id="btn-schema-menu" title="Schema tools">⇅</button>
      <button class="icon-btn" id="btn-theme" title="Toggle theme">◐</button>
      <button class="icon-btn" id="btn-disconnect" title="Disconnect">⏻</button>
    </div>
  </header>

  <div id="shell">
    <!-- LEFT: tables -->
    <aside id="sidebar">
      <div class="side-head">
        <input id="side-search" placeholder="Filter tables…  ( / )">
        <button class="icon-btn" id="btn-refresh" title="Reload schema">⟳</button>
      </div>
      <ul id="table-list"></ul>
      <div class="side-foot"><span id="side-count"></span><button class="link-btn" id="btn-new-table">+ New table</button></div>
    </aside>
    <div id="side-backdrop" class="backdrop" hidden></div>

    <!-- CENTER -->
    <main id="main">
      <section class="view active" id="view-designer">
        <div class="view-toolbar">
          <div class="tb-left">
            <button class="btn btn-sm btn-primary" id="d-add">+ Add table</button>
            <button class="btn btn-sm btn-ghost" id="d-layout" title="Auto layout (Alt+L)">✦ Auto layout</button>
            <button class="btn btn-sm btn-ghost" id="d-fit">⤢ Fit</button>
          </div>
          <div class="tb-right">
            <span class="tb-hint">drag ports → link FKs · double-click header → rename</span>
            <div class="zoom-group">
              <button class="icon-btn" id="d-zout">−</button>
              <span class="zoom-val" id="d-zoom">100%</span>
              <button class="icon-btn" id="d-zin">+</button>
              <button class="icon-btn" id="d-png" title="Export PNG">📷</button>
            </div>
          </div>
        </div>
        <div class="canvas-wrap" id="d-canvas"><svg class="canvas-svg" id="d-svg"></svg></div>
      </section>

      <section class="view" id="view-erd">
        <div class="view-toolbar">
          <div class="tb-left">
            <span class="chip chip-info">read-only · generated from live schema</span>
            <button class="btn btn-sm btn-ghost" id="e-layout">✦ Auto layout</button>
            <button class="btn btn-sm btn-ghost" id="e-fit">⤢ Fit</button>
          </div>
          <div class="tb-right">
            <div class="zoom-group">
              <button class="icon-btn" id="e-zout">−</button>
              <span class="zoom-val" id="e-zoom">100%</span>
              <button class="icon-btn" id="e-zin">+</button>
              <button class="icon-btn" id="e-png" title="Export PNG">📷</button>
            </div>
          </div>
        </div>
        <div class="canvas-wrap" id="e-canvas"><svg class="canvas-svg" id="e-svg"></svg></div>
      </section>

      <section class="view" id="view-data">
        <div id="data-root">
          <div class="empty-state">
            <div class="empty-ico">▤</div>
            <h3>No table selected</h3>
            <p>Pick a table from the left panel to browse &amp; edit its data.</p>
          </div>
        </div>
      </section>

      <section class="view" id="view-sql">
        <div class="ed-toolbar">
          <button class="btn btn-sm btn-primary" id="q-run" title="Ctrl+Enter">▶ Run</button>
          <button class="btn btn-sm btn-ghost" id="q-history">🕘 History</button>
          <span class="tb-sep"></span>
          <span class="tb-hint">export results:</span>
          <button class="btn btn-sm btn-ghost" id="q-csv">CSV</button>
          <button class="btn btn-sm btn-ghost" id="q-json">JSON</button>
          <button class="btn btn-sm btn-ghost" id="q-sql">SQL</button>
          <span class="res-status" id="res-status"></span>
        </div>
        <div class="editor-shell" id="editor-shell">
          <div class="gutter" id="q-gutter">1</div>
          <div class="code-wrap">
            <pre class="code-pre" id="q-pre" aria-hidden="true"></pre>
            <textarea class="code-area" id="q-input" spellcheck="false"
              placeholder="-- Write SQL… e.g.  SELECT * FROM users LIMIT 20"></textarea>
            <div class="ac-popup" id="ac-popup" hidden></div>
          </div>
        </div>
        <div class="res-grid-wrap" id="res-wrap"><div id="res-table"></div></div>
      </section>
    </main>

    <!-- RIGHT: inspector -->
    <aside id="inspector">
      <div class="insp-head" id="insp-head">Properties</div>
      <div class="insp-body" id="insp-body"></div>
    </aside>
    <div id="insp-backdrop" class="backdrop" hidden></div>
  </div>

  <footer id="statusbar">
    <span id="sb-conn">—</span>
    <span id="sb-info"></span>
    <span class="sb-right"><kbd>Alt</kbd>+<kbd>1..4</kbd> views · <kbd>?</kbd> shortcuts</span>
  </footer>
</div>

<input type="file" id="file-import" accept=".json,application/json" hidden>
<div id="modal-root"></div>
<div id="toasts"></div>

<script defer src="assets/js/core.js"></script>
<script defer src="assets/js/designer.js"></script>
<script defer src="assets/js/dataview.js"></script>
<script defer src="assets/js/editor.js"></script>
<script defer src="assets/js/app.js"></script>
</body>
</html>
