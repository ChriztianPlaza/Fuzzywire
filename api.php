<?php
require_once 'config.php';

function sendJson($payload) {
  header('Content-Type: application/json');
  echo json_encode($payload);
  exit;
}

function parsePostedColorOptions($json, $filePrefix = 'color_image_') {
  $options = json_decode($json ?? '[]', true);
  if (!is_array($options)) return [];
  $uploadDir = __DIR__ . '/uploads/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
  $result = [];
  foreach ($options as $index => $opt) {
    if (empty($opt['name']) || empty($opt['color'])) continue;
    $image = $opt['image'] ?? '';
    $fileKey = $filePrefix . $index;
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
      $filename = uniqid() . '_' . basename($_FILES[$fileKey]['name']);
      move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename);
      $image = 'uploads/' . $filename;
    }
    $result[] = ['name' => $opt['name'], 'color' => $opt['color'], 'image' => $image];
  }
  return $result;
}

function requireAdminSession() {
  if (empty($_SESSION['admin_user'])) {
    sendJson(['ok' => false, 'admin_login_required' => true, 'error' => 'Please sign in as admin first.']);
  }
}

function normalizeEmail($email) {
  return strtolower(trim($email));
}

function isLocalRequest() {
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1') || PHP_SAPI === 'cli';
}

function smtpRead($socket) {
  $response = '';
  while (($line = fgets($socket, 515)) !== false) {
    $response .= $line;
    if (strlen($line) >= 4 && $line[3] === ' ') break;
  }
  return $response;
}

function smtpCommand($socket, $command, $expectedCodes) {
  fwrite($socket, $command . "\r\n");
  $response = smtpRead($socket);
  $code = intval(substr($response, 0, 3));
  if (!in_array($code, (array)$expectedCodes, true)) {
    throw new Exception(trim($response));
  }
  return $response;
}

function sendSmtpMail($to, $subject, $body) {
  if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) return false;
  if (!defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD') || SMTP_USERNAME === 'yourgmail@gmail.com' || SMTP_PASSWORD === 'your_gmail_app_password') {
    return false;
  }

  $host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
  $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
  $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : SMTP_USERNAME;
  $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Crafty Fuzzy';
  $remote = ($port == 465 ? 'ssl://' : '') . $host;
  $socket = @fsockopen($remote, $port, $errno, $errstr, 15);
  if (!$socket) return false;

  stream_set_timeout($socket, 15);
  try {
    $greeting = smtpRead($socket);
    if (intval(substr($greeting, 0, 3)) !== 220) throw new Exception(trim($greeting));
    smtpCommand($socket, 'EHLO localhost', 250);
    if ($port != 465) {
      smtpCommand($socket, 'STARTTLS', 220);
      stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
      smtpCommand($socket, 'EHLO localhost', 250);
    }
    smtpCommand($socket, 'AUTH LOGIN', 334);
    smtpCommand($socket, base64_encode(SMTP_USERNAME), 334);
    smtpCommand($socket, base64_encode(SMTP_PASSWORD), 235);
    smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
    smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    smtpCommand($socket, 'DATA', 354);

    $headers = [
      'From: ' . $fromName . ' <' . $fromEmail . '>',
      'To: <' . $to . '>',
      'Subject: ' . $subject,
      'MIME-Version: 1.0',
      'Content-Type: text/plain; charset=UTF-8'
    ];
    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $message = preg_replace("/^\./m", '..', $message);
    fwrite($socket, $message . "\r\n.\r\n");
    $response = smtpRead($socket);
    if (intval(substr($response, 0, 3)) !== 250) throw new Exception(trim($response));
    smtpCommand($socket, 'QUIT', 221);
    fclose($socket);
    return true;
  } catch (Exception $e) {
    fclose($socket);
    return false;
  }
}

function sendOtpEmail($email, $code) {
  $subject = 'Your Crafty Fuzzy verification code';
  $message = "Your Crafty Fuzzy OTP is: $code\n\nThis code expires in 10 minutes.";
  if (sendSmtpMail($email, $subject, $message)) return true;
  $headers = 'From: ' . (defined('SHOP_EMAIL_FROM') ? SHOP_EMAIL_FROM : 'no-reply@craftyfuzzy.local');
  return @mail($email, $subject, $message, $headers);
}

function getCartSummary($db) {
  $sid = session_id();
  $stmt = $db->prepare("SELECT * FROM cart WHERE session_id=? ORDER BY id");
  $stmt->execute([$sid]);
  $rows = $stmt->fetchAll();
  $items = [];
  $total = 0;

  foreach ($rows as $row) {
    $data = json_decode($row['item_data'] ?? '{}', true);
    if (!is_array($data)) $data = [];
    $qty = max(1, intval($row['quantity']));
    $line = 0;
    $name = 'Bouquet';
    $description = '';

    if ($row['item_type'] === 'premade') {
      $name = $data['name'] ?? 'Premade bouquet';
      $line = floatval($data['price'] ?? 0) * $qty;
      $description = 'Ready-made arrangement';
    } else {
      $size = $data['size'] ?? 'Bouquet';
      $minimum = isset($data['base_price']) ? floatval($data['base_price']) : null;
      if ($minimum === null && !empty($data['base_size_id'])) {
        $baseStmt = $db->prepare("SELECT name, price FROM base_sizes WHERE id=?");
        $baseStmt->execute([$data['base_size_id']]);
        $baseSize = $baseStmt->fetch();
        if ($baseSize) {
          $size = $baseSize['name'];
          $minimum = floatval($baseSize['price']);
        }
      }
      if ($minimum === null) $minimum = ['Posy' => 15, 'Bouquet' => 25, 'Statement' => 40][$size] ?? 25;
      $line = 0;
      $parts = [];
      foreach (($data['flowers'] ?? []) as $flowerId => $flowerQty) {
        $flowerStmt = $db->prepare("SELECT name, price_per_stem FROM flowers WHERE id=?");
        $flowerStmt->execute([$flowerId]);
        $flower = $flowerStmt->fetch();
        if ($flower) {
          $count = max(0, intval($flowerQty));
          $line += floatval($flower['price_per_stem']) * $count;
          if ($count > 0) {
            $colorName = $data['flower_colors'][$flowerId]['name'] ?? null;
            $parts[] = $flower['name'] . ' x' . $count . ($colorName ? ' (' . $colorName . ')' : '');
          }
        }
      }
      $line *= $qty;
      $name = $size . ' custom bouquet';
      $description = $parts ? implode(', ', $parts) : 'Custom stems';

      $wrapParts = [];
      foreach (($data['wrappers'] ?? []) as $wrapperId => $wrapperQty) {
        $wrapperStmt = $db->prepare("SELECT name FROM wrappers WHERE id=?");
        $wrapperStmt->execute([$wrapperId]);
        $wrapper = $wrapperStmt->fetch();
        if ($wrapper) {
          $count = max(0, intval($wrapperQty));
          if ($count > 0) {
            $colorName = $data['wrapper_colors'][$wrapperId]['name'] ?? null;
            $wrapParts[] = $wrapper['name'] . ' wrap x' . $count . ($colorName ? ' (' . $colorName . ')' : '');
          }
        }
      }
      if (empty($wrapParts) && !empty($data['wrapper'])) {
        $wrapperStmt = $db->prepare("SELECT name FROM wrappers WHERE id=?");
        $wrapperStmt->execute([$data['wrapper']]);
        $wrapper = $wrapperStmt->fetch();
        if ($wrapper) $wrapParts[] = $wrapper['name'] . ' wrap x1';
      }

      $ribbonParts = [];
      foreach (($data['ribbons'] ?? []) as $ribbonId => $ribbonQty) {
        $ribbonStmt = $db->prepare("SELECT name FROM ribbons WHERE id=?");
        $ribbonStmt->execute([$ribbonId]);
        $ribbon = $ribbonStmt->fetch();
        if ($ribbon) {
          $count = max(0, intval($ribbonQty));
          if ($count > 0) {
            $colorName = $data['ribbon_colors'][$ribbonId]['name'] ?? null;
            $ribbonParts[] = $ribbon['name'] . ' ribbon x' . $count . ($colorName ? ' (' . $colorName . ')' : '');
          }
        }
      }
      if (empty($ribbonParts) && !empty($data['ribbon'])) {
        $ribbonStmt = $db->prepare("SELECT name FROM ribbons WHERE id=?");
        $ribbonStmt->execute([$data['ribbon']]);
        $ribbon = $ribbonStmt->fetch();
        if ($ribbon) $ribbonParts[] = $ribbon['name'] . ' ribbon x1';
      }

      $extras = array_filter(array_merge($wrapParts, $ribbonParts));
      if ($extras) {
        $description .= ' · ' . implode(', ', $extras);
      }
      if ($minimum > 0) {
        $description = $size . ' min ' . number_format($minimum, 2) . ' · ' . $description;
      }
    }

    $items[] = [
      'id' => intval($row['id']),
      'type' => $row['item_type'],
      'name' => $name,
      'description' => $description,
      'quantity' => $qty,
      'line_total' => round($line, 2),
      'data' => $data
    ];
    $total += $line;
  }

  return ['items' => $items, 'total' => round($total, 2)];
}

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'cart') {
  sendJson(['ok' => true, 'cart' => getCartSummary($db)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
  header('Content-Type: application/json');

  if ($action === 'admin_login') {
    if (!rateLimitAllow($db, 'admin_login', 5, 300)) {
      sendJson(['ok' => false, 'error' => 'Too many login attempts. Try again in a few minutes.']);
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
      sendJson(['ok' => false, 'error' => 'Username and password are required.']);
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if (!$admin || empty($admin['password_hash']) || !password_verify($password, $admin['password_hash'])) {
      sendJson(['ok' => false, 'error' => 'Invalid admin credentials.']);
    }

    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
      'id' => intval($admin['id']),
      'username' => $admin['username']
    ];
    sendJson(['ok' => true]);
  }

  if ($action === 'admin_logout') {
    unset($_SESSION['admin_user']);
    sendJson(['ok' => true]);
  }

  if ($action === 'admin_change_password') {
    requireAdminSession();
    $current = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if ($current === '' || strlen($password) < 8) {
      sendJson(['ok' => false, 'error' => 'Current password and a new password of at least 8 characters are required.']);
    }
    if ($password !== $confirm) {
      sendJson(['ok' => false, 'error' => 'Passwords do not match.']);
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE id=? LIMIT 1");
    $stmt->execute([$_SESSION['admin_user']['id']]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($current, $admin['password_hash'])) {
      sendJson(['ok' => false, 'error' => 'Current password is incorrect.']);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE admins SET password_hash=?, updated_at=? WHERE id=?");
    $stmt->execute([$hash, date('Y-m-d H:i:s'), $admin['id']]);
    sendJson(['ok' => true]);
  }

  if ($action === 'auth_logout') {
    $clear = $db->prepare("DELETE FROM cart WHERE session_id=?");
    $clear->execute([session_id()]);
    unset($_SESSION['customer_user']);
    unset($_SESSION['pending_auth_email']);
    sendJson(['ok' => true]);
  }

  if ($action === 'auth_change_password') {
    if (empty($_SESSION['customer_user'])) {
      sendJson(['ok' => false, 'error' => 'Please sign in first.']);
    }
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';
    if (strlen($password) < 6) {
      sendJson(['ok' => false, 'error' => 'Password must be at least 6 characters.']);
    }
    if ($password !== $confirm) {
      sendJson(['ok' => false, 'error' => 'Passwords do not match.']);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE customers SET password_hash=?, updated_at=? WHERE id=?");
    $stmt->execute([$hash, date('Y-m-d H:i:s'), $_SESSION['customer_user']['id']]);
    sendJson(['ok' => true]);
  }

  if ($action === 'auth_password_login') {
    if (!rateLimitAllow($db, 'auth_password_login', 8, 300)) {
      sendJson(['ok' => false, 'error' => 'Too many attempts. Try again in a few minutes.']);
    }
    $email = normalizeEmail($_POST['email'] ?? '');
    $password = $_POST['signin_password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
      sendJson(['ok' => false, 'error' => 'Email and password are required.']);
    }

    $stmt = $db->prepare("SELECT * FROM customers WHERE email=? AND verified=1");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();
    if (!$customer || empty($customer['password_hash']) || !password_verify($password, $customer['password_hash'])) {
      sendJson(['ok' => false, 'error' => 'Invalid email or password.']);
    }

    $_SESSION['customer_user'] = [
      'id' => intval($customer['id']),
      'name' => $customer['name'],
      'email' => $customer['email'],
      'phone' => $customer['phone']
    ];
    unset($_SESSION['pending_auth_email']);
    sendJson(['ok' => true, 'user' => $_SESSION['customer_user']]);
  }
  
  if ($action === 'auth_request_otp') {
    if (!rateLimitAllow($db, 'auth_request_otp', 3, 600)) {
      sendJson(['ok' => false, 'error' => 'Too many OTP requests. Try again in a few minutes.']);
    }
    $mode = ($_POST['mode'] ?? 'signup') === 'signin' ? 'signin' : 'signup';
    $name = trim($_POST['name'] ?? '');
    $email = normalizeEmail($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $passwordHash = null;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($mode === 'signup' && ($name === '' || $phone === ''))) {
      sendJson(['ok' => false, 'error' => 'Please enter a valid name, email, and phone number.']);
    }
    if ($mode === 'signup') {
      $password = $_POST['password'] ?? '';
      $confirm = $_POST['password_confirm'] ?? '';
      if (strlen($password) < 6) sendJson(['ok' => false, 'error' => 'Password must be at least 6 characters.']);
      if ($password !== $confirm) sendJson(['ok' => false, 'error' => 'Passwords do not match.']);
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }

    $existing = $db->prepare("SELECT * FROM customers WHERE email=? AND verified=1");
    $existing->execute([$email]);
    $customer = $existing->fetch();
    if ($mode === 'signup' && $customer) {
      sendJson(['ok' => false, 'error' => 'This email is already registered. Please sign in instead.']);
    }
    if ($mode === 'signin') {
      if (!$customer) sendJson(['ok' => false, 'error' => 'No verified account found for this email. Please sign up first.']);
      $name = $customer['name'];
      $phone = $customer['phone'];
    }

    $code = (string)random_int(100000, 999999);
    $stmt = $db->prepare("INSERT INTO email_otps (email, code, name, phone, password_hash, expires_at, created_at) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$email, $code, $name, $phone, $passwordHash, time() + 600, date('Y-m-d H:i:s')]);
    $_SESSION['pending_auth_email'] = $email;

    $sent = sendOtpEmail($email, $code);
    $response = ['ok' => true, 'email' => $email, 'sent' => $sent];
    if (!$sent) $response['dev_otp'] = $code;
    sendJson($response);
  }

  if ($action === 'auth_verify_otp') {
    if (!rateLimitAllow($db, 'auth_verify_otp', 8, 300)) {
      sendJson(['ok' => false, 'error' => 'Too many attempts. Try again in a few minutes.']);
    }
    $email = normalizeEmail($_SESSION['pending_auth_email'] ?? '');
    $code = trim($_POST['otp'] ?? '');
    if ($email === '' || $code === '') sendJson(['ok' => false, 'error' => 'OTP is required.']);

    $stmt = $db->prepare("SELECT * FROM email_otps WHERE email=? AND code=? AND used=0 ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $code]);
    $otp = $stmt->fetch();
    if (!$otp || intval($otp['expires_at']) < time()) {
      sendJson(['ok' => false, 'error' => 'Invalid or expired OTP.']);
    }

    $db->prepare("UPDATE email_otps SET used=1 WHERE id=?")->execute([$otp['id']]);
    $existing = $db->prepare("SELECT * FROM customers WHERE email=?");
    $existing->execute([$email]);
    $customer = $existing->fetch();
    if ($customer) {
      if (!empty($otp['password_hash'])) {
        $stmt = $db->prepare("UPDATE customers SET name=?, phone=?, password_hash=?, verified=1, updated_at=? WHERE id=?");
        $stmt->execute([$otp['name'], $otp['phone'], $otp['password_hash'], date('Y-m-d H:i:s'), $customer['id']]);
      } else {
        $stmt = $db->prepare("UPDATE customers SET name=?, phone=?, verified=1, updated_at=? WHERE id=?");
        $stmt->execute([$otp['name'], $otp['phone'], date('Y-m-d H:i:s'), $customer['id']]);
      }
      $customerId = $customer['id'];
    } else {
      $stmt = $db->prepare("INSERT INTO customers (name, email, phone, password_hash, verified, created_at, updated_at) VALUES (?,?,?,?,?,?,?)");
      $now = date('Y-m-d H:i:s');
      $stmt->execute([$otp['name'], $email, $otp['phone'], $otp['password_hash'], 1, $now, $now]);
      $customerId = $db->lastInsertId();
    }

    $_SESSION['customer_user'] = [
      'id' => intval($customerId),
      'name' => $otp['name'],
      'email' => $email,
      'phone' => $otp['phone']
    ];
    unset($_SESSION['pending_auth_email']);
    sendJson(['ok' => true, 'user' => $_SESSION['customer_user']]);
  }

  if ($action === 'add_to_cart') {
    if (empty($_SESSION['customer_user'])) {
      sendJson(['ok' => false, 'login_required' => true, 'error' => 'Please sign in first.']);
    }
    $sid = session_id();
    $itemType = $_POST['item_type'] ?? 'premade';
    $itemData = $_POST['item_data'] ?? '{}';
    $qty = max(1, intval($_POST['quantity'] ?? 1));

    if ($itemType === 'custom') {
      $data = json_decode($itemData, true);
      if (!is_array($data)) sendJson(['ok' => false, 'error' => 'Invalid custom bouquet data.']);
      $minimum = isset($data['base_price']) ? floatval($data['base_price']) : 0;
      if ($minimum <= 0 && !empty($data['base_size_id'])) {
        $baseStmt = $db->prepare("SELECT price FROM base_sizes WHERE id=?");
        $baseStmt->execute([$data['base_size_id']]);
        $baseSize = $baseStmt->fetch();
        if ($baseSize) $minimum = floatval($baseSize['price']);
      }
      $flowerTotal = 0;
      foreach (($data['flowers'] ?? []) as $flowerId => $flowerQty) {
        $flowerStmt = $db->prepare("SELECT price_per_stem FROM flowers WHERE id=?");
        $flowerStmt->execute([$flowerId]);
        $flower = $flowerStmt->fetch();
        if ($flower) $flowerTotal += floatval($flower['price_per_stem']) * max(0, intval($flowerQty));
      }
      if ($flowerTotal + 0.0001 < $minimum) {
        sendJson(['ok' => false, 'error' => 'Stem total must be at least ' . number_format($minimum, 2) . ' for this base size.']);
      }
    }

    $stmt = $db->prepare("INSERT INTO cart (session_id, item_type, item_data, quantity, created_at) VALUES (?,?,?,?,?)");
    $stmt->execute([$sid, $itemType, $itemData, $qty, date('Y-m-d H:i:s')]);
    sendJson(['ok' => true, 'message' => 'Added to your bouquet queue', 'cart' => getCartSummary($db)]);
  }

  if ($action === 'remove_cart_item') {
    $stmt = $db->prepare("DELETE FROM cart WHERE id=? AND session_id=?");
    $stmt->execute([$_POST['id'], session_id()]);
    sendJson(['ok' => true, 'cart' => getCartSummary($db)]);
  }

  if ($action === 'checkout') {
    if (empty($_SESSION['customer_user'])) {
      sendJson(['ok' => false, 'login_required' => true, 'error' => 'Please sign in first.']);
    }
    $cart = getCartSummary($db);
    if (empty($cart['items'])) sendJson(['ok' => false, 'error' => 'Your cart is empty.']);

    $customerName = trim($_POST['customer_name'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');
    $gcashReference = trim($_POST['gcash_reference'] ?? '');
    if ($customerName === '' || $address === '' || $gcashReference === '') {
      sendJson(['ok' => false, 'error' => 'Name, full address, and GCash transaction code are required.']);
    }

    $user = $_SESSION['customer_user'];
    $stmt = $db->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, customer_id, google_name, google_email, google_picture, delivery_address, gcash_reference, payment_status, type, items, total, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
      $customerName,
      $user['email'],
      $user['phone'],
      $user['id'],
      $user['name'],
      $user['email'],
      '',
      $address,
      $gcashReference,
      'pending verification',
      'checkout',
      json_encode($cart['items']),
      $cart['total'],
      'pending',
      date('Y-m-d H:i:s')
    ]);

    $clear = $db->prepare("DELETE FROM cart WHERE session_id=?");
    $clear->execute([session_id()]);
    sendJson(['ok' => true, 'order_id' => $db->lastInsertId()]);
  }

  if ($action === 'customer_notifications') {
    if (empty($_SESSION['customer_user'])) sendJson(['ok' => true, 'notifications' => []]);
    $stmt = $db->prepare("SELECT id, total, status, payment_status, created_at FROM orders WHERE customer_id=? OR customer_email=? ORDER BY id DESC LIMIT 12");
    $stmt->execute([$_SESSION['customer_user']['id'], $_SESSION['customer_user']['email']]);
    $notifications = [];
    foreach ($stmt->fetchAll() as $order) {
      $message = 'Order #' . $order['id'] . ': payment ' . ($order['payment_status'] ?: 'pending verification') . ', flowers ' . ucfirst($order['status']);
      $reviewCheck = $db->prepare("SELECT COUNT(*) FROM reviews WHERE order_id=?");
      $reviewCheck->execute([$order['id']]);
      $notifications[] = [
        'id' => intval($order['id']),
        'key' => $order['id'] . '|' . ($order['payment_status'] ?: 'pending verification') . '|' . ($order['status'] ?: 'pending'),
        'message' => $message,
        'status' => $order['status'],
        'payment_status' => $order['payment_status'],
        'can_review' => $order['status'] === 'delivered' && !$reviewCheck->fetchColumn()
      ];
    }
    sendJson(['ok' => true, 'notifications' => $notifications]);
  }

  if ($action === 'submit_review') {
    if (empty($_SESSION['customer_user'])) sendJson(['ok' => false, 'error' => 'Please sign in first.']);
    $orderId = intval($_POST['order_id'] ?? 0);
    $rating = max(1, min(5, intval($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if ($orderId <= 0 || $comment === '') sendJson(['ok' => false, 'error' => 'Review and order are required.']);
    $stmt = $db->prepare("SELECT * FROM orders WHERE id=? AND (customer_id=? OR customer_email=?) AND status='delivered'");
    $stmt->execute([$orderId, $_SESSION['customer_user']['id'], $_SESSION['customer_user']['email']]);
    if (!$stmt->fetch()) sendJson(['ok' => false, 'error' => 'Only delivered orders can be reviewed.']);
    $exists = $db->prepare("SELECT COUNT(*) FROM reviews WHERE order_id=?");
    $exists->execute([$orderId]);
    if ($exists->fetchColumn() > 0) sendJson(['ok' => false, 'error' => 'This order already has a review.']);

    $photoPath = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = __DIR__ . '/uploads/reviews/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
      $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) sendJson(['ok' => false, 'error' => 'Photo must be an image.']);
      $filename = uniqid('review_', true) . '.' . $ext;
      move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
      $photoPath = 'uploads/reviews/' . $filename;
    }
    $stmt = $db->prepare("INSERT INTO reviews (order_id, customer_id, customer_name, rating, comment, photo, approved, created_at) VALUES (?,?,?,?,?,?,1,?)");
    $stmt->execute([$orderId, $_SESSION['customer_user']['id'], $_SESSION['customer_user']['name'], $rating, $comment, $photoPath, date('Y-m-d H:i:s')]);
    sendJson(['ok' => true]);
  }

  if (str_starts_with($action, 'admin_')) {
    requireAdminSession();
  }
  
  if ($action === 'admin_flower_save') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name']; $price = floatval($_POST['price']);
    $color = $_POST['color']; $shape = $_POST['shape'];
    $category = $_POST['category']; $stock = intval($_POST['stock']);
    $inBuilder = isset($_POST['in_builder']) ? 1 : 0;
    $inStock = isset($_POST['in_stock']) ? 1 : 0;
    $best = isset($_POST['best_seller']) ? 1 : 0;
    $colorOptions = $_POST['color_options'] ?? '[]';
    $parsedOptions = json_decode($colorOptions, true);
    if (!is_array($parsedOptions)) $parsedOptions = [];
    $parsedOptions = array_values(array_filter($parsedOptions, function($opt) {
      return !empty($opt['name']) && !empty($opt['color']);
    }));
    if (empty($parsedOptions)) {
      $parsedOptions = [['name' => 'Default', 'color' => $color]];
    }
    $colorOptions = json_encode($parsedOptions);
    
    $imagePath = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
        $imagePath = 'uploads/' . $filename;
    }

    if ($id) {
      $stmt = $db->prepare("UPDATE flowers SET name=?, price_per_stem=?, color=?, shape=?, category=?, stock_count=?, in_builder=?, in_stock=?, best_seller=?, image=?, color_options=? WHERE id=?");
      $stmt->execute([$name, $price, $color, $shape, $category, $stock, $inBuilder, $inStock, $best, $imagePath, $colorOptions, $id]);
    } else {
      $stmt = $db->prepare("INSERT INTO flowers (name, price_per_stem, color, shape, category, stock_count, in_builder, in_stock, best_seller, image, color_options) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$name, $price, $color, $shape, $category, $stock, $inBuilder, $inStock, $best, $imagePath, $colorOptions]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_flower_colors_save') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
      echo json_encode(['ok' => false, 'error' => 'Stem not found.']);
      exit;
    }
    $colorOptions = $_POST['color_options'] ?? '[]';
    $parsedOptions = json_decode($colorOptions, true);
    if (!is_array($parsedOptions)) $parsedOptions = [];
    $parsedOptions = array_values(array_filter($parsedOptions, function($opt) {
      return !empty($opt['name']) && !empty($opt['color']);
    }));
    if (empty($parsedOptions)) {
      echo json_encode(['ok' => false, 'error' => 'Add at least one color option.']);
      exit;
    }
    $stmt = $db->prepare("UPDATE flowers SET color_options=? WHERE id=?");
    $stmt->execute([json_encode($parsedOptions), $id]);
    echo json_encode(['ok' => true]);
    exit;
  }
  
  if ($action === 'admin_flower_delete') {
    $stmt = $db->prepare("DELETE FROM flowers WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }
  
  if ($action === 'admin_toggle_builder') {
    $stmt = $db->prepare("UPDATE flowers SET in_builder = 1 - in_builder WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_base_size_save') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $iconSize = max(30, min(120, intval($_POST['icon_size'] ?? 60)));
    $active = isset($_POST['active']) ? 1 : 0;
    if ($name === '' || $description === '' || $price < 0) {
      echo json_encode(['ok' => false, 'error' => 'Name, description, and a valid price are required.']);
      exit;
    }
    if ($id && !$active) {
      $activeCount = $db->query("SELECT COUNT(*) FROM base_sizes WHERE active = 1")->fetchColumn();
      $stmt = $db->prepare("SELECT active FROM base_sizes WHERE id=?");
      $stmt->execute([$id]);
      $existingSize = $stmt->fetch();
      if ($existingSize && intval($existingSize['active']) === 1 && intval($activeCount) <= 1) {
        echo json_encode(['ok' => false, 'error' => 'Keep at least one active base size in the builder.']);
        exit;
      }
    }
    if ($id) {
      $stmt = $db->prepare("UPDATE base_sizes SET name=?, description=?, price=?, icon_size=?, active=? WHERE id=?");
      $stmt->execute([$name, $description, $price, $iconSize, $active, $id]);
    } else {
      $stmt = $db->prepare("INSERT INTO base_sizes (name, description, price, icon_size, active) VALUES (?,?,?,?,?)");
      $stmt->execute([$name, $description, $price, $iconSize, $active]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_base_size_delete') {
    $id = intval($_POST['id'] ?? 0);
    $activeCount = $db->query("SELECT COUNT(*) FROM base_sizes WHERE active = 1")->fetchColumn();
    $stmt = $db->prepare("SELECT active FROM base_sizes WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && intval($row['active']) === 1 && intval($activeCount) <= 1) {
      echo json_encode(['ok' => false, 'error' => 'Keep at least one active base size in the builder.']);
      exit;
    }
    $stmt = $db->prepare("DELETE FROM base_sizes WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
  }
  
  if ($action === 'admin_bouquet_save') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name']; $desc = $_POST['description']; $price = floatval($_POST['price']);
    $occasion = $_POST['occasion']; $theme = $_POST['color_theme']; $range = $_POST['price_range'];
    $components = $_POST['components']; $featured = isset($_POST['featured']) ? 1 : 0;
    if ($price < 200 || $price > 3000) {
      echo json_encode(['ok' => false, 'error' => 'Bouquet price must be between ₱200 and ₱3,000.']);
      exit;
    }
    if ($price <= 500) $range = '200-500';
    elseif ($price <= 1000) $range = '501-1000';
    else $range = '1001-3000';
    
    $imagePath = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
        $imagePath = 'uploads/' . $filename;
    }

    if ($id) {
      $stmt = $db->prepare("UPDATE bouquets SET name=?, description=?, price=?, occasion=?, color_theme=?, price_range=?, components=?, featured=?, image=? WHERE id=?");
      $stmt->execute([$name, $desc, $price, $occasion, $theme, $range, $components, $featured, $imagePath, $id]);
    } else {
      $stmt = $db->prepare("INSERT INTO bouquets (name, description, price, occasion, color_theme, price_range, components, featured, image) VALUES (?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$name, $desc, $price, $occasion, $theme, $range, $components, $featured, $imagePath]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }
  
  if ($action === 'admin_bouquet_delete') {
    $stmt = $db->prepare("DELETE FROM bouquets WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_wrapper_save') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $style = $_POST['style'] ?? 'paper';
    $inStock = isset($_POST['in_stock']) ? 1 : 0;
    $colorOptions = parsePostedColorOptions($_POST['color_options'] ?? '[]', 'color_image_');
    if (!$colorOptions) {
      echo json_encode(['ok' => false, 'error' => 'Add at least one color with a photo or swatch.']);
      exit;
    }
    $color = $colorOptions[0]['color'];
    $imagePath = $colorOptions[0]['image'] ?? '';
    $colorOptionsJson = json_encode($colorOptions);

    if ($id) {
      $stmt = $db->prepare("UPDATE wrappers SET name=?, color=?, style=?, in_stock=?, image=?, color_options=? WHERE id=?");
      $stmt->execute([$name, $color, $style, $inStock, $imagePath, $colorOptionsJson, $id]);
    } else {
      $stmt = $db->prepare("INSERT INTO wrappers (name, color, style, in_stock, image, color_options) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$name, $color, $style, $inStock, $imagePath, $colorOptionsJson]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_wrapper_delete') {
    $stmt = $db->prepare("DELETE FROM wrappers WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_ribbon_save') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $inStock = isset($_POST['in_stock']) ? 1 : 0;
    $colorOptions = parsePostedColorOptions($_POST['color_options'] ?? '[]', 'color_image_');
    if (!$colorOptions) {
      echo json_encode(['ok' => false, 'error' => 'Add at least one color with a photo or swatch.']);
      exit;
    }
    $color = $colorOptions[0]['color'];
    $imagePath = $colorOptions[0]['image'] ?? '';
    $colorOptionsJson = json_encode($colorOptions);

    if ($id) {
      $stmt = $db->prepare("UPDATE ribbons SET name=?, color=?, in_stock=?, image=?, color_options=? WHERE id=?");
      $stmt->execute([$name, $color, $inStock, $imagePath, $colorOptionsJson, $id]);
    } else {
      $stmt = $db->prepare("INSERT INTO ribbons (name, color, in_stock, image, color_options) VALUES (?,?,?,?,?)");
      $stmt->execute([$name, $color, $inStock, $imagePath, $colorOptionsJson]);
    }
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_ribbon_delete') {
    $stmt = $db->prepare("DELETE FROM ribbons WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_order_status') {
    $allowed = ['pending', 'preparing', 'ready', 'delivering', 'delivered', 'completed', 'cancelled'];
    $status = $_POST['status'] ?? 'pending';
    if (!in_array($status, $allowed, true)) {
      echo json_encode(['ok' => false, 'error' => 'invalid status']);
      exit;
    }
    $stmt = $db->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$status, $_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_payment_status') {
    $allowed = ['pending verification', 'verified', 'rejected'];
    $status = $_POST['status'] ?? 'pending verification';
    if (!in_array($status, $allowed, true)) {
      echo json_encode(['ok' => false, 'error' => 'invalid payment status']);
      exit;
    }
    $stmt = $db->prepare("UPDATE orders SET payment_status=? WHERE id=?");
    $stmt->execute([$status, $_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'admin_order_delete') {
    $stmt = $db->prepare("DELETE FROM orders WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['ok' => true]);
    exit;
  }
  
  echo json_encode(['ok' => false, 'error' => 'unknown action']);
  exit;
}
?>
