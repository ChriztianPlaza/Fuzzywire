// Currency formatter for Philippine Peso
function formatCurrency(amount) {
  return '\u20b1' + Number(amount || 0).toFixed(2);
}

// === TOAST ===
function showToast(msg) {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  const t = document.createElement('div');
  t.className = 'toast';
  t.innerHTML = `<div class="toast-mark">OK</div><div>${msg}</div>`;
  wrap.appendChild(t);
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => {
    t.classList.remove('show');
    setTimeout(() => t.remove(), 400);
  }, 3000);
}

// === AUTH + CART ===
let currentUser = null;
let cartState = { items: [], total: 0 };
let notificationTimer = null;

function initCustomerState() {
  currentUser = window.FUZZYWIRE_AUTH?.user || null;
  updateAuthUI();
  initBouquetFilters();
  initPhotoPreviews();
  loadCart();
  loadNotifications();
  if (!notificationTimer) notificationTimer = setInterval(loadNotifications, 10000);
  document.addEventListener('click', e => {
    const menu = document.getElementById('authMenu');
    if (menu && !menu.contains(e.target)) menu.classList.remove('open');
    const notif = document.getElementById('notifMenu');
    if (notif && !notif.contains(e.target)) notif.classList.remove('open');
  });
}

function initPhotoPreviews() {
  document.addEventListener('click', (e) => {
    if (e.target.closest('.quick-add, .qty-stepper, .stem-color-picker, .stem-color-swatch')) return;

    const trigger = e.target.closest('.photo-preview-trigger');
    if (!trigger) return;

    e.preventDefault();
    e.stopPropagation();

    const caption = trigger.dataset.previewCaption || trigger.getAttribute('alt') || '';
    const img = trigger.matches('img') ? trigger : trigger.querySelector('img');
    const src = trigger.dataset.previewSrc || img?.dataset.previewSrc || img?.getAttribute('src');

    if (src) {
      openPhotoPreview({ src, caption });
      return;
    }

    const svg = trigger.querySelector('svg');
    if (svg) {
      const clone = svg.cloneNode(true);
      clone.setAttribute('width', '320');
      clone.setAttribute('height', '320');
      openPhotoPreview({ caption, html: `<div class="photo-lightbox-svg">${clone.outerHTML}</div>` });
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePhotoPreview();
  });
}

function openPhotoPreview({ src, caption, html }) {
  const overlay = document.getElementById('photoLightbox');
  const content = document.getElementById('photoLightboxContent');
  const capEl = document.getElementById('photoLightboxCaption');
  if (!overlay || !content) return;

  if (src) {
    content.innerHTML = `<img src="${escapeHtml(src)}" alt="${escapeHtml(caption)}">`;
  } else if (html) {
    content.innerHTML = html;
  } else {
    return;
  }

  if (capEl) capEl.textContent = caption || '';
  overlay.classList.add('show');
  document.body.classList.add('photo-lightbox-open');
}

function closePhotoPreview(event) {
  if (event && event.target !== event.currentTarget) return;
  const overlay = document.getElementById('photoLightbox');
  const content = document.getElementById('photoLightboxContent');
  if (!overlay) return;
  overlay.classList.remove('show');
  document.body.classList.remove('photo-lightbox-open');
  if (content) content.innerHTML = '';
}
document.addEventListener('DOMContentLoaded', initCustomerState);

function initBouquetFilters() {
  const grid = document.getElementById('bouquetGrid');
  if (!grid) return;
  const chips = document.querySelectorAll('[data-filter-type]');
  const cards = Array.from(grid.querySelectorAll('.product-card'));
  const empty = document.getElementById('bouquetEmpty');
  const filters = { occasion: 'all', priceMin: null, priceMax: null };

  function applyBouquetFilters() {
    let visible = 0;
    cards.forEach(card => {
      const occasionMatch = filters.occasion === 'all' || card.dataset.occasion === filters.occasion;
      const price = Number(card.dataset.price || 0);
      const priceMatch = filters.priceMin === null || (price >= filters.priceMin && price <= filters.priceMax);
      const show = occasionMatch && priceMatch;
      card.hidden = !show;
      if (show) visible += 1;
    });
    if (empty) empty.hidden = visible > 0;
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      const type = chip.dataset.filterType;
      chips.forEach(other => {
        if (other.dataset.filterType === type) other.classList.remove('active');
      });
      chip.classList.add('active');
      if (type === 'occasion') {
        filters.occasion = chip.dataset.filterValue || 'all';
      } else {
        filters.priceMin = chip.dataset.filterValue === 'all' ? null : Number(chip.dataset.minPrice);
        filters.priceMax = chip.dataset.filterValue === 'all' ? null : Number(chip.dataset.maxPrice);
      }
      applyBouquetFilters();
    });
  });
}

function updateAuthUI() {
  const loginBtn = document.getElementById('loginBtn');
  const authMenu = document.getElementById('authMenu');
  const authUser = document.getElementById('authUser');
  const checkoutAuthLink = document.getElementById('checkoutAuthLink');
  if (!loginBtn || !authMenu || !authUser) return;

  if (currentUser) {
    loginBtn.hidden = true;
    authMenu.hidden = false;
    document.getElementById('notifMenu')?.removeAttribute('hidden');
    authUser.textContent = currentUser.name || currentUser.email || 'Account';
    if (checkoutAuthLink) checkoutAuthLink.hidden = true;
  } else {
    loginBtn.hidden = false;
    authMenu.hidden = true;
    document.getElementById('notifMenu')?.setAttribute('hidden', '');
    authMenu.classList.remove('open');
    authUser.textContent = '';
    if (checkoutAuthLink) checkoutAuthLink.hidden = false;
  }
}

function toggleNotifications() {
  const menu = document.getElementById('notifMenu');
  menu?.classList.toggle('open');
  if (menu?.classList.contains('open')) markNotificationsRead();
  loadNotifications();
}

function markNotificationsRead(notes = null) {
  const count = document.getElementById('notifCount');
  if (notes) {
    const readIds = JSON.parse(sessionStorage.getItem('readNotifications') || '[]');
    const noteIds = notes.map(n => String(n.key || n.id));
    sessionStorage.setItem('readNotifications', JSON.stringify([...new Set([...readIds, ...noteIds])]));
  }
  if (count) count.hidden = true;
}

function loadNotifications() {
  const menu = document.getElementById('notifMenu');
  const dropdown = document.getElementById('notifDropdown');
  const count = document.getElementById('notifCount');
  if (!menu || !dropdown || !count || !currentUser) return;
  fetch('api.php', {
    method: 'POST',
    body: (() => { const fd = new FormData(); fd.append('action', 'customer_notifications'); return fd; })()
  }).then(r => r.json()).then(res => {
    if (!res.ok) return;
    const notes = res.notifications || [];
    const readIds = JSON.parse(sessionStorage.getItem('readNotifications') || '[]');
    const unreadCount = notes.filter(n => !readIds.includes(String(n.key || n.id))).length;
    count.textContent = unreadCount;
    count.hidden = unreadCount === 0 || menu.classList.contains('open');
    dropdown.innerHTML = notes.length ? notes.map(n => `
      <div class="notif-item">
        <div>${escapeHtml(n.message)}</div>
        ${n.status === 'delivered' ? '<div class="notif-delivered">Delivered. Thank you for ordering.</div>' : ''}
        ${n.can_review ? `<button type="button" onclick="openReviewModal(${n.id})">Leave review</button>` : ''}
      </div>
    `).join('') : '<div class="notif-empty">No notifications yet.</div>';
    if (menu.classList.contains('open')) markNotificationsRead(notes);
    const seenDelivered = JSON.parse(sessionStorage.getItem('deliveredOrderNotifications') || '[]');
    const deliveredIds = notes.filter(n => n.status === 'delivered').map(n => Number(n.id));
    const newDelivered = deliveredIds.filter(id => !seenDelivered.includes(id));
    if (newDelivered.length) {
      showToast('Your flowers have been delivered.');
      sessionStorage.setItem('deliveredOrderNotifications', JSON.stringify([...new Set([...seenDelivered, ...deliveredIds])]));
    }
  });
}

function openReviewModal(orderId) {
  const form = document.getElementById('reviewForm');
  if (form) form.reset();
  document.getElementById('reviewOrderId').value = orderId;
  document.getElementById('reviewOverlay')?.classList.add('show');
  document.getElementById('notifMenu')?.classList.remove('open');
}

function closeReviewModal(e) {
  if (e.target.id === 'reviewOverlay') document.getElementById('reviewOverlay').classList.remove('show');
}

function submitReview(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'submit_review');
  fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
    if (!res.ok) {
      alert(res.error || 'Could not submit review.');
      return;
    }
    document.getElementById('reviewOverlay').classList.remove('show');
    showToast('Review submitted');
    loadNotifications();
  }).catch(() => alert('Could not submit review.'));
}

function toggleAuthMenu() {
  document.getElementById('authMenu')?.classList.toggle('open');
}

function openChangePasswordModal() {
  document.getElementById('authMenu')?.classList.remove('open');
  const form = document.getElementById('passwordForm');
  if (form) form.reset();
  document.getElementById('passwordOverlay')?.classList.add('show');
}

function closePasswordModal(e) {
  if (e.target.id === 'passwordOverlay') {
    document.getElementById('passwordOverlay').classList.remove('show');
  }
}

function changePassword(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'auth_change_password');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        alert(res.error || 'Could not change password.');
        return;
      }
      document.getElementById('passwordOverlay').classList.remove('show');
      showToast('Password updated');
    })
    .catch(() => alert('Could not change password.'));
}

function logoutCustomer() {
  const fd = new FormData();
  fd.append('action', 'auth_logout');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        alert(res.error || 'Could not log out.');
        return;
      }
      currentUser = null;
      updateAuthUI();
      loadCart();
      loadNotifications();
      showToast('Logged out');
    })
    .catch(() => alert('Could not log out.'));
}

function openAuthModal(mode = 'signup') {
  resetAuthModal();
  setAuthMode(mode);
  document.getElementById('authOverlay')?.classList.add('show');
}

function closeAuthModal(e) {
  if (e.target.id === 'authOverlay') {
    document.getElementById('authOverlay').classList.remove('show');
  }
}

function resetAuthModal() {
  const startForm = document.getElementById('authStartForm');
  const otpForm = document.getElementById('otpForm');
  const devCode = document.getElementById('authDevCode');
  if (startForm) {
    startForm.hidden = false;
    startForm.reset();
  }
  if (otpForm) {
    otpForm.hidden = true;
    otpForm.reset();
  }
  if (devCode) {
    devCode.hidden = true;
    devCode.textContent = '';
  }
}

function setAuthMode(mode) {
  const isSignin = mode === 'signin';
  const modeInput = document.getElementById('authMode');
  const title = document.getElementById('authTitle');
  const nameGroup = document.getElementById('authName')?.closest('.form-group');
  const phoneGroup = document.getElementById('authPhone')?.closest('.form-group');
  const passwordGroup = document.getElementById('authPasswordGroup');
  const passwordConfirmGroup = document.getElementById('authPasswordConfirmGroup');
  const signinMethodGroup = document.getElementById('signinMethodGroup');
  const signinPasswordGroup = document.getElementById('signinPasswordGroup');
  const nameInput = document.getElementById('authName');
  const phoneInput = document.getElementById('authPhone');
  const passwordInput = document.getElementById('authPassword');
  const passwordConfirmInput = document.getElementById('authPasswordConfirm');
  const signinPasswordInput = document.getElementById('signinPassword');
  const signupTab = document.getElementById('authSignupTab');
  const signinTab = document.getElementById('authSigninTab');

  if (modeInput) modeInput.value = isSignin ? 'signin' : 'signup';
  if (title) title.textContent = isSignin ? 'Sign in to your account' : 'Create your account';
  if (nameGroup) nameGroup.hidden = isSignin;
  if (phoneGroup) phoneGroup.hidden = isSignin;
  if (signinMethodGroup) {
    signinMethodGroup.hidden = !isSignin;
    signinMethodGroup.style.display = isSignin ? '' : 'none';
  }
  if (signinPasswordGroup) {
    signinPasswordGroup.hidden = !isSignin;
    signinPasswordGroup.style.display = isSignin ? '' : 'none';
  }
  if (passwordGroup) passwordGroup.hidden = isSignin;
  if (passwordConfirmGroup) passwordConfirmGroup.hidden = isSignin;
  if (nameInput) nameInput.required = !isSignin;
  if (phoneInput) phoneInput.required = !isSignin;
  if (passwordInput) passwordInput.required = !isSignin;
  if (passwordConfirmInput) passwordConfirmInput.required = !isSignin;
  if (signinPasswordInput) signinPasswordInput.required = isSignin;
  signupTab?.classList.toggle('active', !isSignin);
  signinTab?.classList.toggle('active', isSignin);
  if (isSignin) setSigninMethod('password');
}

function setSigninMethod(method) {
  const isOtp = method === 'otp';
  const isSignin = document.getElementById('authMode')?.value === 'signin';
  const methodInput = document.getElementById('signinMethod');
  const passwordGroup = document.getElementById('signinPasswordGroup');
  const passwordInput = document.getElementById('signinPassword');
  const passwordTab = document.getElementById('signinPasswordTab');
  const otpTab = document.getElementById('signinOtpTab');
  if (methodInput) methodInput.value = isOtp ? 'otp' : 'password';
  if (passwordGroup) {
    passwordGroup.hidden = !isSignin || isOtp;
    passwordGroup.style.display = isSignin && !isOtp ? '' : 'none';
  }
  if (passwordInput) passwordInput.required = isSignin && !isOtp;
  passwordTab?.classList.toggle('active', !isOtp);
  otpTab?.classList.toggle('active', isOtp);
}

function requestOtp(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  const isPasswordSignin = fd.get('mode') === 'signin' && fd.get('signin_method') === 'password';
  fd.append('action', isPasswordSignin ? 'auth_password_login' : 'auth_request_otp');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        alert(res.error || (isPasswordSignin ? 'Could not sign in.' : 'Could not send OTP.'));
        return;
      }
      if (isPasswordSignin) {
        currentUser = res.user;
        updateAuthUI();
        document.getElementById('authOverlay').classList.remove('show');
        showToast('Signed in');
        loadCart();
        return;
      }
      document.getElementById('authStartForm').hidden = true;
      document.getElementById('otpForm').hidden = false;
      document.getElementById('otpEmail').textContent = res.email;
      if (res.dev_otp) {
        const devCode = document.getElementById('authDevCode');
        devCode.hidden = false;
        devCode.textContent = `Email was not sent by SMTP, so use this local test OTP: ${res.dev_otp}`;
      }
      showToast(res.sent ? 'OTP sent to your email' : 'OTP ready for local testing');
    })
    .catch(() => alert('Could not send OTP.'));
}

function verifyOtp(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'auth_verify_otp');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        alert(res.error || 'Could not verify OTP.');
        return;
      }
      currentUser = res.user;
      updateAuthUI();
      document.getElementById('authOverlay').classList.remove('show');
      showToast('Account verified');
      loadCart();
    })
    .catch(() => alert('Could not verify OTP.'));
}

function openCart() {
  document.getElementById('cartOverlay').classList.add('open');
  document.getElementById('cartDrawer').classList.add('open');
  loadCart();
}
function closeCart() {
  document.getElementById('cartOverlay').classList.remove('open');
  document.getElementById('cartDrawer').classList.remove('open');
}

function addToCart(itemType, itemData, qty = 1) {
  if (!currentUser) {
    showToast('Please sign in before adding to cart.');
    openAuthModal();
    return;
  }
  fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=add_to_cart&item_type=${itemType}&item_data=${encodeURIComponent(itemData)}&quantity=${qty}`
  }).then(r => r.json()).then(res => {
    if (res.ok) {
      showToast(res.message);
      loadCart();
    } else if (res.login_required) {
      currentUser = null;
      updateAuthUI();
      openAuthModal();
    } else {
      alert(res.error || 'Could not add this item.');
    }
  }).catch(() => alert('Could not add this item.'));
}

function loadCart() {
  const cartItems = document.getElementById('cartItems');
  if (!cartItems) return;

  fetch('api.php?action=cart')
    .then(r => r.json())
    .then(res => {
      if (!res.ok) return;
      cartState = res.cart;
      renderCart();
    });
}

function renderCart() {
  const cartItems = document.getElementById('cartItems');
  const cartTotal = document.getElementById('cartTotal');
  const countEls = document.querySelectorAll('.cart-count');
  const checkoutToggle = document.getElementById('checkoutToggle');
  const checkoutForm = document.getElementById('checkoutForm');
  if (!cartItems || !cartTotal) return;

  const isSignedIn = Boolean(currentUser);
  countEls.forEach(el => el.textContent = isSignedIn ? cartState.items.length : 0);
  cartTotal.textContent = formatCurrency(isSignedIn ? cartState.total : 0);
  if (checkoutToggle) checkoutToggle.disabled = cartState.items.length === 0;
  if (checkoutForm && cartState.items.length === 0) checkoutForm.hidden = true;

  if (!isSignedIn) {
    cartItems.innerHTML = '<div class="cart-empty">Sign in to start your bouquet order.</div>';
    return;
  }
  if (cartState.items.length === 0) {
    cartItems.innerHTML = '<div class="cart-empty">Your cart is quiet for now.</div>';
    return;
  }

  cartItems.innerHTML = cartState.items.map(item => `
    <div class="cart-item">
      <div class="ci-info">
        <div class="ci-name">${escapeHtml(item.name)}</div>
        <div class="ci-desc">${escapeHtml(item.description)}</div>
      </div>
      <div>
        <div class="ci-price">${formatCurrency(item.line_total)}</div>
        <button class="cart-remove" onclick="removeCartItem(${item.id})">Remove</button>
      </div>
    </div>
  `).join('');
}

function removeCartItem(id) {
  const fd = new FormData();
  fd.append('action', 'remove_cart_item');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.ok) loadCart();
      else alert(res.error || 'Could not remove this item.');
    });
}

function showCheckoutForm() {
  if (!currentUser) {
    openAuthModal();
    return;
  }
  if (cartState.items.length === 0) {
    showToast('Add a bouquet first.');
    return;
  }
  const form = document.getElementById('checkoutForm');
  if (!form) return;
  form.hidden = false;
  const nameInput = form.elements.customer_name;
  if (nameInput && !nameInput.value) nameInput.value = currentUser.name || '';
}

function submitCheckout(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'checkout');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        alert(res.error || 'Could not place this order.');
        return;
      }
      e.target.reset();
      e.target.hidden = true;
      showToast('Order placed. Payment is pending verification.');
      loadCart();
      loadNotifications();
      closeCart();
    })
    .catch(() => alert('Could not place this order.'));
}
// === HERO CROSSFADE ===
let heroInterval;
function startHero() {
  if (heroInterval) clearInterval(heroInterval);
  const slides = document.querySelectorAll('.hero-slide');
  if (slides.length === 0) return;
  let idx = 0;
  slides[0].classList.add('active');
  document.querySelectorAll('.hero-slide-pager span')[0]?.classList.add('active');
  
  heroInterval = setInterval(() => {
    slides[idx].classList.remove('active');
    document.querySelectorAll('.hero-slide-pager span')[idx]?.classList.remove('active');
    idx = (idx + 1) % slides.length;
    slides[idx].classList.add('active');
    document.querySelectorAll('.hero-slide-pager span')[idx]?.classList.add('active');
  }, 6000);
}
document.addEventListener('DOMContentLoaded', startHero);

// === CUSTOMIZE BUILDER ===
let builderState = {
  step: 1,
  base_size_id: null,
  size: '',
  base_price: 0,
  flowers: {},
  flower_colors: {},
  wrappers: {},
  wrapper_colors: {},
  ribbons: {},
  ribbon_colors: {},
  note: ''
};

let builderFlowersData = [];
let builderWrappersData = [];
let builderRibbonsData = [];
let builderBaseSizesData = [];

function initBuilder(flowerData, wrapperData, ribbonData, baseSizeData = []) {
  builderFlowersData = flowerData;
  builderWrappersData = wrapperData;
  builderRibbonsData = ribbonData;
  builderBaseSizesData = baseSizeData;
  
  if (builderBaseSizesData.length > 0) {
    const selected = document.querySelector('.size-card.selected');
    const defaultId = selected ? Number(selected.dataset.sizeId) : builderBaseSizesData[0].id;
    selectSize(defaultId);
  }

  builderFlowersData.forEach(f => {
    const firstColor = (f.color_options && f.color_options[0]) || { name: 'Default', color: f.color };
    builderState.flower_colors[f.id] = { name: firstColor.name, color: firstColor.color };
  });

  builderWrappersData.forEach(w => {
    const firstColor = getDefaultWrapperColor(w.id);
    builderState.wrapper_colors[w.id] = firstColor;
  });

  builderRibbonsData.forEach(r => {
    const firstColor = getDefaultRibbonColor(r.id);
    builderState.ribbon_colors[r.id] = firstColor;
  });
  
  goToStep(1);
  updatePreview();
}

function getDefaultWrapperColor(wrapperId) {
  const wrapper = builderWrappersData.find(x => Number(x.id) === Number(wrapperId));
  if (!wrapper) return { name: 'Default', color: '#C9A876', image: '' };
  const first = (wrapper.color_options && wrapper.color_options[0]) || { name: 'Default', color: wrapper.color, image: wrapper.image || '' };
  return { name: first.name, color: first.color, image: first.image || '' };
}

function getDefaultRibbonColor(ribbonId) {
  const ribbon = builderRibbonsData.find(x => Number(x.id) === Number(ribbonId));
  if (!ribbon) return { name: 'Default', color: '#A08960', image: '' };
  const first = (ribbon.color_options && ribbon.color_options[0]) || { name: 'Default', color: ribbon.color, image: ribbon.image || '' };
  return { name: first.name, color: first.color, image: first.image || '' };
}

function getDefaultStemColor(flowerId) {
  const flower = builderFlowersData.find(x => Number(x.id) === Number(flowerId));
  if (!flower) return { name: 'Default', color: '#D89B9B' };
  const first = (flower.color_options && flower.color_options[0]) || { name: 'Default', color: flower.color };
  return { name: first.name, color: first.color };
}

function selectStemColor(flowerId, btn) {
  const tile = document.querySelector(`.flower-tile[data-id="${flowerId}"]`);
  if (!tile || !btn) return;
  tile.querySelectorAll('.stem-color-swatch').forEach(s => s.classList.remove('selected'));
  btn.classList.add('selected');
  builderState.flower_colors[flowerId] = {
    name: btn.dataset.name,
    color: btn.dataset.color
  };
  const label = tile.querySelector('.stem-color-label');
  if (label) label.textContent = btn.dataset.name;
  updateStemBloomPreview(flowerId, btn.dataset.color);
  updatePreview();
}

function updateStemBloomPreview(flowerId, color) {
  const tile = document.querySelector(`.flower-tile[data-id="${flowerId}"]`);
  const bloom = tile?.querySelector('.ft-bloom');
  if (!bloom || bloom.dataset.hasImage === '1') return;
  if (!bloom.dataset.originalHtml) bloom.dataset.originalHtml = bloom.innerHTML;
  const baseColor = bloom.dataset.baseColor || color;
  bloom.innerHTML = bloom.dataset.originalHtml
    .replaceAll(baseColor, color)
    .replaceAll(baseColor.toLowerCase(), color)
    .replaceAll(baseColor.toUpperCase(), color);
}

function updateOptionBloomPreview(tile, opt, caption) {
  const bloom = tile?.querySelector('.ft-bloom');
  if (!bloom || !opt) return;
  const name = caption || tile.querySelector('.ft-name')?.textContent || '';
  if (opt.image) {
    bloom.dataset.previewSrc = opt.image;
    bloom.dataset.hasImage = '1';
    bloom.dataset.previewCaption = name;
    bloom.innerHTML = `<img src="${escapeHtml(opt.image)}" alt="${escapeHtml(name)}" data-preview-src="${escapeHtml(opt.image)}" data-preview-caption="${escapeHtml(name)}" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">`;
  } else {
    bloom.dataset.hasImage = '0';
    delete bloom.dataset.previewSrc;
    bloom.dataset.previewCaption = name;
    bloom.innerHTML = `<span class="option-color-fallback" style="background:${escapeHtml(opt.color)}"></span>`;
  }
}

function selectWrapperColor(wrapperId, btn) {
  const tile = document.querySelector(`.wrapper-tile[data-id="${wrapperId}"]`);
  if (!tile || !btn) return;
  tile.querySelectorAll('.stem-color-swatch').forEach(s => s.classList.remove('selected'));
  btn.classList.add('selected');
  const opt = {
    name: btn.dataset.name,
    color: btn.dataset.color,
    image: btn.dataset.image || ''
  };
  builderState.wrapper_colors[wrapperId] = opt;
  const label = tile.querySelector('.stem-color-label');
  if (label) label.textContent = opt.name;
  updateOptionBloomPreview(tile, opt);
  updatePreview();
}

function selectRibbonColor(ribbonId, btn) {
  const tile = document.querySelector(`.ribbon-tile[data-id="${ribbonId}"]`);
  if (!tile || !btn) return;
  tile.querySelectorAll('.stem-color-swatch').forEach(s => s.classList.remove('selected'));
  btn.classList.add('selected');
  const opt = {
    name: btn.dataset.name,
    color: btn.dataset.color,
    image: btn.dataset.image || ''
  };
  builderState.ribbon_colors[ribbonId] = opt;
  const label = tile.querySelector('.stem-color-label');
  if (label) label.textContent = opt.name;
  updateOptionBloomPreview(tile, opt);
  updatePreview();
}

function goToStep(step) {
  if (step >= 3 && !meetsMinimumSpend()) {
    showToast(`Add at least ${formatCurrency(builderState.base_price)} in stems to continue.`);
    step = 2;
  }

  builderState.step = step;
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  document.querySelector(`#step-${step}`).classList.add('active');
  
  document.querySelectorAll('.builder-step').forEach(s => {
    s.classList.remove('active', 'done');
    const sNum = parseInt(s.dataset.step);
    if (sNum === step) s.classList.add('active');
    else if (sNum < step) s.classList.add('done');
  });
}

function selectSize(sizeId) {
  const size = builderBaseSizesData.find(x => Number(x.id) === Number(sizeId));
  if (!size) return;
  builderState.base_size_id = Number(size.id);
  builderState.size = size.name;
  builderState.base_price = Number(size.price || 0);
  document.querySelectorAll('.size-card').forEach(c => c.classList.remove('selected'));
  document.querySelector(`.size-card[data-size-id="${size.id}"]`)?.classList.add('selected');
  updatePreview();
}

function changeQty(flowerId, delta) {
  const tile = document.querySelector(`.flower-tile[data-id="${flowerId}"]`);
  if (!builderState.flowers[flowerId]) builderState.flowers[flowerId] = 0;
  const prevQty = builderState.flowers[flowerId];
  builderState.flowers[flowerId] = Math.max(0, builderState.flowers[flowerId] + delta);
  if (builderState.flowers[flowerId] === 0) {
    delete builderState.flowers[flowerId];
    delete builderState.flower_colors[flowerId];
  } else if (prevQty === 0 && !builderState.flower_colors[flowerId]) {
    const selectedSwatch = tile?.querySelector('.stem-color-swatch.selected');
    builderState.flower_colors[flowerId] = selectedSwatch
      ? { name: selectedSwatch.dataset.name, color: selectedSwatch.dataset.color }
      : getDefaultStemColor(flowerId);
  }
  
  const valEl = tile.querySelector('.qty-val');
  if (builderState.flowers[flowerId]) {
    tile.classList.add('has-qty');
    valEl.textContent = builderState.flowers[flowerId];
  } else {
    tile.classList.remove('has-qty');
    valEl.textContent = '0';
  }
  updatePreview();
}

function updateOptionTileQty(stateKey, tileSelector, id, delta) {
  if (!builderState[stateKey][id]) builderState[stateKey][id] = 0;
  builderState[stateKey][id] = Math.max(0, builderState[stateKey][id] + delta);
  if (builderState[stateKey][id] === 0) delete builderState[stateKey][id];

  const tile = document.querySelector(`${tileSelector}[data-id="${id}"]`);
  if (!tile) return;
  const valEl = tile.querySelector('.qty-val');
  if (builderState[stateKey][id]) {
    tile.classList.add('has-qty');
    valEl.textContent = builderState[stateKey][id];
  } else {
    tile.classList.remove('has-qty');
    valEl.textContent = '0';
  }
  updatePreview();
}

function changeWrapperQty(wrapperId, delta) {
  const tile = document.querySelector(`.wrapper-tile[data-id="${wrapperId}"]`);
  if (!builderState.wrappers[wrapperId]) builderState.wrappers[wrapperId] = 0;
  const prevQty = builderState.wrappers[wrapperId];
  builderState.wrappers[wrapperId] = Math.max(0, builderState.wrappers[wrapperId] + delta);
  if (builderState.wrappers[wrapperId] === 0) {
    delete builderState.wrappers[wrapperId];
    delete builderState.wrapper_colors[wrapperId];
  } else if (prevQty === 0) {
    const selectedSwatch = tile?.querySelector('.stem-color-swatch.selected');
    builderState.wrapper_colors[wrapperId] = selectedSwatch
      ? { name: selectedSwatch.dataset.name, color: selectedSwatch.dataset.color, image: selectedSwatch.dataset.image || '' }
      : getDefaultWrapperColor(wrapperId);
  }

  const valEl = tile?.querySelector('.qty-val');
  if (builderState.wrappers[wrapperId]) {
    tile.classList.add('has-qty');
    valEl.textContent = builderState.wrappers[wrapperId];
  } else {
    tile.classList.remove('has-qty');
    valEl.textContent = '0';
  }
  updatePreview();
}

function changeRibbonQty(ribbonId, delta) {
  const tile = document.querySelector(`.ribbon-tile[data-id="${ribbonId}"]`);
  if (!builderState.ribbons[ribbonId]) builderState.ribbons[ribbonId] = 0;
  const prevQty = builderState.ribbons[ribbonId];
  builderState.ribbons[ribbonId] = Math.max(0, builderState.ribbons[ribbonId] + delta);
  if (builderState.ribbons[ribbonId] === 0) {
    delete builderState.ribbons[ribbonId];
    delete builderState.ribbon_colors[ribbonId];
  } else if (prevQty === 0) {
    const selectedSwatch = tile?.querySelector('.stem-color-swatch.selected');
    builderState.ribbon_colors[ribbonId] = selectedSwatch
      ? { name: selectedSwatch.dataset.name, color: selectedSwatch.dataset.color, image: selectedSwatch.dataset.image || '' }
      : getDefaultRibbonColor(ribbonId);
  }

  const valEl = tile?.querySelector('.qty-val');
  if (builderState.ribbons[ribbonId]) {
    tile.classList.add('has-qty');
    valEl.textContent = builderState.ribbons[ribbonId];
  } else {
    tile.classList.remove('has-qty');
    valEl.textContent = '0';
  }
  updatePreview();
}

function updateNote() {
  builderState.note = document.getElementById('note-area').value;
}

function calculateFlowerTotal() {
  let total = 0;
  Object.entries(builderState.flowers).forEach(([id, qty]) => {
    const f = builderFlowersData.find(x => x.id == id);
    if (f) total += f.price * qty;
  });
  return total;
}

function meetsMinimumSpend() {
  return calculateFlowerTotal() >= Number(builderState.base_price || 0);
}

function calculatePrice() {
  return calculateFlowerTotal();
}

function updatePreview() {
  const flowerTotal = calculateFlowerTotal();
  const minimum = Number(builderState.base_price || 0);
  const price = flowerTotal;
  const priceEl = document.getElementById('preview-price');
  if (priceEl) {
    priceEl.textContent = formatCurrency(price);
    priceEl.style.transform = 'scale(1.1)';
    setTimeout(() => priceEl.style.transform = 'scale(1)', 200);
  }

  const minimumNote = document.getElementById('stem-minimum-note');
  if (minimumNote) {
    const remaining = Math.max(0, minimum - flowerTotal);
    if (remaining > 0) {
      minimumNote.innerHTML = `<strong>${escapeHtml(builderState.size)}</strong> requires at least ${formatCurrency(minimum)} in stems. Add ${formatCurrency(remaining)} more to continue.`;
      minimumNote.className = 'stem-minimum-note below-min';
    } else {
      minimumNote.innerHTML = `<strong>${escapeHtml(builderState.size)}</strong> minimum reached (${formatCurrency(minimum)}). You can continue to wrapper.`;
      minimumNote.className = 'stem-minimum-note met-min';
    }
  }

  const flowersNextBtn = document.getElementById('flowers-next-btn');
  if (flowersNextBtn) {
    flowersNextBtn.disabled = !meetsMinimumSpend();
    flowersNextBtn.classList.toggle('is-disabled', !meetsMinimumSpend());
  }
  
  const reviewEl = document.getElementById('review-items');
  if (reviewEl) {
    let html = `<div class="review-row"><span class="label">Base Size</span><span class="val">${escapeHtml(builderState.size)} (${formatCurrency(minimum)} min)</span></div>`;
    Object.entries(builderState.flowers).forEach(([id, qty]) => {
      const f = builderFlowersData.find(x => x.id == id);
      const colorName = builderState.flower_colors[id]?.name;
      if (f) {
        const label = `${f.name} ×${qty}${colorName ? ` (${colorName})` : ''}`;
        html += `<div class="review-row"><span class="label">${label}</span><span class="val">${formatCurrency(f.price * qty)}</span></div>`;
      }
    });
    Object.entries(builderState.wrappers).forEach(([id, qty]) => {
      const w = builderWrappersData.find(x => x.id == id);
      const colorName = builderState.wrapper_colors[id]?.name;
      if (w) html += `<div class="review-row"><span class="label">${escapeHtml(w.name)} wrap ×${qty}${colorName ? ` (${escapeHtml(colorName)})` : ''}</span><span class="val">Included</span></div>`;
    });
    Object.entries(builderState.ribbons).forEach(([id, qty]) => {
      const r = builderRibbonsData.find(x => x.id == id);
      const colorName = builderState.ribbon_colors[id]?.name;
      if (r) html += `<div class="review-row"><span class="label">${escapeHtml(r.name)} ribbon ×${qty}${colorName ? ` (${escapeHtml(colorName)})` : ''}</span><span class="val">Included</span></div>`;
    });
    html += `<div class="review-row total"><span>Total</span><span>${formatCurrency(price)}</span></div>`;
    reviewEl.innerHTML = html;
  }
}

function addCustomToCart() {
  if (!meetsMinimumSpend()) {
    showToast(`Add at least ${formatCurrency(builderState.base_price)} in stems before adding to cart.`);
    goToStep(2);
    return;
  }

  const payload = {
    ...builderState,
    flower_colors: Object.fromEntries(
      Object.keys(builderState.flowers).map(id => [id, builderState.flower_colors[id] || getDefaultStemColor(id)])
    ),
    wrapper_colors: Object.fromEntries(
      Object.keys(builderState.wrappers).map(id => [id, builderState.wrapper_colors[id] || getDefaultWrapperColor(id)])
    ),
    ribbon_colors: Object.fromEntries(
      Object.keys(builderState.ribbons).map(id => [id, builderState.ribbon_colors[id] || getDefaultRibbonColor(id)])
    )
  };
  const data = JSON.stringify(payload);
  addToCart('custom', data);
  builderState.flowers = {};
  builderState.flower_colors = {};
  builderState.wrappers = {};
  builderState.wrapper_colors = {};
  builderState.ribbons = {};
  builderState.ribbon_colors = {};
  document.querySelectorAll('.flower-tile:not(.wrapper-tile):not(.ribbon-tile)').forEach(t => {
    t.classList.remove('has-qty');
    t.querySelector('.qty-val').textContent = '0';
    const flowerId = Number(t.dataset.id);
    const defaultColor = getDefaultStemColor(flowerId);
    builderState.flower_colors[flowerId] = defaultColor;
    t.querySelectorAll('.stem-color-swatch').forEach((s, i) => s.classList.toggle('selected', i === 0));
    const label = t.querySelector('.stem-color-label');
    if (label) label.textContent = defaultColor.name;
  });
  document.querySelectorAll('.wrapper-tile, .ribbon-tile').forEach(t => {
    t.classList.remove('has-qty');
    t.querySelector('.qty-val').textContent = '0';
    const id = Number(t.dataset.id);
    const isWrapper = t.classList.contains('wrapper-tile');
    const defaultColor = isWrapper ? getDefaultWrapperColor(id) : getDefaultRibbonColor(id);
    if (isWrapper) builderState.wrapper_colors[id] = defaultColor;
    else builderState.ribbon_colors[id] = defaultColor;
    t.querySelectorAll('.stem-color-swatch').forEach((s, i) => s.classList.toggle('selected', i === 0));
    const label = t.querySelector('.stem-color-label');
    if (label) label.textContent = defaultColor.name;
    updateOptionBloomPreview(t, defaultColor);
  });
  updatePreview();
  goToStep(1);
}

// === ADMIN MODALS ===
function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  }[char]));
}

function getAdminData(name) {
  return Array.isArray(window[name]) ? window[name] : [];
}

function handleAdminSave(response) {
  return response.json().then(res => {
    if (res.ok) {
      location.reload();
      return;
    }
    if (res.admin_login_required) {
      location.href = '?page=admin';
      return;
    }
    alert(res.error || 'Could not save this change.');
  });
}

function adminLogin(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'admin_login');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        alert(res.error || 'Could not sign in.');
        return;
      }
      location.href = '?page=admin';
    })
    .catch(() => alert('Could not sign in.'));
}

function adminLogout() {
  const fd = new FormData();
  fd.append('action', 'admin_logout');
  fetch('api.php', { method: 'POST', body: fd })
    .then(() => {
      location.href = '?page=admin';
    })
    .catch(() => alert('Could not log out.'));
}

function openAdminPasswordModal() {
  openModal(`
    <h3>Change admin password</h3>
    <form onsubmit="changeAdminPassword(event)">
      <div class="form-group"><label>Current Password</label><input type="password" name="current_password" autocomplete="current-password" required></div>
      <div class="form-group"><label>New Password</label><input type="password" name="password" minlength="8" autocomplete="new-password" required></div>
      <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirm" minlength="8" autocomplete="new-password" required></div>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Password</button>
      </div>
    </form>
  `);
}

function changeAdminPassword(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'admin_change_password');
  fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) {
        if (res.admin_login_required) {
          location.href = '?page=admin';
          return;
        }
        alert(res.error || 'Could not change password.');
        return;
      }
      document.getElementById('modalOverlay').classList.remove('show');
      alert('Admin password updated.');
    })
    .catch(() => alert('Could not change password.'));
}

function openModal(html) {
  const overlay = document.getElementById('modalOverlay');
  document.getElementById('modalContent').innerHTML = html;
  overlay.classList.add('show');
  bindAdminColorFields(document.getElementById('modalContent'));
}
function closeModal(e) {
  if (e.target.id === 'modalOverlay') {
    document.getElementById('modalOverlay').classList.remove('show');
  }
}

function normalizeHexColor(value, fallback = '#D89B9B') {
  const raw = String(value || '').trim();
  const match = raw.match(/^#?([0-9A-Fa-f]{6})$/);
  if (!match) return fallback;
  return `#${match[1].toUpperCase()}`;
}

function hexToRgb(hex) {
  const value = normalizeHexColor(hex);
  return {
    r: parseInt(value.slice(1, 3), 16),
    g: parseInt(value.slice(3, 5), 16),
    b: parseInt(value.slice(5, 7), 16)
  };
}

function rgbToHsl(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const delta = max - min;
  let h = 0;
  let s = 0;
  const l = (max + min) / 2;

  if (delta !== 0) {
    s = delta / (1 - Math.abs(2 * l - 1));
    switch (max) {
      case r: h = ((g - b) / delta) % 6; break;
      case g: h = (b - r) / delta + 2; break;
      default: h = (r - g) / delta + 4; break;
    }
    h *= 60;
    if (h < 0) h += 360;
  }

  return { h, s: s * 100, l: l * 100 };
}

function hslToHex(h, s, l) {
  s /= 100;
  l /= 100;
  const c = (1 - Math.abs(2 * l - 1)) * s;
  const x = c * (1 - Math.abs((h / 60) % 2 - 1));
  const m = l - c / 2;
  let r = 0; let g = 0; let b = 0;

  if (h < 60) [r, g, b] = [c, x, 0];
  else if (h < 120) [r, g, b] = [x, c, 0];
  else if (h < 180) [r, g, b] = [0, c, x];
  else if (h < 240) [r, g, b] = [0, x, c];
  else if (h < 300) [r, g, b] = [x, 0, c];
  else [r, g, b] = [c, 0, x];

  const toHex = v => Math.round((v + m) * 255).toString(16).padStart(2, '0');
  return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
}

function renderSimpleColorPicker(value, options = {}) {
  const hex = normalizeHexColor(value);
  const hsl = rgbToHsl(...Object.values(hexToRgb(hex)));
  const nameAttr = options.name ? `name="${escapeHtml(options.name)}"` : '';
  const inputClass = options.inputClass || 'admin-color-hex';
  const required = options.required ? 'required' : '';
  return `
    <div class="admin-color-field simple-color-picker" data-color-field data-h="${Math.round(hsl.h)}" data-s="${Math.round(hsl.s)}" data-l="${Math.round(hsl.l)}">
      <div class="simple-color-layout">
        <div class="simple-color-area-wrap">
          <canvas class="simple-color-area" width="220" height="120"></canvas>
          <span class="simple-color-marker"></span>
        </div>
      </div>
      <input type="range" class="admin-hue-slider simple-hue-slider" min="0" max="360" value="${Math.round(hsl.h)}">
      <div class="admin-color-top">
        <input type="text" ${nameAttr} class="${inputClass} admin-color-hex" value="${escapeHtml(hex)}" maxlength="7" placeholder="#D89B9B" ${required} oninput="syncSimpleColorFromHex(this)" onblur="syncSimpleColorFromHex(this, true)">
        <span class="admin-color-preview" data-color="${escapeHtml(hex)}" style="background:${escapeHtml(hex)}"></span>
      </div>
    </div>
  `;
}

function renderAdminColorField(name, value, required = false) {
  return renderSimpleColorPicker(value, { name, required });
}

function renderAdminColorPicker(value) {
  return renderSimpleColorPicker(value, { inputClass: 'color-option-hex' });
}

function paintSimpleColorArea(canvas, hue) {
  const ctx = canvas.getContext('2d');
  const w = canvas.width;
  const h = canvas.height;
  ctx.clearRect(0, 0, w, h);
  ctx.fillStyle = hslToHex(hue, 100, 50);
  ctx.fillRect(0, 0, w, h);
  let grad = ctx.createLinearGradient(0, 0, w, 0);
  grad.addColorStop(0, '#fff');
  grad.addColorStop(1, 'rgba(255,255,255,0)');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, w, h);
  grad = ctx.createLinearGradient(0, 0, 0, h);
  grad.addColorStop(0, 'rgba(0,0,0,0)');
  grad.addColorStop(1, '#000');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, w, h);
}

function updateSimpleColorMarker(field) {
  const canvas = field.querySelector('.simple-color-area');
  const marker = field.querySelector('.simple-color-marker');
  if (!canvas || !marker) return;
  const s = Number(field.dataset.s || 0);
  const l = Number(field.dataset.l || 50);
  const rect = canvas.getBoundingClientRect();
  const scaleX = rect.width / canvas.width;
  const scaleY = rect.height / canvas.height;
  marker.style.left = `${(s / 100) * canvas.width * scaleX}px`;
  marker.style.top = `${(1 - l / 100) * canvas.height * scaleY}px`;
}

function setSimpleColorField(field, h, s, l, syncHex = true) {
  field.dataset.h = h;
  field.dataset.s = s;
  field.dataset.l = l;
  const canvas = field.querySelector('.simple-color-area');
  const hueSlider = field.querySelector('.simple-hue-slider');
  const hexInput = field.querySelector('.admin-color-hex');
  const preview = field.querySelector('.admin-color-preview');
  if (hueSlider) hueSlider.value = Math.round(h);
  if (canvas) paintSimpleColorArea(canvas, h);
  updateSimpleColorMarker(field);
  if (syncHex && hexInput) {
    const hex = hslToHex(h, s, l);
    hexInput.value = hex;
    if (preview) {
      preview.style.background = hex;
      preview.dataset.color = hex;
    }
  }
}

function syncSimpleColorFromHex(input, normalize = false) {
  const field = input.closest('[data-color-field]');
  if (!field) return;
  const preview = field.querySelector('.admin-color-preview');
  const fallback = normalizeHexColor(preview?.dataset.color || '#D89B9B');
  const raw = input.value.trim();
  if (normalize || /^#?[0-9A-Fa-f]{6}$/i.test(raw)) {
    const hex = normalizeHexColor(raw, fallback);
    const hsl = rgbToHsl(...Object.values(hexToRgb(hex)));
    if (normalize) input.value = hex;
    setSimpleColorField(field, hsl.h, hsl.s, hsl.l, false);
    if (preview) {
      preview.style.background = hex;
      preview.dataset.color = hex;
    }
  }
}

function bindSimpleColorPickers(root = document) {
  root.querySelectorAll('.simple-color-picker').forEach(field => {
    if (field.dataset.bound === '1') return;
    field.dataset.bound = '1';
    const canvas = field.querySelector('.simple-color-area');
    const hueSlider = field.querySelector('.simple-hue-slider');
    const hexInput = field.querySelector('.admin-color-hex');
    if (!canvas || !hueSlider || !hexInput) return;

    const initHex = normalizeHexColor(hexInput.value || '#D89B9B');
    const initHsl = rgbToHsl(...Object.values(hexToRgb(initHex)));
    setSimpleColorField(field, initHsl.h, initHsl.s, initHsl.l);

    hueSlider.addEventListener('input', () => {
      setSimpleColorField(field, Number(hueSlider.value), Number(field.dataset.s), Number(field.dataset.l));
    });

    const pickAt = (clientX, clientY) => {
      const rect = canvas.getBoundingClientRect();
      const x = Math.max(0, Math.min(rect.width, clientX - rect.left));
      const y = Math.max(0, Math.min(rect.height, clientY - rect.top));
      const s = (x / rect.width) * 100;
      const l = (1 - y / rect.height) * 100;
      setSimpleColorField(field, Number(hueSlider.value), s, l);
    };

    const startDrag = e => {
      field.dataset.dragging = '1';
      pickAt(e.clientX, e.clientY);
    };
    const moveDrag = e => {
      if (field.dataset.dragging === '1') pickAt(e.clientX, e.clientY);
    };
    const endDrag = () => { field.dataset.dragging = '0'; };

    canvas.addEventListener('mousedown', startDrag);
    window.addEventListener('mousemove', moveDrag);
    window.addEventListener('mouseup', endDrag);
    canvas.addEventListener('touchstart', e => {
      if (e.touches[0]) startDrag(e.touches[0]);
    }, { passive: true });
    window.addEventListener('touchmove', e => {
      if (field.dataset.dragging === '1' && e.touches[0]) pickAt(e.touches[0].clientX, e.touches[0].clientY);
    }, { passive: true });
    window.addEventListener('touchend', endDrag);
  });
}

function bindAdminColorFields(root = document) {
  bindSimpleColorPickers(root);
  root.querySelectorAll('.admin-color-hex').forEach(input => syncSimpleColorFromHex(input, true));
}

function parseAdminItemColorOptions(item) {
  let opts = item?.color_options;
  if (typeof opts === 'string') {
    try { opts = JSON.parse(opts); } catch { opts = []; }
  }
  if (!Array.isArray(opts) || !opts.length) {
    return [{ name: 'Default', color: item?.color || '#888888', image: item?.image || '' }];
  }
  return opts.map(opt => ({
    name: opt.name || 'Default',
    color: opt.color || item?.color || '#888888',
    image: opt.image || ''
  }));
}

function renderItemColorOptionRows(options = [], filePrefix = 'color_image_') {
  return options.map((opt, index) => renderItemColorOptionRow(opt, index, filePrefix)).join('');
}

function renderItemColorOptionRow(opt = {}, index = 0, filePrefix = 'color_image_') {
  const image = opt.image || '';
  return `
    <div class="item-color-option-row" data-index="${index}">
      <input type="text" class="item-color-name" placeholder="Color name" value="${escapeHtml(opt.name || '')}">
      ${renderSimpleColorPicker(opt.color || '#888888', { inputClass: 'item-color-hex' })}
      <div class="item-color-image">
        <label class="item-color-upload-label">Photo</label>
        <input type="file" name="${filePrefix}${index}" accept="image/*">
        ${image ? `<img src="${escapeHtml(image)}" alt="">` : ''}
        <input type="hidden" class="item-color-existing-image" value="${escapeHtml(image)}">
      </div>
      <button type="button" class="btn-admin-danger" onclick="this.closest('.item-color-option-row').remove()">Remove</button>
    </div>
  `;
}

function addItemColorOptionRow(listId, name = '', color = '#888888', image = '') {
  const list = document.getElementById(listId);
  if (!list) return;
  const index = list.querySelectorAll('.item-color-option-row').length;
  const row = document.createElement('div');
  row.innerHTML = renderItemColorOptionRow({ name, color, image }, index);
  const node = row.firstElementChild;
  list.appendChild(node);
  bindAdminColorFields(node);
}

function collectItemColorOptions(listId) {
  const list = document.getElementById(listId);
  if (!list) return [];
  return Array.from(list.querySelectorAll('.item-color-option-row')).map(row => ({
    name: row.querySelector('.item-color-name')?.value.trim() || '',
    color: normalizeHexColor(row.querySelector('.item-color-hex')?.value || ''),
    image: row.querySelector('.item-color-existing-image')?.value || ''
  })).filter(opt => opt.name && opt.color);
}

function appendItemColorFiles(listId, fd) {
  const list = document.getElementById(listId);
  if (!list) return;
  Array.from(list.querySelectorAll('.item-color-option-row')).forEach((row, i) => {
    const fileInput = row.querySelector('input[type="file"]');
    if (fileInput?.files?.[0]) fd.set(`color_image_${i}`, fileInput.files[0]);
  });
}

function openFlowerModal(id = '') {
  const f = id ? getAdminData('ADMIN_FLOWERS_DATA').find(x => x.id == id) : {name:'', price_per_stem:'', color:'#D89B9B', shape:'rose', category:'romantic', stock_count:50, in_builder:1, in_stock:1, best_seller:0, image:'', color_options:[{name:'Default', color:'#D89B9B'}]};
  const formId = id ? Number(id) : '';
  const colorOptions = Array.isArray(f.color_options) && f.color_options.length
    ? f.color_options
    : [{ name: 'Default', color: f.color || '#D89B9B' }];
  openModal(`
    <h3>${id ? 'Edit' : 'Add'} Stem</h3>
    <form onsubmit="saveFlower(event, '${formId}')">
      <div class="form-group"><label>Name</label><input type="text" name="name" value="${escapeHtml(f.name)}" required></div>
      <div class="form-row">
        <div class="form-group"><label>Price per Stem</label><input type="number" step="0.01" name="price" value="${escapeHtml(f.price_per_stem)}" required></div>
        <div class="form-group"><label>Stock Count</label><input type="number" name="stock" value="${f.stock_count || 0}" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Default Color</label>${renderAdminColorField('color', f.color, true)}</div>
        <div class="form-group"><label>Shape</label>
          <select name="shape">
            ${['rose','peony','tulip','ranunculus','eucalyptus','lavender','anemone','dusty','dahlia'].map(s => `<option value="${s}" ${f.shape===s?'selected':''}>${s}</option>`).join('')}
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Builder Color Options</label>
        <div class="admin-section-sub" style="margin-bottom:10px">Customers can pick from these colors when customizing.</div>
        <div id="color-options-list">${renderColorOptionRows(colorOptions)}</div>
        <button type="button" class="btn-admin-ghost" style="margin-top:10px" onclick="addColorOptionRow()">+ Add color</button>
      </div>
      <div class="form-group"><label>Category</label>
        <select name="category">
          ${['romantic','modern','wildflower','greenery'].map(c => `<option value="${c}" ${f.category===c?'selected':''}>${c}</option>`).join('')}
        </select>
      </div>
      <div class="form-group"><label>Image Upload</label><input type="file" name="image" accept="image/*"></div>
      ${f.image ? `<img src="${escapeHtml(f.image)}" width="100" style="margin-bottom:10px;border-radius:4px;">` : ''}
      <input type="hidden" name="existing_image" value="${escapeHtml(f.image)}">
      <div class="form-check"><input type="checkbox" name="in_builder" ${f.in_builder?'checked':''}><label>Show in Builder</label></div>
      <div class="form-check"><input type="checkbox" name="in_stock" ${f.in_stock?'checked':''}><label>In Stock</label></div>
      <div class="form-check"><input type="checkbox" name="best_seller" ${f.best_seller?'checked':''}><label>Best Seller</label></div>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Stem</button>
      </div>
    </form>
  `);
}

function renderColorOptionRows(options = []) {
  return options.map(opt => `
    <div class="color-option-row">
      <input type="text" class="color-option-name" placeholder="Color name" value="${escapeHtml(opt.name || '')}">
      ${renderAdminColorPicker(opt.color || '#D89B9B')}
      <button type="button" class="btn-admin-danger" onclick="this.closest('.color-option-row').remove()">Remove</button>
    </div>
  `).join('');
}

function addColorOptionRow(name = '', color = '#D89B9B') {
  const list = document.getElementById('color-options-list');
  if (!list) return;
  const row = document.createElement('div');
  row.className = 'color-option-row';
  row.innerHTML = `
    <input type="text" class="color-option-name" placeholder="Color name" value="${escapeHtml(name)}">
    ${renderAdminColorPicker(color)}
    <button type="button" class="btn-admin-danger" onclick="this.closest('.color-option-row').remove()">Remove</button>
  `;
  list.appendChild(row);
  bindAdminColorFields(row);
}

function collectColorOptions() {
  return Array.from(document.querySelectorAll('.color-option-row')).map(row => ({
    name: row.querySelector('.color-option-name')?.value.trim() || '',
    color: normalizeHexColor(row.querySelector('.color-option-hex')?.value || '')
  })).filter(opt => opt.name && opt.color);
}

function openStemColorsModal(id) {
  const f = getAdminData('ADMIN_FLOWERS_DATA').find(x => x.id == id);
  if (!f) return;
  const colorOptions = Array.isArray(f.color_options) && f.color_options.length
    ? f.color_options
    : [{ name: 'Default', color: f.color || '#D89B9B' }];
  openModal(`
    <h3>Edit Colors: ${escapeHtml(f.name)}</h3>
    <form onsubmit="saveStemColors(event, ${Number(id)})">
      <div class="admin-section-sub" style="margin-bottom:12px">These colors appear in the customize builder for this stem.</div>
      <div id="color-options-list">${renderColorOptionRows(colorOptions)}</div>
      <button type="button" class="btn-admin-ghost" style="margin-top:10px" onclick="addColorOptionRow()">+ Add color</button>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Colors</button>
      </div>
    </form>
  `);
}

function saveStemColors(e, id) {
  e.preventDefault();
  const options = collectColorOptions();
  if (!options.length) {
    alert('Add at least one color option.');
    return;
  }
  const fd = new FormData();
  fd.append('action', 'admin_flower_colors_save');
  fd.append('id', id);
  fd.append('color_options', JSON.stringify(options));
  fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
    if (res.ok) location.reload();
    else alert(res.error || 'Could not save colors.');
  }).catch(() => alert('Could not save colors.'));
}

function saveFlower(e, id) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'admin_flower_save');
  fd.append('color_options', JSON.stringify(collectColorOptions()));
  if (id) fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
    if (res.ok) location.reload();
    else alert(res.error || 'Could not save this stem.');
  }).catch(() => alert('Could not save this stem.'));
}

function deleteFlower(id) {
  if (!confirm('Remove this stem permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'admin_flower_delete');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not delete this stem.'));
}

function toggleBuilder(id) {
  const fd = new FormData();
  fd.append('action', 'admin_toggle_builder');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not update this stem.'));
}

function openBaseSizeModal(id = '') {
  const s = id ? getAdminData('ADMIN_BASE_SIZES_DATA').find(x => x.id == id) : {name:'', description:'', price:'', icon_size:60, active:1};
  const formId = id ? Number(id) : '';
  openModal(`
    <h3>${id ? 'Edit' : 'Add'} Base Size</h3>
    <form onsubmit="saveBaseSize(event, '${formId}')">
      <div class="form-group"><label>Name</label><input type="text" name="name" value="${escapeHtml(s.name)}" required></div>
      <div class="form-group"><label>Description</label><input type="text" name="description" value="${escapeHtml(s.description)}" required></div>
      <div class="form-row">
        <div class="form-group"><label>Minimum Stem Spend</label><input type="number" step="0.01" min="0" name="price" value="${escapeHtml(s.price)}" required></div>
        <div class="form-group"><label>Icon Size</label><input type="number" min="30" max="120" name="icon_size" value="${escapeHtml(s.icon_size || 60)}" required></div>
      </div>
      <div class="form-check"><input type="checkbox" name="active" ${Number(s.active) ? 'checked' : ''}><label>Show in Builder</label></div>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Base Size</button>
      </div>
    </form>
  `);
}

function saveBaseSize(e, id) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'admin_base_size_save');
  if (id) fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not save this base size.'));
}

function deleteBaseSize(id) {
  if (!confirm('Remove this base size permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'admin_base_size_delete');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not delete this base size.'));
}

// Bouquet Modals
function openBouquetModal(id = '') {
  const b = id ? getAdminData('ADMIN_BOUQUETS_DATA').find(x => x.id == id) : {name:'', description:'', price:'', occasion:'romantic', color_theme:'ivory', price_range:'200-500', components:'{}', featured:0, image:''};
  const formId = id ? Number(id) : '';
  openModal(`
    <h3>${id ? 'Edit' : 'Add'} Bouquet</h3>
    <form onsubmit="saveBouquet(event, '${formId}')">
      <div class="form-group"><label>Name</label><input type="text" name="name" value="${escapeHtml(b.name)}" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" required>${escapeHtml(b.description)}</textarea></div>
      <div class="form-row">
        <div class="form-group"><label>Price</label><input type="number" step="0.01" min="200" max="3000" name="price" value="${escapeHtml(b.price)}" required></div>
        <div class="form-group"><label>Occasion</label>
          <select name="occasion">
            ${['romantic','modern','wildflower'].map(c => `<option value="${c}" ${b.occasion===c?'selected':''}>${c}</option>`).join('')}
          </select>
        </div>
      </div>
      <input type="hidden" name="color_theme" value="${escapeHtml(b.color_theme || 'ivory')}">
      <input type="hidden" name="price_range" value="${escapeHtml(b.price_range || '200-500')}">
      <div class="form-group"><label>Image Upload</label><input type="file" name="image" accept="image/*"></div>
      ${b.image ? `<img src="${escapeHtml(b.image)}" width="100" style="margin-bottom:10px;border-radius:4px;">` : ''}
      <input type="hidden" name="existing_image" value="${escapeHtml(b.image)}">
      <div class="form-group"><label>Components (JSON format, e.g., {"1":2,"3":1})</label><input type="text" name="components" value="${escapeHtml(b.components)}" required></div>
      <div class="form-check"><input type="checkbox" name="featured" ${b.featured ? 'checked' : ''}><label>Featured</label></div>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Bouquet</button>
      </div>
    </form>
  `);
}

function saveBouquet(e, id) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'admin_bouquet_save');
  if (id) fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not save this bouquet.'));
}

function deleteBouquet(id) {
  if (!confirm('Remove this bouquet permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'admin_bouquet_delete');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not delete this bouquet.'));
}

function openWrapperModal(id = '') {
  const w = id ? getAdminData('ADMIN_WRAPPERS_DATA').find(x => x.id == id) : {name:'', color:'#C9A876', style:'paper', in_stock:1, image:'', color_options:[]};
  const formId = id ? Number(id) : '';
  const colorOptions = parseAdminItemColorOptions(w);
  openModal(`
    <div class="modal-wide-wrap">
    <h3>${id ? 'Edit' : 'Add'} Wrapper</h3>
    <form onsubmit="saveWrapper(event, '${formId}')">
      <div class="form-group"><label>Name</label><input type="text" name="name" value="${escapeHtml(w.name)}" required></div>
      <div class="form-group"><label>Style</label><input type="text" name="style" value="${escapeHtml(w.style || 'paper')}" required></div>
      <div class="form-group">
        <label>Wrap Colors</label>
        <div class="admin-section-sub" style="margin-bottom:10px">Add each color option with its own photo. Customers pick from these when customizing.</div>
        <div id="wrapper-color-options-list">${renderItemColorOptionRows(colorOptions)}</div>
        <button type="button" class="btn-admin-ghost" style="margin-top:10px" onclick="addItemColorOptionRow('wrapper-color-options-list')">+ Add color</button>
      </div>
      <div class="form-check"><input type="checkbox" name="in_stock" ${w.in_stock ? 'checked' : ''}><label>In Stock</label></div>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Wrapper</button>
      </div>
    </form>
    </div>
  `);
}

function saveWrapper(e, id) {
  e.preventDefault();
  const options = collectItemColorOptions('wrapper-color-options-list');
  if (!options.length) {
    alert('Add at least one color option.');
    return;
  }
  const fd = new FormData(e.target);
  fd.append('action', 'admin_wrapper_save');
  fd.append('color_options', JSON.stringify(options));
  appendItemColorFiles('wrapper-color-options-list', fd);
  if (id) fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
    if (res.ok) location.reload();
    else alert(res.error || 'Could not save this wrapper.');
  }).catch(() => alert('Could not save this wrapper.'));
}

function deleteWrapper(id) {
  if (!confirm('Remove this wrapper permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'admin_wrapper_delete');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not delete this wrapper.'));
}

function openRibbonModal(id = '') {
  const r = id ? getAdminData('ADMIN_RIBBONS_DATA').find(x => x.id == id) : {name:'', color:'#A08960', in_stock:1, image:'', color_options:[]};
  const formId = id ? Number(id) : '';
  const colorOptions = parseAdminItemColorOptions(r);
  openModal(`
    <div class="modal-wide-wrap">
    <h3>${id ? 'Edit' : 'Add'} Ribbon</h3>
    <form onsubmit="saveRibbon(event, '${formId}')">
      <div class="form-group"><label>Name</label><input type="text" name="name" value="${escapeHtml(r.name)}" required></div>
      <div class="form-group">
        <label>Ribbon Colors</label>
        <div class="admin-section-sub" style="margin-bottom:10px">Add each color option with its own photo. Customers pick from these when customizing.</div>
        <div id="ribbon-color-options-list">${renderItemColorOptionRows(colorOptions)}</div>
        <button type="button" class="btn-admin-ghost" style="margin-top:10px" onclick="addItemColorOptionRow('ribbon-color-options-list')">+ Add color</button>
      </div>
      <div class="form-check"><input type="checkbox" name="in_stock" ${r.in_stock ? 'checked' : ''}><label>In Stock</label></div>
      <div class="modal-actions">
        <button type="button" class="btn-admin-ghost" onclick="document.getElementById('modalOverlay').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn-admin">Save Ribbon</button>
      </div>
    </form>
    </div>
  `);
}

function saveRibbon(e, id) {
  e.preventDefault();
  const options = collectItemColorOptions('ribbon-color-options-list');
  if (!options.length) {
    alert('Add at least one color option.');
    return;
  }
  const fd = new FormData(e.target);
  fd.append('action', 'admin_ribbon_save');
  fd.append('color_options', JSON.stringify(options));
  appendItemColorFiles('ribbon-color-options-list', fd);
  if (id) fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
    if (res.ok) location.reload();
    else alert(res.error || 'Could not save this ribbon.');
  }).catch(() => alert('Could not save this ribbon.'));
}

function deleteRibbon(id) {
  if (!confirm('Remove this ribbon permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'admin_ribbon_delete');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not delete this ribbon.'));
}

function updateOrderStatus(id, status) {
  const fd = new FormData();
  fd.append('action', 'admin_order_status');
  fd.append('id', id);
  fd.append('status', status);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not update this order.'));
}

function updatePaymentStatus(id, status) {
  const fd = new FormData();
  fd.append('action', 'admin_payment_status');
  fd.append('id', id);
  fd.append('status', status);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not update this payment.'));
}

function deleteOrder(id) {
  if (!confirm('Remove this order permanently?')) return;
  const fd = new FormData();
  fd.append('action', 'admin_order_delete');
  fd.append('id', id);
  fetch('api.php', { method: 'POST', body: fd }).then(handleAdminSave).catch(() => alert('Could not delete this order.'));
}

// === SPA-STYLE PAGE NAV (public site only) ===
function runInjectedScripts(container) {
  container.querySelectorAll('script').forEach(old => {
    const s = document.createElement('script');
    s.textContent = '(function(){\n' + old.textContent + '\n})();';
    old.replaceWith(s);
  });
}

function reinitPageScripts(page) {
  if (page === 'home') startHero();
  if (page === 'bouquets') initBouquetFilters();
}

async function navigateTo(url, push) {
  const content = document.getElementById('page-content');
  if (!content) { window.location.href = url; return; }

  let res, html;
  try {
    res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
    html = await res.text();
  } catch (e) {
    window.location.href = url;
    return;
  }

  const doc = new DOMParser().parseFromString(html, 'text/html');
  const newContent = doc.getElementById('page-content');
  if (!newContent) { window.location.href = url; return; }

  const apply = () => {
    content.innerHTML = newContent.innerHTML;
    content.dataset.page = newContent.dataset.page || '';
    document.title = doc.title;
    runInjectedScripts(content);
    reinitPageScripts(content.dataset.page);

    document.querySelectorAll('.nav-main a').forEach(a => {
      const href = a.getAttribute('href') || '';
      a.classList.toggle('active', href === '?page=' + content.dataset.page);
    });

    const hash = url.split('#')[1];
    if (hash) {
      const target = document.getElementById(hash);
      if (target) { target.scrollIntoView({ behavior: 'smooth' }); return; }
    }
    window.scrollTo(0, 0);
  };

  if (document.startViewTransition) {
    document.startViewTransition(apply);
  } else {
    apply();
  }

  if (push) history.pushState({ fzSpa: true }, '', url);
}

function initSpaNav() {
  const content = document.getElementById('page-content');
  if (!content) return;

  document.addEventListener('click', e => {
    const a = e.target.closest('a');
    if (!a) return;
    const href = a.getAttribute('href') || '';
    if (!href.startsWith('?page=')) return;
    if (href.startsWith('?page=admin')) return;
    if (a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    navigateTo(a.href, true);
  });

  window.addEventListener('popstate', () => {
    navigateTo(window.location.href, false);
  });
}
document.addEventListener('DOMContentLoaded', initSpaNav);
