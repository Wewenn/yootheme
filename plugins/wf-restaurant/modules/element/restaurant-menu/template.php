<?php
/**
 * WeFrame – Menu Restaurant | template.php v1.0
 * Liste de plats (nom · ligne pointillée · prix) ou cartes, avec badges (végé, épicé, populaire…).
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wf_rm_central_item' ) ) {
    function wf_rm_central_item( $it, $element ) {
        $currency = (string) ( $element['currency'] ?? '€' );
        $cur_pos  = ( 'before' === ( $element['currency_pos'] ?? 'after' ) ) ? 'before' : 'after';
        $leader   = ! empty( $element['leader'] );
        $layout   = ( 'cards' === ( $element['layout'] ?? 'list' ) ) ? 'cards' : 'list';
        $name     = (string) $it['name'];
        $desc     = (string) $it['desc'];
        $price    = trim( (string) $it['price'] );
        $sold     = ! empty( $it['sold_out'] );

        $price_out = '';
        if ( '' !== $price ) {
            if ( preg_match( '/\d/', $price ) && '' !== $currency ) {
                $price_out = ( 'before' === $cur_pos ) ? $currency . ' ' . $price : $price . ' ' . $currency;
            } else {
                $price_out = $price;
            }
        }

        $badges = array();
        if ( ! empty( $it['featured'] ) ) { $badges[] = array( 'Plat du jour', 'accent' ); }
        if ( ! empty( $it['popular'] ) )  { $badges[] = array( 'Populaire', 'accent' ); }
        if ( ! empty( $it['veg'] ) )      { $badges[] = array( 'V', 'v' ); }
        if ( ! empty( $it['vegan'] ) )    { $badges[] = array( 'VG', 'v' ); }
        if ( ! empty( $it['spicy'] ) )    { $badges[] = array( '🌶', 'spicy' ); }
        if ( ! empty( $it['gf'] ) )       { $badges[] = array( 'GF', 'gf' ); }
        if ( $sold )                      { $badges[] = array( 'Épuisé', 'sold' ); }

        $show_img = ! empty( $element['show_images'] );
        $photo    = isset( $it['photo'] ) ? trim( (string) $it['photo'] ) : '';
        $h  = '<div class="wf-rm-item' . ( $sold ? ' wf-rm-sold' : '' ) . '">';
        if ( $show_img && '' !== $photo ) { $h .= '<div class="wf-rm-img"><img src="' . esc_url( $photo ) . '" alt="' . esc_attr( $name ) . '" loading="lazy"></div>'; }
        $h .= '<div class="wf-rm-body"><div class="wf-rm-head">';
        $h .= '<span class="wf-rm-name">' . esc_html( $name ) . '</span>';
        if ( 'list' === $layout && $leader && '' !== $price_out ) { $h .= '<span class="wf-rm-leader" aria-hidden="true"></span>'; }
        if ( '' !== $price_out ) { $h .= '<span class="wf-rm-price">' . esc_html( $price_out ) . '</span>'; }
        $h .= '</div>';
        if ( ! empty( $badges ) ) {
            $h .= '<div class="wf-rm-badges">';
            foreach ( $badges as $b ) { $h .= '<span class="wf-rm-badge wf-rm-badge--' . esc_attr( $b[1] ) . '">' . esc_html( $b[0] ) . '</span>'; }
            $h .= '</div>';
        }
        if ( '' !== $desc ) { $h .= '<p class="wf-rm-desc">' . esc_html( $desc ) . '</p>'; }
        $h .= '</div></div>';
        return $h;
    }
}

$data_source = $props['data_source'] ?? 'inline';
$central     = ( 'central' === $data_source && function_exists( 'wf_resto_menu_items' ) );
if ( ! $central && empty( $children ) ) { return; }

$cols    = ( '2' === ( $props['columns'] ?? '1' ) ) ? 2 : 1;
$layout  = ( 'cards' === ( $props['layout'] ?? 'list' ) ) ? 'cards' : 'list';
$accent  = $props['accent'] ?? '#c0272d';
$n_col   = $props['name_color'] ?? '#111111';
$p_col   = $props['price_color'] ?? '#111111';
$d_col   = $props['desc_color'] ?? '#6b7280';

$html  = '';
$count = 0;
if ( $central ) {
    $items  = wf_resto_menu_items();
    $curcat = null;
    foreach ( $items as $it ) {
        $cat = isset( $it['cat'] ) ? trim( (string) $it['cat'] ) : '';
        if ( $cat !== $curcat ) {
            if ( '' !== $cat ) { $html .= '<div class="wf-rm-cat">' . esc_html( $cat ) . '</div>'; }
            $curcat = $cat;
        }
        $html .= wf_rm_central_item( $it, $props );
    }
    $count = count( $items );
} else {
    foreach ( $children as $child ) {
        $html .= $builder->render( $child, array( 'element' => $props ) );
    }
    $count = count( $children );
}
if ( ! trim( $html ) ) { return; }

$uid = 'wfrm_' . substr( md5( serialize( $props ) . $count ), 0, 8 );
?>
<?php $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( 'div' ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>
<div id="<?= esc_attr( $uid ) ?>" class="wf-rm wf-rm--<?= esc_attr( $layout ) ?>" style="display:grid;grid-template-columns:repeat(<?= $cols ?>,1fr);gap:<?= 'cards' === $layout ? '16px' : '10px 40px' ?>;">
    <?= $html ?>
</div>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>

<style>
#<?= esc_attr( $uid ) ?> .wf-rm-item{ <?= 'cards' === $layout ? 'border:1px solid #ececec;border-radius:14px;padding:16px 18px;' : 'padding:6px 0;' ?> display:flex;gap:14px; }
#<?= esc_attr( $uid ) ?> .wf-rm-img{ flex:0 0 72px; }
#<?= esc_attr( $uid ) ?> .wf-rm-img img{ width:72px;height:72px;object-fit:cover;border-radius:10px;display:block; }
#<?= esc_attr( $uid ) ?> .wf-rm-body{ flex:1;min-width:0; }
#<?= esc_attr( $uid ) ?> .wf-rm-head{ display:flex;align-items:baseline;gap:8px; }
#<?= esc_attr( $uid ) ?> .wf-rm-name{ font-weight:700;font-size:17px;color:<?= esc_attr( $n_col ) ?>;letter-spacing:-.01em; }
#<?= esc_attr( $uid ) ?> .wf-rm-leader{ flex:1;border-bottom:2px dotted #d5d5d5;transform:translateY(-4px); }
#<?= esc_attr( $uid ) ?> .wf-rm-price{ white-space:nowrap;font-weight:700;font-size:17px;color:<?= esc_attr( $p_col ) ?>; }
#<?= esc_attr( $uid ) ?> .wf-rm-desc{ margin:4px 0 0;font-size:14px;line-height:1.45;color:<?= esc_attr( $d_col ) ?>; }
#<?= esc_attr( $uid ) ?> .wf-rm-badges{ display:flex;flex-wrap:wrap;gap:5px;margin-top:5px; }
#<?= esc_attr( $uid ) ?> .wf-rm-badge{ font-size:11px;font-weight:700;line-height:1;padding:3px 7px;border-radius:100px;letter-spacing:.02em; }
#<?= esc_attr( $uid ) ?> .wf-rm-badge--v{ background:#e6f4ea;color:#0a7d32; }
#<?= esc_attr( $uid ) ?> .wf-rm-badge--spicy{ background:#fde8e8;color:#c0272d; }
#<?= esc_attr( $uid ) ?> .wf-rm-badge--gf{ background:#e7f0fb;color:#2563eb; }
#<?= esc_attr( $uid ) ?> .wf-rm-badge--accent{ background:<?= esc_attr( $accent ) ?>;color:#fff; }
#<?= esc_attr( $uid ) ?> .wf-rm-badge--sold{ background:#eceff1;color:#78838d; }
#<?= esc_attr( $uid ) ?> .wf-rm-sold{ opacity:.55; }
#<?= esc_attr( $uid ) ?> .wf-rm-sold .wf-rm-price{ text-decoration:line-through; }
#<?= esc_attr( $uid ) ?> .wf-rm-cat{ grid-column:1/-1; font-weight:800; font-size:13px; text-transform:uppercase; letter-spacing:.06em; color:<?= esc_attr( $accent ) ?>; margin:14px 0 2px; padding-bottom:5px; border-bottom:2px solid <?= esc_attr( $accent ) ?>22; }
#<?= esc_attr( $uid ) ?> .wf-rm-cat:first-child{ margin-top:0; }
@media (max-width:640px){ #<?= esc_attr( $uid ) ?>{ grid-template-columns:1fr !important; } }
</style>
