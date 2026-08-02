<?php
$builderWrappers = array_values(array_filter($wrappers, fn($w) => $w['in_stock']));
$builderRibbons = array_values(array_filter($ribbons, fn($r) => $r['in_stock']));
?>
<section class="builder-page">
  <div class="container">
    <div class="section-head" style="margin-bottom: 30px;">
      <div>
        <span class="eyebrow">Customize</span>
        <h2>Build your bouquet</h2>
      </div>
    </div>

    <div class="builder-controls" style="max-width: 800px; margin: 0 auto;">
      <div class="builder-steps">
        <div class="builder-step active" data-step="1" onclick="goToStep(1)"><span class="num">1</span> Base Size</div>
        <div class="builder-step" data-step="2" onclick="goToStep(2)"><span class="num">2</span> Add Flowers</div>
        <div class="builder-step" data-step="3" onclick="goToStep(3)"><span class="num">3</span> Wrapper</div>
        <div class="builder-step" data-step="4" onclick="goToStep(4)"><span class="num">4</span> Ribbon</div>
        <div class="builder-step" data-step="5" onclick="goToStep(5)"><span class="num">5</span> Review & Note</div>
      </div>

      <div class="step-panel active" id="step-1">
        <h3>Choose a base size</h3>
        <p class="step-lede">Pick a size tier. Each one sets the minimum stem spend for your bouquet.</p>
        <div class="size-grid">
          <?php foreach ($builderBaseSizes as $i => $size): ?>
            <div class="size-card <?= $i === 0 ? 'selected' : '' ?>" data-size-id="<?= $size['id'] ?>" onclick="selectSize(<?= $size['id'] ?>)">
              <div class="size-icon"><?= flowerSVG('rose', '#D89B9B', intval($size['icon_size'] ?: 60)) ?></div>
              <div class="size-name"><?= htmlspecialchars($size['name']) ?></div>
              <div class="size-desc"><?= htmlspecialchars($size['description']) ?></div>
              <div class="size-price">&#8369;<?= number_format((float)$size['price'], 2) ?> min stems</div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="step-nav">
          <div></div>
          <button class="btn btn-primary btn-step" onclick="goToStep(2)">Next: Flowers -></button>
        </div>
      </div>

      <div class="step-panel" id="step-2">
        <h3>Add your stems</h3>
        <p class="step-lede">Use the steppers to choose how many of each, then pick a color.</p>
        <div class="stem-minimum-note" id="stem-minimum-note"></div>
        <div class="flower-picker">
          <?php foreach ($builderFlowers as $f):
            $colorOptions = parseColorOptions($f);
          ?>
            <div class="flower-tile" data-id="<?= $f['id'] ?>">
              <div class="ft-bloom photo-preview-trigger" data-preview-caption="<?= htmlspecialchars($f['name']) ?>" <?= !empty($f['image']) ? 'data-preview-src="' . htmlspecialchars($f['image']) . '"' : '' ?> data-shape="<?= htmlspecialchars($f['shape']) ?>" data-has-image="<?= !empty($f['image']) ? '1' : '0' ?>" data-base-color="<?= htmlspecialchars($f['color']) ?>" title="Click to enlarge">
                <?php if (!empty($f['image'])): ?>
                  <img src="<?= htmlspecialchars($f['image']) ?>" alt="<?= htmlspecialchars($f['name']) ?>" data-preview-src="<?= htmlspecialchars($f['image']) ?>" data-preview-caption="<?= htmlspecialchars($f['name']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                  <?= flowerSVG($f['shape'], $f['color'], 60) ?>
                <?php endif; ?>
              </div>
              <div class="ft-name"><?= htmlspecialchars($f['name']) ?></div>
              <div class="ft-price">&#8369;<?= number_format($f['price_per_stem'], 2) ?>/stem</div>
              <?php if (count($colorOptions) > 0): ?>
                <div class="stem-color-picker">
                  <?php foreach ($colorOptions as $i => $opt): ?>
                    <button type="button"
                      class="stem-color-swatch <?= $i === 0 ? 'selected' : '' ?>"
                      data-flower-id="<?= $f['id'] ?>"
                      data-name="<?= htmlspecialchars($opt['name']) ?>"
                      data-color="<?= htmlspecialchars($opt['color']) ?>"
                      style="background: <?= htmlspecialchars($opt['color']) ?>"
                      title="<?= htmlspecialchars($opt['name']) ?>"
                      onclick="selectStemColor(<?= $f['id'] ?>, this)"></button>
                  <?php endforeach; ?>
                </div>
                <div class="stem-color-label"><?= htmlspecialchars($colorOptions[0]['name']) ?></div>
              <?php endif; ?>
              <div class="qty-stepper">
                <button type="button" onclick="changeQty(<?= $f['id'] ?>, -1)">-</button>
                <span class="qty-val">0</span>
                <button type="button" onclick="changeQty(<?= $f['id'] ?>, 1)">+</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="step-nav">
          <button class="btn btn-ghost btn-step" onclick="goToStep(1)"><- Back</button>
          <button class="btn btn-primary btn-step" id="flowers-next-btn" onclick="goToStep(3)">Next: Wrapper -></button>
        </div>
      </div>

      <div class="step-panel" id="step-3">
        <h3>Choose your wrap</h3>
        <p class="step-lede">Use the steppers to choose how many of each wrap you want, then pick a color.</p>
        <div class="flower-picker">
          <?php foreach ($builderWrappers as $w):
            $colorOptions = parseItemColorOptions($w);
            $firstOpt = $colorOptions[0];
          ?>
            <div class="flower-tile option-tile wrapper-tile" data-id="<?= $w['id'] ?>">
              <div class="ft-bloom photo-preview-trigger" data-preview-caption="<?= htmlspecialchars($w['name']) ?>" <?= !empty($firstOpt['image']) ? 'data-preview-src="' . htmlspecialchars($firstOpt['image']) . '"' : '' ?> data-has-image="<?= !empty($firstOpt['image']) ? '1' : '0' ?>" title="Click to enlarge">
                <?php if (!empty($firstOpt['image'])): ?>
                  <img src="<?= htmlspecialchars($firstOpt['image']) ?>" alt="<?= htmlspecialchars($w['name']) ?>" data-preview-src="<?= htmlspecialchars($firstOpt['image']) ?>" data-preview-caption="<?= htmlspecialchars($w['name']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                  <span class="option-color-fallback" style="background: <?= htmlspecialchars($firstOpt['color']) ?>"></span>
                <?php endif; ?>
              </div>
              <div class="ft-name"><?= htmlspecialchars($w['name']) ?></div>
              <div class="ft-price"><?= ucfirst(htmlspecialchars($w['style'])) ?></div>
              <?php if (count($colorOptions) > 0): ?>
                <div class="stem-color-picker">
                  <?php foreach ($colorOptions as $i => $opt): ?>
                    <button type="button"
                      class="stem-color-swatch <?= $i === 0 ? 'selected' : '' ?>"
                      data-name="<?= htmlspecialchars($opt['name']) ?>"
                      data-color="<?= htmlspecialchars($opt['color']) ?>"
                      data-image="<?= htmlspecialchars($opt['image'] ?? '') ?>"
                      style="background: <?= htmlspecialchars($opt['color']) ?>"
                      title="<?= htmlspecialchars($opt['name']) ?>"
                      onclick="selectWrapperColor(<?= $w['id'] ?>, this)"></button>
                  <?php endforeach; ?>
                </div>
                <div class="stem-color-label"><?= htmlspecialchars($colorOptions[0]['name']) ?></div>
              <?php endif; ?>
              <div class="qty-stepper">
                <button type="button" onclick="changeWrapperQty(<?= $w['id'] ?>, -1)">-</button>
                <span class="qty-val">0</span>
                <button type="button" onclick="changeWrapperQty(<?= $w['id'] ?>, 1)">+</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="step-nav">
          <button class="btn btn-ghost btn-step" onclick="goToStep(2)"><- Back</button>
          <button class="btn btn-primary btn-step" onclick="goToStep(4)">Next: Ribbon -></button>
        </div>
      </div>

      <div class="step-panel" id="step-4">
        <h3>Choose your ribbon</h3>
        <p class="step-lede">Use the steppers to choose how many of each ribbon you want, then pick a color.</p>
        <div class="flower-picker">
          <?php foreach ($builderRibbons as $r):
            $colorOptions = parseItemColorOptions($r);
            $firstOpt = $colorOptions[0];
          ?>
            <div class="flower-tile option-tile ribbon-tile" data-id="<?= $r['id'] ?>">
              <div class="ft-bloom photo-preview-trigger" data-preview-caption="<?= htmlspecialchars($r['name']) ?>" <?= !empty($firstOpt['image']) ? 'data-preview-src="' . htmlspecialchars($firstOpt['image']) . '"' : '' ?> data-has-image="<?= !empty($firstOpt['image']) ? '1' : '0' ?>" title="Click to enlarge">
                <?php if (!empty($firstOpt['image'])): ?>
                  <img src="<?= htmlspecialchars($firstOpt['image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>" data-preview-src="<?= htmlspecialchars($firstOpt['image']) ?>" data-preview-caption="<?= htmlspecialchars($r['name']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                  <span class="option-color-fallback" style="background: <?= htmlspecialchars($firstOpt['color']) ?>"></span>
                <?php endif; ?>
              </div>
              <div class="ft-name"><?= htmlspecialchars($r['name']) ?></div>
              <div class="ft-price">Ribbon</div>
              <?php if (count($colorOptions) > 0): ?>
                <div class="stem-color-picker">
                  <?php foreach ($colorOptions as $i => $opt): ?>
                    <button type="button"
                      class="stem-color-swatch <?= $i === 0 ? 'selected' : '' ?>"
                      data-name="<?= htmlspecialchars($opt['name']) ?>"
                      data-color="<?= htmlspecialchars($opt['color']) ?>"
                      data-image="<?= htmlspecialchars($opt['image'] ?? '') ?>"
                      style="background: <?= htmlspecialchars($opt['color']) ?>"
                      title="<?= htmlspecialchars($opt['name']) ?>"
                      onclick="selectRibbonColor(<?= $r['id'] ?>, this)"></button>
                  <?php endforeach; ?>
                </div>
                <div class="stem-color-label"><?= htmlspecialchars($colorOptions[0]['name']) ?></div>
              <?php endif; ?>
              <div class="qty-stepper">
                <button type="button" onclick="changeRibbonQty(<?= $r['id'] ?>, -1)">-</button>
                <span class="qty-val">0</span>
                <button type="button" onclick="changeRibbonQty(<?= $r['id'] ?>, 1)">+</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="step-nav">
          <button class="btn btn-ghost btn-step" onclick="goToStep(3)"><- Back</button>
          <button class="btn btn-primary btn-step" onclick="goToStep(5)">Review -></button>
        </div>
      </div>

      <div class="step-panel" id="step-5">
        <h3>Final touches</h3>
        <p class="step-lede">Add a note and review your arrangement.</p>
        <textarea class="note-area" id="note-area" placeholder="A handwritten note for your recipient..." oninput="updateNote()"></textarea>
        
        <div class="review-summary" style="margin-top: 24px;">
          <h4>Order Summary</h4>
          <div id="review-items"></div>
        </div>

        <div class="step-nav">
          <button class="btn btn-ghost btn-step" onclick="goToStep(4)"><- Back</button>
          <button class="btn btn-primary btn-step" onclick="addCustomToCart()">Add to Cart</button>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  const FLOWERS = <?= json_encode(array_map(function($f) {
    return [
      'id' => intval($f['id']),
      'name' => $f['name'],
      'price' => floatval($f['price_per_stem']),
      'color' => $f['color'],
      'shape' => $f['shape'],
      'color_options' => parseColorOptions($f)
    ];
  }, $builderFlowers)) ?>;
  const WRAPPERS = <?= json_encode(array_map(function($w) {
    return [
      'id' => intval($w['id']),
      'name' => $w['name'],
      'color' => $w['color'],
      'style' => $w['style'],
      'image' => $w['image'] ?? '',
      'color_options' => parseItemColorOptions($w)
    ];
  }, $builderWrappers)) ?>;
  const RIBBONS = <?= json_encode(array_map(function($r) {
    return [
      'id' => intval($r['id']),
      'name' => $r['name'],
      'color' => $r['color'],
      'image' => $r['image'] ?? '',
      'color_options' => parseItemColorOptions($r)
    ];
  }, $builderRibbons)) ?>;
  const BASE_SIZES = <?= json_encode(array_map(function($s) { return ['id'=>intval($s['id']), 'name'=>$s['name'], 'description'=>$s['description'], 'price'=>floatval($s['price']), 'icon_size'=>intval($s['icon_size'])]; }, $builderBaseSizes)) ?>;
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initBuilder(FLOWERS, WRAPPERS, RIBBONS, BASE_SIZES));
  } else {
    initBuilder(FLOWERS, WRAPPERS, RIBBONS, BASE_SIZES);
  }
</script>
