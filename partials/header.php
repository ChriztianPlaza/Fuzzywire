<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crafty Fuzzy</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="?page=home" class="brand">
      <span class="brand-mark"><?= flowerSVG('rose', '#B8775C', 32) ?></span>
      Crafty Fuzzy
    </a>
    <nav class="nav-main">
<a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
<a href="?page=bouquets" class="<?= $page === 'bouquets' ? 'active' : '' ?>">Bouquets</a>
<a href="?page=customize" class="<?= $page === 'customize' ? 'active' : '' ?>">Customize</a>
<a href="?page=about" class="<?= $page === 'about' ? 'active' : '' ?>">About & Care</a>
<a href="?page=home#reviews" class="">Reviews</a>
    </nav>
    <div class="header-actions">
      <?php $signedInCustomer = $_SESSION['customer_user'] ?? null; ?>
      <button class="nav-toggle" id="navToggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="navMobile" onclick="toggleMobileNav()">
        <span></span><span></span><span></span>
      </button>
      <button class="cart-btn auth-btn" id="loginBtn" onclick="openAuthModal()" <?= $signedInCustomer ? 'hidden' : '' ?>>Sign in / Sign up</button>
      <div class="auth-menu" id="authMenu" <?= $signedInCustomer ? '' : 'hidden' ?>>
        <button class="auth-user" id="authUser" type="button" onclick="toggleAuthMenu()"><?= htmlspecialchars($signedInCustomer['name'] ?? $signedInCustomer['email'] ?? 'Account') ?></button>
        <div class="auth-dropdown" id="authDropdown">
          <button type="button" onclick="openChangePasswordModal()">Change password</button>
          <button type="button" onclick="logoutCustomer()">Logout</button>
        </div>
      </div>
      <div class="notif-menu" id="notifMenu" <?= $signedInCustomer ? '' : 'hidden' ?>>
        <button class="notif-btn" type="button" onclick="toggleNotifications()" title="Notifications">&#128276;</button>
        <span class="notif-count" id="notifCount" hidden>0</span>
        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-empty">No notifications yet.</div>
        </div>
      </div>
      <button class="cart-btn" onclick="openCart()">Cart <span class="cart-count">0</span></button>
    </div>
  </div>
</header>

<div class="nav-mobile-overlay" id="navMobileOverlay" onclick="closeMobileNav()"></div>
<nav class="nav-mobile" id="navMobile" aria-label="Mobile navigation">
<a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
<a href="?page=bouquets" class="<?= $page === 'bouquets' ? 'active' : '' ?>">Bouquets</a>
<a href="?page=customize" class="<?= $page === 'customize' ? 'active' : '' ?>">Customize</a>
<a href="?page=about" class="<?= $page === 'about' ? 'active' : '' ?>">About & Care</a>
<a href="?page=home#reviews">Reviews</a>
</nav>

<div class="toast-wrap" id="toastWrap"></div>

<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-head">
    <h3>Your Selections</h3>
    <button class="cart-close" onclick="closeCart()">&times;</button>
  </div>
  <div class="cart-items" id="cartItems">
    <div class="cart-empty">Your cart is quiet for now.</div>
  </div>
  <div class="cart-foot">
    <div class="cart-total"><span>Subtotal</span> <span id="cartTotal">&#8369;0.00</span></div>
    <button class="btn btn-primary" id="checkoutToggle" onclick="showCheckoutForm()" style="width:100%;justify-content:center">Checkout</button>
    <div class="checkout-auth-link" id="checkoutAuthLink" <?= $signedInCustomer ? 'hidden' : '' ?>>Already have an account? <button type="button" onclick="openAuthModal('signin')">Sign in</button></div>
    <form class="checkout-form" id="checkoutForm" onsubmit="submitCheckout(event)" hidden>
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="customer_name" autocomplete="name" required>
      </div>
      <div class="form-group">
        <label>Full Delivery Address</label>
        <textarea name="delivery_address" rows="4" autocomplete="street-address" required></textarea>
      </div>
      <div class="payment-box">
        <div class="payment-copy">
          <strong>Pay first with GCash</strong>
          <span>Scan the QR code, then enter your GCash transaction reference below.</span>
        </div>
        <?php if (defined('GCASH_QR_IMAGE') && file_exists(GCASH_QR_IMAGE)): ?>
          <img class="gcash-qr" src="<?= htmlspecialchars(GCASH_QR_IMAGE) ?>" alt="GCash QR code">
        <?php else: ?>
          <div class="gcash-placeholder">Add your QR at<br><code>assets/gcash-qr.png</code></div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>GCash Transaction Code</label>
        <input type="text" name="gcash_reference" placeholder="Example: 1234 567 8901" required>
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Place Order</button>
    </form>
  </div>
</div>

<div class="modal-overlay" id="authOverlay" onclick="closeAuthModal(event)">
  <div class="modal auth-modal">
    <h3 id="authTitle">Create your account</h3>
    <div class="auth-switch">
      <button type="button" id="authSignupTab" class="active" onclick="setAuthMode('signup')">Sign up</button>
      <button type="button" id="authSigninTab" onclick="setAuthMode('signin')">Sign in</button>
    </div>
    <form id="authStartForm" onsubmit="requestOtp(event)" autocomplete="off">
      <input type="hidden" name="mode" id="authMode" value="signup">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" id="authName" autocomplete="off" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" autocomplete="off" required>
      </div>
      <div class="signin-method" id="signinMethodGroup" hidden>
        <button type="button" id="signinPasswordTab" class="active" onclick="setSigninMethod('password')">Password</button>
        <button type="button" id="signinOtpTab" onclick="setSigninMethod('otp')">Send OTP</button>
      </div>
      <input type="hidden" name="signin_method" id="signinMethod" value="password">
      <div class="form-group">
        <label>Phone Number</label>
        <input type="tel" name="phone" id="authPhone" autocomplete="off" required>
      </div>
      <div class="form-group" id="signinPasswordGroup" hidden>
        <label>Password</label>
        <input type="password" name="signin_password" id="signinPassword" autocomplete="current-password">
      </div>
      <div class="form-group" id="authPasswordGroup">
        <label>Password</label>
        <input type="password" name="password" id="authPassword" minlength="6" autocomplete="new-password" required>
      </div>
      <div class="form-group" id="authPasswordConfirmGroup">
        <label>Confirm Password</label>
        <input type="password" name="password_confirm" id="authPasswordConfirm" minlength="6" autocomplete="new-password" required>
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Next</button>
    </form>
    <form id="otpForm" onsubmit="verifyOtp(event)" hidden>
      <p class="auth-note">Enter the 6-digit OTP sent to <span id="otpEmail"></span>.</p>
      <div class="form-group">
        <label>Email OTP</label>
        <input type="text" name="otp" inputmode="numeric" maxlength="6" required>
      </div>
      <div class="auth-dev-code" id="authDevCode" hidden></div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Verify Account</button>
      <button class="btn btn-ghost" type="button" onclick="resetAuthModal()" style="width:100%;justify-content:center;margin-top:10px">Use another email</button>
    </form>
  </div>
</div>

<div class="modal-overlay" id="passwordOverlay" onclick="closePasswordModal(event)">
  <div class="modal auth-modal">
    <h3>Change password</h3>
    <form id="passwordForm" onsubmit="changePassword(event)">
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="password" minlength="6" autocomplete="new-password" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="password_confirm" minlength="6" autocomplete="new-password" required>
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Save Password</button>
    </form>
  </div>
</div>

<div class="modal-overlay" id="reviewOverlay" onclick="closeReviewModal(event)">
  <div class="modal auth-modal">
    <h3>Review your flowers</h3>
    <form id="reviewForm" onsubmit="submitReview(event)" enctype="multipart/form-data">
      <input type="hidden" name="order_id" id="reviewOrderId">
      <div class="form-group">
        <label>Stars</label>
        <select name="rating" required>
          <option value="5">5 stars</option>
          <option value="4">4 stars</option>
          <option value="3">3 stars</option>
          <option value="2">2 stars</option>
          <option value="1">1 star</option>
        </select>
      </div>
      <div class="form-group">
        <label>Review</label>
        <textarea name="comment" rows="4" required></textarea>
      </div>
      <div class="form-group">
        <label>Photo</label>
        <input type="file" name="photo" accept="image/*">
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Submit Review</button>
    </form>
  </div>
</div>

<div class="photo-lightbox" id="photoLightbox" onclick="closePhotoPreview(event)">
  <div class="photo-lightbox-inner" onclick="event.stopPropagation()">
    <button class="photo-lightbox-close" type="button" onclick="closePhotoPreview()" aria-label="Close preview">&times;</button>
    <div class="photo-lightbox-content" id="photoLightboxContent"></div>
    <div class="photo-lightbox-caption" id="photoLightboxCaption"></div>
  </div>
</div>

<script>
  window.FUZZYWIRE_AUTH = {
    user: <?= json_encode($_SESSION['customer_user'] ?? null) ?>
  };
</script>
