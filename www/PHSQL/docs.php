<?php
declare(strict_types=1);
$DOC_VERSION = '1.0.0';
$GENERATED   = date('Y/m/d H:i');
$toc = [
['overview',     'Product Overview'],
['files',        'File Structure'],
['architecture', 'Architecture & Request Flow'],
['api',          'API Protocol & Actions'],
['internals',    'Backend Internals'],
['security',     'Security'],
['vault',        'LocalStorage Encryption'],
['modules',      'Frontend Modules'],
['schema-model', 'Schema Data Model'],
['designer',     'Visual Designer Engine'],
['sqlgen',       'SQL Generation & Migration'],
['erd',          'ERD & PNG Export'],
['dataview',     'Data Viewer'],
['editor',       'SQL Editor'],
['jsonschema',   'JSON Schema Format'],
['shortcuts',    'Keyboard Shortcuts'],
['theming',      'Theming & Design'],
['scenarios',    'Use Case Scenarios'],
['trouble',      'Troubleshooting'],
['extending',    'Extension Guide'],
];
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PHSQL Docs — Complete Documentation</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23ffb454'/><ellipse cx='16' cy='11' rx='8' ry='3.4' fill='%23151a21'/><path d='M8 11v9c0 1.9 3.6 3.4 8 3.4s8-1.5 8-3.4v-9' stroke='%23151a21' stroke-width='2.6' fill='none'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{
--f-body:'Vazirmatn',system-ui,Tahoma,sans-serif;
--f-mono:'JetBrains Mono',ui-monospace,Menlo,monospace;
--ease:cubic-bezier(.22,.9,.3,1);
}
[data-theme="dark"]{
color-scheme:dark;
--bg0:#0b0e12; --bg1:#10141a; --bg2:#151a22; --bg3:#1c2330;
--line:#232c3a; --line2:#31405a;
--tx0:#e9eef6; --tx1:#a7b3c4; --tx2:#67788e;
--acc:#ffb454; --acc-soft:rgba(255,180,84,.13);
--cyan:#4cc9f0; --cyan-soft:rgba(76,201,240,.12);
--green:#4ade9d; --green-soft:rgba(74,222,157,.12);
--red:#ff6b7d; --red-soft:rgba(255,107,125,.11);
--grid-dot:#1b2330;
--code-bg:#0d1117; --shadow:0 14px 38px rgba(0,0,0,.45);
}
[data-theme="light"]{
color-scheme:light;
--bg0:#eef1f6; --bg1:#f7f8fb; --bg2:#ffffff; --bg3:#edf1f8;
--line:#d9e0ea; --line2:#b9c6d8;
--tx0:#17212e; --tx1:#46566b; --tx2:#8494a8;
--acc:#d97a08; --acc-soft:rgba(217,122,8,.12);
--cyan:#0d8ec7; --cyan-soft:rgba(13,142,199,.1);
--green:#0f9d63; --green-soft:rgba(15,157,99,.1);
--red:#d63b52; --red-soft:rgba(214,59,82,.09);
--grid-dot:#dde3ec;
--code-bg:#f3f5f9; --shadow:0 10px 30px rgba(23,42,68,.13);
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;scroll-padding-top:86px}
body{
font-family:var(--f-body);font-size:15px;line-height:1.95;color:var(--tx0);
background:
radial-gradient(900px 480px at 90% -5%, var(--acc-soft), transparent 60%),
radial-gradient(700px 500px at -5% 30%, var(--cyan-soft), transparent 55%),
radial-gradient(circle, var(--grid-dot) 1.1px, transparent 1.5px) 0 0/26px 26px,
var(--bg0);
background-attachment:fixed;
}
::selection{background:var(--acc-soft)}
::-webkit-scrollbar{width:10px;height:10px}
::-webkit-scrollbar-thumb{background:var(--line2);border-radius:8px}
a{color:var(--cyan);text-decoration:none}
a:hover{text-decoration:underline}
code,kbd,pre{font-family:var(--f-mono);font-size:.86em}
:not(pre)>code{background:var(--bg3);border:1px solid var(--line);border-radius:6px;padding:1.5px 7px;
direction:ltr;display:inline-block;line-height:1.6;color:var(--acc)}
kbd{background:var(--bg3);border:1px solid var(--line2);border-bottom-width:2.5px;border-radius:5px;
padding:1px 7px;font-size:.78em;color:var(--tx1);direction:ltr;display:inline-block}
.ltr{direction:ltr;text-align:left;display:block}
#top{position:sticky;top:0;z-index:50;display:flex;align-items:center;gap:14px;height:60px;padding:0 22px;
background:color-mix(in srgb, var(--bg1) 88%, transparent);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.brand{font-weight:900;font-size:20px;letter-spacing:.4px}
.brand b{color:var(--acc)}
.chip{font-family:var(--f-mono);font-size:11px;padding:3px 11px;border-radius:20px;direction:ltr}
.chip-v{background:var(--acc-soft);color:var(--acc);border:1px solid color-mix(in srgb,var(--acc) 35%,transparent)}
.chip-d{background:var(--cyan-soft);color:var(--cyan)}
#top .spacer{flex:1}
.tbtn{display:inline-flex;align-items:center;gap:7px;background:var(--bg2);border:1px solid var(--line);
color:var(--tx1);border-radius:9px;padding:7px 14px;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;
transition:.18s var(--ease)}
.tbtn:hover{border-color:var(--acc);color:var(--tx0);transform:translateY(-1px)}
.tbtn.primary{background:var(--acc);border-color:transparent;color:#181206}
.hero{max-width:1180px;margin:0 auto;padding:64px 28px 26px;position:relative}
.hero .kicker{font-family:var(--f-mono);color:var(--acc);font-size:13px;letter-spacing:.25em;margin-bottom:14px;direction:ltr;text-align:left}
.hero h1{font-size:clamp(30px,5vw,52px);font-weight:900;line-height:1.35;max-width:18em}
.hero h1 em{font-style:normal;color:var(--acc);position:relative}
.hero h1 em::after{content:'';position:absolute;right:0;left:0;bottom:6px;height:9px;background:var(--acc-soft);z-index:-1;border-radius:4px}
.hero p{color:var(--tx1);max-width:64ch;margin-top:16px;font-size:16px}
.hero-stats{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}
.stat{background:var(--bg1);border:1px solid var(--line);border-radius:12px;padding:12px 20px;min-width:130px;
transition:.2s var(--ease)}
.stat:hover{transform:translateY(-3px);border-color:var(--line2);box-shadow:var(--shadow)}
.stat b{display:block;font-size:24px;font-weight:900;color:var(--acc)}
.stat span{font-size:12px;color:var(--tx2)}
.wrap{max-width:1180px;margin:0 auto;padding:10px 28px 90px;display:grid;grid-template-columns:250px minmax(0,1fr);gap:40px}
nav#toc{position:sticky;top:80px;align-self:start;max-height:calc(100vh - 100px);overflow:auto;
border:1px solid var(--line);border-radius:14px;background:var(--bg1);padding:14px 10px}
nav#toc .toc-title{font-size:11px;font-weight:800;letter-spacing:.18em;color:var(--tx2);padding:4px 12px 10px}
nav#toc a{display:flex;gap:9px;align-items:baseline;padding:6px 12px;border-radius:8px;color:var(--tx1);
font-size:13.2px;transition:.14s;border-left:2.5px solid transparent}
nav#toc a i{font-style:normal;font-family:var(--f-mono);font-size:10.5px;color:var(--tx2);min-width:20px}
nav#toc a:hover{background:var(--bg2);color:var(--tx0);text-decoration:none}
nav#toc a.on{background:var(--acc-soft);color:var(--acc);border-left-color:var(--acc);font-weight:700}
nav#toc a.on i{color:var(--acc)}
main{min-width:0}
section.doc{padding-top:34px;margin-bottom:26px;animation:rise .5s var(--ease) both}
@keyframes rise{from{opacity:0;transform:translateY(16px)}}
.sec-head{display:flex;align-items:center;gap:14px;margin-bottom:18px;padding-bottom:12px;border-bottom:1.5px solid var(--line)}
.sec-num{font-family:var(--f-mono);font-weight:700;font-size:15px;color:#181206;background:var(--acc);
border-radius:10px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;flex:none;
box-shadow:0 5px 16px var(--acc-soft)}
.sec-head h2{font-size:25px;font-weight:900}
section.doc h3{font-size:18px;font-weight:800;margin:30px 0 10px;color:var(--tx0)}
section.doc h3::before{content:'‹ ';color:var(--acc)}
section.doc p{color:var(--tx1);margin:9px 0}
section.doc ul,section.doc ol{color:var(--tx1);padding-left:24px;margin:9px 0}
section.doc li{margin:5px 0}
section.doc li::marker{color:var(--acc)}
section.doc b,strong{color:var(--tx0)}
.callout{border-radius:12px;padding:13px 17px;margin:15px 0;font-size:14px;border:1px solid;display:flex;gap:11px;line-height:1.85}
.callout .ic{font-size:17px;line-height:1.5}
.c-note{background:var(--cyan-soft);border-color:color-mix(in srgb,var(--cyan) 30%,transparent)}
.c-warn{background:var(--acc-soft);border-color:color-mix(in srgb,var(--acc) 35%,transparent)}
.c-danger{background:var(--red-soft);border-color:color-mix(in srgb,var(--red) 30%,transparent)}
.c-tip{background:var(--green-soft);border-color:color-mix(in srgb,var(--green) 30%,transparent)}
.codebox{position:relative;margin:15px 0;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:var(--code-bg)}
.codebox .cb-head{display:flex;align-items:center;gap:10px;padding:7px 14px;background:var(--bg2);
border-bottom:1px solid var(--line);font-family:var(--f-mono);font-size:11px;color:var(--tx2);direction:ltr}
.codebox .cb-head .dots{display:flex;gap:5px}
.codebox .cb-head .dots i{width:9px;height:9px;border-radius:50%;background:var(--line2)}
.codebox .cb-head .dots i:first-child{background:var(--acc)}
.copy-btn{margin-left:auto;background:var(--bg3);border:1px solid var(--line);color:var(--tx1);border-radius:6px;
font-family:var(--f-mono);font-size:10.5px;padding:3px 10px;cursor:pointer;transition:.15s}
.copy-btn:hover{color:var(--acc);border-color:var(--acc)}
.codebox pre{padding:15px 17px;overflow:auto;direction:ltr;text-align:left;line-height:1.75;color:var(--tx1);margin:0}
.codebox pre .k{color:var(--acc)} .codebox pre .s{color:var(--green)} .codebox pre .n{color:var(--cyan)}
.codebox pre .c{color:var(--tx2);font-style:italic}
.tbl-wrap{overflow:auto;border:1px solid var(--line);border-radius:12px;margin:15px 0}
table.tbl{width:100%;border-collapse:collapse;font-size:13.5px;min-width:560px}
.tbl th{background:var(--bg2);color:var(--tx0);font-weight:800;text-align:left;padding:10px 15px;
border-bottom:1.5px solid var(--line2);white-space:nowrap;font-size:12.5px}
.tbl td{padding:9px 15px;border-bottom:1px solid var(--line);color:var(--tx1);vertical-align:top}
.tbl tr:last-child td{border-bottom:none}
.tbl tr{transition:background .12s}
.tbl tbody tr:hover{background:var(--bg2)}
.tbl code{white-space:nowrap}
.badge{display:inline-block;font-family:var(--f-mono);font-size:10.5px;font-weight:700;padding:2.5px 9px;border-radius:12px;direction:ltr}
.b-get{background:var(--green-soft);color:var(--green)}
.b-post{background:var(--cyan-soft);color:var(--cyan)}
.b-danger{background:var(--red-soft);color:var(--red)}
.b-acc{background:var(--acc-soft);color:var(--acc)}
.flow{display:flex;align-items:stretch;gap:0;direction:ltr;overflow:auto;padding:20px 4px;margin:16px 0}
.fnode{flex:1;min-width:130px;background:var(--bg1);border:1.5px solid var(--line2);border-radius:13px;padding:13px 12px;
text-align:center;direction:ltr;transition:.2s var(--ease)}
.fnode:hover{transform:translateY(-4px);border-color:var(--acc);box-shadow:var(--shadow)}
.fnode .fi{font-size:21px;display:block;margin-bottom:5px}
.fnode b{font-size:13.5px;display:block}
.fnode span{font-size:10.5px;color:var(--tx2);font-family:var(--f-mono);direction:ltr;display:block;margin-top:3px}
.farr{align-self:center;color:var(--acc);font-size:19px;padding:0 7px;animation:blinkA 1.6s ease infinite}
@keyframes blinkA{50%{opacity:.35}}
.file-tree{background:var(--code-bg);border:1px solid var(--line);border-radius:12px;padding:18px 22px;direction:ltr;
font-family:var(--f-mono);font-size:13px;line-height:2.05;color:var(--tx1);overflow:auto}
.file-tree .dir{color:var(--acc);font-weight:700}
.file-tree .cm{color:var(--tx2)}
#totop{position:fixed;right:22px;bottom:22px;z-index:60;width:44px;height:44px;border-radius:12px;background:var(--acc);
color:#181206;border:0;font-size:18px;cursor:pointer;opacity:0;pointer-events:none;transition:.25s var(--ease);
box-shadow:0 8px 22px var(--acc-soft)}
#totop.show{opacity:1;pointer-events:auto}
#totop:hover{transform:translateY(-3px)}
footer.doc-foot{border-top:1px solid var(--line);padding:26px;text-align:center;color:var(--tx2);font-size:12.5px}
footer.doc-foot b{color:var(--acc)}
@media (max-width:920px){
.wrap{grid-template-columns:1fr;padding:10px 16px 70px}
nav#toc{position:static;max-height:none;display:flex;flex-wrap:wrap;gap:4px;padding:10px}
nav#toc .toc-title{width:100%}
nav#toc a{border-left:0;border:1px solid var(--line);border-radius:20px;padding:4px 12px}
nav#toc a.on{border-color:var(--acc)}
.hero{padding:40px 18px 16px}
}
</style>
</head>
<body>
<header id="top">
<span class="brand">PH<b>SQL</b> <span style="color:var(--tx2);font-weight:400;font-size:14px">Docs</span></span>
<span class="chip chip-v">v<?= htmlspecialchars($DOC_VERSION) ?></span>
<span class="chip chip-d"><?= htmlspecialchars($GENERATED) ?></span>
<span class="spacer"></span>
<button class="tbtn" id="theme-btn" title="Toggle Theme">◐ Theme</button>
<a class="tbtn primary" href="index.php">Back to App ↩</a>
</header>
<div class="hero">
<div class="kicker">// PHSQL · TECHNICAL DOCUMENTATION</div>
<h1>Complete <em>PHSQL</em> Documentation — From Architecture to the Last Line of Code</h1>
<p>
This document covers all technical details of the tool: backend communication protocol, visual engine algorithms, 
SQL and Migration generation logic, credential encryption, JSON Schema format, and extension guide. 
Everything is exactly according to the actual implementation of the files.
</p>
<div class="hero-stats">
<div class="stat"><b>12</b><span>API Actions</span></div>
<div class="stat"><b>5</b><span>JavaScript Modules</span></div>
<div class="stat"><b>0</b><span>Frameworks & Libraries</span></div>
<div class="stat"><b>SPA</b><span>No Page Refresh</span></div>
<div class="stat"><b>AES-256</b><span>Vault Encryption</span></div>
</div>
</div>
<div class="wrap">
<nav id="toc">
<div class="toc-title">Table of Contents</div>
<?php foreach ($toc as $i => [$id, $title]): ?>
<a href="#<?= $id ?>" data-spy="<?= $id ?>"><i><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></i><?= $title ?></a>
<?php endforeach; ?>
</nav>
<main>
<section class="doc" id="overview">
<div class="sec-head"><div class="sec-num">01</div><h2>Product Overview</h2></div>
<p>
<b>PHSQL</b> is a web-based database management tool for <b>MySQL / MariaDB</b> designed to replace phpMyAdmin. 
The main goal: turning "database design with raw SQL" into a <b>Visual</b> experience — tables are displayed as 
draggable cards on a canvas, and FK relationships are created by dragging lines between columns.
</p>
<h3>Key Features</h3>
<ul>
<li><b>Connection Manager</b> — Multiple saved connections with AES-GCM encryption in LocalStorage, connection test before login.</li>
<li><b>Visual Table Designer</b> — Node-based canvas (like n8n), define PK/Unique/Index with one click, create FK by dragging ports.</li>
<li><b>ERD</b> — Read-only view of the entire schema with infinite Zoom/Pan and PNG export.</li>
<li><b>Migration Generator</b> — Live diff between current database and your design → SQL preview → one-click execution.</li>
<li><b>Data Viewer</b> — Inline cell editing (like Excel), filter, sort, pagination, Insert/Delete.</li>
<li><b>SQL Editor</b> — Highlighting, schema-based autocomplete, history, CSV/JSON/SQL export.</li>
<li><b>JSON Schema Import/Export</b> — Backup, migration between environments, and team collaboration.</li>
</ul>
<h3>Tech Stack</h3>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Layer</th><th>Technology</th><th>Description</th></tr></thead>
<tbody>
<tr><td>Backend</td><td><code>PHP 8.x</code> Pure</td><td>No framework; only PDO. Single file: <code>api.php</code></td></tr>
<tr><td>Database</td><td><code>MySQL 5.7+</code> / <code>MariaDB 10.3+</code></td><td>Uses <code>information_schema</code> for introspection</td></tr>
<tr><td>Frontend</td><td><code>HTML5 + CSS3 + Vanilla JS (ES6+)</code></td><td>No libraries; SPA with Fetch API</td></tr>
<tr><td>Visual Rendering</td><td><code>SVG</code></td><td>Nodes and edges are SVG (not Canvas) to enable DOM events and PNG export</td></tr>
<tr><td>Local Storage</td><td><code>LocalStorage + Web Crypto API</code></td><td>Connections, theme, query history, node positions</td></tr>
<tr><td>Fonts</td><td>Vazirmatn / Space Grotesk / JetBrains Mono</td><td>From Google Fonts with system fallback</td></tr>
</tbody>
</table></div>
</section>

<section class="doc" id="files">
<div class="sec-head"><div class="sec-num">02</div><h2>File Structure</h2></div>
<div class="file-tree">
<span class="dir">phsql/</span>
├── index.php            <span class="cm"># SPA Shell: Connection page + 3-column layout + modals</span>
├── api.php              <span class="cm"># Entire backend: Router + 12 actions + PDO</span>
├── docs.php             <span class="cm"># This documentation</span>
├── README.md
└── <span class="dir">assets/</span>
    ├── <span class="dir">css/</span>
    │   └── app.css      <span class="cm"># Design system: Theme variables, components, animations</span>
    └── <span class="dir">js/</span>
        ├── core.js      <span class="cm"># Utilities, API client, Vault, Toast/Modal, hlSQL</span>
        ├── designer.js  <span class="cm"># Canvas engine, SQL generation, diff algorithm, PNG export</span>
        ├── dataview.js  <span class="cm"># Data grid, inline editing, filter and pagination</span>
        ├── editor.js    <span class="cm"># SQL editor, autocomplete, history</span>
        └── app.js       <span class="cm"># Boot, sidebar, routing, inspector, migration</span>
</div>
<div class="callout c-note"><span class="ic">ℹ️</span><div>
There are no other PHP files. All data requests go only to <code>api.php</code>, and 
<code>index.php</code> merely serves the initial HTML (the SPA manages everything with JS after boot).
</div></div>
</section>

<section class="doc" id="architecture">
<div class="sec-head"><div class="sec-num">03</div><h2>Architecture & Request Flow</h2></div>
<p>Architecture is a <b>Single Page Application (SPA)</b> with a single endpoint:</p>
<div class="flow">
<div class="fnode"><span class="fi">🖥️</span><b>Browser (SPA)</b><span>index.php + 5 JS modules</span></div>
<div class="farr">⟶</div>
<div class="fnode"><span class="fi">📡</span><b>Fetch API</b><span>POST JSON {action}</span></div>
<div class="farr">⟶</div>
<div class="fnode"><span class="fi">🐘</span><b>api.php Router</b><span>match($action)</span></div>
<div class="farr">⟶</div>
<div class="fnode"><span class="fi">🔐</span><b>PHP Session</b><span>$_SESSION['phsql_conn']</span></div>
<div class="farr">⟶</div>
<div class="fnode"><span class="fi">🔌</span><b>PDO</b><span>ERRMODE_EXCEPTION</span></div>
<div class="farr">⟶</div>
<div class="fnode"><span class="fi">🗄️</span><b>MySQL</b><span>information_schema</span></div>
</div>
<h3>Full Request Cycle</h3>
<ol>
<li>Frontend calls the <code>api(action, data)</code> function in <code>core.js</code> — a <code>POST</code> with a JSON body.</li>
<li><code>api.php</code> reads the body with <code>json_decode(file_get_contents('php://input'))</code>.</li>
<li>The action is dispatched to the corresponding function via <code>match()</code>.</li>
<li>The function gets the connection from the Session and creates PDO <b>on every request</b> (PDO is not stored in Session; because it's not serializable).</li>
<li>The response is always JSON: <code>{ok:true, ...}</code> or <code>{ok:false, error:"..."}</code> along with the appropriate HTTP status.</li>
</ol>
<h3>Why is State kept in Session?</h3>
<p>
PHP is stateless; so that the user doesn't send the password again on every request after "Connect", 
credentials (including password) are stored in <code>$_SESSION['phsql_conn']</code>. 
The <code>db()</code> function creates a PDO with <code>static</code> that lives only for the duration of that request. 
At the beginning of the connection, <code>SET SESSION group_concat_max_len = 1000000</code> is executed so that 
<code>GROUP_CONCAT</code> in index/FK introspection is not truncated.
</p>
<h3>Frontend Boot (Exact Order)</h3>
<ol>
<li><code>DOMContentLoaded</code> → Apply saved theme, bind events, load saved connections from Vault.</li>
<li>User fills the form and clicks <b>Connect</b> → <code>api('connect')</code> → save in server Session + save in browser Vault.</li>
<li><code>enterApp()</code> → Two parallel requests: <code>api('tables')</code> and <code>api('schema')</code>.</li>
<li>Schema is converted to internal model via <code>normalizeSchema()</code>; node positions are restored from LocalStorage or Auto-Layout is executed.</li>
<li>Two copies are kept: <code>App.dbSchema</code> (live database state — read-only) and <code>App.schema</code> (user design — editable).</li>
<li>Designer/ERD/Data/SQL views are initialized and the default view (Designer) is rendered.</li>
</ol>
</section>

<section class="doc" id="api">
<div class="sec-head"><div class="sec-num">04</div><h2>API Protocol & Actions</h2></div>
<p>
All requests: <span class="badge b-post">POST api.php</span> with a JSON body containing the <code>action</code> field. 
Success response: <code>{ok:true, ...}</code>. Error: <code>{ok:false, error:"message"}</code> with appropriate HTTP code 
(<code>400</code> invalid parameter, <code>401</code> not connected, <code>404</code> unknown action, <code>500</code> database/server error).
</p>
<div class="callout c-tip"><span class="ic">💡</span><div>
All values (VALUES) are bound with <b>Prepared Statements</b> — no value is concatenated inside SQL. 
Only <b>identifiers</b> (table/column names) are placed inside backticks via the <code>qi()</code> function.
</div></div>
<h3>4.1 <code>connect</code> — Test and Establish Connection</h3>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>request / response<button class="copy-btn">copy</button></div><pre>{ <span class="k">"action"</span>: <span class="s">"connect"</span>, <span class="k">"host"</span>: <span class="s">"127.0.0.1"</span>, <span class="k">"port"</span>: <span class="n">3306</span>,
<span class="k">"user"</span>: <span class="s">"root"</span>, <span class="k">"pass"</span>: <span class="s">"***"</span>, <span class="k">"db"</span>: <span class="s">"my_app"</span> }
{ <span class="k">"ok"</span>: true, <span class="k">"version"</span>: <span class="s">"8.0.36"</span>, <span class="k">"db"</span>: <span class="s">"my_app"</span> }</pre></div>
<p>First connects without dbname, then executes <code>USE `db`</code> to also validate the database existence. On success, credentials are saved in Session.</p>
<h3>4.2 <code>disconnect</code> / <code>status</code></h3>
<p><code>disconnect</code> completely destroys the Session (<code>session_destroy()</code>). <code>status</code> returns current connection info + server version.</p>
<h3>4.3 <code>tables</code> — List of Tables</h3>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>response<button class="copy-btn">copy</button></div><pre>{ <span class="k">"ok"</span>: true, <span class="k">"tables"</span>: [
{ <span class="k">"name"</span>: <span class="s">"users"</span>, <span class="k">"engine"</span>: <span class="s">"InnoDB"</span>, <span class="k">"collation"</span>: <span class="s">"utf8mb4_general_ci"</span>,
<span class="k">"est_rows"</span>: <span class="n">1520</span>, <span class="k">"auto_increment"</span>: <span class="n">1521</span>, <span class="k">"comment"</span>: <span class="s">""</span> } ] }</pre></div>
<p>From <code>information_schema.TABLES</code>, only <code>BASE TABLE</code>s (views are excluded). <code>est_rows</code> is the InnoDB estimate.</p>
<h3>4.4 <code>schema</code> — Full Schema (Core of Designer/ERD)</h3>
<p>Four queries to <code>information_schema</code> (COLUMNS, STATISTICS with <code>GROUP_CONCAT</code>, KEY_COLUMN_USAGE + REFERENTIAL_CONSTRAINTS) and then assembly:</p>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>response (one table)<button class="copy-btn">copy</button></div><pre>{ <span class="k">"ok"</span>: true, <span class="k">"schema"</span>: [ {
<span class="k">"name"</span>: <span class="s">"orders"</span>, <span class="k">"engine"</span>: <span class="s">"InnoDB"</span>, <span class="k">"comment"</span>: <span class="s">""</span>,
<span class="k">"columns"</span>: [ {
<span class="k">"name"</span>: <span class="s">"user_id"</span>, <span class="k">"type"</span>: <span class="s">"INT"</span>, <span class="k">"length"</span>: null, <span class="k">"unsigned"</span>: true,
<span class="k">"nullable"</span>: false, <span class="k">"default"</span>: null, <span class="k">"ai"</span>: false, <span class="k">"comment"</span>: <span class="s">""</span>,
<span class="k">"fullType"</span>: <span class="s">"int unsigned"</span> } ],
<span class="k">"primaryKey"</span>: [<span class="s">"id"</span>],
<span class="k">"indexes"</span>: [ { <span class="k">"name"</span>: <span class="s">"idx_status"</span>, <span class="k">"unique"</span>: false, <span class="k">"columns"</span>: [<span class="s">"status"</span>] } ],
<span class="k">"foreignKeys"</span>: [ { <span class="k">"name"</span>: <span class="s">"orders_ibfk_1"</span>, <span class="k">"columns"</span>: [<span class="s">"user_id"</span>],
<span class="k">"refTable"</span>: <span class="s">"users"</span>, <span class="k">"refColumns"</span>: [<span class="s">"id"</span>],
<span class="k">"onUpdate"</span>: <span class="s">"CASCADE"</span>, <span class="k">"onDelete"</span>: <span class="s">"NO ACTION"</span> } ] } ] }</pre></div>
<p>The <code>parseType()</code> function parses the <code>COLUMN_TYPE</code> column (e.g., <code>int(11) unsigned</code> or <code>varchar(255)</code>) into base/length/unsigned. Multi-column FKs are supported with arrays.</p>
<h3>4.5 <code>table_data</code> — Paginated Data</h3>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
<tbody>
<tr><td><code>table</code></td><td>string</td><td>—</td><td>Table name (required)</td></tr>
<tr><td><code>page</code> / <code>per</code></td><td>int</td><td>1 / 50</td><td><code>per</code> is clamped between 1 and 500</td></tr>
<tr><td><code>sort</code> / <code>dir</code></td><td>string</td><td>null / asc</td><td>sort column must exist in the column list (prevents injection)</td></tr>
<tr><td><code>q</code></td><td>string</td><td>""</td><td>Global search: text columns with <code>LIKE %q%</code> and numeric columns with <code>= q</code></td></tr>
<tr><td><code>filters</code></td><td>array</td><td>[]</td><td>Array of <code>{col, op, val}</code> — operators: <code>= != &gt; &lt; &gt;= &lt;= LIKE IS NULL IS NOT NULL</code></td></tr>
</tbody>
</table></div>
<p>Response includes <code>rows</code>, <code>total</code> (COUNT with the same WHERE), <code>columns</code> (name+data type for building filters), and <code>pk</code> (primary key columns for secure editing).</p>
<h3>4.6 <code>query</code> — Execute a Query</h3>
<p>If the result has columns (SELECT/SHOW/…) → <code>{type:"result", columns, rows, count, truncated, ms}</code>. 
A maximum of <b>1000 rows</b> (<code>MAX_FETCH_ROWS</code>) is returned and marked with <code>truncated:true</code>. 
Otherwise → <code>{type:"exec", affected, ms}</code>. Execution time is reported in milliseconds.</p>
<h3>4.7 <code>exec_script</code> — Execute multiple statements (Migration)</h3>
<p>The script is split into separate statements via <code>splitStatements()</code> (Section 5) and executed in order. 
If statement #N fails, the entire execution stops and the message is returned along with the statement itself. Response: <code>{executed: n}</code>.</p>
<h3>4.8 <code>row_save</code> / <code>row_insert</code> / <code>row_delete</code></h3>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>row_save — Inline editing<button class="copy-btn">copy</button></div><pre>{ <span class="k">"action"</span>: <span class="s">"row_save"</span>, <span class="k">"table"</span>: <span class="s">"users"</span>,
<span class="k">"pk"</span>:  [ { <span class="k">"col"</span>: <span class="s">"id"</span>, <span class="k">"val"</span>: <span class="n">42</span> } ],
<span class="k">"set"</span>: { <span class="k">"email"</span>: <span class="s">"new@mail.com"</span>, <span class="k">"bio"</span>: null } }
UPDATE `users` SET `email` = ?, `bio` = ? WHERE `id` &lt;=&gt; ? LIMIT …</pre></div>
<ul>
<li>In WHERE, the <b>NULL-safe</b> operator <code>&lt;=&gt;</code> is used so that rows with NULL in PK are also matched correctly.</li>
<li><code>row_insert</code> skips empty values (so database default is applied) and returns <code>lastInsertId</code>.</li>
<li><code>row_delete</code> with <code>LIMIT 1</code> for each row — bulk deletion from frontend is done via loop.</li>
<li>Table without PK → inline edit/delete is disabled ("no PK" message in UI).</li>
</ul>
<h3>4.9 <code>dump</code> — Full SQL Export</h3>
<p>For each table: <code>DROP TABLE IF EXISTS</code> + <code>SHOW CREATE TABLE</code> output, inside a script surrounded by 
<code>SET FOREIGN_KEY_CHECKS=0/1</code> so restore is possible without order errors. Optional <code>tables</code> parameter to dump a specific table.</p>
</section>

<section class="doc" id="internals">
<div class="sec-head"><div class="sec-num">05</div><h2>Backend Internals</h2></div>
<h3>5.1 <code>qi()</code> Function — Securing Identifiers</h3>
<p>Identifiers (table/column names) never go inside prepared statements, so they are secured like this:</p>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>api.php<button class="copy-btn">copy</button></div><pre><span class="k">function</span> qi(string $id): string {
<span class="k">return</span> <span class="s">'`'</span> . str_replace(<span class="s">'`'</span>, <span class="s">'``'</span>, $id) . <span class="s">'`'</span>;
}</pre></div>
<h3>5.2 <code>splitStatements()</code> Algorithm</h3>
<p>A character-by-character state machine that splits the SQL script into statements. States:</p>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>State</th><th>Enter State</th><th>Behavior</th></tr></thead>
<tbody>
<tr><td>Normal</td><td>—</td><td><code>;</code> → end statement and flush buffer</td></tr>
<tr><td>Line (<code>inL</code>)</td><td><code>--</code> or <code>#</code></td><td>Continues until <code>\n</code>; <code>;</code> inside comment is ignored</td></tr>
<tr><td>Block (<code>inB</code>)</td><td><code>/*</code></td><td>Until <code>*/</code></td></tr>
<tr><td>Single string (<code>inS</code>)</td><td><code>'</code></td><td><code>''</code> double = escape; <code>\'</code> is also tolerated</td></tr>
<tr><td>Double string (<code>inD</code>)</td><td><code>"</code></td><td>Similar to above</td></tr>
</tbody>
</table></div>
<div class="callout c-warn"><span class="ic">⚠️</span><div>
<b>Known edge case:</b> complex backslash-escape like <code>'a\\'</code> (escaped backslash at the end of string) might close early. 
In practice, it doesn't happen for the tool's own generated migrations because <code>''</code> is used.
</div></div>
<h3>5.3 Error Handling</h3>
<p>The entire dispatch is inside <code>try/catch</code>: <code>PDOException</code> → database error message with code 500, any other <code>Throwable</code> → "Server error". 
Error HTML is never returned — output is always valid JSON. <code>ok()</code> and <code>fail()</code> functions are of type <code>never</code> (definite exit).</p>
<h3>5.4 <code>parseType()</code> Function</h3>
<p>Breaks the <code>COLUMN_TYPE</code> string with regex: <code>int(11) unsigned</code> ← <code>{type:"int", length:null, unsigned:true}</code>; 
<code>decimal(10,2)</code> ← <code>length:"10,2"</code>; for <code>ENUM/SET</code> the entire list of values is kept in <code>length</code> for a complete round-trip.</p>
</section>

<section class="doc" id="security">
<div class="sec-head"><div class="sec-num">06</div><h2>Security</h2></div>
<h3>What is Implemented</h3>
<ul>
<li><b>Prepared Statements for all values</b> — UPDATE/INSERT/DELETE/filters all use placeholders. No user-input is concatenated inside SQL.</li>
<li><b>Operator whitelist</b> in filters — only whitelisted operators are accepted.</li>
<li><b>Sort column validation</b> — must be in the list of actual table columns.</li>
<li><b>Identifier escaping</b> with <code>qi()</code>.</li>
<li><b>Secure headers</b>: <code>X-Content-Type-Options: nosniff</code> and <code>Cache-Control: no-store</code>.</li>
<li><b>Browser Vault encryption</b> with AES-GCM (Section 7).</li>
<li><b>Session-only credentials</b> — password is never returned in Logs or API responses.</li>
</ul>
<h3>Known Limitations (You should know!)</h3>
<div class="callout c-danger"><span class="ic">🚨</span><div>
<b>PHSQL is a development tool and has no authentication.</b> Anyone with access to the server URL has access to the database. 
In production, definitely put it behind a reverse-proxy with auth, firewall, or internal network.
</div></div>
<ul>
<li><b>No CSRF token</b> — because the tool is local. If you expose it, add one.</li>
<li><b>Vault encryption is "obfuscation-grade":</b> the AES key is derived from a fixed passphrase inside the code; it prevents random reading of the localStorage file but is not real protection against an attacker who has access to the same browser. Recommendation: use a database user with limited access and disable "Remember password" on shared systems.</li>
<li><b>No rate limiting.</b></li>
<li>Dangerous queries (DROP, etc.) are intentionally unrestricted — full power is given to the developer.</li>
</ul>
</section>

<section class="doc" id="vault">
<div class="sec-head"><div class="sec-num">07</div><h2>LocalStorage Encryption (Vault)</h2></div>
<p>The <code>Vault</code> module in <code>core.js</code> is implemented with <b>Web Crypto API</b>:</p>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Parameter</th><th>Value</th></tr></thead>
<tbody>
<tr><td>Algorithm</td><td><code>AES-GCM</code> with 256-bit key (authenticated encryption)</td></tr>
<tr><td>Derivation</td><td><code>PBKDF2-SHA256</code> with <b>60,000</b> iterations</td></tr>
<tr><td>Salt</td><td><code>phsql.v1</code> (fixed)</td></tr>
<tr><td>IV</td><td>12 random bytes for each save (<code>crypto.getRandomValues</code>)</td></tr>
<tr><td>Storage Format</td><td><code>localStorage["phsql:connections"] = base64(iv) + "." + base64(ciphertext)</code></td></tr>
<tr><td>Decrypt Error</td><td>If tampered, <code>decrypt</code> fails and empty fallback is returned (without crash)</td></tr>
</tbody>
</table></div>
<h3>LocalStorage Keys Used in the Entire App</h3>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Key</th><th>Encryption</th><th>Content</th></tr></thead>
<tbody>
<tr><td><code>phsql:connections</code></td><td><span class="badge b-acc">AES-GCM</span></td><td>List of connections (host/port/user/db + password only if Remember is enabled)</td></tr>
<tr><td><code>phsql.theme</code></td><td>plain</td><td><code>dark</code> / <code>light</code></td></tr>
<tr><td><code>phsql.scratch</code></td><td>plain</td><td>Text inside SQL Editor (auto-save with debounce)</td></tr>
<tr><td><code>phsql.qhist</code></td><td>plain</td><td>Last 50 queries: <code>{sql, ts, ok, ms}</code></td></tr>
<tr><td><code>phsql.pos.{host}:{port}/{db}</code></td><td>plain</td><td>Node positions: <code>{"users":[x,y], ...}</code></td></tr>
</tbody>
</table></div>
</section>

<section class="doc" id="modules">
<div class="sec-head"><div class="sec-num">08</div><h2>Frontend Modules</h2></div>
<p>Scripts are loaded with <code>defer</code> and in dependency order. All are coordinated around a global <code>App</code> object (defined in core.js).</p>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>File</th><th>Responsibilities</th><th>Key Outputs</th></tr></thead>
<tbody>
<tr><td><code>core.js</code></td><td>DOM helpers (<code>el/svgEl/esc</code>), <code>api()</code> client, <code>Vault</code> encryption, Toast system, <code>modal/confirmBox/promptBox</code> modals, <code>contextMenu</code> right-click menu, CSV/INSERT download and conversion, <code>hlSQL</code> highlighter</td><td><code>App</code>, <code>toast()</code>, <code>SQL_KEYWORDS</code></td></tr>
<tr><td><code>designer.js</code></td><td><code>NodeCanvas</code> class (SVG rendering, drag, pan/zoom, FK linking), SQL generators (<code>typeSQL/columnDef/createTableSQL/topoTables</code>), diff algorithm i.e., <code>migrationStatements()</code>, <code>exportCanvasPNG()</code> output</td><td><code>NodeCanvas</code>, <code>migrationStatements()</code></td></tr>
<tr><td><code>dataview.js</code></td><td><code>App.data</code> object: paginated fetch, grid rendering, inline editing, new row, delete, filter chips, current page CSV export</td><td><code>App.data.open(table)</code></td></tr>
<tr><td><code>editor.js</code></td><td><code>App.editor</code> object: overlay highlighting, scroll sync, line number gutter, autocomplete with mirror-caret, execute and render result, history, export</td><td><code>App.editor.run()</code></td></tr>
<tr><td><code>app.js</code></td><td>Boot and connection page, <code>normalizeSchema()</code>, sidebar and table menu, routing between views, Inspector (right panel), designer operations (Add/Rename/FK), Migration modal, JSON import/export, global shortcuts</td><td><code>switchView()</code>, <code>openMigration()</code>, <code>refreshSchema()</code></td></tr>
</tbody>
</table></div>
</section>

<section class="doc" id="schema-model">
<div class="sec-head"><div class="sec-num">09</div><h2>Schema Data Model in JavaScript</h2></div>
<p>The raw response of <code>api('schema')</code> is converted to the internal model via <code>normalizeSchema()</code>. Final structure of each table:</p>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>Table model<button class="copy-btn">copy</button></div><pre>{
<span class="k">name</span>: <span class="s">"orders"</span>, <span class="k">comment</span>: <span class="s">""</span>, <span class="k">engine</span>: <span class="s">"InnoDB"</span>,
<span class="k">columns</span>: [{
<span class="k">name</span>: <span class="s">"user_id"</span>, <span class="k">type</span>: <span class="s">"INT"</span>,
<span class="k">length</span>: null,
<span class="k">unsigned</span>: true, <span class="k">nullable</span>: false,
<span class="k">default</span>: null,
<span class="k">ai</span>: false, <span class="k">comment</span>: <span class="s">""</span>
}],
<span class="k">pk</span>: [<span class="s">"id"</span>],
<span class="k">indexes</span>: [{ <span class="k">name</span>, <span class="k">unique</span>, <span class="k">columns</span>: [] }],
<span class="k">fks</span>: [{
<span class="k">name</span>, <span class="k">columns</span>: [], <span class="k">refTable</span>, <span class="k">refColumns</span>: [],
<span class="k">column</span>, <span class="k">refColumn</span>,
<span class="k">onDelete</span>, <span class="k">onUpdate</span>
}],
<span class="k">pos</span>: { <span class="k">x</span>: <span class="n">380</span>, <span class="k">y</span>: <span class="n">40</span> }
}</pre></div>
<h3>Normalization Rules</h3>
<ul>
<li><code>RESTRICT</code> in MySQL is equivalent to <code>NO ACTION</code> → mapped to <code>NO ACTION</code> to avoid generating false diffs.</li>
<li>Indexes that are exactly a copy of an FK column (implicit index that MySQL creates for FK) are <b>filtered</b> so Migration doesn't get full of unnecessary DROP/ADD.</li>
<li><code>PRIMARY</code> index is removed from the indexes list and kept only in <code>pk</code>.</li>
<li>Two independent copies are made: <code>App.dbSchema = live model</code> and <code>App.schema = structuredClone(it)</code> for editing.</li>
</ul>
</section>

<section class="doc" id="designer">
<div class="sec-head"><div class="sec-num">10</div><h2>Visual Designer Engine</h2></div>
<p>The <code>NodeCanvas</code> class is used for both Designer (editable) and ERD (read-only); the difference is only in the <code>editable</code> flag and data source.</p>
<h3>10.1 Geometric Constants</h3>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Constant</th><th>Value</th><th>Usage</th></tr></thead>
<tbody>
<tr><td><code>NODE_W</code></td><td>240px</td><td>Width of all cards</td></tr>
<tr><td><code>HEAD_H</code></td><td>36px</td><td>Card header height (table name)</td></tr>
<tr><td><code>ROW_H</code></td><td>25px</td><td>Height of each column row</td></tr>
<tr><td><code>NODE_PAD</code></td><td>7px</td><td>Card bottom padding</td></tr>
<tr><td>Zoom</td><td>0.2 × to 2.5 ×</td><td>clamped in wheel and buttons</td></tr>
</tbody>
</table></div>
<p>Card height: <code>HEAD_H + n×ROW_H + NODE_PAD</code>.</p>
<h3>10.2 Coordinate System (Pan / Zoom)</h3>
<p>All nodes are inside a single <code>&lt;g class="viewport"&gt;</code> with a unified transform:</p>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>Transform math<button class="copy-btn">copy</button></div><pre>transform = translate(px, py) scale(z)
world = (screen − pan) / z
z2 = clamp(z × e^(−deltaY × 0.0012), 0.2, 2.5)
px2 = sx − (sx − px) × (z2 / z)
py2 = sy − (sy − py) × (z2 / z)
backgroundSize = 24×z px;  backgroundPosition = (px, py)</pre></div>
<h3>10.3 SVG Structure of Each Node</h3>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>node<button class="copy-btn">copy</button></div><pre>&lt;g class="node" transform="translate(x,y)" data-table="users"&gt;
&lt;rect class="node-body" rx="12"/&gt;
&lt;path class="node-head"/&gt;
&lt;text class="node-title"&gt;users&lt;/text&gt;
&lt;g class="node-row" data-col="id"&gt;
&lt;rect class="row-bg"/&gt;
&lt;circle class="pk-dot"/&gt;
&lt;text class="col-name"&gt;id&lt;/text&gt;
&lt;text class="col-type"&gt;int ai&lt;/text&gt;
&lt;circle class="port" cx="0"/&gt;
&lt;circle class="port" cx="240"/&gt;
&lt;/g&gt;…
&lt;/g&gt;</pre></div>
<p>Port position for each column: <code>x = pos.x + (right side? 240 : 0)</code> and <code>y = pos.y + 36 + i×25 + 12.5</code>.</p>
<h3>10.4 Drawing Edges (FK)</h3>
<p>For each FK, a cubic bezier curve from the child column port to the reference column port. The port sides are chosen automatically 
(if child is left of parent: right←left and vice versa). Control points: 
<code>dx = clamp(|Δx| × 0.5, 45, 170)</code>. A wide invisible path (<code>edge-hit</code>) 
is placed for tooltip and right-click "Drop FK" on the edge. During node drag, edges are updated throttled with <code>requestAnimationFrame</code>.</p>
<h3>10.5 Interactions (editable mode only)</h3>
<ul>
<li><b>Header drag</b> → move node (screen coordinates are converted to world via <code>_world()</code>).</li>
<li><b>Drag from port</b> → link mode: temporary green dashed path follows the mouse; valid ports under mouse are highlighted with <code>elementFromPoint</code>; dropping on another table's port → FK creation modal (choose ON DELETE / ON UPDATE).</li>
<li><b>Click on node/column</b> → select and update Inspector.</li>
<li><b>Drag empty space</b> → pan. <b>Wheel</b> → zoom. <b>Double-click header</b> → rename table (with cascade on FKs).</li>
<li><b>Right-click on edge</b> → delete FK.</li>
</ul>
<h3>10.6 Auto-Layout Algorithm</h3>
<ol>
<li>Topological sort of tables with Kahn's algorithm based on FKs (parent before child).</li>
<li>Calculate "level" of each table: longest path from root (<code>level = max(level(ref)+1)</code>).</li>
<li>Layout: each level in a column with <b>340px</b> horizontal spacing; tables in each level top-to-bottom by height with <b>60px</b> vertical spacing.</li>
<li>Finally <code>fit()</code> — calculate bounding box and adjust zoom/pan to see the entire diagram.</li>
</ol>
<div class="callout c-note"><span class="ic">ℹ️</span><div>
Positions are saved in LocalStorage via <code>savePositions()</code> after each drag 
(key <code>phsql.pos.{host}:{port}/{db}</code>) and restored on the next visit. If there are no positions, Auto-Layout is executed.
</div></div>
</section>

<section class="doc" id="sqlgen">
<div class="sec-head"><div class="sec-num">11</div><h2>SQL Generation & Migration Generator</h2></div>
<h3>11.1 Building SQL Components</h3>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Function</th><th>Logic</th></tr></thead>
<tbody>
<tr><td><code>typeSQL(c)</code></td><td><code>BOOLEAN → TINYINT(1)</code>; ENUM/SET get the list of values from <code>length</code>; length is appended only for NEEDS_LEN types (like VARCHAR/DECIMAL)</td></tr>
<tr><td><code>defaultClause(c)</code></td><td><code>CURRENT_TIMESTAMP/NOW()</code> without quote; numeric value for numeric column without quote; others with quote and escaped as <code>''</code></td></tr>
<tr><td><code>columnDef(c)</code></td><td>Full combination: <code>`name` TYPE(len) [UNSIGNED] [NOT] NULL [DEFAULT x] [AUTO_INCREMENT] [COMMENT 'x']</code></td></tr>
<tr><td><code>createTableSQL(t)</code></td><td>Body: column definitions + PRIMARY KEY + UNIQUE/INDEX + CONSTRAINT FKs with ON DELETE/UPDATE; end of table <code>ENGINE=InnoDB DEFAULT CHARSET=utf8mb4</code></td></tr>
<tr><td><code>topoTables()</code></td><td>Kahn on FK graph so parent is CREATEd before child; in case of a cycle, remainders are added at the end</td></tr>
</tbody>
</table></div>
<h3>11.2 Diff Algorithm — Heart of Migration Generator</h3>
<p>The <code>migrationStatements(oldSchema, newSchema)</code> function compares two states and generates a list of labeled statements:</p>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Change</th><th>Detection</th><th>Generated Statement</th></tr></thead>
<tbody>
<tr><td>Table deleted</td><td>In old, not in new</td><td><span class="badge b-danger">DROP TABLE `x`</span></td></tr>
<tr><td>New table</td><td>In new, not in old</td><td><span class="badge b-get">CREATE TABLE …</span> (with topological order)</td></tr>
<tr><td>New / deleted column</td><td>Compare column names</td><td><code>ADD COLUMN</code> / <code>DROP COLUMN</code></td></tr>
<tr><td>Modified column</td><td>Signature of <code>typeSQL | unsigned | nullable | default | ai</code> is different</td><td><code>MODIFY COLUMN</code></td></tr>
<tr><td>PK change</td><td>Compare <code>pk.join(',')</code></td><td><code>DROP PRIMARY KEY</code> then <code>ADD PRIMARY KEY (…)</code></td></tr>
<tr><td>New / deleted index</td><td>Signature of <code>U:/I: + columns</code></td><td><code>DROP INDEX</code> / <code>ADD [UNIQUE] INDEX</code></td></tr>
<tr><td>New / deleted FK</td><td>Signature of <code>column → refTable.refColumn / onDelete / onUpdate</code></td><td><code>DROP FOREIGN KEY</code> / <code>ADD CONSTRAINT … FOREIGN KEY</code></td></tr>
</tbody>
</table></div>
<p>Order of statements inside ALTER is important: first <code>MODIFY</code>s, then PK changes, then indexes and FKs.</p>
<h3>11.3 Execution Flow in UI</h3>
<ol>
<li>User clicks <b>⚡ Migrate</b> or <kbd>Ctrl</kbd>+<kbd>S</kbd> → <code>openMigration()</code>.</li>
<li>If diff is empty → toast "Schema is in sync".</li>
<li>Otherwise preview modal: each statement with colored type label (<span class="badge b-get">add</span> <span class="badge b-acc">alter</span> <span class="badge b-danger">drop</span>) and highlighted SQL.</li>
<li>With "Execute" all statements are sent to <code>exec_script</code>.</li>
<li>On success: <code>refreshSchema()</code> → database is introspected again and Designer syncs with the new state.</li>
<li>On error: server error message (including statement number and its text) is displayed and modal stays open.</li>
</ol>
<div class="callout c-warn"><span class="ic">⚠️</span><div>
DDL in MySQL causes implicit commit; therefore migration execution is not truly atomic. If an error occurs in the middle of the script, 
previous statements have been applied — exactly for this reason preview is shown before execution.
</div></div>
</section>

<section class="doc" id="erd">
<div class="sec-head"><div class="sec-num">12</div><h2>ERD & PNG Export</h2></div>
<p>The ERD view is the same <code>NodeCanvas</code> with <code>editable:false</code> and gets its data from <code>App.dbSchema</code> 
(live database state, not untouched design). Drag and link are disabled; only pan/zoom, selection for read-only Inspector, and Auto-Layout/Fit are active.</p>
<h3>PNG Export Mechanism</h3>
<ol>
<li>Because node styles are defined inside the SVG itself (injected <code>&lt;style&gt;</code> tag), the SVG is serializable.</li>
<li><code>exportCanvasPNG()</code> clones and replaces <code>var(--…)</code>s with the computed value of the current theme (so light/dark theme is exported correctly).</li>
<li>Adds a <code>&lt;rect&gt;</code> as background, then serialize → data URL → <code>Image</code> → draw on Canvas with <b>2× scale</b> → <code>toBlob</code> → download.</li>
</ol>
</section>

<section class="doc" id="dataview">
<div class="sec-head"><div class="sec-num">13</div><h2>Data Viewer</h2></div>
<h3>13.1 Inline Editing Cycle</h3>
<ol>
<li>Click on cell → cell turns into <code>&lt;input&gt;</code> (<code>editing</code> class).</li>
<li><kbd>Enter</kbd> or blur → commit; <kbd>Esc</kbd> → cancel.</li>
<li>Changed value is recorded in <code>dirty: Map&lt;rowIndex, Map&lt;col, value&gt;&gt;</code> and cell turns amber.</li>
<li>The "💾 Save changes (n)" button sends all dirty rows in order with <code>row_save</code> — each row's PK is read from its original values.</li>
<li>After saving, the page is refetched.</li>
</ol>
<h3>13.2 Special Values Convention</h3>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>User Input</th><th>Result</th></tr></thead>
<tbody>
<tr><td>Text <code>NULL</code> (exactly four letters)</td><td><code>NULL</code> value is saved</td></tr>
<tr><td>Empty cell that was previously NULL</td><td>Remains NULL</td></tr>
<tr><td>New row: empty field</td><td>Skipped in INSERT → database default is applied</td></tr>
<tr><td>New row: <code>NULL</code></td><td>Explicit NULL value</td></tr>
</tbody>
</table></div>
<h3>13.3 Filter, Sort, Pagination</h3>
<ul>
<li><b>Global search</b> (top input) with 350ms debounce → <code>q</code> parameter.</li>
<li><b>Filter chips:</b> select column + operator + value → removable blue chips; all combined with <code>AND</code>.</li>
<li><b>Sort:</b> click on header → asc → desc (orange arrow is displayed).</li>
<li><b>Pagination:</b> "« ‹ › »" buttons + counter; change per from select (25/50/100/250).</li>
<li><b>Delete:</b> row checkboxes → confirm in modal → <code>row_delete</code> loop.</li>
<li><b>CSV:</b> from current page (not entire table) with proper escape of quote/comma/newline.</li>
</ul>
</section>

<section class="doc" id="editor">
<div class="sec-head"><div class="sec-num">14</div><h2>SQL Editor</h2></div>
<h3>14.1 Overlay Highlighting Technique</h3>
<p>A transparent <code>&lt;textarea&gt;</code> (invisible text, orange caret) is placed <b>exactly over</b> a highlighted <code>&lt;pre&gt;</code> with the same font/size/padding. 
On every keystroke: <code>hlSQL()</code> runs, placed inside <code>pre</code>, and scrolls are synced. Line number gutter is also synced separately.</p>
<p>Highlighter regex has 6 groups (in priority order):</p>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Group</th><th>Content</th><th>Color</th></tr></thead>
<tbody>
<tr><td>1</td><td>Comments (<code>/* */</code> , <code>--</code> , <code>#</code>)</td><td>Gray italic</td></tr>
<tr><td>2</td><td>Strings <code>'…'</code> and <code>"…"</code></td><td>Green</td></tr>
<tr><td>3</td><td>Numbers</td><td>Amber</td></tr>
<tr><td>4</td><td>Keywords (SELECT, CREATE, …)</td><td>Orange bold</td></tr>
<tr><td>5</td><td>Functions (COUNT, NOW, CONCAT, …)</td><td>Blue</td></tr>
<tr><td>6</td><td>Backticked identifiers</td><td>Text color</td></tr>
</tbody>
</table></div>
<h3>14.2 Autocomplete</h3>
<ul>
<li>On every keystroke, the word before the caret is extracted with regex.</li>
<li>If pattern is <code>table.</code> → only <b>columns of that table</b> are suggested; otherwise table names + keywords (max 12 items).</li>
<li>Popup position is calculated with <b>mirror div</b> technique: a div styled same as text up to caret is created and cursor offset is read.</li>
<li>Navigation: <kbd>↑</kbd><kbd>↓</kbd>, confirm with <kbd>Enter</kbd>/<kbd>Tab</kbd>, close with <kbd>Esc</kbd>.</li>
</ul>
<h3>14.3 Result and Exports</h3>
<ul>
<li><kbd>Ctrl</kbd>+<kbd>Enter</kbd> → execute; status in bar: row count + time (ms) + truncate warning.</li>
<li>Result table renders max 500 rows (exports are complete).</li>
<li><b>CSV</b>: same core conversion. <b>JSON</b>: pretty-print rows array. <b>SQL</b>: after getting table name, INSERT statements with full escape are generated.</li>
<li>History: last 50 entries in LocalStorage; its menu opens by clicking 🕘 and puts the query inside the editor.</li>
</ul>
</section>

<section class="doc" id="jsonschema">
<div class="sec-head"><div class="sec-num">15</div><h2>JSON Schema Format (Import / Export)</h2></div>
<p>Format is versioned (<code>phsql: 1</code> field) and used for backup, transferring between environments, and team collaboration. Node positions are also saved so team layout is transferred.</p>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>my_app.phsql.json<button class="copy-btn">copy</button></div><pre>{
<span class="k">"phsql"</span>: <span class="n">1</span>,
<span class="k">"db"</span>: <span class="s">"my_app"</span>,
<span class="k">"generated"</span>: <span class="s">"2025-01-15T10:30:00.000Z"</span>,
<span class="k">"tables"</span>: [
{
<span class="k">"name"</span>: <span class="s">"users"</span>, <span class="k">"engine"</span>: <span class="s">"InnoDB"</span>, <span class="k">"comment"</span>: <span class="s">""</span>,
<span class="k">"columns"</span>: [
{ <span class="k">"name"</span>: <span class="s">"id"</span>,    <span class="k">"type"</span>: <span class="s">"INT"</span>,     <span class="k">"unsigned"</span>: true, <span class="k">"nullable"</span>: false, <span class="k">"ai"</span>: true },
{ <span class="k">"name"</span>: <span class="s">"email"</span>, <span class="k">"type"</span>: <span class="s">"VARCHAR"</span>, <span class="k">"length"</span>: <span class="s">"255"</span>, <span class="k">"nullable"</span>: false }
],
<span class="k">"primaryKey"</span>: [<span class="s">"id"</span>],
<span class="k">"indexes"</span>: [ { <span class="k">"name"</span>: <span class="s">"uq_users_email"</span>, <span class="k">"unique"</span>: true, <span class="k">"columns"</span>: [<span class="s">"email"</span>] } ],
<span class="k">"foreignKeys"</span>: [],
<span class="k">"position"</span>: { <span class="k">"x"</span>: <span class="n">40</span>, <span class="k">"y"</span>: <span class="n">40</span> }
},
{
<span class="k">"name"</span>: <span class="s">"orders"</span>,
<span class="k">"columns"</span>: [
{ <span class="k">"name"</span>: <span class="s">"id"</span>,      <span class="k">"type"</span>: <span class="s">"INT"</span>, <span class="k">"ai"</span>: true, <span class="k">"nullable"</span>: false },
{ <span class="k">"name"</span>: <span class="s">"user_id"</span>, <span class="k">"type"</span>: <span class="s">"INT"</span>, <span class="k">"unsigned"</span>: true, <span class="k">"nullable"</span>: false },
{ <span class="k">"name"</span>: <span class="s">"total"</span>,   <span class="k">"type"</span>: <span class="s">"DECIMAL"</span>, <span class="k">"length"</span>: <span class="s">"10,2"</span>, <span class="k">"default"</span>: <span class="s">"0.00"</span> }
],
<span class="k">"primaryKey"</span>: [<span class="s">"id"</span>],
<span class="k">"foreignKeys"</span>: [ { <span class="k">"name"</span>: <span class="s">"fk_orders_user_id"</span>, <span class="k">"column"</span>: <span class="s">"user_id"</span>,
<span class="k">"refTable"</span>: <span class="s">"users"</span>, <span class="k">"refColumn"</span>: <span class="s">"id"</span>,
<span class="k">"onDelete"</span>: <span class="s">"CASCADE"</span>, <span class="k">"onUpdate"</span>: <span class="s">"NO ACTION"</span> } ],
<span class="k">"position"</span>: { <span class="k">"x"</span>: <span class="n">380</span>, <span class="k">"y"</span>: <span class="n">40</span> }
}
]
}</pre></div>
<h3>Import Compatibility</h3>
<p>Import parser is flexible and accepts these equivalent names: 
<code>pk</code> ↔ <code>primaryKey</code> · <code>fks</code> ↔ <code>foreignKeys</code> · <code>ai</code> ↔ <code>auto_increment</code> · 
<code>column/columns</code> and <code>refColumn/refColumns</code> in FK. 
After import: replace <code>App.schema</code> → go to Designer → <b>Migration modal auto-opens</b> to build tables in target database.</p>
</section>

<section class="doc" id="shortcuts">
<div class="sec-head"><div class="sec-num">16</div><h2>Keyboard Shortcuts</h2></div>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Shortcut</th><th>Action</th><th>Scope</th></tr></thead>
<tbody>
<tr><td><kbd>Alt</kbd>+<kbd>1</kbd> … <kbd>4</kbd></td><td>Go to Designer / ERD / Data / SQL Editor</td><td>Global</td></tr>
<tr><td><kbd>Ctrl</kbd>+<kbd>S</kbd></td><td>Save row changes (in Data) or open Migration (elsewhere)</td><td>Global</td></tr>
<tr><td><kbd>Ctrl</kbd>+<kbd>Enter</kbd></td><td>Execute query</td><td>SQL Editor</td></tr>
<tr><td><kbd>Alt</kbd>+<kbd>N</kbd></td><td>New table</td><td>Global</td></tr>
<tr><td><kbd>Alt</kbd>+<kbd>L</kbd></td><td>Canvas Auto-layout</td><td>Designer</td></tr>
<tr><td><kbd>/</kbd></td><td>Focus on table filter</td><td>Global</td></tr>
<tr><td><kbd>?</kbd></td><td>Show help modal</td><td>Global</td></tr>
<tr><td><kbd>Esc</kbd></td><td>Close modal / autocomplete</td><td>Global</td></tr>
<tr><td><kbd>Tab</kbd></td><td>Two spaces in editor (instead of focus jump)</td><td>SQL Editor</td></tr>
<tr><td><kbd>Enter</kbd> / <kbd>Esc</kbd></td><td>commit / cancel cell edit</td><td>Data Viewer</td></tr>
</tbody>
</table></div>
</section>

<section class="doc" id="theming">
<div class="sec-head"><div class="sec-num">17</div><h2>Theming & Design System</h2></div>
<p>Theme is defined with <b>CSS Custom Properties</b> on <code>[data-theme]</code> and dark/light switch only changes one attribute — updates instantly everywhere. User choice is saved in LocalStorage and applied on boot (dark is default).</p>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Token</th><th>Role</th><th>Dark Value</th></tr></thead>
<tbody>
<tr><td><code>--acc</code></td><td>Brand color and main actions (amber)</td><td><span style="color:var(--acc)">■</span> <code>#ffb454</code></td></tr>
<tr><td><code>--cyan</code></td><td>FK edges, links, info chips</td><td><span style="color:var(--cyan)">■</span> <code>#4cc9f0</code></td></tr>
<tr><td><code>--green / --red</code></td><td>Success/create and danger/delete</td><td><code>#4ade9d / #ff6b7d</code></td></tr>
<tr><td><code>--bg0..3</code></td><td>Background levels (4 depth layers)</td><td><code>#0b0e12 → #1c2330</code></td></tr>
<tr><td><code>--grid-dot</code></td><td>Canvas background dots</td><td><code>#1b2330</code></td></tr>
</tbody>
</table></div>
<ul>
<li><b>Typography:</b> display = Space Grotesk (brand/titles), body = IBM Plex Sans / Vazirmatn, code = JetBrains Mono.</li>
<li><b>Layout:</b> 3-column grid <code>236px | 1fr | 300px</code>. At 1180px → Inspector becomes sliding; at 880px → sidebar also sliding + compressed toolbar.</li>
<li><b>Animations:</b> view entry fadeIn, tabs sliding underline, nodes staggered fadeUp, toasts slide, modal scale+fade, temporary FK edge with animated dash.</li>
</ul>
</section>

<section class="doc" id="scenarios">
<div class="sec-head"><div class="sec-num">18</div><h2>Use Case Scenarios — Code Path</h2></div>
<h3>Scenario 1: Building Database from Scratch</h3>
<ol>
<li>New connection → <code>api('connect')</code> → save Session + Vault → <code>enterApp()</code>.</li>
<li>"+ Add table" button → <code>addTable()</code>: creates a table with <code>id INT UNSIGNED AUTO_INCREMENT PK</code> column in the center of current view and opens in Inspector.</li>
<li>Add columns from Inspector; change type/length with select; turn on AI/PK with switch and pill.</li>
<li>Create second table; drag from <code>user_id</code> port to <code>users.id</code> port → FK modal → save in model.</li>
<li><kbd>Ctrl</kbd>+<kbd>S</kbd> → <code>migrationStatements()</code> generates two CREATE TABLEs (parent first due to topo-sort).</li>
<li>Preview → Execute → <code>exec_script</code> → <code>refreshSchema()</code> → database is ready.</li>
</ol>
<h3>Scenario 2: Working with Existing Database</h3>
<ol>
<li>Connect → auto introspection → Designer/ERD with restored layout or Auto-Layout.</li>
<li>ERD: zoom/pan on diagram; edge colors and tooltip on each FK (name + ON DELETE).</li>
<li>Click on table in sidebar → <code>App.data.open(name)</code> → data grid.</li>
<li>Edit cell → turns amber → Save → <code>row_save</code> with secure PK.</li>
<li>SQL view → write query with autocomplete → execute → CSV export from toolbar.</li>
</ol>
<h3>Scenario 3: Migration and Collaboration</h3>
<ol>
<li>⇅ menu → "Export schema JSON" → download <code>{db}.phsql.json</code> (includes node positions).</li>
<li>Colleague: ⇅ menu → "Import schema JSON" → select file → model is replaced → Migration modal auto-opens.</li>
<li>Execute → exact same structure is built in target database.</li>
<li>For full structure backup (without positions): "Export SQL dump" from the same menu.</li>
</ol>
</section>

<section class="doc" id="trouble">
<div class="sec-head"><div class="sec-num">19</div><h2>Troubleshooting and FAQs</h2></div>
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>Issue</th><th>Cause and Solution</th></tr></thead>
<tbody>
<tr><td>"Connection failed" when connecting</td><td>Check host/port, <code>pdo_mysql</code> being enabled, user access. If error is "Unknown database", create the database first (tool intentionally doesn't create databases).</td></tr>
<tr><td>"Invalid server response"</td><td>PHP output something else before JSON (warning/notices or old PHP version). <code>php -v</code> should be 8.x; check errors in error log.</td></tr>
<tr><td>Inline edit disabled ("no PK")</td><td>Table has no primary key — for WHERE safety, editing requires PK. Add a PK from Designer and Migrate.</td></tr>
<tr><td>SELECT result truncated</td><td><code>MAX_FETCH_ROWS = 1000</code> limit in api.php (memory protection). Use export for full data.</td></tr>
<tr><td>Migration says "in sync"</td><td>Design has no difference with database — did you save changes in Designer? (model, not database, is the diff source)</td></tr>
<tr><td>JSON import gives error</td><td>File must have <code>tables</code> array; check if JSON is valid (version <code>phsql: 1</code>).</td></tr>
<tr><td>Saved password doesn't return</td><td>"Remember password" option was off during save.</td></tr>
<tr><td>ERD is empty</td><td>Database has no tables or introspection hasn't happened yet — ⟳ button in sidebar.</td></tr>
<tr><td>Node layout jumped</td><td>Positions are per-connection in LocalStorage; with browser storage cleared, Auto-Layout replaces it.</td></tr>
<tr><td>Fonts don't load</td><td>Needs internet for Google Fonts; automatic system fallback is active.</td></tr>
</tbody>
</table></div>
</section>

<section class="doc" id="extending">
<div class="sec-head"><div class="sec-num">20</div><h2>Extension Guide</h2></div>
<h3>20.1 Adding a New API Action</h3>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>api.php<button class="copy-btn">copy</button></div><pre><span class="k">function</span> action_my_feature(array $in): never {
$rows = db()->query(<span class="s">"SELECT …"</span>)->fetchAll();
ok([<span class="s">'data'</span> => $rows]);
}
<span class="s">'my_feature'</span> => action_my_feature($input),</pre></div>
<div class="codebox"><div class="cb-head"><span class="dots"><i></i><i></i><i></i></span>frontend<button class="copy-btn">copy</button></div><pre><span class="k">const</span> r = <span class="k">await</span> api(<span class="s">'my_feature'</span>, { param: <span class="n">1</span> });
toast(<span class="s">'Done'</span>, <span class="s">'ok'</span>);</pre></div>
<h3>20.2 Adding a New Data Type to Designer</h3>
<ol>
<li>Add the name to <code>COL_TYPES</code> array in designer.js.</li>
<li>If it takes length: to <code>NEEDS_LEN</code> and if needed <code>DEFAULT_LEN</code>.</li>
<li>If numeric: to <code>NUMERIC</code> (for DEFAULT behavior without quote).</li>
<li>If it has special behavior (like BOOLEAN→TINYINT(1)) inside <code>typeSQL()</code>.</li>
</ol>
<h3>20.3 Adding a New View</h3>
<ol>
<li>In index.php: add a <code>&lt;section class="view" id="view-x"&gt;</code> + tab button with <code>data-view="x"</code>.</li>
<li>In app.js extend the <code>switchView</code> shortcut array and put onShow logic if needed.</li>
</ol>
<h3>20.4 Suggested Roadmap</h3>
<ul>
<li>Multi-column FK editor in UI (backend and model already support it)</li>
<li>Undo/Redo for design model (command-stack framework)</li>
<li>Command Palette with <kbd>Ctrl</kbd>+<kbd>K</kbd></li>
<li>VIEW / TRIGGER / PROCEDURE management</li>
<li>Authentication layer for team deployment</li>
</ul>
</section>
</main>
</div>
<footer class="doc-foot">
<b>PHSQL</b> · Documentation version <?= htmlspecialchars($DOC_VERSION) ?> · Generated at <?= htmlspecialchars($GENERATED) ?> · Developer Behzad Shahbazi Fard
</footer>
<button id="totop" title="Back to top">↑</button>
<script>
const root = document.documentElement;
root.dataset.theme = localStorage.getItem('phsql.docs.theme') || 'dark';
document.getElementById('theme-btn').addEventListener('click', () => {
root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
localStorage.setItem('phsql.docs.theme', root.dataset.theme);
});

const links = [...document.querySelectorAll('nav#toc a')];
const map = new Map(links.map(a => [a.dataset.spy, a]));
const io = new IntersectionObserver(entries => {
for (const e of entries) {
if (e.isIntersecting) {
links.forEach(l => l.classList.remove('on'));
map.get(e.target.id)?.classList.add('on');
}
}
}, { rootMargin: '-20% 0px -70% 0px' });
document.querySelectorAll('section.doc').forEach(s => io.observe(s));

document.querySelectorAll('.copy-btn').forEach(btn => {
btn.addEventListener('click', async () => {
const pre = btn.closest('.codebox').querySelector('pre');
try {
await navigator.clipboard.writeText(pre.innerText);
btn.textContent = '✓ copied';
} catch { btn.textContent = '✕'; }
setTimeout(() => btn.textContent = 'copy', 1400);
});
});

const tt = document.getElementById('totop');
addEventListener('scroll', () => tt.classList.toggle('show', scrollY > 500), { passive: true });
tt.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
</script>
</body>
</html>
