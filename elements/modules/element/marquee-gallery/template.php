<?php
/**
 * WeFrame – Marquee Gallery | template.php
 *
 * Bande d'images horizontale et infinie. Défilement en CSS pur (les enfants
 * sont dupliqués pour une boucle sans couture). Ni JavaScript inline, ni bloc
 * de configuration.
 *
 * ── Le bombement ─────────────────────────────────────────────────────────
 * L'arc haut et bas est un **rayon vertical de `border-radius`**, associé à un
 * rayon horizontal de 50 %. Les deux coins du haut se partagent alors toute la
 * largeur et se rejoignent au milieu : le bord supérieur devient un seul arc,
 * bombé vers le haut. Idem en bas.
 *
 * D'où le piège, et la raison de ce correctif : ce rayon vertical se compte en
 * pixels, pas en pourcentage de la hauteur. Dès qu'il atteint la moitié de la
 * hauteur de l'image, l'arc du haut rejoint celui du bas, les bords latéraux
 * droits disparaissent et le rectangle s'effondre en **lentille** — l'image est
 * rognée jusqu'au sujet. Un `border-radius: 50% / 50%` (ou toute valeur en
 * pourcentage un peu généreuse) donne exactement ça.
 *
 * Le bombement est donc saisi en pixels et **plafonné à 30 % de la hauteur
 * d'image**, desktop et mobile chacun avec la sienne : il reste toujours 40 %
 * de hauteur de bord latéral droit, et l'image garde sa silhouette de photo.
 */
defined( 'ABSPATH' ) || exit;

$props = is_array( $props ?? null ) ? $props : array();
if ( ! isset( $builder ) || ! is_object( $builder ) || empty( $children ) || ! is_array( $children ) ) { return; }

$speed    = min( 120, max( 10, (int) ( $props['speed'] ?? 40 ) ) );
$speed_m  = max( 0, (int) ( $props['speed_mobile'] ?? 0 ) );
$reverse  = ! empty( $props['reverse'] );
$hover    = ! empty( $props['pause_on_hover'] );
$gap      = min( 80, max( 0, (int) ( $props['gap'] ?? 24 ) ) );
$img_h    = min( 500, max( 100, (int) ( $props['img_height'] ?? 260 ) ) );
$img_h_m  = min( 400, max( 80, (int) ( $props['img_height_mobile'] ?? 170 ) ) );
$radius   = min( 40, max( 0, (int) ( $props['radius'] ?? 14 ) ) );
$gray     = ! empty( $props['grayscale'] );
$col_hov  = ! empty( $props['color_on_hover'] ) && $gray;
$fade     = ! empty( $props['fade_edges'] );
$fade_col = (string) ( $props['fade_color'] ?? '#ffffff' );
if ( ! preg_match( '/^#([0-9a-fA-F]{3}){1,2}$/', $fade_col ) ) { $fade_col = '#ffffff'; }

/* ── Bombement ───────────────────────────────────────────────────────────
   Profondeur d'arc en pixels, et son plafond : la moitié de la hauteur est le
   point de rupture (la lentille), 30 % laisse une marge confortable. */
$bulge   = ! empty( $props['bulge'] );
$bulge_d = min( 60, max( 0, (int) ( $props['bulge_depth'] ?? 18 ) ) );
$bulge_m = min( 40, max( 0, (int) ( $props['bulge_depth_mobile'] ?? 0 ) ) );

$arc   = 0;
$arc_m = 0;
if ( $bulge && $bulge_d > 0 ) {
    $arc = min( $bulge_d, (int) floor( $img_h * 0.30 ) );

    /* Sur mobile, 0 veut dire « à l'échelle » : l'arc suit la réduction de
       hauteur, pour que la courbure reste visuellement la même. */
    $raw_m = $bulge_m > 0 ? $bulge_m : (int) round( $bulge_d * $img_h_m / max( 1, $img_h ) );
    $arc_m = min( $raw_m, (int) floor( $img_h_m * 0.30 ) );
}
$bombe = $arc > 0;

/* Un arc et un arrondi de coin ne tiennent pas dans le même `border-radius` :
   quand le bombement est actif, c'est lui qui décrit la forme. */
$img_shape   = $bombe ? '50% / ' . $arc . 'px' : $radius . 'px';
$img_shape_m = '50% / ' . max( 1, $arc_m ) . 'px';

$html = '';
foreach ( $children as $child ) {
    $html .= (string) $builder->render( $child, array( 'element' => $props ) );
}
if ( ! trim( $html ) ) { return; }

$uid = wf_uid( 'wfmq_', $props, count( $children ) );
$fade_w = (int) ( $gap * 3 );
$half_gap = (int) round( $gap / 2 );
$has_mob_speed = $speed_m > 0 && $speed_m !== $speed;
$filter = $gray ? 'grayscale(1)' : 'none';
$dir = $reverse ? 'reverse' : 'normal';
?>
<?php $wfTag = ! empty( $props['html_element'] ) ? $props['html_element'] : 'div'; $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( $wfTag ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>

<div id="<?= esc_attr( $uid ) ?>" class="wf-mq<?= $bombe ? ' wf-mq--bombe' : '' ?>" style="position:relative;overflow:hidden;">
    <?php if ( $fade ) : ?>
    <div aria-hidden="true" style="position:absolute;top:0;left:0;bottom:0;width:<?= $fade_w ?>px;z-index:2;pointer-events:none;background:linear-gradient(90deg,<?= esc_attr( $fade_col ) ?>,transparent);"></div>
    <div aria-hidden="true" style="position:absolute;top:0;right:0;bottom:0;width:<?= $fade_w ?>px;z-index:2;pointer-events:none;background:linear-gradient(270deg,<?= esc_attr( $fade_col ) ?>,transparent);"></div>
    <?php endif; ?>
    <div class="wf-mq-track" style="display:flex;width:max-content;align-items:center;animation:wf-mq-scroll <?= $speed ?>s linear infinite;animation-direction:<?= $dir ?>;">
        <?= $html ?>
        <?= $html ?>
    </div>
</div>

<style>
@keyframes wf-mq-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
#<?= esc_attr( $uid ) ?> .wf-sp-fig { margin: 0 <?= $half_gap ?>px; flex-shrink: 0; }
#<?= esc_attr( $uid ) ?> .wf-sp-fig img { height: <?= $img_h ?>px; width: auto; max-width: none; border-radius: <?= $img_shape ?> !important; filter: <?= $filter ?>; transition: filter .3s ease; }
<?php if ( $bombe ) : ?>
/* L'arc rogne les coins sur toute la largeur : sans plancher, un portrait étroit
   n'aurait plus que sa colonne centrale. Le cadrage prend le relais plutôt que
   la déformation. */
#<?= esc_attr( $uid ) ?>.wf-mq--bombe .wf-sp-fig img { min-width: <?= (int) round( $img_h * 0.75 ) ?>px; object-fit: cover; }
<?php endif; ?>
<?php if ( $col_hov ) : ?>
#<?= esc_attr( $uid ) ?> .wf-sp-fig:hover img { filter: grayscale(0); }
<?php endif; ?>
<?php if ( $hover ) : ?>
#<?= esc_attr( $uid ) ?>:hover .wf-mq-track { animation-play-state: paused; }
<?php endif; ?>
<?php if ( $has_mob_speed ) : ?>
@media (max-width:767px) { #<?= esc_attr( $uid ) ?> .wf-mq-track { animation-duration: <?= $speed_m ?>s !important; } }
<?php endif; ?>
@media (max-width:767px) {
    #<?= esc_attr( $uid ) ?> .wf-sp-fig img { height: <?= $img_h_m ?>px; }
<?php if ( $bombe ) : ?>
    /* La hauteur baisse, la flèche doit baisser avec elle : sinon c'est ici, sur
       l'image la plus basse, que la lentille réapparaîtrait en premier. */
    #<?= esc_attr( $uid ) ?>.wf-mq--bombe .wf-sp-fig img { border-radius: <?= $img_shape_m ?> !important; min-width: <?= (int) round( $img_h_m * 0.75 ) ?>px; }
<?php endif; ?>
}
@media (prefers-reduced-motion: reduce) { #<?= esc_attr( $uid ) ?> .wf-mq-track { animation: none !important; } }
</style>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>
