# Marquee Gallery — le bombement des images

Correctif de `wf_marquee_gallery` : les images se bombaient jusqu'à devenir des
lentilles. Elles gardent désormais leur silhouette de photo.

## Ce qui n'allait pas

Le bombement est un **rayon vertical de `border-radius`**, posé avec un rayon
horizontal de 50 % :

```css
border-radius: 50% / 22px;
```

Le rayon horizontal de 50 % donne à chaque coin du haut la moitié de la largeur.
Les deux coins se rejoignent donc au milieu : le bord supérieur n'est plus un
segment mais **un seul arc**, le plus haut en son centre. Idem en bas. C'est
tout le bombement — aucun filtre, aucun SVG, aucune transformation 3D.

Le piège est dans l'autre moitié de la règle. Ce rayon vertical est une
**flèche d'arc en pixels**, pas une fraction de la hauteur. Tant qu'il reste
sous la moitié de la hauteur de l'image, il subsiste un morceau de bord latéral
droit de chaque côté et la forme se lit comme une photo. Passé cette moitié,
l'arc du haut rejoint celui du bas, les bords latéraux disparaissent et le
rectangle **s'effondre en lentille** : les quatre coins sont mangés, le sujet
est rogné, il ne reste que la colonne centrale.

Une valeur en pourcentage (`50% / 50%`) franchit ce seuil par construction,
quelle que soit la hauteur d'image. Une valeur en pixels le franchit dès que
l'image est un peu basse — ou dès qu'on baisse « Hauteur images » sans toucher
au bombement.

## Ce qui a changé

La flèche d'arc est saisie en pixels et **plafonnée à 30 % de la hauteur
d'image** :

```php
$arc = min( $bulge_depth, (int) floor( $img_h * 0.30 ) );
```

Le seuil de rupture étant à 50 %, 30 % laisse toujours **40 % de la hauteur en
bord latéral droit**. Le curseur peut donc être poussé à fond sans jamais
produire la lentille : au maximum, l'image est un coussin bien marqué, pas un
œil.

Le plafond est recalculé pour le mobile avec la hauteur mobile — c'est là que la
lentille apparaissait en premier, l'image y étant la plus basse pendant que la
flèche, elle, ne bougeait pas.

Trois réglages, groupe **Bombement** de l'onglet « Réglages » :

| Réglage | Rôle |
|---|---|
| `bulge` | Active le bombement. |
| `bulge_depth` | Flèche de l'arc en pixels (0–60), plafonnée comme ci-dessus. |
| `bulge_depth_mobile` | Idem pour le mobile. **0 = à l'échelle** : la flèche suit la réduction de hauteur, la courbure reste visuellement la même. |

Deux conséquences à connaître :

- **`radius` est ignoré quand le bombement est actif.** Un arrondi de coin et un
  arc ne tiennent pas dans le même `border-radius` : la propriété ne porte
  qu'un rayon par coin. Quand le bombement est coché, c'est lui qui décrit la
  forme ; le champ « Arrondi » le dit dans sa description.
- **Une largeur minimale** de 75 % de la hauteur est posée sur l'image, avec
  `object-fit: cover`. L'arc rogne les coins sur toute la largeur : un portrait
  étroit n'aurait plus grand-chose à montrer. Le cadrage prend le relais plutôt
  que la déformation.

## Vérifié, et pas vérifié

Vérifié dans un vrai Chromium (Playwright), en rendant `template.php` avec un
stub des fonctions WordPress, à 1400 px et 390 px :

- flèche 16 px sur des images de 215 px : bords latéraux droits intacts, arcs
  haut et bas symétriques, encoches entre images — le rendu visé ;
- la même en 390 px de large : la flèche descend à 9 px pour 120 px de hauteur,
  la courbure se lit identique ;
- curseur au maximum (60 px) sur des images de 200 px : plafonné à 60 px, soit
  80 px de bord droit conservés — coussin marqué, pas de lentille ;
- bombement décoché : `border-radius: 14px`, l'arrondi de coin classique
  revient tel quel.

**Pas vérifié** — la limite habituelle du projet : rien de ce qui se passe
*dans le panneau du builder*. L'affichage conditionnel des deux champs de
profondeur (`"show": "bulge"`, calqué sur `color_on_hover`) n'a pas pu être
testé ici. À contrôler à la première pose de l'élément dans YOOtheme.

Le ZIP n'a pas été rebâti : `build_elements.py` vit sur le poste de travail.
Recopier le dossier puis relancer la construction, voir
[README.md](README.md).
