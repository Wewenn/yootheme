# Éléments WeFrame pour `wf-yoo-elements`

Trois éléments YOOtheme Pro, écrits dans la convention exacte du plugin
`wf-yoo-elements` (v6.28.0) : un dossier par élément, `element.json` +
`template.php` + `images/ic30.svg`, `icon` **et** `iconSmall` déclarés,
collections saisies avec le champ natif « Articles » (`content-items`).

| Dossier | Nom interne | Titre dans le panneau | Groupe |
|---|---|---|---|
| `panel-pp/` | `wf_panel_pp` | Panneau ++ | WeFrame · Contenu & Sections |
| `perspective-slider/` | `wf_persp_slider` | Carrousel perspective infini | WeFrame · Scroll & Images |
| `perspective-slider-item/` | `wf_persp_slider_item` | Image (carrousel perspective) | WeFrame · Scroll & Images |

## ZIP prêts à installer

**`dist/wf-yoo-elements-sans-wc-6.47.0.zip` — c'est celui à poser.** Construit
sur le build 6.44.0 fourni le 10/09, avec ses 236 définitions intactes.

| Version | Ce qu'elle apporte | Base |
|---|---|---|
| **6.47.0** | le panneau [Éléments WeFrame](INDEX-BUILDER.md) dans le builder | 6.46.0 |
| 6.46.0 | les deux modules de défilement de Section : [sections côte à côte](SECTIONS-COTE-A-COTE.md) et [défilement latéral](DEFILEMENT-LATERAL.md) | 6.44.1 |
| 6.44.1 | les quatre correctifs de la [revue](REVUE-6.44.md), dont la fatale PHP 8 d'`agenda-pro` | 6.44.0 |
| 6.31.0 | Panneau ++, carrousel perspective, correctif `container: true` | 6.28.0 |

Chaque version ne touche qu'une poignée de fichiers : les modules concernés,
leur câblage dans `bootstrap.php`, l'en-tête de version et les notes.

**Le 6.31.0 est conservé** parce qu'il est le seul à contenir le **carrousel
perspective infini** : le build 6.44 du 10/09 ne l'a plus (voir
[REVUE-6.44.md](REVUE-6.44.md), « dix définitions disparues »). Sa base est en
revanche seize versions en arrière — n'installe pas les deux.

La variante complète (avec WooCommerce) n'a jamais pu être régénérée : ses
sources n'ont pas été fournies.

**Vider le cache YOOtheme après l'installation.**

## Installation depuis les sources

L'arborescence sous `elements/` reproduit celle du plugin. Il suffit donc de
recopier les trois dossiers :

```
elements/modules/element/panel-pp                 ->  _sources\wf-yoo-elements\modules\element\panel-pp
elements/modules/element/perspective-slider       ->  _sources\wf-yoo-elements\modules\element\perspective-slider
elements/modules/element/perspective-slider-item  ->  _sources\wf-yoo-elements\modules\element\perspective-slider-item
```

puis de rebâtir les ZIP :

```powershell
cd "C:\Users\Ewen\Documents\Claude\We Frame\_build_suite"
python build_elements.py 6.47.0
```

`bootstrap.php` charge les éléments avec `./element/*/element.json` : rien à
déclarer, les trois dossiers sont pris automatiquement.

Penser à reporter aussi, sans quoi un rebuild depuis `_sources` perdrait
le travail que le ZIP livré contient :

- le correctif `container: true` — quatre lignes, voir
  [CORRECTIF-container-true.md](CORRECTIF-container-true.md) ;
- les modules `modules/wf-section-scroll.php`, `modules/wf-section-ribbon.php`
  et `modules/wf-builder-index.php`, et leurs ajouts dans `bootstrap.php` — voir
  [INDEX-BUILDER.md](INDEX-BUILDER.md),
  [DEFILEMENT-LATERAL.md](DEFILEMENT-LATERAL.md) et
  [SECTIONS-COTE-A-COTE.md](SECTIONS-COTE-A-COTE.md). Rien à ajouter dans `assets/` :
  si les keyframes `wf-hs-x` et `wf-hs-bar` de la v1 du défilement latéral y ont
  été recopiées, il faut au contraire les retirer.

---

## Panneau ++ (`wf_panel_pp`)

Un panneau à fond couleur ou image, coupé en deux : le contenu — saisi dans la
**sous-mise en page** (mini-constructeur, champ `builder-fragment`) — et un
visuel posé à côté, au-dessus ou en dessous.

Le réglage central est **« Marge autour de l'image »** :

- **Aucune — à fond perdu** : l'image touche les bords du panneau. La marge
  intérieure ne porte que sur la colonne de contenu ; l'arrondi du panneau plus
  `overflow:hidden` rognent proprement les coins de l'image. C'est le rendu
  « hero » de la maquette.
- **La même que le contenu** : l'image reprend `pad_x` / `pad_y`.
- **Personnalisée** : une valeur libre.

Deux façons de dimensionner le visuel :

- **Proportions automatiques + hauteur 0** → mode « étirée » : l'image sort du
  flux et épouse la hauteur du panneau, imposée par le texte. Un portrait ne
  fait donc pas grandir tout le bloc. Un plancher de 180 px évite un panneau
  vide qui s'effondre ; « Hauteur minimale » sert de garde-fou.
- **Proportions choisies** (1:1, 16:9…) ou **hauteur en px** → l'image reste
  dans le flux et c'est elle qui donne la hauteur.

Responsive : point de bascule au choix (959 px, 639 px ou jamais), ordre du
visuel une fois empilé, hauteur d'image en version empilée, masquage sur
mobile, marges intérieures et alignement du texte propres au mobile, largeur de
colonne dédiée à la tablette. `background-attachment:fixed` est repassé en
`scroll` sous 960 px — iOS ne sait pas l'afficher.

Zéro JavaScript.

### Reproduire la maquette

Fond `#8ec08e` · arrondi 18 · image à droite · largeur 42 % · écart 0 ·
marge autour de l'image « Aucune — à fond perdu » · marges intérieures 48/48 ·
titre, texte et bouton déposés dans la sous-mise en page.

---

## Carrousel perspective infini (`wf_persp_slider` + `wf_persp_slider_item`)

Une rangée d'images qui défile sans fin dans une scène en perspective : plus une
carte s'éloigne du centre, plus elle pivote (`rotateY`) et recule (`translateZ`),
ce qui donne la courbure de cylindre.

**Ce qui n'est pas un rail.** Les cartes ne sont pas dans un bloc que l'on
translate d'un seul geste : chacune est placée à sa position calculée, ramenée
dans une période par un modulo. Sans rail, pas de saut au bouclage — et chaque
carte peut recevoir sa propre rotation, ce qu'un rail unique interdit.

Réglages : profondeur du point de fuite, courbure (cylindre vers l'intérieur,
mur vers l'extérieur, ou plate), rotation et recul aux bords, courbure verticale
en arc, fondu aux bords, taille et espacement des cartes, arrondi, ombre, noir
et blanc au repos, légende (jamais / toujours / au survol), vitesse et sens,
pause au survol, glisser à la souris ou au doigt, fond, bords estompés.

Responsive : largeur de carte par palier (bureau / tablette / mobile), hauteur
et espacement propres au mobile, et une case « aplatir sur mobile » qui coupe
rotation et profondeur. Ces valeurs vivent dans des **variables CSS**
(`--w`, `--h`, `--g`, `--flat`) relues à chaque mesure : ce sont les media
queries qui décident, pas un JavaScript qui devinerait la largeur de l'écran.

Sobriété : le défilement s'arrête hors écran (`IntersectionObserver`), au survol
et au focus clavier, et ne démarre pas du tout si le visiteur a demandé moins
d'animations. Les copies portent `aria-hidden` et sortent de la navigation au
clavier. **Sans JavaScript, les cartes restent affichées en simple rangée
horizontale** — rien ne disparaît.

Un « Recul aux bords » au-delà de ~350 px fait chevaucher les cartes : c'est
voulu pour un effet de couloir, à baisser sinon.

---

## Vérifié, et pas vérifié

Vérifié dans un vrai Chromium (Playwright), sur les gabarits réellement rendus,
à 1280 / 900 / 560 px :

- Panneau ++ : image à fond perdu bien collée aux trois bords (décalage 0/0/0),
  colonnes 58/42, image à gauche avec marge de 24 px, bandeau 16:9 au-dessus,
  empilement correct aux deux paliers, aucun débordement horizontal de la page.
- Carrousel : démarre en entrant dans l'écran et s'arrête en sortant, défile
  dans le bon sens, se suspend au survol, boucle sans trou sur 90 frames,
  répond au glissement, reconstruit ses copies au redimensionnement, clones
  `aria-hidden` et hors tabulation, rendu correct sans JavaScript.

**Pas vérifié** — la limite habituelle du projet : rien de ce qui se passe
*dans le panneau du builder*. Champs affichés ou non, conditions `enable`,
groupes, icônes, sélecteur de contenu dynamique, bouton « Ajouter un média » et
mini-constructeur n'ont pas pu être testés ici. À contrôler à la première pose
des éléments dans YOOtheme.
