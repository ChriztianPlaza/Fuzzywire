<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div class="hero-copy">
        <span class="eyebrow">Autumn Collection 2024</span>
        <h1>Quiet blooms,<br>intentionally gathered.</h1>
        <p class="lede">Crafty Fuzzy creates handmade fuzzy flower arrangements with soft textures and calm palettes. No clutter, just flowers.</p>
        <div class="hero-ctas">
          <a href="?page=bouquets" class="btn btn-primary">Shop Bouquets</a>
          <?php if (!empty($customizeEnabled)): ?>
          <a href="?page=customize" class="btn btn-ghost">Build Your Own</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="hero-visual">
        <?php 
        $slides = [
          ['rose', '#D89B9B', 'Morning Light'],
          ['peony', '#E8B8B8', 'Blush Garden'],
          ['rose', '#B8775C', 'Terracotta Sky']
        ];
        foreach ($slides as $i => $s): ?>
          <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>">
            <?= flowerSVG($s[0], $s[1], 300) ?>
            <div class="hero-slide-label"><?= $s[2] ?></div>
          </div>
        <?php endforeach; ?>
        <div class="hero-slide-pager">
          <?php foreach ($slides as $i => $s): ?>
            <span class="<?= $i === 0 ? 'active' : '' ?>"></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="editorial">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Curated Collection</span>
        <h2>This season's quiet moments</h2>
      </div>
      <p>A small selection of arrangements designed for slow mornings and gentle spaces.</p>
    </div>
    <div class="editorial-grid">
      <?php foreach (array_slice($bouquets, 0, 4) as $b): 
        $comp = json_decode($b['components'], true);
        $firstFlowerId = array_key_first($comp);
        $firstFlower = null;
        foreach ($flowers as $f) { if ($f['id'] == $firstFlowerId) $firstFlower = $f; }
      ?>
        <div class="editorial-card">
          <span class="eyebrow"><?= ucfirst($b['occasion']) ?></span>
          <h3><?= htmlspecialchars($b['name']) ?></h3>
          <p><?= htmlspecialchars($b['description']) ?></p>
          <a href="?page=bouquets" class="editorial-link">View arrangement →</a>
          <?php if ($firstFlower): ?>
            <div class="editorial-bloom"><?= flowerSVG($firstFlower['shape'], $firstFlower['color'], 240) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="reviews-section" id="reviews">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Customer Reviews</span>
        <h2>Reviews</h2>
      </div>
      <p>Fresh feedback from customers after their flowers arrive.</p>
    </div>
    <?php if (empty($reviews)): ?>
      <div class="reviews-empty">No reviews yet. Delivered orders can leave one from the notification bell.</div>
    <?php else: ?>
      <div class="reviews-grid">
        <?php foreach (array_slice($reviews, 0, 6) as $review): ?>
          <article class="review-card">
            <div class="review-stars" aria-label="<?= intval($review['rating']) ?> out of 5 stars">
              <?= str_repeat('&#9733;', max(1, min(5, intval($review['rating'])))) ?>
            </div>
            <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
            <?php if (!empty($review['photo'])): ?>
              <img class="review-photo" src="<?= htmlspecialchars($review['photo']) ?>" alt="Customer review photo">
            <?php endif; ?>
            <div class="review-name"><?= htmlspecialchars($review['customer_name'] ?: 'Crafty Fuzzy customer') ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
