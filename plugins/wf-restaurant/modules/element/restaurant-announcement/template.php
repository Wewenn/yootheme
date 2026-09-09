<?php
/**
 * WeFrame – Bandeau annonce | template.php v1.0
 * Mode central : lit le store wf_resto (annonce pilotée depuis « Mon restaurant »).
 */
defined( 'ABSPATH' ) || exit;

$data_source = $props['data_source'] ?? 'central';
$message = '';
$ltext   = '';
$lurl    = '';

if ( 'central' === $data_source && function_exists( 'wf_resto_get' ) ) {
    $store = wf_resto_get();
    $an    = isset( $store['announce'] ) ? $store['announce'] : array();
    if ( empty( $an['active'] ) ) { return; }
    $message = (string) ( $an['message'] ?? '' );
    $ltext   = (string) ( $an['link_text'] ?? '' );
    $lurl    = (string) ( $an['link_url'] ?? '' );
} else {
    $message = (string) ( $props['message'] ?? '' );
    $ltext   = (string) ( $props['link_text'] ?? '' );
    $lurl    = (string) ( $props['link_url'] ?? '' );
}

if ( '' === trim( $message ) ) { return; }

$bg    = $props['bg'] ?? '#111111';
$color = $props['color'] ?? '#ffffff';
$align = $props['align'] ?? 'center';
$dism  = ! empty( $props['dismissible'] );
$jc    = array( 'left' => 'flex-start', 'right' => 'flex-end', 'center' => 'center' );
$just  = $jc[ $align ] ?? 'center';

$uid = 'wfann_' . substr( md5( $message . $lurl . $bg ), 0, 8 );
$key = 'wfann_' . substr( md5( $message . $lurl ), 0, 10 );
?>
<?php $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( 'div' ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>
<div id="<?= esc_attr( $uid ) ?>" class="wf-ann" style="display:flex;align-items:center;justify-content:<?= esc_attr( $just ) ?>;gap:14px;flex-wrap:wrap;background:<?= esc_attr( $bg ) ?>;color:<?= esc_attr( $color ) ?>;padding:12px 18px;border-radius:10px;font-weight:600;line-height:1.4;">
    <span class="wf-ann-msg"><?= esc_html( $message ) ?></span>
    <?php if ( '' !== trim( $ltext ) && '' !== trim( $lurl ) ) : ?>
        <a class="wf-ann-link" href="<?= esc_url( $lurl ) ?>" style="color:<?= esc_attr( $bg ) ?>;background:<?= esc_attr( $color ) ?>;padding:6px 14px;border-radius:100px;text-decoration:none;font-weight:700;white-space:nowrap;"><?= esc_html( $ltext ) ?></a>
    <?php endif; ?>
    <?php if ( $dism ) : ?>
        <button type="button" class="wf-ann-x" aria-label="Fermer" style="margin-left:auto;background:transparent;border:0;color:<?= esc_attr( $color ) ?>;opacity:.7;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
    <?php endif; ?>
</div>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>
<?php if ( $dism ) : ?>
<script>
(function(){
    var el = document.getElementById('<?= esc_js( $uid ) ?>'); if (!el) return;
    var key = '<?= esc_js( $key ) ?>';
    try { if (localStorage.getItem(key) === '1') { el.style.display = 'none'; return; } } catch(e){}
    var x = el.querySelector('.wf-ann-x'); if (!x) return;
    x.addEventListener('click', function(){
        el.style.display = 'none';
        try { localStorage.setItem(key, '1'); } catch(e){}
    });
})();
</script>
<?php endif; ?>
