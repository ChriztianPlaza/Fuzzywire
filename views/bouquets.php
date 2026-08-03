<section class="builder-page">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Premade Arrangements</span>
        <h2>Ready to send</h2>
      </div>
      <p>Filter by occasion or price to find the right sentiment.</p>
    </div>

    <div class="filter-bar">
      <div class="filter-group">
        <span class="label">Occasion</span>
        <button class="chip active" type="button" data-filter-type="occasion" data-filter-value="all">All</button>
        <button class="chip" type="button" data-filter-type="occasion" data-filter-value="romantic">Romantic</button>
        <button class="chip" type="button" data-filter-type="occasion" data-filter-value="modern">Modern</button>
        <button class="chip" type="button" data-filter-type="occasion" data-filter-value="wildflower">Wildflower</button>
      </div>
      <div class="filter-group">
        <span class="label">Price</span>
        <button class="chip active" type="button" data-filter-type="price" data-filter-value="all">Any</button>
        <button class="chip" type="button" data-filter-type="price" data-min-price="200" data-max-price="500">&#8369;200-&#8369;500</button>
        <button class="chip" type="button" data-filter-type="price" data-min-price="500.01" data-max-price="1000">&#8369;501-&#8369;1,000</button>
        <button class="chip" type="button" data-filter-type="price" data-min-price="1000.01" data-max-price="3000">&#8369;1,001-&#8369;3,000</button>
      </div>
    </div>

    <div class="product-grid" id="bouquetGrid">
      <?php foreach ($bouquets as $b):
        $comp = json_decode($b['components'], true);
        $firstFlowerId = is_array($comp) ? array_key_first($comp) : null;
        $firstFlower = null;
        foreach ($flowers as $f) { if ($f['id'] == $firstFlowerId) $firstFlower = $f; }
      ?>
        <div class="product-card" data-occasion="<?= htmlspecialchars(strtolower($b['occasion'])) ?>" data-price="<?= htmlspecialchars((string)$b['price']) ?>">
          <div class="card-media">
            <?php if (!empty($b['image'])): ?>
              <img class="photo-preview-trigger" src="<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['name']) ?>" data-preview-src="<?= htmlspecialchars($b['image']) ?>" data-preview-caption="<?= htmlspecialchars($b['name']) ?>" title="Click to enlarge" style="width:100%;height:100%;object-fit:cover;">
            <?php elseif ($firstFlower): ?>
              <div class="photo-preview-trigger photo-preview-svg" data-preview-caption="<?= htmlspecialchars($b['name']) ?>" title="Click to enlarge">
                <?= flowerSVG($firstFlower['shape'], $firstFlower['color'], 200) ?>
              </div>
            <?php endif; ?>
            <span class="card-tag"><?= ucfirst($b['occasion']) ?></span>
            <button class="quick-add" type="button" onclick="addToCart('premade', '<?= htmlspecialchars(json_encode(['bouquet_id' => intval($b['id'])]), ENT_QUOTES) ?>')">Quick Add</button>
          </div>
          <div class="card-info">
            <div class="name"><?= htmlspecialchars($b['name']) ?></div>
            <div class="desc"><?= htmlspecialchars($b['description']) ?></div>
            <div class="price">&#8369;<?= number_format($b['price'], 2) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="products-empty" id="bouquetEmpty" hidden>No bouquets match those filters.</div>
  </div>
</section>
