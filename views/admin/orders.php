<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=orders');
    exit;
}

function summarizeOrderItems($items) {
    $decoded = json_decode($items ?? '', true);
    if (!$decoded) return $items ?: 'No item details';
    if (array_is_list($decoded)) {
        return implode(', ', array_map(function($item) {
            return ($item['name'] ?? 'Item') . ' - ' . number_format((float)($item['line_total'] ?? 0), 2);
        }, $decoded));
    }
    if (isset($decoded['name'])) return $decoded['name'];
    if (isset($decoded['size'])) return $decoded['size'] . ' custom bouquet';
    return json_encode($decoded);
}
?>

<div class="admin-head">
  <div><h1>Orders</h1><div class="sub">Track customer bouquet requests</div></div>
</div>
<table class="admin-table">
  <thead><tr><th>Customer</th><th>Delivery</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
  <tbody>
    <?php if (empty($orders)): ?>
      <tr><td colspan="8" style="color:var(--ink-faint);text-align:center;padding:28px">No orders yet.</td></tr>
    <?php else: foreach ($orders as $o): ?>
      <tr>
        <td>
          <div class="name-cell"><?= htmlspecialchars($o['customer_name'] ?: 'Guest') ?></div>
          <div style="font-size:0.78rem;color:var(--ink-faint)"><?= htmlspecialchars($o['customer_email'] ?: 'No email') ?></div>
          <?php if (!empty($o['customer_phone'])): ?>
            <div style="font-size:0.78rem;color:var(--terracotta)">Phone: <?= htmlspecialchars($o['customer_phone']) ?></div>
          <?php endif; ?>
          <?php if (!empty($o['google_email'])): ?>
            <div style="font-size:0.78rem;color:var(--ink-faint)">Account: <?= htmlspecialchars($o['google_email']) ?></div>
          <?php endif; ?>
        </td>
        <td><?= nl2br(htmlspecialchars($o['delivery_address'] ?: 'No address')) ?></td>
        <td><?= htmlspecialchars(summarizeOrderItems($o['items'])) ?></td>
        <td>&#8369;<?= number_format((float)$o['total'], 2) ?></td>
        <td>
          <div style="font-size:0.82rem;margin-bottom:6px">GCash: <?= htmlspecialchars($o['gcash_reference'] ?: 'No ref') ?></div>
          <select onchange="updatePaymentStatus(<?= $o['id'] ?>, this.value)">
            <?php foreach (['pending verification', 'verified', 'rejected'] as $paymentStatus): ?>
              <option value="<?= $paymentStatus ?>" <?= ($o['payment_status'] ?? 'pending verification') === $paymentStatus ? 'selected' : '' ?>><?= ucfirst($paymentStatus) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <select onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)">
            <?php foreach (['pending', 'preparing', 'ready', 'delivering', 'delivered', 'completed', 'cancelled'] as $status): ?>
              <option value="<?= $status ?>" <?= $o['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><?= htmlspecialchars($o['created_at']) ?></td>
        <td><button class="btn-admin-danger" onclick="deleteOrder(<?= $o['id'] ?>)">Delete</button></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
