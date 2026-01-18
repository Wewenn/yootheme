<?php
/**
 * Template de contenu alternatif (simplifié)
 * Utilisé si vous avez besoin d'un template plus simple
 */
?>

<div class="ba-content">
    <?php if ($props['image_before']) : ?>
        <div class="ba-content-before">
            <img src="<?= $props['image_before'] ?>" alt="<?= $props['label_before'] ?: 'Avant' ?>">
        </div>
    <?php endif ?>

    <?php if ($props['image_after']) : ?>
        <div class="ba-content-after">
            <img src="<?= $props['image_after'] ?>" alt="<?= $props['label_after'] ?: 'Après' ?>">
        </div>
    <?php endif ?>
</div>
