# Défilement latéral dans les réglages de Section

Module `modules/wf-section-scroll.php` : un groupe de champs « Défilement
latéral » dans l'onglet **Paramètres** de la Section native, pour qu'une section
se parcoure à l'horizontale au lieu de descendre.

> **v2 — moteur piloté par script.** La première version faisait tout en CSS
> avec `animation-timeline`. Elle ne marchait pas chez toi, et pour une raison
> que j'avais moi-même signalée comme non vérifiée : elle *devinait* la
> structure rendue par YOOtheme. La v2 ne devine plus rien, elle mesure. Voir
> [Pourquoi un script](#pourquoi-un-script-et-non-du-css-seul).

---

## La question de départ : comment le faire correctement

Il y a deux familles de solutions, et une seule est défendable.

**Détourner la molette** — `wheel` + `preventDefault()` + `scrollLeft` piloté à
la main. C'est ce qu'on trouve dans la plupart des tutoriels, et c'est le
« scroll hijacking ». Ça casse le clavier (Page suivante, Origine, Fin), ça tue
l'inertie du trackpad, ça rend la barre de défilement mensongère, ça empêche la
recherche dans la page d'atteindre le contenu hors champ, et le geste tactile
devient imprévisible. **Écarté.**

**Ne jamais toucher au défilement de la page.** On construit une zone verticale
plus haute que l'écran, on y colle un hublot en `position: sticky`, et on décale
le rail à l'intérieur *en fonction de la progression de cette zone dans
l'écran*. Le défilement reste celui du navigateur : son inertie, son clavier, sa
barre, son geste tactile. C'est ce que font Apple sur ses pages produit et GSAP
ScrollTrigger. **Retenu.**

Le script ne fait donc qu'une chose : lire `window.pageYOffset` et poser un
`transform`. Aucun événement de défilement n'est annulé, aucune valeur de
`scrollTop` n'est écrite.

---

## Pourquoi un script, et non du CSS seul

La v1 lisait la progression avec `animation-timeline: view()`, sans une ligne de
JavaScript. C'est séduisant — l'animation tourne hors du thread principal — mais
deux problèmes de fond l'ont condamnée :

**1. Le CSS ne sait pas mesurer.** Selon que le conteneur de la section est
actif ou non, la grille est enfant direct de la section, ou nichée dans un
conteneur, ou dans deux. Le CSS ne peut que *nommer* des sélecteurs : chacun
était un pari sur une structure que je n'avais pas sous les yeux. Et un pari
perdu ne se voit pas — la section ne fait simplement rien, sans le moindre
message. C'est exactement ce qui s'est passé.

**2. Firefox.** `animation-timeline` reste derrière un drapeau dans Firefox
stable (`layout.css.scroll-driven-animations.enabled`, encore le cas en v152,
juin 2026) — environ 84 % de support global. Le mode épinglé n'existait pas pour
un visiteur sur six.

Un script, lui :

- **trouve** le rail en descendant dans le vrai DOM (`.uk-grid`, sinon le
  premier descendant qui a au moins deux enfants) ;
- **compte** les panneaux et **mesure** leurs largeurs réelles ;
- **fabrique** le niveau de DOM manquant si le rail est enfant direct de la
  section ;
- **dit dans la console** ce qu'il n'a pas trouvé, en nommant la section.

Plus de pari, un diagnostic quand ça coince, et ça marche dans Firefox.

**Le socle reste en CSS pur.** Sans JavaScript, la section est une vraie bande
latérale (`overflow-x` + `scroll-snap`) qu'on parcourt au doigt, au trackpad et
aux flèches. Le mode épinglé est un enrichissement par-dessus, jamais un
prérequis : script absent, script en erreur, section en dessous du seuil
d'écran — dans les trois cas on retombe sur une bande latérale utilisable.

---

## Ce que j'ai retenu de khanhnguyen.design

**Le site est bloqué depuis cet environnement** (le proxy de sortie refuse
`khanhnguyen.design`, en `curl` comme en WebFetch). Je ne l'ai donc pas vu
tourner ; je n'ai que tes deux captures. Ce que j'en ai tiré, et qui est dans le
module :

| Ce que montrent les captures | Ce qui a changé |
|---|---|
| Panneaux **plein écran en hauteur**, à ras des bords | `min-height:100vh` sur les panneaux, marges verticales de la section mises à zéro en mode épinglé |
| Contenu qui **déborde d'un panneau sur le suivant** | le hublot rogne, les panneaux non : rien n'est enfermé dans une boîte |
| Mouvement **amorti**, pas collé au pixel du scroll | réglage « Souplesse » (voir plus bas) |

Ce que je **n'ai pas** repris : le rail de navigation fixe à gauche. Ce n'est
pas du défilement de section — c'est un élément à part, à poser dans une section
« sticky » classique. Si tu le veux, c'est un autre chantier.

---

## Le mouvement : le réglage « Souplesse »

C'est ce qui sépare un défilement latéral fait à la main d'un site de studio.
La position du rail n'est pas recopiée du scroll, elle est **interpolée vers sa
cible** :

```js
var k = 1 - (douceur / 100) * 0.86;   // 0 → k=1 (collé) ; 100 → k≈0,14 (glisse)
x += (cible - x) * k;
```

À 0 le rail suit le scroll au pixel. À 100 (défaut : 70) il glisse encore un
instant après l'arrêt. Le défilement de la page n'est jamais retardé pour
autant : seul le rail est amorti, la page reste sous les doigts du visiteur.

`prefers-reduced-motion: reduce` ramène `k` à 1. On épingle quand même — c'est
une mise en page, pas une décoration — mais sans glissement résiduel.

La boucle `requestAnimationFrame` **s'arrête d'elle-même** dès que le rail a
rejoint sa cible (`x === cible`), et ne redémarre qu'au défilement suivant. Pas
de boucle qui tourne dans le vide.

---

## Zéro balisage côté serveur

Le module **n'ajoute aucune balise au rendu**. La section reçoit :

- la classe `wf-hs` via son champ `class` natif — c'est la prise du script ;
- ses valeurs en **variables CSS** posées dans son champ CSS, que YOOtheme
  préfixe avec l'id du nœud (`.el-element` désigne la section) :
  `--wf-hs-mode`, `--wf-hs-dir`, `--wf-hs-len`, `--wf-hs-ease`, `--wf-hs-bp`.

Le script relit ces variables avec `getComputedStyle`. Rien en dur dans le
HTML, et une section dont le script ne tourne pas reste une section valide.

Le seul élément fabriqué à l'exécution est le hublot, et seulement quand il
manque (`<div class="wf-hs-view">` inséré autour du rail).

Le moteur est imprimé **une seule fois par page**, dans `wp_footer`, et
seulement si au moins une section l'a demandé (`$GLOBALS['wf_shs_needed']`).

---

## Cinq pièges, tous trouvés à l'exécution

Aucun ne se voit à la lecture du code. Aucun ne se voit dans l'inspecteur :
`position` vaut bien `sticky`, `top` vaut bien `0`, et rien ne colle.

**1. `position: sticky` est borné par la boîte de *contenu* du parent.** La
hauteur de défilement était posée en `padding-bottom`. Cas minimal, mesuré dans
Chromium — à 400 px après le début de la zone, le hublot devrait être à `top:0` :

```
A · parent height:1600px          top=  0   COLLE
B · parent padding-bottom:1000px  top=-400  NE COLLE PAS
```

→ hauteur réelle (`style.height`), et marges verticales de la section à zéro,
sinon `box-sizing:border-box` rogne la hauteur posée et la traversée s'arrête
juste avant la fin.

**2. `scrollWidth` vaut `clientWidth` quand l'élément n'a pas de boîte de
défilement.** Or le mode épinglé met justement `overflow:visible` sur le rail.
La course mesurée valait **0**. → on prend la géométrie réelle, du bord gauche
du premier panneau au bord droit du dernier.

**3. Un hublot en `display:flex` rétrécit son rail.** Le rail devient un
élément flex et se réduit à la largeur de son contenu visible : 334 px au lieu
de 1170. → le hublot reste un bloc ordinaire.

**4. `clientWidth` inclut le padding du conteneur.** Mesurer la course contre
le hublot (1200) au lieu du rail (1170) décalait la fin de course de 30 px.
→ on mesure contre `rail.getBoundingClientRect().width`.

**5. Un élément collant est borné par son *parent*, pas par la section.** Avec
deux niveaux de conteneurs, gonfler la section laissait le conteneur
intermédiaire à un écran de haut : le hublot filait à **−1170 px** au lieu de
rester à 0. → la hauteur et la plage de défilement vont sur
`vue.parentElement`, pas sur la section.

**Et un sixième, de logique.** Sous le seuil d'écran, le repli remettait tout à
plat… puis le `ResizeObserver` rallumait l'épinglage dans la foulée : la hauteur
et la translation revenaient, et la section restait cassée sur mobile. → un seul
interrupteur `actif`, que `scroll`, `ResizeObserver` et la boucle
`requestAnimationFrame` consultent tous les trois.

C'est exactement le genre de choses qu'un banc d'essai attrape et qu'une
relecture ne trouve pas.

---

## Les réglages

| Champ | Rôle |
|---|---|
| **Défilement latéral** | Non · Bande latérale (le visiteur fait défiler) · Épinglée (le scroll de la page fait défiler) |
| **Sens du défilement** | vers la gauche (on avance à droite) · vers la droite (on part de la droite) |
| **Largeur d'un panneau** | plein écran · ¾ · ⅔ · ½ · ⅓ · selon le contenu |
| **Écart entre panneaux** | en px ; remplace la gouttière de la ligne, neutralisée pour que les panneaux s'alignent au pixel |
| **Longueur de la traversée** | en % ; 100 = un pixel vers le bas pour un pixel vers la gauche. Plus haut, la traversée est plus lente. Mode épinglé seulement. |
| **Souplesse du mouvement** | 0 = collé au scroll, 100 = glisse après l'arrêt. Défaut 70. Mode épinglé seulement. |
| **Accroche** | `scroll-snap` sur les panneaux. Bande latérale seulement — en épinglé, c'est la longueur de traversée qui règle le rythme. |
| **À partir de** | tous les écrans · tablette · bureau (défaut) · grand écran — en dessous, la section reprend son empilement vertical |
| **Progression** | fine barre en haut du hublot, remplie par `scaleX(var(--wf-hs-progress))`. Mode épinglé seulement : en bande latérale, la barre du navigateur suffit. |

**Le nombre de panneaux n'est pas un réglage** — il est mesuré. La v1 le lisait
dans l'arbre du builder ; la v2 compte les enfants du rail à l'exécution, ce qui
reste juste même si un panneau est masqué par une condition d'affichage.

**Une seule ligne dans la section, dont les colonnes sont les panneaux.** Avec
un seul panneau, le module ne fait rien et le dit :

```
WF défilement latéral : il faut au moins deux colonnes, 1 trouvée(s) dans <div…>
```

Même chose s'il ne trouve aucune ligne. Si une section ne bouge pas, la console
donne la raison, et l'élément fautif est cliquable dans l'inspecteur.

---

## Ce qu'il faut savoir avant de l'activer

Le mode épinglé a un coût, indépendamment de la qualité de la mise en œuvre :

- la page devient plus haute que son contenu, donc **la barre de défilement
  ment** sur la longueur réelle ;
- les ancres internes et la **recherche dans la page** n'atteignent pas les
  panneaux hors champ ;
- sur un panneau plus haut qu'un écran, le contenu est **rogné** par le hublot.

C'est pourquoi le défaut est « Non », que le seuil par défaut est « Bureau »
(≥ 960 px), et que la bande latérale — qui n'a aucun de ces défauts — est
proposée en premier.

---

## Installation

```
elements/modules/wf-section-scroll.php  ->  _sources\wf-yoo-elements\modules\
```

Deux ajouts dans `modules/bootstrap.php`, à côté de ceux de `wf-section-fx.php` :

```php
// Defilement lateral (bande / epingle) dans les reglages de Section
require_once __DIR__ . '/wf-section-scroll.php';
```

```php
// Defilement lateral ajoute a la Section native.
if ( function_exists( 'wf_shs_boot' ) ) {
    try {
        wf_shs_boot( $builder );
    } catch ( \Throwable $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WF section scroll: ' . $e->getMessage() );
        }
    }
}
```

**Rien à ajouter dans `assets/css/weframe-shared.css`.** La v1 y déposait deux
keyframes (`wf-hs-x`, `wf-hs-bar`) ; la v2 n'anime plus rien en CSS — le rail
est translaté par le script, la barre lit `--wf-hs-progress`. Si tu avais
recopié ces keyframes, **retire-les** : le fichier
`wf-section-scroll.keyframes.css` a disparu du dépôt.

Le ZIP `dist/wf-yoo-elements-sans-wc-6.31.0.zip` contient déjà tout cela câblé,
keyframes retirées comprises. (Le 6.30.0, qui portait la v1, a été supprimé :
c'est la version qui ne marchait pas.)

---

## Vérifié

**63 assertions dans un Chromium réel** (`hs2-probe.mjs`), sur le CSS et le
script réellement produits par `wf_shs_css()` et `wf_shs_script()`, et sur
**trois structures de DOM** — dont celle que le CSS seul ne pouvait pas traiter :

| Structure | Ce qui est vérifié en plus |
|---|---|
| grille dans un conteneur | — |
| **grille enfant direct de la section** | le hublot est bien fabriqué par le script |
| **deux niveaux de conteneurs** | la hauteur va sur le bon élément, le hublot colle |

Pour chacune des trois : section épinglée, hublot collant, hublot haut d'un
écran, course = (n−1) × largeur de panneau, hauteur posée = écran + course,
rail à zéro au début, à la moitié à mi-course, à la course entière à la fin,
hublot à `top: 0` sur toute la traversée, progression 0 → 0,5 → 1, et dernier
panneau bien au centre de l'écran en fin de course.

Plus :

- **souplesse** : à `ease 80`, le rail est mesuré *en retard* juste après un
  saut de scroll, puis rejoint sa cible à moins de 8 px ;
- **sens inverse** : part de la course entière, revient à zéro ;
- **traversée 200 %** : hauteur = écran + 2 × course ;
- **barre de progression** : présente (3 px) et à `scaleX(0,5)` à mi-course ;
- **bande latérale** : jamais épinglée, débordement horizontal réel, accroche
  `x mandatory`, écart de 24 px, panneau à ⅔ ;
- **un seul panneau** : pas épinglée, aucune hauteur imposée, et
  l'avertissement attendu en console ;
- **seuil d'écran** : à 700 px, plus épinglée, hauteur rendue, rail remis à
  plat, la ligne repasse à la ligne, **aucun débordement horizontal de page**.

**Pas vérifié** : le rendu dans un vrai YOOtheme. Le banc d'essai imite le
préfixeur (`.el-element` → `#id`) et un sous-ensemble fidèle d'UIkit
(`uk-section`, `uk-container`, `uk-grid` et leurs gouttières), mais la structure
réelle de la Section reste à confirmer à la première pose. La différence avec la
v1, c'est qu'une structure inattendue ne casse plus le module en silence : le
script cherche, mesure, et te dit en console ce qu'il n'a pas trouvé.

Le panneau du builder lui non plus n'a pas pu être testé (champs, conditions
`enable`, groupe dans l'onglet Paramètres) — limite habituelle du projet.
