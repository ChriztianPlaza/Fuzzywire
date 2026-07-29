<?php
session_start();
error_reporting(E_ERROR | E_PARSE);

define('GCASH_QR_IMAGE', 'assets/gcash-qr.png');
define('SHOP_EMAIL_FROM', 'no-reply@fuzzyotp.local');
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Crafty Fuzzy');

 $dataDir = __DIR__ . '/data';
 if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);
 $dbPath = $dataDir . '/fuzzywire.db';
 $db = new PDO('sqlite:' . $dbPath);
 $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

 $db->exec("CREATE TABLE IF NOT EXISTS flowers (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price_per_stem REAL,
  color TEXT, shape TEXT, in_builder INTEGER DEFAULT 1, in_stock INTEGER DEFAULT 1,
  stock_count INTEGER DEFAULT 50, category TEXT DEFAULT 'general', best_seller INTEGER DEFAULT 0, image TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS wrappers (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, color TEXT, style TEXT DEFAULT 'paper', in_stock INTEGER DEFAULT 1, image TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS ribbons (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, color TEXT, in_stock INTEGER DEFAULT 1, image TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS base_sizes (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT, price REAL,
  icon_size INTEGER DEFAULT 60, active INTEGER DEFAULT 1)");
 $db->exec("CREATE TABLE IF NOT EXISTS bouquets (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, description TEXT, price REAL,
  occasion TEXT, color_theme TEXT, price_range TEXT, components TEXT, featured INTEGER DEFAULT 0, image TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT, customer_name TEXT, customer_email TEXT,
  type TEXT, items TEXT, total REAL, status TEXT DEFAULT 'pending', created_at TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS cart (
  id INTEGER PRIMARY KEY AUTOINCREMENT, session_id TEXT, item_type TEXT,
  item_data TEXT, quantity INTEGER DEFAULT 1, created_at TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS customers (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, phone TEXT,
  verified INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS email_otps (
  id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, code TEXT, name TEXT, phone TEXT,
  password_hash TEXT, expires_at INTEGER, used INTEGER DEFAULT 0, created_at TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, customer_id INTEGER,
  customer_name TEXT, rating INTEGER, comment TEXT, photo TEXT, approved INTEGER DEFAULT 1, created_at TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS admins (
  id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password_hash TEXT,
  created_at TEXT, updated_at TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
  rl_key TEXT PRIMARY KEY, count INTEGER DEFAULT 1, window_start INTEGER)");

// Safely add image columns if they don't exist
try { $db->exec("ALTER TABLE flowers ADD COLUMN image TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE bouquets ADD COLUMN image TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN google_name TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN google_email TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN google_picture TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN delivery_address TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN gcash_reference TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN payment_status TEXT DEFAULT 'pending verification'"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN customer_phone TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE orders ADD COLUMN customer_id INTEGER"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE customers ADD COLUMN password_hash TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE email_otps ADD COLUMN password_hash TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE reviews ADD COLUMN photo TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE base_sizes ADD COLUMN icon_size INTEGER DEFAULT 60"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE base_sizes ADD COLUMN active INTEGER DEFAULT 1"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE flowers ADD COLUMN color_options TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE wrappers ADD COLUMN image TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE ribbons ADD COLUMN image TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE wrappers ADD COLUMN color_options TEXT"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE ribbons ADD COLUMN color_options TEXT"); } catch (Exception $e) {}

require_once __DIR__ . '/helpers.php';
$flowersNeedingColors = $db->query("SELECT id, color FROM flowers WHERE color_options IS NULL OR color_options = ''")->fetchAll();
if ($flowersNeedingColors) {
  $colorStmt = $db->prepare("UPDATE flowers SET color_options=? WHERE id=?");
  foreach ($flowersNeedingColors as $row) {
    $base = $row['color'] ?: '#D89B9B';
    $opts = json_encode([
      ['name' => 'Default', 'color' => $base],
      ['name' => 'Light', 'color' => adjustBrightness($base, 0.18)],
      ['name' => 'Deep', 'color' => adjustBrightness($base, -0.22)],
    ]);
    $colorStmt->execute([$opts, $row['id']]);
  }
}

function migrateItemColorOptions($db, $table) {
  $rows = $db->query("SELECT id, color, image FROM {$table} WHERE color_options IS NULL OR color_options = ''")->fetchAll();
  if (!$rows) return;
  $stmt = $db->prepare("UPDATE {$table} SET color_options=? WHERE id=?");
  foreach ($rows as $row) {
    $base = $row['color'] ?: '#888888';
    $opts = [['name' => 'Default', 'color' => $base, 'image' => $row['image'] ?: '']];
    $stmt->execute([json_encode($opts), $row['id']]);
  }
}
migrateItemColorOptions($db, 'wrappers');
migrateItemColorOptions($db, 'ribbons');

$db->exec("CREATE TABLE IF NOT EXISTS app_meta (key TEXT PRIMARY KEY, value TEXT)");

function isSeedDone($db, $key) {
  $stmt = $db->prepare("SELECT value FROM app_meta WHERE key=?");
  $stmt->execute([$key]);
  return (bool)$stmt->fetchColumn();
}

function markSeedDone($db, $key) {
  $stmt = $db->prepare("INSERT OR REPLACE INTO app_meta (key, value) VALUES (?, '1')");
  $stmt->execute([$key]);
}

function ensureSeedFlag($db, $key, $table) {
  if (!isSeedDone($db, $key) && $db->query("SELECT COUNT(*) FROM $table")->fetchColumn() > 0) {
    markSeedDone($db, $key);
  }
}

ensureSeedFlag($db, 'flowers_seeded', 'flowers');
ensureSeedFlag($db, 'wrappers_seeded', 'wrappers');
ensureSeedFlag($db, 'ribbons_seeded', 'ribbons');
ensureSeedFlag($db, 'base_sizes_seeded', 'base_sizes');
ensureSeedFlag($db, 'bouquets_seeded', 'bouquets');

// Seed Data (first install only — never re-seed after admin deletes rows)
if (!isSeedDone($db, 'flowers_seeded') && $db->query("SELECT COUNT(*) FROM flowers")->fetchColumn() == 0) {
  $flowers = [
    ['Garden Rose', 4.50, '#D89B9B', 'rose', 1, 1, 80, 'romantic', 1],
    ['Blush Peony', 6.00, '#E8B8B8', 'peony', 1, 1, 40, 'romantic', 1],
    ['Ivory Tulip', 3.00, '#F0E8D8', 'tulip', 1, 1, 60, 'modern', 0],
    ['Buttercup', 2.50, '#E8C572', 'ranunculus', 1, 1, 70, 'modern', 0],
    ['Silver Eucalyptus', 2.00, '#A8B5A0', 'eucalyptus', 1, 1, 100, 'greenery', 1],
    ['Lavender Stem', 1.50, '#9B8FB5', 'lavender', 1, 1, 90, 'wildflower', 0],
    ['Wine Anemone', 3.50, '#8B5A6B', 'anemone', 1, 1, 50, 'wildflower', 0],
    ['Dusty Miller', 2.00, '#C5C2B8', 'dusty', 1, 1, 60, 'greenery', 0],
    ['Pink Dahlia', 4.00, '#C9A0A8', 'dahlia', 1, 1, 45, 'romantic', 0],
    ['Terracotta Rose', 4.50, '#B8775C', 'rose', 1, 1, 30, 'modern', 1],
  ];
  $stmt = $db->prepare("INSERT INTO flowers (name, price_per_stem, color, shape, in_builder, in_stock, stock_count, category, best_seller) VALUES (?,?,?,?,?,?,?,?,?)");
  foreach ($flowers as $f) $stmt->execute($f);
  markSeedDone($db, 'flowers_seeded');
}

if (!isSeedDone($db, 'wrappers_seeded') && $db->query("SELECT COUNT(*) FROM wrappers")->fetchColumn() == 0) {
  $wrappers = [
    ['Kraft Paper', '#C9A876', 'paper'], ['Soft Ivory Tissue', '#F5EFE5', 'tissue'],
    ['Sage Linen', '#A8B5A0', 'linen'], ['Dusty Rose Wrap', '#D4B5B0', 'paper'],
    ['Terracotta Cloth', '#B8775C', 'cloth'], ['Stone Grey Paper', '#9A968C', 'paper'],
  ];
  $stmt = $db->prepare("INSERT INTO wrappers (name, color, style) VALUES (?,?,?)");
  foreach ($wrappers as $w) $stmt->execute($w);
  markSeedDone($db, 'wrappers_seeded');
}

if (!isSeedDone($db, 'ribbons_seeded') && $db->query("SELECT COUNT(*) FROM ribbons")->fetchColumn() == 0) {
  $ribbons = [
    ['Natural Twine', '#A08960'], ['Sage Silk', '#A8B5A0'], ['Dusty Rose Silk', '#C9A0A0'],
    ['Ivory Satin', '#F5EFE5'], ['Terracotta Velvet', '#B8775C'], ['Stone Linen', '#9A968C'],
  ];
  $stmt = $db->prepare("INSERT INTO ribbons (name, color) VALUES (?,?)");
  foreach ($ribbons as $r) $stmt->execute($r);
  markSeedDone($db, 'ribbons_seeded');
}

if (!isSeedDone($db, 'base_sizes_seeded') && $db->query("SELECT COUNT(*) FROM base_sizes")->fetchColumn() == 0) {
  $baseSizes = [
    ['Posy', 'Small, sweet', 15, 40, 1],
    ['Bouquet', 'Everyday luxury', 25, 60, 1],
    ['Statement', 'Grand gesture', 40, 80, 1],
  ];
  $stmt = $db->prepare("INSERT INTO base_sizes (name, description, price, icon_size, active) VALUES (?,?,?,?,?)");
  foreach ($baseSizes as $size) $stmt->execute($size);
  markSeedDone($db, 'base_sizes_seeded');
}

if (!isSeedDone($db, 'bouquets_seeded') && $db->query("SELECT COUNT(*) FROM bouquets")->fetchColumn() == 0) {
  $bouquets = [
    ['Morning Light', 'Soft ivory roses and eucalyptus, wrapped in kraft paper with twine.', 499, 'romantic', 'ivory', '200-500', '{"1":6,"5":4}', 1],
    ['Sage Garden', 'Blush peonies and dusty miller in a sage linen wrap.', 599, 'romantic', 'sage', '501-1000', '{"2":4,"8":3,"5":4}', 1],
    ['Terracotta Sky', 'Warm terracotta roses with silver dollar eucalyptus.', 899, 'modern', 'terracotta', '501-1000', '{"10":6,"5":3}', 1],
    ['Wild Meadow', 'Lavender, anemones, and buttercups gathered loosely.', 1500, 'wildflower', 'lavender', '1001-3000', '{"6":5,"7":4,"4":5}', 1],
  ];
  $stmt = $db->prepare("INSERT INTO bouquets (name, description, price, occasion, color_theme, price_range, components, featured) VALUES (?,?,?,?,?,?,?,?)");
  foreach ($bouquets as $b) $stmt->execute($b);
  markSeedDone($db, 'bouquets_seeded');
}
?>
