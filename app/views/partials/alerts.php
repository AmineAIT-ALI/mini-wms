<?php
/**
 * Inline alert partial.
 * Usage: include this partial with $alerts variable set:
 *   $alerts = [['type' => 'error', 'message' => '...'], ...]
 * Or pass inline via $alert_type + $alert_message for single alert.
 */
if (!empty($alerts) && is_array($alerts)):
    foreach ($alerts as $alert):
?>
<div class="alert alert-<?= e($alert['type']) ?>" role="alert">
    <?= e($alert['message']) ?>
</div>
<?php
    endforeach;
elseif (!empty($alert_message)):
?>
<div class="alert alert-<?= e($alert_type ?? 'info') ?>" role="alert">
    <?= e($alert_message) ?>
</div>
<?php endif; ?>
