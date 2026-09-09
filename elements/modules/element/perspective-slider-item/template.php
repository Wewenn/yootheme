<?php
/**
 * WeFrame – Carrousel perspective infini : image (item).
 *
 * L'item ne porte aucune mise en forme : positions, rotations et légendes sont
 * décidées par le parent, qui recopie ces cartes autant de fois qu'il en faut
 * pour couvrir la largeur. La classe wf-pspv-card sert de repère au parent —
 * elle doit rester telle quelle.
 */
defined( 'ABSPATH' ) || exit;

$props = is_array( $props ?? null ) ? $props : array();

$img = trim( (string) ( $props['image'] ?? '' ) );
if ( '' === $img ) { return; }
if ( ! preg_match( '#^(https?:)?//#i', $img ) ) {
	if ( ! preg_match( '#^data:#i', $img ) ) { $img = '/' . ltrim( $img, '/' ); }
}

$title = trim( (string) ( $props['title'] ?? '' ) );

$link = $props['link'] ?? '';
if ( is_array( $link ) ) { $link = $link['url'] ?? ''; }
$link = trim( (string) $link );

$tag  = '' !== $link ? 'a' : 'div';
$href = '' !== $link ? ' href="' . esc_url( $link ) . '"' : '';
?>
<<?= $tag ?> class="wf-pspv-card"<?= $href ?> draggable="false">
	<img class="wf-pspv-img" src="<?= esc_url( $img ) ?>" alt="<?= esc_attr( $title ) ?>" loading="lazy" decoding="async" draggable="false">
	<?php if ( '' !== $title ) : ?>
	<span class="wf-pspv-cap"><?= esc_html( $title ) ?></span>
	<?php endif; ?>
</<?= $tag ?>>
