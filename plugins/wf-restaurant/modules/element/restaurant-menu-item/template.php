<?php
/**
 * WeFrame – Menu Restaurant Item (plat) | template.php v1.0
 */
defined( 'ABSPATH' ) || exit;

$name  = (string) ( $props['name'] ?? '' );
$desc  = (string) ( $props['description'] ?? '' );
$price = trim( (string) ( $props['price'] ?? '' ) );
$img   = trim( (string) ( $props['image'] ?? '' ) );
$alt   = (string) ( $props['image_alt'] ?? '' );

if ( '' === $name ) { return; }
if ( $img && ! preg_match( '#^https?://#i', $img ) ) { $img = '/' . ltrim( $img, '/' ); }

$currency = (string) ( $element['currency'] ?? '€' );
$cur_pos  = ( 'before' === ( $element['currency_pos'] ?? 'after' ) ) ? 'before' : 'after';
$show_img = ! empty( $element['show_images'] );
$leader   = ! empty( $element['leader'] );
$layout   = ( 'cards' === ( $element['layout'] ?? 'list' ) ) ? 'cards' : 'list';

// Prix formaté (devise seulement si le prix est numérique)
$price_out = '';
if ( '' !== $price ) {
    if ( preg_match( '/\d/', $price ) && '' !== $currency ) {
        $price_out = ( 'before' === $cur_pos ) ? $currency . ' ' . $price : $price . ' ' . $currency;
    } else {
        $price_out = $price;
    }
}

// Badges
$badges = array();
if ( ! empty( $props['is_popular'] ) ) { $badges[] = array( 'Populaire', 'accent' ); }
if ( ! empty( $props['is_new'] ) )     { $badges[] = array( 'Nouveau', 'accent' ); }
if ( ! empty( $props['is_veg'] ) )     { $badges[] = array( 'V', 'v' ); }
if ( ! empty( $props['is_vegan'] ) )   { $badges[] = array( 'VG', 'v' ); }
if ( ! empty( $props['is_spicy'] ) )   { $badges[] = array( '🌶', 'spicy' ); }
if ( ! empty( $props['is_gf'] ) )      { $badges[] = array( 'GF', 'gf' ); }
$cb = trim( (string) ( $props['custom_badge'] ?? '' ) );
if ( '' !== $cb ) { $badges[] = array( $cb, 'accent' ); }
?>
<div class="wf-rm-item">
    <?php if ( $show_img && $img ) : ?>
    <div class="wf-rm-img"><img src="<?= esc_url( $img ) ?>" alt="<?= esc_attr( $alt ? $alt : $name ) ?>" loading="lazy"></div>
    <?php endif; ?>
    <div class="wf-rm-body">
        <div class="wf-rm-head">
            <span class="wf-rm-name"><?= esc_html( $name ) ?></span>
            <?php if ( 'list' === $layout && $leader && '' !== $price_out ) : ?><span class="wf-rm-leader" aria-hidden="true"></span><?php endif; ?>
            <?php if ( '' !== $price_out ) : ?><span class="wf-rm-price"><?= esc_html( $price_out ) ?></span><?php endif; ?>
        </div>
        <?php if ( ! empty( $badges ) ) : ?>
        <div class="wf-rm-badges">
            <?php foreach ( $badges as $b ) : ?><span class="wf-rm-badge wf-rm-badge--<?= esc_attr( $b[1] ) ?>"><?= esc_html( $b[0] ) ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ( '' !== $desc ) : ?><p class="wf-rm-desc"><?= esc_html( $desc ) ?></p><?php endif; ?>
    </div>
</div>
