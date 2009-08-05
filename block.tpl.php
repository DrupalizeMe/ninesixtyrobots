<?php
// $Id$
?>
<div id="block-<?php print $block->module .'-'. $block->delta; ?>" class="block block-<?php print $block->module ?>">
<?php if ($block->subject): ?>
  <h3 class="title"><?php print $block->subject ?></h3>
<?php endif;?>

  <div class="content">
    <?php print $block->content ?>
  </div>
</div>