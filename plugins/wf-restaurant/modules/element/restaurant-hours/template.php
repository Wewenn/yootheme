<?php
/**
 * WeFrame – Horaires Restaurant | template.php v2
 */
defined( 'ABSPATH' ) || exit;

// ── Helper functions declared first (guards prevent double-declaration) ──────

if ( ! function_exists( 'wf_rh_hm' ) ) {
    function wf_rh_hm( string $s ): ?int {
        if ( ! $s || strpos( $s, ':' ) === false ) { return null; }
        $p = explode( ':', $s );
        return (int) $p[0] * 60 + (int) $p[1];
    }
}

if ( ! function_exists( 'wf_rh_fmt' ) ) {
    function wf_rh_fmt( int $minutes, string $fmt = '24h' ): string {
        $h = intdiv( $minutes, 60 );
        $m = $minutes % 60;
        if ( $fmt === '12h' ) {
            $period = $h >= 12 ? 'PM' : 'AM';
            $h12    = $h > 12 ? $h - 12 : ( $h === 0 ? 12 : $h );
            return sprintf( '%d:%02d %s', $h12, $m, $period );
        }
        return sprintf( '%02dh%02d', $h, $m );
    }
}

if ( ! function_exists( 'wf_rh_table_html' ) ) {
    function wf_rh_table_html( string $uid, array $schedule, int $day_idx, int $tbl_fs, int $tbl_gap, string $tbl_closed, string $tbl_sep, bool $tbl_hl, string $tbl_tbg, string $tbl_tc, string $tbl_tw, string $tbl_lc, string $tbl_tic, string $time_fmt ): string {
        $day_order  = [ 1, 2, 3, 4, 5, 6, 0 ]; // Mon → Sun
        $day_labels = [ 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi' ];

        $out = '<div id="' . esc_attr( $uid ) . '_t" class="wf-rh-table" style="display:flex;flex-direction:column;gap:' . $tbl_gap . 'px;font-size:' . $tbl_fs . 'px;width:100%;">';

        foreach ( $day_order as $di ) {
            $is_today = ( $di === $day_idx );
            $row_bg   = ( $is_today && $tbl_hl && $tbl_tbg ) ? 'background:' . esc_attr( $tbl_tbg ) . ';' : '';
            $row_tc   = ( $is_today && $tbl_hl && $tbl_tc  ) ? 'color:' . esc_attr( $tbl_tc )  . ';' : '';
            $lbl_w    = ( $is_today && $tbl_hl ) ? $tbl_tw : '400';
            $lbl_c    = $tbl_lc  ? 'color:' . esc_attr( $tbl_lc )  . ';' : '';
            $tic_css  = $tbl_tic ? 'color:' . esc_attr( $tbl_tic ) . ';' : '';

            $slots = $schedule[ $di ] ?? [];
            $times = [];
            foreach ( $slots as $slot ) {
                $o = wf_rh_hm( $slot[0] ?? '' );
                $c = wf_rh_hm( $slot[1] ?? '' );
                if ( $o !== null && $c !== null ) {
                    $times[] = wf_rh_fmt( $o, $time_fmt ) . ' – ' . wf_rh_fmt( $c, $time_fmt );
                }
            }

            $out .= '<div class="wf-rh-row' . ( $is_today ? ' wf-rh-today' : '' ) . '" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:8px;padding:3px 8px;border-radius:4px;' . $row_bg . $row_tc . '">';
            $out .= '<span class="wf-rh-day-label" style="font-weight:' . esc_attr( $lbl_w ) . ';' . $lbl_c . '">' . esc_html( $day_labels[ $di ] ) . '</span>';

            if ( empty( $times ) ) {
                $out .= '<span class="wf-rh-closed-text" style="opacity:.4;' . $tic_css . '">' . esc_html( $tbl_closed ) . '</span>';
            } else {
                $sep_html   = ' <span style="opacity:.35;">' . esc_html( $tbl_sep ) . '</span> ';
                $times_html = implode( $sep_html, array_map( 'esc_html', $times ) );
                $out .= '<span class="wf-rh-day-times" style="' . $tic_css . '">' . $times_html . '</span>';
            }

            $out .= '</div>';
        }

        $out .= '</div>';
        return $out;
    }
}

// ── Props ────────────────────────────────────────────────────────

$tz          = $props['timezone']          ?? 'Europe/Paris';
$txt_open    = $props['text_open']         ?? 'Actuellement ouvert';
$txt_closed  = $props['text_closed']       ?? 'Actuellement fermé';
$txt_ca      = $props['text_closes_at']    ?? 'Ferme à';
$txt_oa      = $props['text_opens_at']     ?? 'Ouvre à';
$txt_tom     = $props['text_tomorrow']     ?? 'Ouvre demain à';
$show_next   = ! empty( $props['show_next'] );
$alignment   = $props['alignment']         ?? 'center';
$separator   = $props['separator']         ?? '–';
$show_day    = ! empty( $props['show_day'] );
$txt_today   = $props['text_today']        ?? 'Aujourd\'hui';
$time_fmt    = $props['time_format']       ?? '24h';

$style       = $props['style']             ?? 'pill';
$bg_open     = $props['bg_open']           ?? 'rgba(34,197,94,0.15)';
$bg_closed   = $props['bg_closed']         ?? 'rgba(239,68,68,0.15)';
$tc_open     = $props['text_color_open']   ?? '#16a34a';
$tc_closed   = $props['text_color_closed'] ?? '#dc2626';
$dot_open    = $props['dot_color_open']    ?? '#22c55e';
$dot_closed  = $props['dot_color_closed']  ?? '#ef4444';
$dot_sz      = max( 4, (int)( $props['dot_size']         ?? 10 ) );
$fs          = max( 10, (int)( $props['font_size']        ?? 14 ) );
$fs_mob      = max( 10, (int)( $props['font_size_mobile'] ?? 12 ) );
$fw          = $props['font_weight']       ?? '600';
$rad         = (int)( $props['radius']      ?? 100 );
$ph          = (int)( $props['padding_h']   ?? 20 );
$pv          = (int)( $props['padding_v']   ?? 10 );
$max_w       = max( 0, (int)( $props['max_width'] ?? 0 ) );
$wrap_mob    = ! empty( $props['wrap_mobile'] );
$mob_layout  = $props['mobile_layout']   ?? 'inline';
$hide_next_m = ! empty( $props['hide_next_mobile'] );
$mob_full    = ! empty( $props['mobile_full_width'] );
$ph_mob      = max( 0, (int)( $props['padding_h_mobile'] ?? 0 ) );
$pv_mob      = max( 0, (int)( $props['padding_v_mobile'] ?? 0 ) );
$show_border = ! empty( $props['show_border'] );
$bw          = max( 1, (int)( $props['border_width']  ?? 1 ) );
$border_o    = $props['border_open']       ?? '#22c55e';
$border_c    = $props['border_closed']     ?? '#ef4444';
$blur        = (int)( $props['backdrop_blur'] ?? 0 );
$bg_img      = $props['bg_image']          ?? '';

$show_table  = ! empty( $props['show_table'] );
$table_pos   = $props['table_position']    ?? 'below';
$show_badge  = $table_pos !== 'only';
$tbl_fs      = max( 10, (int)( $props['table_font_size']     ?? 13 ) );
$tbl_gap     = max( 0,  (int)( $props['table_row_gap']       ?? 6 ) );
$tbl_closed  = $props['table_closed_text'] ?? 'Fermé';
$tbl_sep     = $props['table_sep']         ?? '|';
$tbl_hl      = ! empty( $props['table_highlight_today'] );
$tbl_tbg     = trim( $props['table_today_bg']    ?? 'rgba(255,255,255,0.08)' );
$tbl_tc      = trim( $props['table_today_color'] ?? '' );
$tbl_tw      = $props['table_today_weight'] ?? '700';
$tbl_lc      = trim( $props['table_label_color'] ?? '' );
$tbl_tic     = trim( $props['table_time_color']  ?? '' );

// ── Build schedule ───────────────────────────────────────────────

$days_keys = [ 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ];
$schedule  = [];
foreach ( $days_keys as $d ) {
    $enabled    = ! empty( $props[ $d . '_enabled' ] );
    $schedule[] = $enabled ? [
        [ $props[ $d . '_lunch_open' ]  ?? '', $props[ $d . '_lunch_close' ]  ?? '' ],
        [ $props[ $d . '_dinner_open' ] ?? '', $props[ $d . '_dinner_close' ] ?? '' ],
    ] : [];
}

// ── Source centrale (réglages restaurant, pilotable au téléphone) ─
$data_source = $props['data_source'] ?? 'inline';
$force_state = null;   // null | true (ouvert forcé) | false (fermé forcé)
$exc_active  = false;
$exc_from    = '';
$exc_to      = '';
$exc_msg     = '';
if ( $data_source === 'central' && function_exists( 'wf_resto_get' ) ) {
    $store = wf_resto_get();
    if ( ! empty( $store['tz'] ) ) { $tz = $store['tz']; }
    $schedule = [];
    foreach ( $days_keys as $d ) {
        $row     = isset( $store['days'][ $d ] ) ? $store['days'][ $d ] : [];
        $enabled = ! empty( $row['enabled'] );
        $schedule[] = $enabled ? [
            [ $row['lunch_open']  ?? '', $row['lunch_close']  ?? '' ],
            [ $row['dinner_open'] ?? '', $row['dinner_close'] ?? '' ],
        ] : [];
    }
    $ov = $store['override'] ?? 'auto';
    if ( 'open' === $ov )   { $force_state = true; }
    if ( 'closed' === $ov ) { $force_state = false; }
    $exc = isset( $store['exception'] ) ? $store['exception'] : [];
    if ( ! empty( $exc['active'] ) ) {
        $exc_active = true;
        $exc_from   = $exc['from']    ?? '';
        $exc_to     = $exc['to']      ?? '';
        $exc_msg    = $exc['message'] ?? '';
    }
}

// ── PHP: état courant ────────────────────────────────────────────

// Guard: an empty/invalid timezone string makes new DateTimeZone() throw
// an uncaught exception → site-wide PHP fatal. Validate and fall back.
try {
    $tz_obj = new DateTimeZone( $tz ?: 'Europe/Paris' );
} catch ( \Exception $e ) {
    $tz_obj = new DateTimeZone( 'Europe/Paris' );
}
$now_dt  = new DateTime( 'now', $tz_obj );
$day_idx = (int) $now_dt->format( 'w' );
$now_min = (int) $now_dt->format( 'G' ) * 60 + (int) $now_dt->format( 'i' );

$is_open   = false;
$closes_at = null;
foreach ( $schedule[ $day_idx ] ?? [] as $slot ) {
    $o = wf_rh_hm( $slot[0] ?? '' );
    $c = wf_rh_hm( $slot[1] ?? '' );
    if ( $o !== null && $c !== null && $now_min >= $o && $now_min < $c ) {
        $is_open = true; $closes_at = $c; break;
    }
}

// Fermeture exceptionnelle (par dates) puis override manuel — source centrale
$in_exc = false;
if ( $exc_active ) {
    $today_ymd = $now_dt->format( 'Y-m-d' );
    $ge = ( '' === $exc_from || $today_ymd >= $exc_from );
    $le = ( '' === $exc_to   || $today_ymd <= $exc_to );
    $in_exc = $ge && $le;
}
if ( $in_exc ) {
    $is_open = false; $closes_at = null;
    if ( '' !== $exc_msg ) { $txt_closed = $exc_msg; }
} elseif ( true === $force_state ) {
    $is_open = true;
} elseif ( false === $force_state ) {
    $is_open = false; $closes_at = null;
}

// ── Prochaine heure ──────────────────────────────────────────────

$day_names = [ 'dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi' ];
$next_text = '';
if ( $show_next ) {
    if ( $is_open && $closes_at !== null ) {
        $next_text = $txt_ca . ' ' . wf_rh_fmt( $closes_at, $time_fmt );
    } else {
        foreach ( $schedule[ $day_idx ] ?? [] as $slot ) {
            $o = wf_rh_hm( $slot[0] ?? '' );
            if ( $o !== null && $o > $now_min ) { $next_text = $txt_oa . ' ' . wf_rh_fmt( $o, $time_fmt ); break; }
        }
        if ( ! $next_text ) {
            for ( $dd = 1; $dd <= 7; $dd++ ) {
                $di = ( $day_idx + $dd ) % 7;
                foreach ( $schedule[ $di ] ?? [] as $slot ) {
                    $o = wf_rh_hm( $slot[0] ?? '' );
                    if ( $o !== null ) {
                        $lbl       = $dd === 1 ? $txt_tom : 'Ouvre ' . $day_names[ $di ] . ' à';
                        $next_text = $lbl . ' ' . wf_rh_fmt( $o, $time_fmt );
                        break 2;
                    }
                }
            }
        }
    }
}

if ( $in_exc ) { $next_text = ''; }

// ── Badge computed values ────────────────────────────────────────

$init_bg  = $is_open ? $bg_open  : $bg_closed;
$init_tc  = $is_open ? $tc_open  : $tc_closed;
$init_dot = $is_open ? $dot_open : $dot_closed;
$init_brd = $is_open ? $border_o : $border_c;
$init_lbl = $is_open ? $txt_open : $txt_closed;

$jc_map    = [ 'left' => 'flex-start', 'right' => 'flex-end', 'center' => 'center' ];
$jc        = $jc_map[ $alignment ] ?? 'center';
$blur_css  = $blur > 0 ? "backdrop-filter:blur({$blur}px);-webkit-backdrop-filter:blur({$blur}px);" : '';
$bar_css   = $style === 'bar' ? "width:100%;justify-content:{$jc};" : '';
$maxw_css  = $max_w > 0 ? "max-width:{$max_w}px;" : '';
$brd_css   = $show_border ? "border:{$bw}px solid " . esc_attr( $init_brd ) . ";" : 'border:0;';

$bg_img_css = '';
if ( $bg_img ) {
    if ( ! preg_match( '#^https?://#i', $bg_img ) ) { $bg_img = '/' . ltrim( $bg_img, '/' ); }
    $bg_img_css = 'background-image:url(' . esc_url( $bg_img ) . ');background-size:cover;background-position:center;';
}

// ── UID & JS config ──────────────────────────────────────────────

$uid = 'wfrh_' . substr( md5( serialize( $props ) ), 0, 8 );

$cfg = json_encode( [
    'uid'         => $uid,
    'tz'          => $tz,
    'schedule'    => $schedule,
    'txtOpen'     => $txt_open,
    'txtClosed'   => $txt_closed,
    'txtCA'       => $txt_ca,
    'txtOA'       => $txt_oa,
    'txtTom'      => $txt_tom,
    'showNext'    => $show_next,
    'timeFormat'  => $time_fmt,
    'dayNames'    => $day_names,
    'bgOpen'      => $bg_open,
    'bgClosed'    => $bg_closed,
    'tcOpen'      => $tc_open,
    'tcClosed'    => $tc_closed,
    'dotOpen'     => $dot_open,
    'dotClosed'   => $dot_closed,
    'showBorder'  => $show_border,
    'borderWidth' => $bw,
    'borderOpen'  => $border_o,
    'borderClosed'=> $border_c,
    'force'       => true === $force_state ? 'open' : ( false === $force_state ? 'closed' : null ),
    'exc'         => $exc_active ? [ 'from' => $exc_from, 'to' => $exc_to, 'msg' => $exc_msg ] : null,
], JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE );
?>
<?php $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( 'div' ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>

<?php if ( $show_table && $table_pos === 'above' ) : ?>
<?= wf_rh_table_html( $uid, $schedule, $day_idx, $tbl_fs, $tbl_gap, $tbl_closed, $tbl_sep, $tbl_hl, $tbl_tbg, $tbl_tc, $tbl_tw, $tbl_lc, $tbl_tic, $time_fmt ) ?>
<?php endif; ?>

<?php if ( $show_badge ) : ?>
<div class="wf-rh-align" style="display:flex;justify-content:<?= esc_attr( $jc ) ?>;width:100%;">
<div id="<?= esc_attr( $uid ) ?>" class="wf-rh wf-rh--<?= esc_attr( $style ) ?>" style="display:inline-flex;align-items:center;gap:10px;padding:<?= $pv ?>px <?= $ph ?>px;border-radius:<?= $rad ?>px;font-size:<?= $fs ?>px;font-weight:<?= esc_attr( $fw ) ?>;line-height:1.4;background:<?= esc_attr( $init_bg ) ?>;color:<?= esc_attr( $init_tc ) ?>;<?= $brd_css ?><?= $blur_css ?><?= $bg_img_css ?><?= $bar_css ?><?= $maxw_css ?>transition:background .3s,color .3s,border-color .3s;box-sizing:border-box;">
    <span class="wf-rh-main" style="display:inline-flex;align-items:center;gap:10px;">
        <span class="wf-rh-dot" style="width:<?= $dot_sz ?>px;height:<?= $dot_sz ?>px;border-radius:50%;flex-shrink:0;background:<?= esc_attr( $init_dot ) ?>;animation:wf-rh-pulse 2s ease-in-out infinite;"></span>
        <?php if ( $show_day ) : ?>
        <span class="wf-rh-prefix" style="opacity:.65;"><?= esc_html( $txt_today ) ?> :</span>
        <?php endif; ?>
        <span class="wf-rh-status"><?= esc_html( $init_lbl ) ?></span>
    </span>
    <?php if ( $show_next ) : ?>
    <span class="wf-rh-sep" style="opacity:.4;<?= ! $next_text ? 'display:none;' : '' ?>"><?= esc_html( $separator ) ?></span>
    <span class="wf-rh-next"><?= esc_html( $next_text ) ?></span>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>

<?php if ( $show_table && in_array( $table_pos, [ 'below', 'only' ], true ) ) : ?>
<?= wf_rh_table_html( $uid, $schedule, $day_idx, $tbl_fs, $tbl_gap, $tbl_closed, $tbl_sep, $tbl_hl, $tbl_tbg, $tbl_tc, $tbl_tw, $tbl_lc, $tbl_tic, $time_fmt ) ?>
<?php endif; ?>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>

<style>
@keyframes wf-rh-pulse{0%,100%{opacity:1}50%{opacity:.45}}
<?php if ( $fs_mob !== $fs ) : ?>
@media(max-width:767px){
    #<?= esc_attr( $uid ) ?> { font-size: <?= $fs_mob ?>px !important; }
}
<?php endif; ?>
<?php if ( $wrap_mob ) : ?>
@media(max-width:767px){
    #<?= esc_attr( $uid ) ?> { flex-wrap: wrap !important; justify-content: center !important; }
}
<?php endif; ?>
<?php if ( 'stack' === $mob_layout ) : ?>
@media(max-width:767px){
    #<?= esc_attr( $uid ) ?> { flex-direction: column !important; align-items: center !important; gap: 3px !important; }
    #<?= esc_attr( $uid ) ?> .wf-rh-sep { display: none !important; }
    #<?= esc_attr( $uid ) ?> .wf-rh-next { opacity: .8; font-weight: 400; }
}
<?php endif; ?>
<?php if ( $hide_next_m ) : ?>
@media(max-width:767px){
    #<?= esc_attr( $uid ) ?> .wf-rh-sep, #<?= esc_attr( $uid ) ?> .wf-rh-next { display: none !important; }
}
<?php endif; ?>
<?php if ( $mob_full ) : ?>
@media(max-width:767px){
    #<?= esc_attr( $uid ) ?> { width: 100% !important; max-width: 100% !important; justify-content: center !important; }
}
<?php endif; ?>
<?php if ( $ph_mob > 0 || $pv_mob > 0 ) : ?>
@media(max-width:767px){
    #<?= esc_attr( $uid ) ?> { padding: <?= $pv_mob > 0 ? $pv_mob : $pv ?>px <?= $ph_mob > 0 ? $ph_mob : $ph ?>px !important; }
}
<?php endif; ?>
<?php if ( $style !== 'bar' ) : ?>
@media(max-width:480px){
    #<?= esc_attr( $uid ) ?> { max-width: 100% !important; box-sizing: border-box; }
}
<?php endif; ?>
</style>

<?php if ( $show_badge ) : ?>
<script>
(function(){
    var c = <?= $cfg ?>;
    var el     = document.getElementById(c.uid); if (!el) return;
    var dot    = el.querySelector('.wf-rh-dot');
    var status = el.querySelector('.wf-rh-status');
    var next   = el.querySelector('.wf-rh-next');
    var sep    = el.querySelector('.wf-rh-sep');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function hm(s) {
        if (!s) return null;
        var p = s.split(':');
        return p.length < 2 ? null : parseInt(p[0], 10) * 60 + parseInt(p[1], 10);
    }

    function fmtH(m) {
        var h = Math.floor(m / 60), mn = m % 60;
        if (c.timeFormat === '12h') {
            var period = h >= 12 ? 'PM' : 'AM';
            var h12    = h > 12 ? h - 12 : (h === 0 ? 12 : h);
            return h12 + ':' + pad(mn) + ' ' + period;
        }
        return pad(h) + 'h' + pad(mn);
    }

    function update() {
        var now = new Date();
        var hour = 0, minute = 0, dayIdx = now.getDay();
        try {
            var tzDate = new Date(now.toLocaleString('en-US', { timeZone: c.tz, hour12: false }));
            hour = tzDate.getHours(); minute = tzDate.getMinutes(); dayIdx = tzDate.getDay();
        } catch(e) {}

        var nowMin = hour * 60 + minute;
        var slots  = c.schedule[dayIdx] || [];
        var isOpen = false, closesAt = null;

        for (var i = 0; i < slots.length; i++) {
            var o = hm(slots[i][0]), cl = hm(slots[i][1]);
            if (o !== null) if (cl !== null) if (nowMin >= o) if (nowMin < cl) {
                isOpen = true; closesAt = cl; break;
            }
        }

        // Source centrale : fermeture exceptionnelle (dates) puis override manuel
        var forced = null, forcedMsg = '';
        if (c.exc) {
            var ld = now;
            try { ld = new Date(now.toLocaleString('en-US', { timeZone: c.tz })); } catch(e) {}
            var ymd = ld.getFullYear() + '-' + pad(ld.getMonth() + 1) + '-' + pad(ld.getDate());
            var geOk = (!c.exc.from); if (!geOk) geOk = (ymd >= c.exc.from);
            var leOk = (!c.exc.to);   if (!leOk) leOk = (ymd <= c.exc.to);
            if (geOk) if (leOk) { forced = 'closed'; forcedMsg = c.exc.msg || ''; }
        }
        if (forced === null) if (c.force) forced = c.force;
        if (forced === 'open')   { isOpen = true;  closesAt = null; }
        if (forced === 'closed') { isOpen = false; closesAt = null; }

        el.style.background  = isOpen ? c.bgOpen  : c.bgClosed;
        el.style.color       = isOpen ? c.tcOpen  : c.tcClosed;
        dot.style.background = isOpen ? c.dotOpen : c.dotClosed;
        if (c.showBorder) {
            el.style.borderColor = isOpen ? c.borderOpen : c.borderClosed;
        }
        status.textContent = isOpen ? c.txtOpen : c.txtClosed;

        if (forced === 'closed') if (forcedMsg) {
            status.textContent = forcedMsg;
            if (next) next.textContent = '';
            if (sep) sep.style.display = 'none';
            return;
        }

        if (!next || !c.showNext) return;

        var nextTxt = '';
        if (isOpen) {
            nextTxt = c.txtCA + ' ' + fmtH(closesAt);
        } else {
            var found = false;
            for (var j = 0; j < slots.length; j++) {
                var oMin = hm(slots[j][0]);
                if (oMin !== null) if (oMin > nowMin) { nextTxt = c.txtOA + ' ' + fmtH(oMin); found = true; break; }
            }
            if (!found) {
                for (var d = 1; d <= 7; d++) {
                    var di = (dayIdx + d) % 7;
                    var ds = c.schedule[di] || [];
                    for (var k = 0; k < ds.length; k++) {
                        var o2 = hm(ds[k][0]);
                        if (o2 !== null) {
                            var lbl = d === 1 ? c.txtTom : 'Ouvre ' + c.dayNames[di] + ' à';
                            nextTxt = lbl + ' ' + fmtH(o2);
                            found = true; break;
                        }
                    }
                    if (found) break;
                }
            }
        }

        next.textContent = nextTxt;
        if (sep) sep.style.display = nextTxt ? '' : 'none';
    }

    update();
    setInterval(update, 60000);
})();
</script>
<?php endif; ?>
