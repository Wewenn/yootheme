<?php
/**
 * WeFrame – Carrousel perspective infini | template.php v1.0
 *
 * Une rangée d'images qui défile sans fin dans une scène en perspective : plus
 * une carte s'éloigne du centre, plus elle pivote et recule, ce qui donne la
 * courbure de cylindre. Les cartes ne sont pas dans un rail que l'on translate
 * en bloc — chacune est placée à sa position calculée, ramenée dans une période
 * par un modulo. Sans rail, pas de saut au bouclage, et chaque carte peut
 * recevoir sa propre rotation.
 *
 * Largeur, hauteur, espacement et « aplatir sur mobile » vivent dans des
 * variables CSS lues à chaque mesure : le responsive est donc géré par les
 * media queries, pas par du JavaScript qui devinerait la largeur de l'écran.
 *
 * Sans JavaScript, les cartes restent affichées en simple rangée horizontale.
 * Configuration inlinée (pas de <script type="application/json">).
 */
defined( 'ABSPATH' ) || exit;

$props = is_array( $props ?? null ) ? $props : array();

if ( empty( $children ) ) { return; }
if ( ! isset( $builder ) || ! is_object( $builder ) ) { return; }

$cards = '';
foreach ( $children as $child ) {
	$cards .= (string) $builder->render( $child, array( 'element' => $props ) );
}
if ( '' === trim( $cards ) ) { return; }
$count = max( 1, substr_count( $cards, 'class="wf-pspv-card"' ) );

/* ── Perspective ─────────────────────────────────────────────────────── */
$persp = max( 400, min( 4000, (int) ( $props['perspective'] ?? 1200 ) ) );

$curve = (string) ( $props['curve'] ?? 'in' );
if ( ! in_array( $curve, array( 'in', 'out', 'flat' ), true ) ) { $curve = 'in'; }

$rot = max( 0, min( 80, (int) ( $props['rotate'] ?? 42 ) ) );
if ( 'flat' === $curve ) { $rot = 0; }
$dir3 = ( 'out' === $curve ) ? -1 : 1;

$depth = max( 0, min( 800, (int) ( $props['depth'] ?? 260 ) ) );
$arc   = max( -200, min( 200, (int) ( $props['arc'] ?? 0 ) ) );
$fade  = max( 0, min( 100, (int) ( $props['fade'] ?? 0 ) ) );

/* ── Cartes ──────────────────────────────────────────────────────────── */
$cardW = max( 80, min( 900, (int) ( $props['card_w'] ?? 320 ) ) );
$cardH = max( 80, min( 900, (int) ( $props['card_h'] ?? 200 ) ) );
$gap   = max( 0, min( 200, (int) ( $props['gap'] ?? 32 ) ) );
$rad   = max( 0, min( 60, (int) ( $props['radius'] ?? 8 ) ) );

$shadow = ! empty( $props['shadow'] );
$gray   = ! empty( $props['grayscale'] );

$cap = (string) ( $props['caption'] ?? 'none' );
if ( ! in_array( $cap, array( 'none', 'always', 'hover' ), true ) ) { $cap = 'none'; }
$capColor = (string) ( $props['caption_color'] ?? '#ffffff' );

/* ── Défilement ──────────────────────────────────────────────────────── */
$speed = max( 0, min( 400, (int) ( $props['speed'] ?? 60 ) ) );

$dir = (string) ( $props['direction'] ?? 'left' );
if ( ! in_array( $dir, array( 'left', 'right' ), true ) ) { $dir = 'left'; }
$auto = ( 'right' === $dir ) ? $speed : -$speed;

$pauseHover = ! empty( $props['pause_hover'] );
$drag       = ! empty( $props['drag'] );

/* ── Habillage ───────────────────────────────────────────────────────── */
$bg   = trim( (string) ( $props['bg'] ?? '' ) );
$padY = max( 0, min( 200, (int) ( $props['pad_y'] ?? 40 ) ) );
$mask = ! empty( $props['mask'] );

/* ── Responsive ──────────────────────────────────────────────────────── */
$cardWT = max( 80, min( 900, (int) ( $props['card_w_tablet'] ?? 260 ) ) );
$cardWM = max( 60, min( 700, (int) ( $props['card_w_mobile'] ?? 200 ) ) );
$cardHM = max( 60, min( 700, (int) ( $props['card_h_mobile'] ?? 130 ) ) );
$gapM   = max( 0, min( 120, (int) ( $props['gap_mobile'] ?? 16 ) ) );
$flatM  = ! empty( $props['flat_mobile'] );

$uid = wf_uid( 'wfpspv_', $props, $count );

$cfg = wp_json_encode( array(
	'uid'   => $uid,
	'w'     => $cardW,
	'g'     => $gap,
	'rot'   => $rot,
	'dir3'  => $dir3,
	'depth' => $depth,
	'arc'   => $arc,
	'fade'  => round( $fade / 100, 3 ),
	'auto'  => $auto,
	'drag'  => $drag ? 1 : 0,
	'hover' => $pauseHover ? 1 : 0,
), JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE );
?>
<?php $wfTag = ! empty( $props['html_element'] ) ? $props['html_element'] : 'div'; $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( $wfTag ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>

<div id="<?= esc_attr( $uid ) ?>" class="wf-pspv">
	<div class="wf-pspv-stage"><?= $cards ?></div>
</div>

<style>
#<?= esc_attr( $uid ) ?>.wf-pspv{
	--w:<?= $cardW ?>px;
	--h:<?= $cardH ?>px;
	--g:<?= $gap ?>px;
	--flat:0;
	position:relative;
	overflow:hidden;
	<?= '' !== $bg ? 'background:' . esc_attr( $bg ) . ';' : '' ?>
	<?= $drag ? 'cursor:grab;user-select:none;-webkit-user-select:none;' : '' ?>
	<?php if ( $mask ) : ?>
	-webkit-mask-image:linear-gradient(90deg,transparent 0,#000 10%,#000 90%,transparent 100%);
	mask-image:linear-gradient(90deg,transparent 0,#000 10%,#000 90%,transparent 100%);
	<?php endif; ?>
}
<?php if ( $drag ) : ?>
#<?= esc_attr( $uid ) ?>.wf-pspv.is-grabbing{cursor:grabbing;}
<?php endif; ?>
#<?= esc_attr( $uid ) ?> .wf-pspv-stage{
	position:relative;
	height:calc(var(--h) + <?= 2 * $padY ?>px);
	perspective:<?= $persp ?>px;
	perspective-origin:50% 50%;
	/* Sans JavaScript : une rangée horizontale toute simple. */
	display:flex;
	align-items:center;
	gap:var(--g);
	<?= $drag ? 'touch-action:pan-y;' : '' ?>
}
#<?= esc_attr( $uid ) ?>.wf-pspv--on .wf-pspv-stage{display:block;}
#<?= esc_attr( $uid ) ?> .wf-pspv-card{
	flex:0 0 auto;
	display:block;
	width:var(--w);
	height:var(--h);
	border-radius:<?= $rad ?>px;
	overflow:hidden;
	background:#f4f4f5;
	text-decoration:none;
	transform-origin:50% 50%;
	backface-visibility:hidden;
	<?= $shadow ? 'box-shadow:0 18px 40px rgba(0,0,0,.20);' : '' ?>
}
#<?= esc_attr( $uid ) ?>.wf-pspv--on .wf-pspv-card{
	position:absolute;
	top:50%;
	left:0;
	margin-top:calc(var(--h) * -0.5);
	will-change:transform;
}
#<?= esc_attr( $uid ) ?> .wf-pspv-img{
	display:block;
	width:100%;
	height:100%;
	object-fit:cover;
	pointer-events:none;
	-webkit-user-drag:none;
	<?= $gray ? 'filter:grayscale(1);transition:filter .4s ease;' : '' ?>
}
<?php if ( $gray ) : ?>
#<?= esc_attr( $uid ) ?> .wf-pspv-card:hover .wf-pspv-img,
#<?= esc_attr( $uid ) ?> .wf-pspv-card:focus-visible .wf-pspv-img{filter:none;}
<?php endif; ?>
<?php if ( 'none' === $cap ) : ?>
#<?= esc_attr( $uid ) ?> .wf-pspv-cap{display:none;}
<?php else : ?>
#<?= esc_attr( $uid ) ?> .wf-pspv-cap{
	position:absolute;left:0;right:0;bottom:0;
	padding:16px 18px;
	font-size:14px;line-height:1.35;
	color:<?= esc_attr( $capColor ) ?>;
	background:linear-gradient(to top,rgba(0,0,0,.75),rgba(0,0,0,0));
	pointer-events:none;
	<?= 'hover' === $cap ? 'opacity:0;transition:opacity .3s ease;' : '' ?>
}
<?php endif; ?>
<?php if ( 'hover' === $cap ) : ?>
#<?= esc_attr( $uid ) ?> .wf-pspv-card:hover .wf-pspv-cap,
#<?= esc_attr( $uid ) ?> .wf-pspv-card:focus-visible .wf-pspv-cap{opacity:1;}
<?php endif; ?>
@media(max-width:959px){
	#<?= esc_attr( $uid ) ?>.wf-pspv{--w:<?= $cardWT ?>px;}
}
@media(max-width:639px){
	#<?= esc_attr( $uid ) ?>.wf-pspv{--w:<?= $cardWM ?>px;--h:<?= $cardHM ?>px;--g:<?= $gapM ?>px;<?= $flatM ? '--flat:1;' : '' ?>}
}
</style>

<script>
(function(){
	var C = <?= $cfg ?>;
	function boot(){
		var el = document.getElementById(C.uid);
		if (!el) { return; }
		var stage = el.querySelector('.wf-pspv-stage');
		if (!stage) { return; }

		var origin = [];
		var found = stage.querySelectorAll('.wf-pspv-card');
		for (var i = 0; i < found.length; i++) { origin.push(found[i]); }
		if (origin.length === 0) { return; }

		var calm = false;
		if (window.matchMedia) {
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { calm = true; }
		}
		var auto = calm ? 0 : C.auto;

		var nodes = [], cardW = C.w, gap = C.g, flat = 0;
		var step = 1, cycle = 1, stageW = 1;
		var x = 0, vDrag = 0, lastX = 0, moved = 0;
		var down = false, paused = false, visible = true;
		var raf = 0, last = 0;

		/* Les mesures viennent des variables CSS : ce sont les media queries
		   qui décident, pas une largeur d'écran devinée en JavaScript. */
		function readCss(){
			var cs = window.getComputedStyle(el);
			var w = parseFloat(cs.getPropertyValue('--w'));
			var g = parseFloat(cs.getPropertyValue('--g'));
			var f = parseFloat(cs.getPropertyValue('--flat'));
			cardW = isNaN(w) ? C.w : w;
			gap   = isNaN(g) ? C.g : g;
			flat  = isNaN(f) ? 0 : f;
			if (cardW <= 0) { cardW = C.w; }
			step = cardW + gap;
			if (step <= 0) { step = 1; }
		}

		/* Assez de copies pour couvrir la scène, plus une carte de marge de
		   chaque côté : le bouclage se fait alors toujours hors champ. */
		function build(){
			readCss();
			stageW = stage.getBoundingClientRect().width;
			if (stageW <= 0) { stageW = 1; }

			var sets = Math.ceil(Math.ceil((stageW + step * 3) / step) / origin.length);
			if (sets < 2) { sets = 2; }

			var old = stage.querySelectorAll('.wf-pspv-clone');
			for (var k = 0; k < old.length; k++) { old[k].parentNode.removeChild(old[k]); }

			nodes = origin.slice(0);
			for (var s = 1; s < sets; s++) {
				for (var i = 0; i < origin.length; i++) {
					var c = origin[i].cloneNode(true);
					c.className = c.className + ' wf-pspv-clone';
					c.setAttribute('aria-hidden', 'true');
					if (c.tagName === 'A') { c.setAttribute('tabindex', '-1'); }
					var links = c.querySelectorAll('a');
					for (var m = 0; m < links.length; m++) { links[m].setAttribute('tabindex', '-1'); }
					stage.appendChild(c);
					nodes.push(c);
				}
			}
			cycle = nodes.length * step;
		}

		function layout(){
			var half = stageW / 2;
			if (half <= 0) { half = 1; }
			for (var j = 0; j < nodes.length; j++) {
				var p = (x + j * step) % cycle;
				if (p < 0) { p += cycle; }
				p -= step;

				var t = (p + cardW / 2 - half) / half;
				if (t > 1.35)  { t = 1.35; }
				if (t < -1.35) { t = -1.35; }

				var a = 0, z = 0, y = 0;
				if (flat !== 1) {
					a = t * C.rot * C.dir3;
					z = -Math.abs(t) * C.depth;
					y = Math.abs(t) * C.arc;
				}

				var st = nodes[j].style;
				st.transform = 'translate3d(' + p.toFixed(1) + 'px,' + y.toFixed(1) + 'px,' + z.toFixed(1) + 'px) rotateY(' + a.toFixed(2) + 'deg)';
				if (C.fade > 0) {
					var ab = Math.abs(t);
					if (ab > 1) { ab = 1; }
					st.opacity = (1 - ab * C.fade).toFixed(3);
				}
			}
		}

		function busy(){
			if (!visible) { return false; }
			if (down) { return true; }
			if (vDrag !== 0) { return true; }
			if (paused) { return false; }
			if (auto !== 0) { return true; }
			return false;
		}

		function frame(ts){
			var dt = last ? Math.min(64, ts - last) : 16;
			last = ts;
			if (!down) {
				if (!paused) { x += auto * dt / 1000; }
				x += vDrag;
				vDrag *= 0.93;
				if (Math.abs(vDrag) < 0.05) { vDrag = 0; }
			}
			layout();
			if (busy()) { raf = requestAnimationFrame(frame); return; }
			raf = 0; last = 0;
		}

		function run(){
			if (raf) { return; }
			if (!visible) { return; }
			last = 0;
			raf = requestAnimationFrame(frame);
		}

		function rebuild(){
			build();
			layout();
			run();
		}

		el.classList.add('wf-pspv--on');
		rebuild();

		if (C.hover === 1) {
			el.addEventListener('mouseenter', function(){ paused = true; });
			el.addEventListener('mouseleave', function(){ paused = false; run(); });
			/* Au clavier, la mise en pause suit le focus. */
			el.addEventListener('focusin',  function(){ paused = true; });
			el.addEventListener('focusout', function(){ paused = false; run(); });
		}

		if (C.drag === 1) {
			el.addEventListener('pointerdown', function(e){
				down = true; moved = 0; lastX = e.clientX; vDrag = 0;
				el.classList.add('is-grabbing');
				if (el.setPointerCapture) {
					try { el.setPointerCapture(e.pointerId); } catch (err) {}
				}
				run();
			});
			el.addEventListener('pointermove', function(e){
				if (!down) { return; }
				var d = e.clientX - lastX;
				lastX = e.clientX;
				x += d;
				vDrag = d;
				moved += Math.abs(d);
				layout();
			});
			var release = function(){
				if (!down) { return; }
				down = false;
				el.classList.remove('is-grabbing');
				run();
			};
			el.addEventListener('pointerup', release);
			el.addEventListener('pointercancel', release);
			el.addEventListener('lostpointercapture', release);
			/* Un glissement ne doit pas ouvrir le lien survolé. */
			el.addEventListener('click', function(e){
				if (moved > 6) { e.preventDefault(); e.stopPropagation(); }
			}, true);
			el.addEventListener('dragstart', function(e){ e.preventDefault(); });
		}

		var tmo = 0;
		window.addEventListener('resize', function(){
			clearTimeout(tmo);
			tmo = setTimeout(rebuild, 150);
		});
		window.addEventListener('load', rebuild);

		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function(entries){
				for (var i = 0; i < entries.length; i++) {
					visible = entries[i].isIntersecting;
					if (visible) { run(); }
				}
			}, { threshold: 0 });
			io.observe(el);
		}
	}
	if (window.WF) {
		if (WF.ready) { WF.ready(boot); return; }
	}
	if (document.readyState !== 'loading') { boot(); }
	else { document.addEventListener('DOMContentLoaded', boot); }
})();
</script>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>
