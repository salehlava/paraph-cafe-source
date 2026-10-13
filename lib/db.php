<?php
/**
 * SQLite database: connection, schema, first-run seeding, settings.
 * The database file is created automatically at storage/paraf.sqlite.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $dir = ROOT . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dir . '/paraf.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    if ($version < 1) {
        db_install($pdo);
    }
    if ($version < 2) {
        db_single_admin($pdo);
    }
    if ($version < 3) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS rate_hits (key TEXT NOT NULL, ts INTEGER NOT NULL);
                    CREATE INDEX IF NOT EXISTS rate_hits_key ON rate_hits(key, ts);
                    PRAGMA user_version = 3;');
    }
    return $pdo;
}

/**
 * Version 2: the site has exactly ONE admin account.
 * Keeps the oldest admin, removes any others, and adds a database rule
 * that refuses to create a second one.
 */
function db_single_admin(PDO $pdo): void
{
    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM admins WHERE id != (SELECT MIN(id) FROM admins)');
    $pdo->exec("CREATE TRIGGER IF NOT EXISTS single_admin BEFORE INSERT ON admins
                WHEN (SELECT COUNT(*) FROM admins) >= 1
                BEGIN SELECT RAISE(ABORT, 'Only one admin account is allowed'); END");
    $pdo->exec('PRAGMA user_version = 2');
    $pdo->commit();
}

function db_install(PDO $pdo): void
{
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS admins (
            id            INTEGER PRIMARY KEY,
            username      TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS categories (
            id       INTEGER PRIMARY KEY,
            icon     TEXT NOT NULL DEFAULT 'cup',
            title_fa TEXT NOT NULL,
            title_en TEXT NOT NULL,
            note_fa  TEXT NOT NULL DEFAULT '',
            note_en  TEXT NOT NULL DEFAULT '',
            sort     INTEGER NOT NULL DEFAULT 0,
            visible  INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS items (
            id          INTEGER PRIMARY KEY,
            category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            name_fa     TEXT NOT NULL,
            name_en     TEXT NOT NULL,
            price       INTEGER,
            is_addon    INTEGER NOT NULL DEFAULT 0,
            available   INTEGER NOT NULL DEFAULT 1,
            sort        INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS items_cat ON items(category_id, sort);
        CREATE TABLE IF NOT EXISTS members (
            id         INTEGER PRIMARY KEY,
            phone      TEXT NOT NULL UNIQUE,
            name       TEXT NOT NULL,
            birthday   TEXT NOT NULL DEFAULT '',
            points     INTEGER NOT NULL DEFAULT 0,
            note       TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS points_log (
            id         INTEGER PRIMARY KEY,
            member_id  INTEGER NOT NULL REFERENCES members(id) ON DELETE CASCADE,
            delta      INTEGER NOT NULL,
            reason     TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS otp (
            phone      TEXT PRIMARY KEY,
            code_hash  TEXT NOT NULL,
            expires_at INTEGER NOT NULL,
            attempts   INTEGER NOT NULL DEFAULT 0,
            last_sent  INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS sms_log (
            id         INTEGER PRIMARY KEY,
            recipients TEXT NOT NULL,
            message    TEXT NOT NULL,
            driver     TEXT NOT NULL,
            ok         INTEGER NOT NULL,
            response   TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    SQL);

    $pdo->beginTransaction();

    // Secret used to hash one-time codes.
    $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)')
        ->execute(['app_secret', bin2hex(random_bytes(32))]);

    // Seed the menu from data/menu.php the first time.
    if (!(int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn()) {
        $menu    = require ROOT . '/data/menu.php';
        $addCat  = $pdo->prepare('INSERT INTO categories (icon, title_fa, title_en, note_fa, note_en, sort) VALUES (?, ?, ?, ?, ?, ?)');
        $addItem = $pdo->prepare('INSERT INTO items (category_id, name_fa, name_en, price, is_addon, sort) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($menu as $ci => $cat) {
            $addCat->execute([$cat['icon'], $cat['title']['fa'], $cat['title']['en'], $cat['note']['fa'] ?? '', $cat['note']['en'] ?? '', $ci + 1]);
            $catId = (int) $pdo->lastInsertId();
            foreach ($cat['items'] as $ii => $item) {
                $addItem->execute([$catId, $item[0], $item[1], $item[2], ($item[3] ?? '') === 'addon' ? 1 : 0, $ii + 1]);
            }
        }
    }

    $pdo->exec('PRAGMA user_version = 1');
    $pdo->commit();
}

/* ---------- Settings (stored values override SETTINGS_DEFAULTS) ---------- */

function setting(string $key, ?string $default = null): ?string
{
    $all = settings_all();
    return $all[$key] ?? SETTINGS_DEFAULTS[$key] ?? $default;
}

function settings_all(bool $reload = false): array
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache;
}

function set_settings(array $values): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    foreach ($values as $k => $v) {
        $stmt->execute([$k, (string) $v]);
    }
    settings_all(true);
}

/* ---------- Menu ---------- */

/** Visible categories with their items, ordered. Pass $all = true for admin (includes hidden). */
function menu_load(bool $all = false): array
{
    $cats = db()->query('SELECT * FROM categories' . ($all ? '' : ' WHERE visible = 1') . ' ORDER BY sort, id')->fetchAll();
    $items = db()->query('SELECT * FROM items ORDER BY sort, id')->fetchAll();

    $byCat = [];
    foreach ($items as $it) {
        $byCat[$it['category_id']][] = $it;
    }
    foreach ($cats as &$c) {
        $c['items'] = $byCat[$c['id']] ?? [];
    }
    return $cats;
}
