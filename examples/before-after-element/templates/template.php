<?php
/**
 * Template pour le composant Avant/Après
 */

// Variables
$id = $props['id'];
$image_before = $props['image_before'];
$image_after = $props['image_after'];
$label_before = $props['label_before'];
$label_after = $props['label_after'];
$slider_position = $props['slider_position'] ?? 50;
$orientation = $props['orientation'] ?? 'horizontal';
$handle_style = $props['handle_style'] ?? 'default';
$image_height = $props['image_height'];

// Classes
$classes = array_filter([
    'ba-container',
    'ba-orientation-' . $orientation,
    'ba-handle-' . $handle_style,
    ...$props['class']
]);

// Styles inline
$styles = [];
if ($image_height) {
    $styles[] = 'height: ' . $image_height . 'px';
}

// Attributs du container
$attrs = [
    'id' => $id,
    'class' => implode(' ', $classes),
    'data-position' => $slider_position,
    'data-orientation' => $orientation
];

if (!empty($styles)) {
    $attrs['style'] = implode('; ', $styles);
}

?>

<?php if ($image_before && $image_after) : ?>

<div<?= $this->attrs($attrs) ?>>

    <!-- Image Avant -->
    <div class="ba-image ba-before">
        <img src="<?= $image_before ?>" alt="<?= $label_before ?: 'Avant' ?>" uk-img>
        <?php if ($label_before) : ?>
            <div class="ba-label ba-label-before">
                <span class="uk-label uk-label-success"><?= $label_before ?></span>
            </div>
        <?php endif ?>
    </div>

    <!-- Image Après (overlay) -->
    <div class="ba-image ba-after">
        <img src="<?= $image_after ?>" alt="<?= $label_after ?: 'Après' ?>" uk-img>
        <?php if ($label_after) : ?>
            <div class="ba-label ba-label-after">
                <span class="uk-label uk-label-primary"><?= $label_after ?></span>
            </div>
        <?php endif ?>
    </div>

    <!-- Slider Handle -->
    <div class="ba-slider">
        <div class="ba-handle">
            <?php if ($handle_style === 'arrows') : ?>
                <span class="ba-arrow ba-arrow-left" uk-icon="icon: chevron-left"></span>
                <span class="ba-arrow ba-arrow-right" uk-icon="icon: chevron-right"></span>
            <?php elseif ($handle_style === 'circle') : ?>
                <div class="ba-circle">
                    <span uk-icon="icon: move"></span>
                </div>
            <?php else : ?>
                <span class="ba-line-left" uk-icon="icon: chevron-left"></span>
                <span class="ba-divider"></span>
                <span class="ba-line-right" uk-icon="icon: chevron-right"></span>
            <?php endif ?>
        </div>
    </div>

</div>

<?php else : ?>

<div class="uk-alert uk-alert-warning" uk-alert>
    <p><strong>Composant Avant/Après :</strong> Veuillez sélectionner les deux images (avant et après).</p>
</div>

<?php endif ?>
