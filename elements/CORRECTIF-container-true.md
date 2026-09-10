# Correctif — `container: true` manquant sur 4 éléments

> **À reporter dans `_sources`.** Le correctif ci-dessous est appliqué dans le
> ZIP livré (`dist/wf-yoo-elements-sans-wc-6.29.0.zip`), mais **pas** dans
> `_sources\wf-yoo-elements\`. Le prochain `python build_elements.py` referait
> donc ressortir le bug. Une ligne à ajouter dans quatre fichiers.

## Le défaut

Quatre éléments lisent `$children` dans leur `template.php` sans déclarer
`"container": true` dans leur `element.json`. YOOtheme ne leur passe alors
aucun enfant : c'est exactement la famille de bugs corrigée en v6.26.0 sur
`list-reveal` et `scatter-cards` (« un élément qui lit `$children` sans être
conteneur → il n'affiche jamais rien, et rien ne le signale »).

Trois d'entre eux viennent du lot v6.28.0, le quatrième est plus ancien.

| Élément | Nom interne | Titre dans le panneau |
|---|---|---|
| `hover-preview` | `wf_hover_preview` | Aperçus au survol |
| `icon-fan` | `wf_icon_fan` | Éventail d'icônes |
| `image-corridor` | `wf_image_corridor` | Couloir d'images |
| `infinite-gallery` | `wf_infinite_gallery` | Galerie infinie |

Mesuré en rendant les vrais gabarits avec, puis sans, enfants :

```
infinite-gallery   avec 4 enfants :   6626 octets   |  sans enfant : 0 octets
image-corridor     avec 4 enfants :   3936 octets   |  sans enfant : 0 octets
hover-preview      avec 4 enfants :   5197 octets   |  sans enfant : 4386 octets
icon-fan           avec 4 enfants :   1802 octets   |  sans enfant : 0 octets
```

Trois n'affichent donc **rien du tout** en production ; `hover-preview` affiche
son volet de texte mais aucune vignette. Les gabarits, eux, sont bons : dès que
les enfants arrivent, tout se rend. Il ne manquait que le drapeau.

## Le correctif

Une ligne, juste après `"element": true,` dans chacun des quatre
`element.json` :

```diff
     "icon": "${url:images/ic30.svg}",
     "iconSmall": "${url:images/ic30.svg}",
     "element": true,
+    "container": true,
     "width": 520,
```

Fichiers concernés :

```
modules/element/hover-preview/element.json
modules/element/icon-fan/element.json
modules/element/image-corridor/element.json
modules/element/infinite-gallery/element.json
```

## Comment le bug a été trouvé

En balayant les 199 éléments : pour chacun, l'`element.json` est lu et le
`template.php` cherché pour `$children` ou `$builder->render`. Tout élément qui
en contient sans porter `"container": true` est signalé. Après correction, le
balayage ne remonte plus rien — et il ne remonte rien non plus sur `icon`,
`iconSmall`, les noms internes en double ou `&#038;`.

Ce contrôle vaut la peine d'être ajouté à `_devtest/tools/audit_elements.py`,
puisque c'est la troisième fois que ce défaut passe en production.

## Reste ouvert

Les **9 titres d'items en double** signalés dans la passation sont toujours là
(8 × « Image », 5 × « Carte », 3 × « Étape », 2 × « Élément », « Mot », « Avis »,
« Entrée », « Logo », « Lien »). C'est le lot C, non traité ici. Le nouvel item
livré s'appelle « Image (carrousel perspective) » : il n'alourdit pas la liste.
