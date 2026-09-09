<?php
/**
 * WeFrame – Réservation de table | template.php v1.1
 * Formulaire front -> créneaux dynamiques (capacité par service) -> AJAX wf_resa_submit.
 */
defined( 'ABSPATH' ) || exit;

$title   = (string) ( $props['title'] ?? 'Réserver une table' );
$intro   = (string) ( $props['intro'] ?? '' );
$btn     = (string) ( $props['button_label'] ?? 'Envoyer la demande' );
$accent  = $props['accent'] ?? '#c0272d';
$show_em = ! empty( $props['show_email'] );
$show_no = ! empty( $props['show_note'] );
$maxp    = max( 1, (int) ( $props['max_party'] ?? 20 ) );
$dmode   = ( ( $props['date_mode'] ?? 'strip' ) === 'calendar' ) ? 'calendar' : 'strip';
$fbg     = $props['field_bg'] ?? '#ffffff';
$fbd     = $props['field_border'] ?? '#d5d5d5';
$ftx     = $props['field_text'] ?? '#111111';
$lcol    = $props['label_color'] ?? '#333333';
$btxt    = $props['button_text'] ?? '#ffffff';
$rad     = max( 0, (int) ( $props['radius'] ?? 10 ) );
$dbg     = $props['day_bg'] ?? '#f4f4f5';
$dtx     = $props['day_text'] ?? '#111111';
$sbg     = $props['slot_bg'] ?? '#ffffff';
$sbd     = $props['slot_border'] ?? '#d5d5d5';
$stx     = $props['slot_text'] ?? '#111111';
$srad    = max( 0, (int) ( $props['slot_radius'] ?? 100 ) );
$tstyle  = trim( (string) ( $props['title_style'] ?? '' ) );

// « split » : calendrier et créneaux côte à côte, comme un écran de prise de
// rendez-vous. Réservé au mode calendrier — la bande de jours n'a pas de sens
// dans une colonne étroite.
$layout  = ( ( $props['layout'] ?? 'compact' ) === 'split' ) ? 'split' : 'compact';
if ( 'calendar' !== ( $props['date_mode'] ?? 'strip' ) ) { $layout = 'compact'; }
$maxw    = 'split' === $layout ? 880 : 520;

$store      = function_exists( 'wf_resto_get' ) ? wf_resto_get() : array();
$resa_on    = ! empty( $store['resa']['enabled'] );
$days_ahead = isset( $store['resa']['days_ahead'] ) ? (int) $store['resa']['days_ahead'] : 30;

$uid     = 'wfresa_' . substr( md5( serialize( $props ) ), 0, 8 );
$today   = current_time( 'Y-m-d' );
$maxdate = date( 'Y-m-d', strtotime( $today . ' +' . $days_ahead . ' days' ) );
$nonce   = wp_create_nonce( 'wf_resa' );
$ajax    = admin_url( 'admin-ajax.php' );

// Jours OUVERTS à la réservation (prochains jours) = horaires + hors exception + hors blocage.
$dayKeys   = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
$dayLabels = array( 'mon' => 'Lun', 'tue' => 'Mar', 'wed' => 'Mer', 'thu' => 'Jeu', 'fri' => 'Ven', 'sat' => 'Sam', 'sun' => 'Dim' );
$moLabels  = array( 1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.' );
$blocked   = isset( $store['resa']['blocked'] ) ? (array) $store['resa']['blocked'] : array();
$exc       = isset( $store['exception'] ) ? $store['exception'] : array();
$openDays  = array();
$openSet   = array();
$scan      = min( 90, max( 1, $days_ahead ) );
for ( $off = 0; $off <= $scan; $off++ ) {
	$ts  = strtotime( $today . ' +' . $off . ' days' );
	$dt  = date( 'Y-m-d', $ts );
	$key = $dayKeys[ (int) date( 'N', $ts ) - 1 ];
	$row = isset( $store['days'][ $key ] ) ? $store['days'][ $key ] : array();
	$hasWin = ! empty( $row['enabled'] ) && ( ( ! empty( $row['lunch_open'] ) && ! empty( $row['lunch_close'] ) ) || ( ! empty( $row['dinner_open'] ) && ! empty( $row['dinner_close'] ) ) );
	if ( ! $hasWin ) { continue; }
	if ( in_array( $dt, $blocked, true ) ) { continue; }
	if ( ! empty( $exc['active'] ) ) {
		$ge = ( '' === ( $exc['from'] ?? '' ) || $dt >= $exc['from'] );
		$le = ( '' === ( $exc['to'] ?? '' ) || $dt <= $exc['to'] );
		if ( $ge && $le ) { continue; }
	}
	$openSet[] = $dt;
	if ( count( $openDays ) < 14 ) {
		$openDays[] = array( 'date' => $dt, 'lbl' => $dayLabels[ $key ], 'd' => (int) date( 'j', $ts ), 'mo' => $moLabels[ (int) date( 'n', $ts ) ] );
	}
}
?>
<?php $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( 'div' ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>
<div id="<?= esc_attr( $uid ) ?>" class="wf-resa wf-resa--<?= esc_attr( $layout ) ?>" style="max-width:<?= (int) $maxw ?>px;">
    <?php if ( '' !== $title ) : ?><h3 class="wf-resa-title<?= $tstyle ? ' ' . esc_attr( $tstyle ) : '' ?>"<?= $tstyle ? '' : ' style="margin:0 0 6px;font-size:22px;"' ?>><?= esc_html( $title ) ?></h3><?php endif; ?>
    <?php if ( '' !== $intro ) : ?><p class="wf-resa-intro" style="margin:0 0 16px;color:#555;line-height:1.5;"><?= esc_html( $intro ) ?></p><?php endif; ?>

    <form class="wf-resa-form" novalidate>
        <div class="wf-resa-grid2">
            <label>Prénom<input type="text" name="prenom" required></label>
            <label>Nom<input type="text" name="nom" required></label>
        </div>
        <label>Téléphone<input type="tel" name="tel" required inputmode="tel" placeholder="06 12 34 56 78"></label>
        <label>Personnes<input type="number" name="couverts" min="1" max="<?= (int) $maxp ?>" value="2" required></label>
        <div class="wf-resa-pick">
        <div class="wf-resa-dayfield">
            <span class="wf-resa-slabel">Jour</span>
            <?php if ( 'calendar' === $dmode && ! empty( $openSet ) ) : ?>
            <div class="wf-resa-cal" data-open="<?= esc_attr( wp_json_encode( array_values( $openSet ) ) ) ?>" data-min="<?= esc_attr( $today ) ?>" data-max="<?= esc_attr( $maxdate ) ?>"></div>
            <?php elseif ( ! empty( $openDays ) ) : ?>
            <div class="wf-resa-days">
                <?php foreach ( $openDays as $od ) : ?>
                    <button type="button" class="wf-resa-day" data-date="<?= esc_attr( $od['date'] ) ?>"><span class="wf-resa-day-l"><?= esc_html( $od['lbl'] ) ?></span><span class="wf-resa-day-n"><?= (int) $od['d'] ?></span><span class="wf-resa-day-m"><?= esc_html( $od['mo'] ) ?></span></button>
                <?php endforeach; ?>
            </div>
            <details class="wf-resa-other"><summary>Autre date…</summary><input type="date" class="wf-resa-datefar" min="<?= esc_attr( $today ) ?>" max="<?= esc_attr( $maxdate ) ?>"></details>
            <?php else : ?>
            <input type="date" class="wf-resa-datefar" min="<?= esc_attr( $today ) ?>" max="<?= esc_attr( $maxdate ) ?>" required>
            <?php endif; ?>
            <input type="hidden" name="date" value="">
        </div>

        <?php if ( $resa_on ) : ?>
        <div class="wf-resa-slotwrap">
            <span class="wf-resa-slabel">Créneau</span>
            <div class="wf-resa-slots" aria-live="polite"><p class="wf-resa-hint">Choisissez le jour et le nombre de personnes pour voir les créneaux.</p></div>
        </div>
        <input type="hidden" name="heure" value="">
        <input type="hidden" name="service" value="">
        <?php else : ?>
        <label>Heure<input type="time" name="heure" step="900" required></label>
        <?php endif; ?>
        </div>


        <?php if ( $show_em ) : ?><label>E-mail<input type="email" name="email" placeholder="vous@exemple.fr"></label><?php endif; ?>
        <?php if ( $show_no ) : ?><label>Précision (optionnel)<input type="text" name="note" maxlength="140" placeholder="Allergies, anniversaire, terrasse…"></label><?php endif; ?>
        <input type="text" name="company" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;" aria-hidden="true">
        <button type="submit" class="wf-resa-btn"<?= $resa_on ? ' disabled' : '' ?>><?= esc_html( $btn ) ?></button>
        <p class="wf-resa-msg" role="status" style="display:none;margin:12px 0 0;"></p>
    </form>
</div>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>

<style>
#<?= esc_attr( $uid ) ?> .wf-resa-form{display:flex;flex-direction:column;gap:12px}
#<?= esc_attr( $uid ) ?> .wf-resa-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
#<?= esc_attr( $uid ) ?> label{display:flex;flex-direction:column;gap:5px;font-size:13px;font-weight:600;color:#333}
#<?= esc_attr( $uid ) ?> input{padding:11px 12px;border:1px solid #d5d5d5;border-radius:10px;font-size:16px;font-weight:400;color:#111;background:#fff}
#<?= esc_attr( $uid ) ?> input:focus{outline:none;border-color:<?= esc_attr( $accent ) ?>;box-shadow:0 0 0 3px <?= esc_attr( $accent ) ?>22}
#<?= esc_attr( $uid ) ?> .wf-resa-slabel{font-size:13px;font-weight:600;color:#333;display:block;margin-bottom:6px}
#<?= esc_attr( $uid ) ?> .wf-resa-dayfield{display:flex;flex-direction:column;gap:4px}
#<?= esc_attr( $uid ) ?> .wf-resa-days{display:flex;gap:8px;overflow-x:auto;padding:2px 0 6px;-webkit-overflow-scrolling:touch;scrollbar-width:thin}
#<?= esc_attr( $uid ) ?> .wf-resa-day{flex:0 0 auto;display:flex;flex-direction:column;align-items:center;gap:1px;min-width:58px;padding:8px 10px;border:1px solid #d5d5d5;border-radius:12px;background:#fff;cursor:pointer;color:#111;line-height:1.1}
#<?= esc_attr( $uid ) ?> .wf-resa-day-l{font-size:11px;font-weight:700;color:#888;text-transform:uppercase}
#<?= esc_attr( $uid ) ?> .wf-resa-day-n{font-size:20px;font-weight:800}
#<?= esc_attr( $uid ) ?> .wf-resa-day-m{font-size:10px;color:#888}
#<?= esc_attr( $uid ) ?> .wf-resa-day.sel{background:<?= esc_attr( $accent ) ?>;border-color:<?= esc_attr( $accent ) ?>;color:#fff}
#<?= esc_attr( $uid ) ?> .wf-resa-day.sel .wf-resa-day-l,#<?= esc_attr( $uid ) ?> .wf-resa-day.sel .wf-resa-day-m{color:rgba(255,255,255,.85)}
#<?= esc_attr( $uid ) ?> .wf-resa-other summary{font-size:13px;color:#666;cursor:pointer;list-style:none}
#<?= esc_attr( $uid ) ?> .wf-resa-datefar{margin-top:6px}
#<?= esc_attr( $uid ) ?> .wf-resa-hint{margin:0;color:#888;font-size:14px}
#<?= esc_attr( $uid ) ?> .wf-resa-svc{margin:8px 0 4px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#888}
#<?= esc_attr( $uid ) ?> .wf-resa-slotrow{display:flex;flex-wrap:wrap;gap:8px}
#<?= esc_attr( $uid ) ?> .wf-resa-slot{padding:9px 14px;border:1px solid #d5d5d5;border-radius:100px;background:#fff;font-size:15px;font-weight:600;cursor:pointer;color:#111}
#<?= esc_attr( $uid ) ?> .wf-resa-slot small{display:block;font-size:10px;font-weight:500;color:#c47f0a;margin-top:1px}
#<?= esc_attr( $uid ) ?> .wf-resa-slot.sel{background:<?= esc_attr( $accent ) ?>;border-color:<?= esc_attr( $accent ) ?>;color:#fff}
#<?= esc_attr( $uid ) ?> .wf-resa-slot.sel small{color:#fff}
#<?= esc_attr( $uid ) ?> .wf-resa-btn{margin-top:4px;padding:14px;border:0;border-radius:12px;background:<?= esc_attr( $accent ) ?>;color:#fff;font-size:16px;font-weight:700;cursor:pointer}
#<?= esc_attr( $uid ) ?> .wf-resa-btn[disabled]{opacity:.5;cursor:not-allowed}
#<?= esc_attr( $uid ) ?> .wf-resa-msg.ok{color:#0a7d32;font-weight:600}
#<?= esc_attr( $uid ) ?> .wf-resa-msg.err{color:#b32d2e;font-weight:600}
@media(max-width:520px){#<?= esc_attr( $uid ) ?> .wf-resa-grid2{grid-template-columns:1fr}}
/* Design du formulaire (surcharge) */
#<?= esc_attr( $uid ) ?> .wf-resa-title{margin:0 0 6px}
#<?= esc_attr( $uid ) ?> input,#<?= esc_attr( $uid ) ?> textarea,#<?= esc_attr( $uid ) ?> select{background:<?= esc_attr( $fbg ) ?>;border-color:<?= esc_attr( $fbd ) ?>;color:<?= esc_attr( $ftx ) ?>;border-radius:<?= $rad ?>px}
#<?= esc_attr( $uid ) ?> label{color:<?= esc_attr( $lcol ) ?>}
#<?= esc_attr( $uid ) ?> .wf-resa-btn{color:<?= esc_attr( $btxt ) ?>;border-radius:<?= $rad + 2 ?>px}
#<?= esc_attr( $uid ) ?> .wf-resa-day{border-radius:<?= $rad ?>px;background:<?= esc_attr( $dbg ) ?>;color:<?= esc_attr( $dtx ) ?>}
#<?= esc_attr( $uid ) ?> .wf-resa-day-n{color:<?= esc_attr( $dtx ) ?>}
#<?= esc_attr( $uid ) ?> .wf-resa-day.sel,#<?= esc_attr( $uid ) ?> .wf-resa-day.sel .wf-resa-day-n{color:#fff}
/* Créneaux (surcharge) */
#<?= esc_attr( $uid ) ?> .wf-resa-slot{background:<?= esc_attr( $sbg ) ?>;border-color:<?= esc_attr( $sbd ) ?>;color:<?= esc_attr( $stx ) ?>;border-radius:<?= $srad ?>px}
#<?= esc_attr( $uid ) ?> .wf-resa-slot.sel{background:<?= esc_attr( $accent ) ?>;border-color:<?= esc_attr( $accent ) ?>;color:#fff}
/* ── Calendrier ──────────────────────────────────────────────────────
   Une carte sobre : le mois au centre, deux chevrons, une grille carrée.
   Les jours ouverts prennent la couleur d'accent, le jour choisi se remplit,
   le jour courant porte un point. Les mois hors période sont inatteignables :
   les chevrons se désactivent au lieu de laisser filer vers le passé. */
#<?= esc_attr( $uid ) ?> .wf-resa-cal{border:1px solid <?= esc_attr( $fbd ) ?>;border-radius:<?= $rad + 4 ?>px;padding:14px;background:<?= esc_attr( $fbg ) ?>}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-head{display:grid;grid-template-columns:32px 1fr 32px;align-items:center;margin-bottom:10px}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-title{text-align:center;font-weight:600;font-size:15px;color:<?= esc_attr( $ftx ) ?>;text-transform:capitalize;letter-spacing:-.01em}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-nav{border:0;background:transparent;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:17px;line-height:1;color:<?= esc_attr( $ftx ) ?>;display:flex;align-items:center;justify-content:center;transition:background .15s}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-nav:hover:not([disabled]){background:rgba(127,127,127,.12)}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-nav[disabled]{opacity:.25;cursor:default}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-wd{text-align:center;font-size:11px;color:#9ca3af;font-weight:500;padding-bottom:6px}
/* display:flex — un jour fermé est un <span>, qui ne centre pas son texte
   comme un <button> : sans ça, une colonne sur deux part à gauche. */
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d{position:relative;display:flex;align-items:center;justify-content:center;border:0;padding:0;background:transparent;border-radius:10px;aspect-ratio:1;cursor:pointer;font:inherit;font-size:14px;font-weight:500;line-height:1;color:<?= esc_attr( $accent ) ?>;font-variant-numeric:tabular-nums;transition:background .15s,color .15s}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d:hover:not([disabled]){background:rgba(127,127,127,.10)}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d.off{color:#c9ccd1;cursor:default;font-weight:400}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d.pad{color:#dcdee2;cursor:default;font-weight:400}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d.now::after{content:"";position:absolute;left:50%;bottom:5px;width:3px;height:3px;margin-left:-1.5px;border-radius:50%;background:currentColor}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d.sel{background:<?= esc_attr( $accent ) ?>;color:#fff;font-weight:600}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-d.sel:hover{background:<?= esc_attr( $accent ) ?>}
#<?= esc_attr( $uid ) ?> .wf-resa-cal-none{margin:10px 2px 0;font-size:12.5px;color:#9ca3af;text-align:center}

/* ── Panneau des créneaux ─────────────────────────────────────────── */
#<?= esc_attr( $uid ) ?>.wf-resa--split .wf-resa-pick{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start}
#<?= esc_attr( $uid ) ?>.wf-resa--split .wf-resa-slotwrap{border:1px solid <?= esc_attr( $fbd ) ?>;border-radius:<?= $rad + 4 ?>px;padding:14px;background:<?= esc_attr( $fbg ) ?>;min-height:100%}
#<?= esc_attr( $uid ) ?>.wf-resa--split .wf-resa-slotrow{display:grid;grid-template-columns:1fr 1fr;gap:8px}
#<?= esc_attr( $uid ) ?>.wf-resa--split .wf-resa-slot{text-align:center}
@media(max-width:720px){
	#<?= esc_attr( $uid ) ?>.wf-resa--split .wf-resa-pick{grid-template-columns:1fr}
	#<?= esc_attr( $uid ) ?>.wf-resa--split .wf-resa-slotwrap{min-height:0}
}
</style>

<script>
(function(){
    var root = document.getElementById('<?= esc_js( $uid ) ?>'); if (!root) return;
    var form = root.querySelector('.wf-resa-form');
    var btn  = root.querySelector('.wf-resa-btn');
    var msg  = root.querySelector('.wf-resa-msg');
    var cfg  = { url: <?= wp_json_encode( $ajax ) ?>, nonce: <?= wp_json_encode( $nonce ) ?>, slots: <?= $resa_on ? 'true' : 'false' ?> };

    function show(text, ok){ msg.textContent = text; msg.className = 'wf-resa-msg ' + (ok ? 'ok' : 'err'); msg.style.display = 'block'; }

    var dateEl   = form.querySelector('input[name="date"]');
    var partEl   = form.querySelector('input[name="couverts"]');
    var dayChips = root.querySelectorAll('.wf-resa-day');
    var farDate  = root.querySelector('.wf-resa-datefar');
    var onDate   = function(){};

    function selDay(el){ for (var i = 0; i < dayChips.length; i++){ dayChips[i].classList.remove('sel'); } if (el){ el.classList.add('sel'); } }
    for (var c = 0; c < dayChips.length; c++){
        dayChips[c].addEventListener('click', function(){
            dateEl.value = this.getAttribute('data-date');
            selDay(this);
            if (farDate){ farDate.value = ''; }
            onDate();
        });
    }
    if (farDate){
        farDate.addEventListener('change', function(){
            if (!this.value){ return; }
            dateEl.value = this.value;
            selDay(null);
            onDate();
        });
    }

    if (cfg.slots) {
        var box    = root.querySelector('.wf-resa-slots');
        var hHeure = form.querySelector('input[name="heure"]');
        var hSvc   = form.querySelector('input[name="service"]');

        function clearSel(){ hHeure.value = ''; hSvc.value = ''; btn.disabled = true; }

        function renderGroup(label, arr){
            if (!arr) return '';
            if (!arr.length) return '';
            var h = '<div class="wf-resa-svc">' + label + '</div><div class="wf-resa-slotrow">';
            for (var i = 0; i < arr.length; i++){
                var s = arr[i];
                var rem = '';
                if (s.remaining) if (s.remaining <= 6) rem = '<small>reste ' + s.remaining + '</small>';
                h += '<button type="button" class="wf-resa-slot" data-t="' + s.time + '" data-s="' + s.service + '">' + s.time + rem + '</button>';
            }
            h += '</div>';
            return h;
        }

        function bindSlots(){
            var slots = box.querySelectorAll('.wf-resa-slot');
            for (var i = 0; i < slots.length; i++){
                slots[i].addEventListener('click', function(){
                    var cur = box.querySelectorAll('.wf-resa-slot.sel');
                    for (var j = 0; j < cur.length; j++){ cur[j].classList.remove('sel'); }
                    this.classList.add('sel');
                    hHeure.value = this.getAttribute('data-t');
                    hSvc.value   = this.getAttribute('data-s');
                    btn.disabled = false;
                });
            }
        }

        function loadSlots(){
            clearSel();
            var date = dateEl.value;
            if (!date){ box.innerHTML = '<p class="wf-resa-hint">Choisissez le jour et le nombre de personnes pour voir les créneaux.</p>'; return; }
            box.innerHTML = '<p class="wf-resa-hint">Recherche des créneaux…</p>';
            var fd = new FormData();
            fd.append('action', 'wf_resa_slots');
            fd.append('_wfnonce', cfg.nonce);
            fd.append('date', date);
            fd.append('couverts', partEl.value || '2');
            fetch(cfg.url, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (!res) { box.innerHTML = '<p class="wf-resa-hint">Erreur, réessayez.</p>'; return; }
                    if (!res.success){ box.innerHTML = '<p class="wf-resa-hint">' + (res.data || 'Erreur') + '</p>'; return; }
                    var d = res.data;
                    if (d.closed){ box.innerHTML = '<p class="wf-resa-hint">' + (d.reason || 'Aucun créneau disponible ce jour-là.') + '</p>'; return; }
                    var html = renderGroup('Midi', d.slots.midi) + renderGroup('Soir', d.slots.soir);
                    if (!html){ box.innerHTML = '<p class="wf-resa-hint">Complet pour ce nombre de personnes.</p>'; return; }
                    box.innerHTML = html;
                    bindSlots();
                })
                .catch(function(){ box.innerHTML = '<p class="wf-resa-hint">Connexion impossible.</p>'; });
        }

        onDate = loadSlots;
        partEl.addEventListener('change', function(){ if (dateEl.value){ loadSlots(); } });
        if (dayChips.length){ dayChips[0].click(); }
    }

    var calEl = root.querySelector('.wf-resa-cal');
    if (calEl) {
        var openArr = [];
        try { openArr = JSON.parse(calEl.getAttribute('data-open') || '[]'); } catch (er) { openArr = []; }
        var openMap = {}; for (var oi = 0; oi < openArr.length; oi++){ openMap[openArr[oi]] = true; }
        var minDate   = calEl.getAttribute('data-min') || '';
        var maxDate   = calEl.getAttribute('data-max') || '';
        var firstOpen = openArr.length ? openArr[0] : minDate;
        var selDate = '';
        var wdays  = ['Lu','Ma','Me','Je','Ve','Sa','Di'];
        var mnames = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        var view = new Date((firstOpen || minDate) + 'T00:00:00');
        if (isNaN(view.getTime())) { view = new Date(); }

        function pad2(n){ return (n < 10 ? '0' : '') + n; }
        function ymd(y, m, d){ return y + '-' + pad2(m + 1) + '-' + pad2(d); }
        // Un mois se compare comme un entier : plus simple et sans surprise de
        // fuseau qu'une comparaison de dates.
        function ymOf(s){ return s ? parseInt(s.slice(0, 4), 10) * 12 + parseInt(s.slice(5, 7), 10) - 1 : null; }
        var minYM = ymOf(minDate);
        var maxYM = ymOf(maxDate);

        function clampView(){
            var cur = view.getFullYear() * 12 + view.getMonth();
            if (minYM !== null) if (cur < minYM) { view = new Date(Math.floor(minYM / 12), minYM % 12, 1); }
            cur = view.getFullYear() * 12 + view.getMonth();
            if (maxYM !== null) if (cur > maxYM) { view = new Date(Math.floor(maxYM / 12), maxYM % 12, 1); }
        }

        function renderCal(){
            clampView();
            var y = view.getFullYear(), m = view.getMonth();
            var cur = y * 12 + m;
            var startWd = (new Date(y, m, 1).getDay() + 6) % 7;   // lundi en tête
            var ndays   = new Date(y, m + 1, 0).getDate();
            var prevN   = new Date(y, m, 0).getDate();
            // On ne remonte jamais avant le mois courant, ni au-delà de la
            // dernière date réservable : les chevrons se désactivent.
            var noPrev = (minYM !== null) ? (cur <= minYM) : false;
            var noNext = (maxYM !== null) ? (cur >= maxYM) : false;

            var h = '<div class="wf-resa-cal-head">'
                  + '<button type="button" class="wf-resa-cal-nav" data-d="-1" aria-label="Mois précédent"' + (noPrev ? ' disabled' : '') + '>&lsaquo;</button>'
                  + '<span class="wf-resa-cal-title" aria-live="polite">' + mnames[m] + ' ' + y + '</span>'
                  + '<button type="button" class="wf-resa-cal-nav" data-d="1" aria-label="Mois suivant"' + (noNext ? ' disabled' : '') + '>&rsaquo;</button>'
                  + '</div><div class="wf-resa-cal-grid" role="grid">';

            for (var w = 0; w < 7; w++){ h += '<span class="wf-resa-cal-wd">' + wdays[w] + '</span>'; }
            // Jours du mois précédent, en gris : la grille garde sa forme.
            for (var s = startWd; s > 0; s--){
                h += '<span class="wf-resa-cal-d pad">' + (prevN - s + 1) + '</span>';
            }
            for (var dd = 1; dd <= ndays; dd++){
                var ds = ymd(y, m, dd);
                var open = openMap[ds] === true;
                var cls = 'wf-resa-cal-d';
                if (!open) { cls += ' off'; }
                if (ds === minDate) { cls += ' now'; }
                if (ds === selDate) { cls += ' sel'; }
                if (open) {
                    h += '<button type="button" class="' + cls + '" data-date="' + ds + '"'
                       + (ds === selDate ? ' aria-current="date"' : '') + '>' + dd + '</button>';
                } else {
                    h += '<span class="' + cls + '" aria-disabled="true">' + dd + '</span>';
                }
            }
            // Jours du mois suivant pour compléter la dernière ligne.
            var used = startWd + ndays, tail = (7 - (used % 7)) % 7;
            for (var t = 1; t <= tail; t++){ h += '<span class="wf-resa-cal-d pad">' + t + '</span>'; }
            h += '</div>';

            var monthHasOpen = false;
            for (var k = 0; k < openArr.length; k++){
                if (ymOf(openArr[k]) === cur) { monthHasOpen = true; break; }
            }
            if (!monthHasOpen) { h += '<p class="wf-resa-cal-none">Aucune date réservable ce mois-ci.</p>'; }

            calEl.innerHTML = h;
        }

        calEl.addEventListener('click', function(e){
            var nav = e.target.closest ? e.target.closest('.wf-resa-cal-nav') : null;
            if (nav){
                if (nav.disabled) { return; }
                view.setDate(1);
                view.setMonth(view.getMonth() + parseInt(nav.getAttribute('data-d'), 10));
                renderCal();
                return;
            }
            var cell = e.target.closest ? e.target.closest('button.wf-resa-cal-d') : null;
            if (cell){
                selDate = cell.getAttribute('data-date');
                dateEl.value = selDate;
                renderCal();
                onDate();
            }
        });

        renderCal();
        if (cfg.slots){ if (firstOpen){ selDate = firstOpen; dateEl.value = firstOpen; renderCal(); onDate(); } }
    }

    form.addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(form);
        fd.append('action', 'wf_resa_submit');
        fd.append('_wfnonce', cfg.nonce);
        btn.disabled = true;
        fetch(cfg.url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res) if (res.success) { show(res.data || 'Merci !', true); form.reset(); return; }
                btn.disabled = false;
                var m = 'Une erreur est survenue.';
                if (res) if (res.data) m = res.data;
                show(m, false);
            })
            .catch(function(){ btn.disabled = false; show('Connexion impossible, réessayez.', false); });
    });
})();
</script>
