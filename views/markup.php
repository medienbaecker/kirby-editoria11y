<script type="application/json" id="kirby-editoria11y"><?= $config ?></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= $script ?>" nonce="<?= $nonce ?>"></script>
<?php endforeach ?>
