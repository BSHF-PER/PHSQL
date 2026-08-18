<?php
declare(strict_types=1);

/**
 * PHSQL — Backend API (Pure PHP 8.x + PDO, no framework)
 * Protocol: POST JSON { action: "...", ... } → JSON { ok: true|false, ... }
 */

session_name('phsql');
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

const MAX_FETCH_ROWS = 1000;

/* ---------- helpers ---------- */

function respond(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}
function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    respond(['ok' => false, 'error' => $msg]);
}
function ok(array $data = []): never {
    respond(['ok' => true] + $data);
}
function qi(string $id): string { // quote identifier
    return '`' . str_replace('`', '``', $id) . '`';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $c = $_SESSION['phsql_conn'] ?? null;
    if (!$c) fail('Not connected', 401);
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['db']),
            $c['user'], $c['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $pdo->exec('SET SESSION group_concat_max_len = 1000000');
    } catch (PDOException $e) {
        fail('Connection failed: ' . $e->getMessage(), 500);
    }
    return $pdo;
}

function parseType(string $columnType): array {
    $unsigned = str_contains(strtolower($columnType), 'unsigned');
    if (preg_match('/^([a-z]+)(?:\((.+?)\))?\s*(.*)$/is', trim($columnType), $m)) {
        $base = strtolower($m[1]);
        if ($base === 'enum' || $base === 'set') {
            return ['type' => $base, 'length' => $m[2] ?? '', 'unsigned' => false];
        }
        return ['type' => $base, 'length' => ($m[2] ?? '') !== '' ? $m[2] : null, 'unsigned' => $unsigned];
    }
    return ['type' => strtolower($columnType), 'length' => null, 'unsigned' => false];
}

/** Split a SQL script into statements (respects strings & comments). */
function splitStatements(string $sql): array {
    $out = []; $buf = ''; $inS = false; $inD = false; $inB = false; $inL = false;
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i]; $nx = $sql[$i + 1] ?? ''; $pv = $sql[$i - 1] ?? '';
        if ($inL) { $buf .= $ch; if ($ch === "\n") $inL = false; continue; }
        if ($inB) { $buf .= $ch; if ($ch === '*' && $nx === '/') { $buf .= '/'; $i++; $inB = false; } continue; }
        if ($inS) {
            $buf .= $ch;
            if ($ch === "'" && $pv !== '\\') { if ($nx === "'") { $buf .= $nx; $i++; } else $inS = false; }
            continue;
        }
        if ($inD) {
            $buf .= $ch;
            if ($ch === '"' && $pv !== '\\') { if ($nx === '"') { $buf .= $nx; $i++; } else $inD = false; }
            continue;
        }
        if ($ch === '-' && $nx === '-') { $inL = true; $buf .= $ch; continue; }
        if ($ch === '#')                { $inL = true; $buf .= $ch; continue; }
        if ($ch === '/' && $nx === '*') { $inB = true; $buf .= $ch; continue; }
        if ($ch === "'") { $inS = true; $buf .= $ch; continue; }
        if ($ch === '"') { $inD = true; $buf .= $ch; continue; }
        if ($ch === ';') { if (trim($buf) !== '') $out[] = trim($buf); $buf = ''; continue; }
        $buf .= $ch;
    }
    if (trim($buf) !== '') $out[] = trim($buf);
    return $out;
}

/* ---------- actions ---------- */

function action_connect(array $in): never {
    foreach (['host', 'user', 'db'] as $f) {
        if (!isset($in[$f]) || $in[$f] === '') fail("Missing field: $f");
    }
    $host = (string)$in['host'];
    $port = (int)($in['port'] ?? 3306);
    $user = (string)$in['user'];
    $pass = (string)($in['pass'] ?? '');
    $name = (string)$in['db'];
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->query('USE ' . qi($name));
        $version = (string)$pdo->query('SELECT VERSION() v')->fetch()['v'];
    } catch (PDOException $e) {
        fail('Connection failed: ' . $e->getMessage(), 401);
    }
    $_SESSION['phsql_conn'] = ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass, 'db' => $name];
    ok(['version' => $version, 'db' => $name]);
}

function action_disconnect(): never {
    unset($_SESSION['phsql_conn']);
    session_destroy();
    ok();
}

function action_status(): never {
    $c = $_SESSION['phsql_conn'] ?? null;
    if (!$c) ok(['connected' => false]);
    $v = db()->query('SELECT VERSION() v')->fetch()['v'];
    ok(['connected' => true, 'host' => $c['host'], 'port' => $c['port'], 'db' => $c['db'], 'user' => $c['user'], 'version' => $v]);
}

function action_tables(): never {
    $rows = db()->query(
        "SELECT TABLE_NAME name, ENGINE engine, TABLE_COLLATION collation,
                TABLE_ROWS est_rows, AUTO_INCREMENT auto_increment, TABLE_COMMENT comment
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
         ORDER BY TABLE_NAME"
    )->fetchAll();
    ok(['tables' => $rows]);
}

function action_schema(): never {
    $pdo = db();
    $tables = [];
    foreach ($pdo->query("SELECT TABLE_NAME t, ENGINE e, TABLE_COMMENT c
                          FROM information_schema.TABLES
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE='BASE TABLE'") as $r) {
        $tables[$r['t']] = ['name' => $r['t'], 'engine' => $r['e'], 'comment' => $r['c'],
            'columns' => [], 'primaryKey' => [], 'indexes' => [], 'foreignKeys' => []];
    }
    $st = $pdo->query("SELECT * FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION");
    foreach ($st as $r) {
        $t = $r['TABLE_NAME'];
        if (!isset($tables[$t])) continue;
        $p = parseType((string)$r['COLUMN_TYPE']);
        $tables[$t]['columns'][] = [
            'name'     => $r['COLUMN_NAME'],
            'type'     => strtoupper($p['type']),
            'length'   => $p['length'],
            'unsigned' => $p['unsigned'],
            'nullable' => $r['IS_NULLABLE'] === 'YES',
            'default'  => $r['COLUMN_DEFAULT'],
            'ai'       => str_contains(strtolower((string)$r['EXTRA']), 'auto_increment'),
            'comment'  => (string)$r['COLUMN_COMMENT'],
            'fullType' => (string)$r['COLUMN_TYPE'],
        ];
        if ($r['COLUMN_KEY'] === 'PRI') $tables[$t]['primaryKey'][] = $r['COLUMN_NAME'];
    }
    $st = $pdo->query("SELECT TABLE_NAME t, INDEX_NAME n, NON_UNIQUE nu,
                              GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) c
                       FROM information_schema.STATISTICS
                       WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME <> 'PRIMARY'
                       GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE");
    foreach ($st as $r) {
        if (!isset($tables[$r['t']])) continue;
        $tables[$r['t']]['indexes'][] = ['name' => $r['n'], 'unique' => (int)$r['nu'] === 0,
            'columns' => explode(',', (string)$r['c'])];
    }
    $st = $pdo->query("SELECT k.TABLE_NAME t, k.CONSTRAINT_NAME n,
                              GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION) c,
                              k.REFERENCED_TABLE_NAME rt,
                              GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION) rc,
                              MAX(r.UPDATE_RULE) ur, MAX(r.DELETE_RULE) dr
                       FROM information_schema.KEY_COLUMN_USAGE k
                       JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                         ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
                        AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                        AND r.TABLE_NAME = k.TABLE_NAME
                       WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
                       GROUP BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME");
    foreach ($st as $r) {
        if (!isset($tables[$r['t']])) continue;
        $tables[$r['t']]['foreignKeys'][] = [
            'name' => $r['n'], 'columns' => explode(',', (string)$r['c']),
            'refTable' => $r['rt'], 'refColumns' => explode(',', (string)$r['rc']),
            'onUpdate' => $r['ur'], 'onDelete' => $r['dr'],
        ];
    }
    ok(['schema' => array_values($tables)]);
}

function action_table_data(array $in): never {
    $table = (string)($in['table'] ?? fail('table required'));
    $page  = max(1, (int)($in['page'] ?? 1));
    $per   = min(500, max(1, (int)($in['per'] ?? 50)));
    $sort  = $in['sort'] ?? null;
    $dir   = strtolower((string)($in['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
    $filters = $in['filters'] ?? [];
    $q = trim((string)($in['q'] ?? ''));
    $pdo = db();

    $cm = $pdo->prepare("SELECT COLUMN_NAME c, DATA_TYPE t FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION");
    $cm->execute([$table]);
    $meta = $cm->fetchAll();
    if (!$meta) fail('Table not found: ' . $table, 404);
    $colNames = array_column($meta, 'c');

    $where = []; $params = [];
    $OPS = ['=' => '=', '!=' => '!=', '>' => '>', '<' => '<', '>=' => '>=', '<=' => '<=', 'LIKE' => 'LIKE'];
    foreach ($filters as $f) {
        $col = (string)($f['col'] ?? ''); $op = (string)($f['op'] ?? '='); $val = $f['val'] ?? '';
        if ($col === '' || !in_array($col, $colNames, true)) continue;
        if ($op === 'IS NULL')      { $where[] = qi($col) . ' IS NULL'; continue; }
        if ($op === 'IS NOT NULL')  { $where[] = qi($col) . ' IS NOT NULL'; continue; }
        if (!isset($OPS[$op])) continue;
        $where[] = qi($col) . ' ' . $OPS[$op] . ' ?';
        $params[] = $val;
    }
    if ($q !== '') {
        $parts = [];
        foreach ($meta as $m) {
            if (in_array($m['t'], ['varchar','char','text','tinytext','mediumtext','longtext','enum','set'], true)) {
                $parts[] = qi($m['c']) . ' LIKE ?'; $params[] = "%$q%";
            } else {
                $parts[] = '(' . qi($m['c']) . ' IS NOT NULL AND ' . qi($m['c']) . ' = ?)'; $params[] = $q;
            }
        }
        if ($parts) $where[] = '(' . implode(' OR ', $parts) . ')';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $sortSql  = ($sort && in_array($sort, $colNames, true)) ? ' ORDER BY ' . qi((string)$sort) . ' ' . $dir : '';

    $st = $pdo->prepare('SELECT COUNT(*) FROM ' . qi($table) . $whereSql);
    $st->execute($params);
    $total = (int)$st->fetchColumn();

    $offset = ($page - 1) * $per;
    $st = $pdo->prepare('SELECT * FROM ' . qi($table) . $whereSql . $sortSql . " LIMIT $per OFFSET $offset");
    $st->execute($params);
    $rows = $st->fetchAll();

    $pk = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
                         ORDER BY ORDINAL_POSITION");
    $pk->execute([$table]);
    ok(['rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per,
        'columns' => $meta, 'pk' => array_column($pk->fetchAll(), 'COLUMN_NAME')]);
}

function action_query(array $in): never {
    $sql = trim((string)($in['sql'] ?? ''));
    if ($sql === '') fail('Empty query');
    $t0 = microtime(true);
    try { $stmt = db()->query($sql); }
    catch (PDOException $e) { fail('SQL error: ' . $e->getMessage(), 500); }
    $ms = round((microtime(true) - $t0) * 1000, 1);
    if ($stmt->columnCount() > 0) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $truncated = count($rows) > MAX_FETCH_ROWS;
        if ($truncated) $rows = array_slice($rows, 0, MAX_FETCH_ROWS);
        ok(['type' => 'result', 'columns' => array_keys($rows[0] ?? []), 'rows' => $rows,
            'count' => count($rows), 'truncated' => $truncated, 'ms' => $ms]);
    }
    ok(['type' => 'exec', 'affected' => $stmt->rowCount(), 'ms' => $ms]);
}

function action_exec_script(array $in): never {
    $sql = (string)($in['sql'] ?? '');
    $stmts = splitStatements($sql);
    if (!$stmts) fail('Nothing to execute');
    $pdo = db();
    foreach ($stmts as $i => $s) {
        try { $pdo->query($s); }
        catch (PDOException $e) {
            fail('Statement #' . ($i + 1) . ' failed: ' . $e->getMessage() . "\n\n" . $s, 500);
        }
    }
    ok(['executed' => count($stmts)]);
}

function action_row_save(array $in): never {
    $table = (string)($in['table'] ?? fail('table required'));
    $pk  = $in['pk'] ?? [];
    $set = $in['set'] ?? [];
    if (!$set) fail('Nothing to update');
    if (!$pk)  fail('Table has no primary key — inline edit disabled');
    $setSql = []; $params = [];
    foreach ($set as $col => $val) { $setSql[] = qi((string)$col) . ' = ?'; $params[] = $val; }
    $where = [];
    foreach ($pk as $k) { $where[] = qi((string)$k['col']) . ' <=> ?'; $params[] = $k['val']; }
    $st = db()->prepare('UPDATE ' . qi($table) . ' SET ' . implode(', ', $setSql) . ' WHERE ' . implode(' AND ', $where));
    $st->execute($params);
    ok(['affected' => $st->rowCount()]);
}

function action_row_insert(array $in): never {
    $table = (string)($in['table'] ?? fail('table required'));
    $vals  = $in['values'] ?? [];
    if (!$vals) fail('Nothing to insert');
    $cols = []; $marks = []; $params = [];
    foreach ($vals as $col => $val) {
        $cols[] = qi((string)$col); $marks[] = '?'; $params[] = $val;
    }
    $pdo = db();
    $st = $pdo->prepare('INSERT INTO ' . qi($table) . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $marks) . ')');
    $st->execute($params);
    ok(['id' => $pdo->lastInsertId() ?: null]);
}

function action_row_delete(array $in): never {
    $table = (string)($in['table'] ?? fail('table required'));
    $pk = $in['pk'] ?? [];
    if (!$pk) fail('Table has no primary key');
    $where = []; $params = [];
    foreach ($pk as $k) { $where[] = qi((string)$k['col']) . ' <=> ?'; $params[] = $k['val']; }
    $st = db()->prepare('DELETE FROM ' . qi($table) . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
    $st->execute($params);
    ok(['affected' => $st->rowCount()]);
}

function action_dump(array $in): never {
    $pdo = db();
    $names = $in['tables'] ?? array_column(
        $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM), 0
    );
    $out = ['-- PHSQL schema dump', '-- ' . date('c'), 'SET FOREIGN_KEY_CHECKS = 0;', ''];
    foreach ((array)$names as $t) {
        $row = $pdo->query('SHOW CREATE TABLE ' . qi((string)$t))->fetch(PDO::FETCH_NUM);
        if (!$row) continue;
        $out[] = 'DROP TABLE IF EXISTS ' . qi((string)$t) . ';';
        $out[] = $row[1] . ';';
        $out[] = '';
    }
    $out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    ok(['sql' => implode("\n", $out)]);
}

/* ---------- dispatch ---------- */

$raw   = file_get_contents('php://input');
$input = $raw !== '' && $raw !== false ? (json_decode($raw, true) ?? []) : ($_POST ?: []);
$action = (string)($input['action'] ?? '');

try {
    match ($action) {
        'connect'     => action_connect($input),
        'disconnect'  => action_disconnect(),
        'status'      => action_status(),
        'tables'      => action_tables(),
        'schema'      => action_schema(),
        'table_data'  => action_table_data($input),
        'query'       => action_query($input),
        'exec_script' => action_exec_script($input),
        'row_save'    => action_row_save($input),
        'row_insert'  => action_row_insert($input),
        'row_delete'  => action_row_delete($input),
        'dump'        => action_dump($input),
        default       => fail("Unknown action: $action", 404),
    };
} catch (PDOException $e) {
    fail('Database error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    fail('Server error: ' . $e->getMessage(), 500);
}
