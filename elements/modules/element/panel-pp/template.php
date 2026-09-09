<?php
/**
 * WeFrame – Panneau ++ | template.php v1.0
 *
 * Un panneau (fond couleur ou image) coupé en deux colonnes : le contenu, saisi
 * dans la sous-mise en page (mini-constructeur), et un visuel posé à côté.
 *
 * Le point délicat est la marge de l'image. Sur un panneau « hero », l'image
 * touche les bords : la marge intérieure porte donc sur la colonne de contenu,
 * jamais sur le panneau lui-même. La colonne image reçoit sa propre marge —
 * nulle, identique au contenu, ou personnalisée — et c'est ce seul réglage qui
 * décide du rendu à fond perdu. L'arrondi du panneau plus overflow:hidden
 * suffisent alors à rogner proprement l'image dans les coins.
 *
 * En mode « étirée » (proportions automatiques + hauteur 0), le visuel est
 * sorti du flux : la hauteur du panneau vient du texte, et l'image se cale
 * dessus au lieu de l'imposer. C'est ce qui évite qu'un portrait fasse
 * grandir tout le bloc.
 *
 * Zéro JavaScript.
 */
defined( 'ABSPATH' ) || exit;

$props = is_array( $props ?? null ) ? $props : array();

/* ── Contenu : sous-mise en page, sinon éditeur de secours ───────────── */
$inner = '';
if ( ! empty( $children ) && isset( $builder ) && is_object( $builder ) ) {
	foreach ( $children as $child ) {
		$inner .= (string) $builder->render( $child, array( 'element' => $props ) );
	}
}
if ( '' === trim( $inner ) ) {
	$inner = wp_kses_post( (string) ( $props['content'] ?? '' ) );
}

/* ── Fond du panneau ─────────────────────────────────────────────────── */
$bgColor = trim( (string) ( $props['bg_color'] ?? '#f4f4f5' ) );

$bgImg = trim( (string) ( $props['bg_image'] ?? '' ) );
if ( '' !== $bgImg ) {
	if ( ! preg_match( '#^(https?:)?//#i', $bgImg ) ) {
		if ( ! preg_match( '#^data:#i', $bgImg ) ) { $bgImg = '/' . ltrim( $bgImg, '/' ); }
	}
}

$bgPos = (string) ( $props['bg_position'] ?? '50% 50%' );
if ( ! in_array( $bgPos, array( '50% 50%', '50% 0%', '50% 100%', '0% 50%', '100% 50%', '0% 0%', '100% 0%', '0% 100%', '100% 100%' ), true ) ) { $bgPos = '50% 50%'; }

$bgSize = (string) ( $props['bg_size'] ?? 'cover' );
if ( ! in_array( $bgSize, array( 'cover', 'contain', 'auto' ), true ) ) { $bgSize = 'cover'; }

$bgRep = (string) ( $props['bg_repeat'] ?? 'no-repeat' );
if ( ! in_array( $bgRep, array( 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ), true ) ) { $bgRep = 'no-repeat'; }

$bgFixed = ! empty( $props['bg_fixed'] );
$bgBlur  = max( 0, min( 40, (int) ( $props['bg_blur'] ?? 0 ) ) );

$ovColor = trim( (string) ( $props['ov_color'] ?? '#000000' ) );
$ovOp    = max( 0, min( 100, (int) ( $props['ov_opacity'] ?? 0 ) ) );

/* ── Cadre ───────────────────────────────────────────────────────────── */
$radius = max( 0, min( 80, (int) ( $props['radius'] ?? 16 ) ) );
$minH   = max( 0, min( 1200, (int) ( $props['min_height'] ?? 0 ) ) );
$bdCol  = trim( (string) ( $props['border_color'] ?? '' ) );
$bdW    = max( 0, min( 8, (int) ( $props['border_width'] ?? 0 ) ) );

$shadow = (string) ( $props['shadow'] ?? 'none' );
$shadowCss = '';
if ( 'soft' === $shadow )   { $shadowCss = 'box-shadow:0 12px 32px rgba(0,0,0,.10);'; }
if ( 'strong' === $shadow ) { $shadowCss = 'box-shadow:0 28px 70px rgba(0,0,0,.22);'; }

/* ── Disposition ─────────────────────────────────────────────────────── */
$side = (string) ( $props['media_side'] ?? 'right' );
if ( ! in_array( $side, array( 'right', 'left', 'top', 'bottom', 'none' ), true ) ) { $side = 'right'; }

$mediaW  = max( 15, min( 75, (int) ( $props['media_width'] ?? 42 ) ) );
$mediaWT = max( 15, min( 75, (int) ( $props['media_width_tablet'] ?? 45 ) ) );
$gap     = max( 0, min( 120, (int) ( $props['gap'] ?? 0 ) ) );

$valign = (string) ( $props['valign'] ?? 'center' );
if ( ! in_array( $valign, array( 'start', 'center', 'end' ), true ) ) { $valign = 'center'; }

$align = (string) ( $props['content_align'] ?? 'left' );
if ( ! in_array( $align, array( 'left', 'center', 'right' ), true ) ) { $align = 'left'; }

$contentMax = max( 0, min( 1000, (int) ( $props['content_max'] ?? 0 ) ) );

$padX  = max( 0, min( 160, (int) ( $props['pad_x'] ?? 48 ) ) );
$padY  = max( 0, min( 160, (int) ( $props['pad_y'] ?? 48 ) ) );
$padXm = max( 0, min( 120, (int) ( $props['pad_x_mobile'] ?? 24 ) ) );
$padYm = max( 0, min( 120, (int) ( $props['pad_y_mobile'] ?? 32 ) ) );

/* ── Visuel ──────────────────────────────────────────────────────────── */
$img = trim( (string) ( $props['image'] ?? '' ) );
if ( '' !== $img ) {
	if ( ! preg_match( '#^(https?:)?//#i', $img ) ) {
		if ( ! preg_match( '#^data:#i', $img ) ) { $img = '/' . ltrim( $img, '/' ); }
	}
}
$hasMedia = ( 'none' !== $side );
if ( '' === $img ) { $hasMedia = false; }

$imgAlt = (string) ( $props['image_alt'] ?? '' );

$imgPad = (string) ( $props['image_pad'] ?? 'none' );
if ( ! in_array( $imgPad, array( 'none', 'panel', 'custom' ), true ) ) { $imgPad = 'none'; }
$imgPadVal = max( 0, min( 120, (int) ( $props['image_pad_value'] ?? 24 ) ) );

/* Marge de la colonne image, en « haut/bas gauche/droite ». */
$frameInsetY = 0;
$frameInsetX = 0;
if ( 'panel' === $imgPad )  { $frameInsetY = $padY;      $frameInsetX = $padX; }
if ( 'custom' === $imgPad ) { $frameInsetY = $imgPadVal; $frameInsetX = $imgPadVal; }

$imgFit = (string) ( $props['image_fit'] ?? 'cover' );
if ( ! in_array( $imgFit, array( 'cover', 'contain' ), true ) ) { $imgFit = 'cover'; }

$imgPos = (string) ( $props['image_pos'] ?? '50% 50%' );
if ( ! in_array( $imgPos, array( '50% 50%', '50% 0%', '50% 100%', '0% 50%', '100% 50%' ), true ) ) { $imgPos = '50% 50%'; }

$imgRad = max( 0, min( 80, (int) ( $props['image_radius'] ?? 12 ) ) );
if ( 'none' === $imgPad ) { $imgRad = 0; }

$ratio = (string) ( $props['image_ratio'] ?? 'auto' );
if ( ! in_array( $ratio, array( 'auto', '1/1', '4/3', '3/2', '16/9', '3/4', '4/5' ), true ) ) { $ratio = 'auto'; }

$imgH  = max( 0, min( 900, (int) ( $props['image_height'] ?? 0 ) ) );
$imgHm = max( 0, min( 700, (int) ( $props['image_height_mobile'] ?? 240 ) ) );

/* Mode « étirée » : hors flux, la hauteur du panneau vient du contenu.
   Réservé aux colonnes côte à côte — au-dessus ou en dessous, il n'y a pas de
   hauteur à épouser, l'image garde alors ses proportions naturelles. */
$fill = ( 'auto' === $ratio );
if ( $imgH > 0 ) { $fill = false; }
if ( 'top' === $side )    { $fill = false; }
if ( 'bottom' === $side ) { $fill = false; }

/* Hauteur du visuel quand il reste dans le flux. */
$imgHeightCss = 'height:auto;';
if ( 'auto' !== $ratio )      { $imgHeightCss = 'aspect-ratio:' . $ratio . ';height:auto;'; }
elseif ( $imgH > 0 )          { $imgHeightCss = 'height:' . $imgH . 'px;'; }

$imgLink = $props['image_link'] ?? '';
if ( is_array( $imgLink ) ) { $imgLink = $imgLink['url'] ?? ''; }
$imgLink = trim( (string) $imgLink );

$hover = (string) ( $props['image_hover'] ?? 'none' );
if ( ! in_array( $hover, array( 'none', 'zoom', 'lift' ), true ) ) { $hover = 'none'; }

/* ── Responsive ──────────────────────────────────────────────────────── */
$bp = (int) ( $props['stack_at'] ?? 959 );
if ( ! in_array( $bp, array( 0, 639, 959 ), true ) ) { $bp = 959; }

$stackOrder = (string) ( $props['stack_order'] ?? 'after' );
if ( ! in_array( $stackOrder, array( 'after', 'before' ), true ) ) { $stackOrder = 'after'; }

$hideM = ! empty( $props['hide_image_mobile'] );

$alignM = (string) ( $props['align_mobile'] ?? 'inherit' );
if ( ! in_array( $alignM, array( 'inherit', 'left', 'center' ), true ) ) { $alignM = 'inherit'; }

/* Rien à afficher : ni contenu, ni visuel. */
if ( '' === trim( $inner ) ) {
	if ( ! $hasMedia ) { return; }
}

$uid = wf_uid( 'wfppp_', $props );

/* Colonnes : les fractions absorbent l'écart, contrairement aux pourcentages. */
$cols = 'minmax(0,1fr)';
if ( $hasMedia ) {
	if ( 'right' === $side ) { $cols = 'minmax(0,' . ( 100 - $mediaW ) . 'fr) minmax(0,' . $mediaW . 'fr)'; }
	if ( 'left' === $side )  { $cols = 'minmax(0,' . $mediaW . 'fr) minmax(0,' . ( 100 - $mediaW ) . 'fr)'; }
}
$colsT = 'minmax(0,1fr)';
if ( $hasMedia ) {
	if ( 'right' === $side ) { $colsT = 'minmax(0,' . ( 100 - $mediaWT ) . 'fr) minmax(0,' . $mediaWT . 'fr)'; }
	if ( 'left' === $side )  { $colsT = 'minmax(0,' . $mediaWT . 'fr) minmax(0,' . ( 100 - $mediaWT ) . 'fr)'; }
}

/* L'image passe devant le contenu sans changer l'ordre du HTML :
   le texte reste lu en premier par les lecteurs d'écran et par Google. */
$mediaFirst = false;
if ( 'left' === $side ) { $mediaFirst = true; }
if ( 'top' === $side )  { $mediaFirst = true; }

$frameTag = '' !== $imgLink ? 'a' : 'span';
$frameHref = '' !== $imgLink ? ' href="' . esc_url( $imgLink ) . '"' : '';
?>
<?php $wfTag = ! empty( $props['html_element'] ) ? $props['html_element'] : 'div'; $wfEl = ( isset( $this ) && is_object( $this ) && method_exists( $this, 'el' ) ) ? $this->el( $wfTag ) : null; if ( $wfEl ) { echo $wfEl( $props, isset( $attrs ) ? $attrs : array() ); } ?>

<div id="<?= esc_attr( $uid ) ?>" class="wf-ppp">
	<?php if ( '' !== $bgImg ) : ?>
	<span class="wf-ppp-bg" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( '' !== $bgImg && $ovOp > 0 && '' !== $ovColor ) : ?>
	<span class="wf-ppp-ov" aria-hidden="true"></span>
	<?php endif; ?>

	<div class="wf-ppp-grid">
		<div class="wf-ppp-content"><div class="wf-ppp-inner"><?= $inner ?></div></div>
		<?php if ( $hasMedia ) : ?>
		<div class="wf-ppp-media">
			<<?= $frameTag ?> class="wf-ppp-frame"<?= $frameHref ?>>
				<img class="wf-ppp-img" src="<?= esc_url( $img ) ?>" alt="<?= esc_attr( $imgAlt ) ?>" loading="lazy" decoding="async">
			</<?= $frameTag ?>>
		</div>
		<?php endif; ?>
	</div>
</div>

<style>
#<?= esc_attr( $uid ) ?>.wf-ppp{
	position:relative;
	overflow:hidden;
	border-radius:<?= $radius ?>px;
	<?= '' !== $bgColor ? 'background-color:' . esc_attr( $bgColor ) . ';' : '' ?>
	<?= $minH > 0 ? 'min-height:' . $minH . 'px;' : '' ?>
	<?= ( '' !== $bdCol && $bdW > 0 ) ? 'border:' . $bdW . 'px solid ' . esc_attr( $bdCol ) . ';' : '' ?>
	<?= $shadowCss ?>
	box-sizing:border-box;
}
<?php if ( '' !== $bgImg ) : ?>
#<?= esc_attr( $uid ) ?> .wf-ppp-bg{
	position:absolute;inset:0;display:block;pointer-events:none;
	background-image:url('<?= esc_url( $bgImg ) ?>');
	background-position:<?= esc_attr( $bgPos ) ?>;
	background-size:<?= esc_attr( $bgSize ) ?>;
	background-repeat:<?= esc_attr( $bgRep ) ?>;
	<?= $bgFixed ? 'background-attachment:fixed;' : '' ?>
	<?php if ( $bgBlur > 0 ) : ?>
	filter:blur(<?= $bgBlur ?>px);
	/* Le flou dilue les bords : on agrandit légèrement pour ne pas voir le vide. */
	transform:scale(<?= 1 + min( 0.2, $bgBlur / 100 ) ?>);
	<?php endif; ?>
}
<?php endif; ?>
<?php if ( '' !== $bgImg && $ovOp > 0 && '' !== $ovColor ) : ?>
#<?= esc_attr( $uid ) ?> .wf-ppp-ov{
	position:absolute;inset:0;display:block;pointer-events:none;
	background:<?= esc_attr( $ovColor ) ?>;
	opacity:<?= round( $ovOp / 100, 2 ) ?>;
}
<?php endif; ?>
#<?= esc_attr( $uid ) ?> .wf-ppp-grid{
	position:relative;
	display:grid;
	grid-template-columns:<?= $cols ?>;
	<?= $gap > 0 ? 'gap:' . $gap . 'px;' : '' ?>
	align-items:stretch;
	<?= $minH > 0 ? 'min-height:' . $minH . 'px;' : '' ?>
	height:100%;
}
#<?= esc_attr( $uid ) ?> .wf-ppp-content{
	align-self:<?= $valign ?>;
	padding:<?= $padY ?>px <?= $padX ?>px;
	text-align:<?= $align ?>;
	box-sizing:border-box;
	min-width:0;
}
#<?= esc_attr( $uid ) ?> .wf-ppp-inner{
	<?= $contentMax > 0 ? 'max-width:' . $contentMax . 'px;' : '' ?>
	<?= ( $contentMax > 0 && 'center' === $align ) ? 'margin-left:auto;margin-right:auto;' : '' ?>
	<?= ( $contentMax > 0 && 'right' === $align ) ? 'margin-left:auto;' : '' ?>
}
<?php if ( $hasMedia ) : ?>
#<?= esc_attr( $uid ) ?> .wf-ppp-media{
	position:relative;
	min-width:0;
	box-sizing:border-box;
	<?= $mediaFirst ? 'order:-1;' : '' ?>
	<?php if ( $fill ) : ?>
	/* Hors flux : la hauteur du panneau reste celle du contenu. */
	min-height:180px;
	<?php else : ?>
	display:flex;
	align-items:<?= 'start' === $valign ? 'flex-start' : ( 'end' === $valign ? 'flex-end' : 'center' ) ?>;
	padding:<?= $frameInsetY ?>px <?= $frameInsetX ?>px;
	<?php endif; ?>
}
#<?= esc_attr( $uid ) ?> .wf-ppp-frame{
	display:block;
	overflow:hidden;
	<?= $imgRad > 0 ? 'border-radius:' . $imgRad . 'px;' : '' ?>
	<?php if ( $fill ) : ?>
	position:absolute;
	top:<?= $frameInsetY ?>px;right:<?= $frameInsetX ?>px;bottom:<?= $frameInsetY ?>px;left:<?= $frameInsetX ?>px;
	<?php else : ?>
	width:100%;
	<?php endif; ?>
}
#<?= esc_attr( $uid ) ?> .wf-ppp-img{
	display:block;
	width:100%;
	object-fit:<?= $imgFit ?>;
	object-position:<?= esc_attr( $imgPos ) ?>;
	<?= $fill ? 'height:100%;' : $imgHeightCss ?>
	<?= 'none' !== $hover ? 'transition:transform .5s cubic-bezier(.22,.9,.32,1);' : '' ?>
}
<?php if ( 'zoom' === $hover ) : ?>
#<?= esc_attr( $uid ) ?> .wf-ppp-frame:hover .wf-ppp-img,
#<?= esc_attr( $uid ) ?> .wf-ppp-frame:focus-visible .wf-ppp-img{transform:scale(1.06);}
<?php endif; ?>
<?php if ( 'lift' === $hover ) : ?>
#<?= esc_attr( $uid ) ?> .wf-ppp-frame:hover .wf-ppp-img,
#<?= esc_attr( $uid ) ?> .wf-ppp-frame:focus-visible .wf-ppp-img{transform:translateY(-8px) scale(1.02);}
<?php endif; ?>
<?php endif; ?>

<?php /* Tablette : largeur de colonne dédiée, tant que l'empilement n'a pas déjà eu lieu. */ ?>
<?php if ( $hasMedia && 959 !== $bp ) : ?>
@media(max-width:959px){
	#<?= esc_attr( $uid ) ?> .wf-ppp-grid{grid-template-columns:<?= $colsT ?>;}
}
<?php endif; ?>

<?php if ( '' !== $bgImg && $bgFixed ) : ?>
@media(max-width:959px){
	/* background-attachment:fixed est ignoré ou cassé sur iOS. */
	#<?= esc_attr( $uid ) ?> .wf-ppp-bg{background-attachment:scroll;}
}
<?php endif; ?>

<?php if ( $bp > 0 ) : ?>
@media(max-width:<?= $bp ?>px){
	#<?= esc_attr( $uid ) ?> .wf-ppp-grid{grid-template-columns:minmax(0,1fr);}
	<?php if ( $hasMedia ) : ?>
	#<?= esc_attr( $uid ) ?> .wf-ppp-media{
		order:<?= 'before' === $stackOrder ? '-1' : '1' ?>;
		position:static;
		min-height:0;
		display:block;
		<?= $fill ? 'padding:' . $frameInsetY . 'px ' . $frameInsetX . 'px;' : '' ?>
	}
	#<?= esc_attr( $uid ) ?> .wf-ppp-frame{position:static;width:100%;}
	#<?= esc_attr( $uid ) ?> .wf-ppp-img{
		<?php if ( $imgHm > 0 ) : ?>
		height:<?= $imgHm ?>px;
		<?= 'auto' !== $ratio ? 'aspect-ratio:auto;' : '' ?>
		<?php elseif ( 'auto' === $ratio ) : ?>
		height:auto;
		<?php endif; ?>
	}
	<?php endif; ?>
}
<?php endif; ?>

@media(max-width:639px){
	#<?= esc_attr( $uid ) ?> .wf-ppp-content{
		padding:<?= $padYm ?>px <?= $padXm ?>px;
		<?= 'inherit' !== $alignM ? 'text-align:' . $alignM . ';' : '' ?>
	}
	<?php if ( $hasMedia && 'panel' === $imgPad ) : ?>
	#<?= esc_attr( $uid ) ?> .wf-ppp-media{padding:<?= $padYm ?>px <?= $padXm ?>px;}
	<?php endif; ?>
	<?php if ( $hasMedia && $hideM ) : ?>
	#<?= esc_attr( $uid ) ?> .wf-ppp-media{display:none;}
	<?php endif; ?>
	<?php if ( $contentMax > 0 && 'center' === $alignM ) : ?>
	#<?= esc_attr( $uid ) ?> .wf-ppp-inner{margin-left:auto;margin-right:auto;}
	<?php endif; ?>
}

<?php if ( 'none' !== $hover ) : ?>
@media(prefers-reduced-motion:reduce){
	#<?= esc_attr( $uid ) ?> .wf-ppp-img{transition:none;}
	#<?= esc_attr( $uid ) ?> .wf-ppp-frame:hover .wf-ppp-img,
	#<?= esc_attr( $uid ) ?> .wf-ppp-frame:focus-visible .wf-ppp-img{transform:none;}
}
<?php endif; ?>
</style>
<?php if ( ! empty( $wfEl ) ) { echo $wfEl->end(); } ?>
