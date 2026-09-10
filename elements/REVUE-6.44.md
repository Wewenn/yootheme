# Revue de wf-yoo-elements 6.44.0 → correctifs 6.44.1

Analyse du ZIP fourni le 10/09 : 1 199 fichiers, **702 fichiers PHP passés au
parseur PHP 8.4**, **236 `element.json` validés** (239 dossiers), plus le
balayage habituel : `$children` sans `container`, `icon`/`iconSmall`, items qui
se déclarent élément, titres en double, entités mal encodées, `wf_uid`,
échappement des sorties, points d'entrée AJAX, appels réseau, SQL.

Le script est dans [`tools/audit-elements.py`](tools/audit-elements.py) —
il se relance sur n'importe quel build.

---

## Corrigé dans le 6.44.1

### 1. `agenda-pro` ne compilait pas — bloquant

`modules/element/agenda-pro/template.php`, ligne 14 :

```php
esc_attr('48%'===$p['image_ratio']?'0.48':'100%'===$p['image_ratio']?'1':'0.62')
```

Ternaire imbriqué sans parenthèses : déprécié en PHP 7.4, **erreur fatale de
compilation depuis PHP 8.0** — la version que l'en-tête exige.

```
PHP Fatal error: Unparenthesized `a ? b : c ? d : e` is not supported
                 in agenda-pro/template.php on line 14
```

Ce n'est pas une erreur d'exécution qu'un `try` rattrape : PHP n'arrive pas à
lire le fichier. Poser l'élément faisait tomber la page en « erreur critique »,
sur le site comme dans l'aperçu du builder. Seul fichier des 702 dans ce cas.

Correctif : une paire de parenthèses. Vérifié en rendant le vrai gabarit avec
des substituts WordPress — trois événements sortis, triés, les passés marqués
`is-past`, et les trois branches rendent bien `0.48` / `1` / `0.62`.

### 2. Trois items se déclaraient éléments

`composable-source-item`, `property-compare-item`, `property-map-item`
portaient `element: true`. Ils apparaissaient donc dans la liste « ajouter un
élément » du panneau, où ils se rendent hors de leur parent sans rien produire.
Les items sains du plugin (`drag-gallery-item`, `stack-spread-item`…) ne le
déclarent pas.

### 3. `composable-source-item` n'avait pas d'icône

Ni `icon`, ni `iconSmall`, ni `images/ic30.svg`, ni `group` : icône vide dans le
panneau et hors des catégories WeFrame. Icône ajoutée (les deux accolades du
jeton `{{variable}}`, dans le style maison — viewBox 256, 30×30,
`currentColor`) et rattachement au groupe de son parent.

### 4. Une URL d'image non échappée

`drag-gallery` : `<img src="<?= $photo['src'] ?>">`, seul `<?=` sans
échappement sur les 236 gabarits. La valeur vient de la médiathèque, donc d'un
auteur et pas d'un visiteur — `esc_url()` suffit.

**Après correctifs : 702 fichiers PHP, 0 erreur ; 236 JSON valides ; plus aucun
item avec `element: true` ; plus aucune icône manquante.**

---

## Signalé, pas corrigé

Hors du périmètre demandé, mais à traiter dans `_sources`.

### Dix définitions ont disparu sans changelog

`wf_dot_matrix`, `wf_hover_preview`, `wf_icon_fan` (+ item),
`wf_image_corridor` (+ item), `wf_infinite_gallery` (+ item),
`wf_persp_slider` (+ item). Aucun de ces noms n'existe plus dans le build : ce
ne sont pas des renommages, `hover-preview-list` est un nouvel élément.

**Une page qui en utilise un ne le rend plus** — le nœud devient inconnu.
Quatre d'entre eux sont ceux dont j'avais livré le correctif `container: true`.

Deux suppressions documentées (6.32 : Services, Modal) sont à moitié faites :
`services-hover-modal/`, `services-hover-modal-item/` et `draggable-modal/`
n'ont plus que leur dossier `images/`. Sans effet — le glob
`element/*/element.json` ne les charge pas — mais c'est l'écart entre 239
dossiers et 236 définitions.

### Deux appels à des services tiers

`qr-code` construit `https://api.qrserver.com/…&data=<contenu>` : chaque
visiteur envoie le contenu du QR à un tiers, avec son IP. Le champ accepte une
source dynamique. Si le service tombe, tous les QR du site deviennent des
images cassées. Un encodeur QR en PHP pur tient en une quinzaine de Ko.

`store-locator` charge Leaflet depuis `unpkg.com` (sans SRI), et l'enqueue se
fait depuis le gabarit — donc au rendu, avec une carte non stylée le temps que
la feuille arrive en pied de page.

Les deux contredisent le principe affiché dans les notes 6.29 (« aucun CDN et
aucune requête vers un service externe ») ; `property-map` prouve que le natif
est faisable.

### Trois durcissements

- **`wf-load-more`** : `meta_key` / `meta_value` / `meta_compare` viennent de
  `$_POST['opts']` et ne sont que `sanitize_key`és. Avec `orderby=meta_value`,
  un visiteur peut trier des articles publics par une métadonnée privée et lire
  l'ordre, ou tester l'existence d'une clé. Aucune valeur n'est affichée : la
  fuite se limite à un oracle d'ordre et d'existence. Refuser les clés
  commençant par `_`, ou figer `meta_key` sur les props enregistrées.
- **`access-condition`** : le filtrage est irréprochable (le `return` précède le
  rendu des enfants, `hash_equals` pour les valeurs), mais l'aperçu builder
  repose sur `is_admin()`, vrai aussi pendant tout appel `admin-ajax`, `nopriv`
  compris. Aucun chemin public ne rend ce gabarit aujourd'hui ; par sécurité,
  conditionner à `current_user_can('edit_posts')`.
- **114 Ko sur chaque page** : `weframe-shared` (css+js), `wf-motion-kit`
  (css+js, 85 Ko à eux deux), `wf-drag-gallery` (css+js) et `wf-section-fx.js`
  sont enqueués sans condition. Une page sans élément WeFrame les télécharge.
  À l'inverse `matter.min.js` (81 Ko) n'est chargé que par `gravity-world` :
  c'est le bon modèle.

### Cohérence du catalogue

- En-tête 6.44.0, **notes qui s'arrêtent à 6.40** ; `NOUVEAUTES-6.29.md` et
  `VALIDATION-6.29.md` sont titrés « 6.31.0 » à l'intérieur.
- **Deux catégories jumelles** : « Texte & Titres » (20) et « Texte &
  Typographie » (3 : `handwriting`, `media-words`, `media-words-item`).
- **11 titres d'items en double** (« Image » ×6, « Carte » ×5, « Étape » ×3,
  puis « Événement », « Élément », « Mot », « Avis », « Entrée », « Logo »,
  « Lien », « Panneau » ×2). C'était 9 en début de semaine.
- `horizontal-words` déclare `bg`, `distance` et `height` : jamais exposés dans
  le panneau, jamais lus par le gabarit.
- `pinned-expertise-*` nomme son icône `images/icon.svg` au lieu de `ic30.svg`
  (le fichier existe, le JSON pointe dessus — écart de convention).
- `wp_unique_id()` dans `pinned-expertise-scroll` alors que `wf_uid()` existe.
- Les 13 `.md` sont livrés dans le ZIP, donc déposés sur le site et lisibles à
  leur URL.

---

## Ce qui est solide

- Les quatre points d'entrée AJAX vérifient tous un nonce ; celui d'admin exige
  `manage_options`.
- `wf-data-sources.php` utilise **`wp_safe_remote_get`** — pas de SSRF via une
  URL de CSV ou d'iCal — avec cache par transient, redirections bornées et
  500 lignes maximum.
- `access-condition` filtre réellement côté serveur : le contenu refusé
  n'existe pas dans le HTML.
- La seule requête SQL brute est une chaîne littérale.
- Aucun élément ne lit `$children` sans `container: true` : la famille de bugs
  qui revenait trois fois est absente du catalogue.
- Aucun nom interne en double, aucune entité HTML dans les libellés, aucun `id`
  DOM en dur sans `wf_uid`.

---

## État de mes livraisons dans ce build

| Livré | Dans la 6.44 |
|---|---|
| Panneau ++ | **Intégré en 6.33, à l'octet près** (un saut de ligne final d'écart) |
| Carrousel perspective infini | Absent (`wf_persp_slider` introuvable) |
| Défilement latéral de Section | Absent : pas de `wf-section-scroll.php`, aucune trace de `wf_hs` |
| Correctif `container: true` ×4 | Sans objet : les quatre éléments ont été supprimés |

`wf-section-fx.php` occupe `wf_anim*`, `wf_bg*` et `wf_edge*` : **aucune
collision** avec les champs `wf_hs*`, le module se pose à côté sans rien
toucher.
