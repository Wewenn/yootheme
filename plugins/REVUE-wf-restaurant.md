# Revue de WF Restaurant — v1.15.1 → v1.16.0

Plugin relu en entier : `wf-restaurant.php` (2318 lignes), `modules/bootstrap.php`
et les 5 éléments YOOtheme. Le code source corrigé est dans
[`wf-restaurant/`](wf-restaurant/), le ZIP installable dans
`dist/wf-restaurant-1.16.0.zip`.

**À reporter dans `_sources`.** Comme pour le correctif `container: true`, le
patch vit ici et dans le ZIP, pas dans tes sources : sans report, la prochaine
livraison ferait ressortir les bugs.

---

## 1. « Aujourd'hui » ne marche pas — trouvé et reproduit

Ce n'est pas un souci d'affichage : **WordPress refuse l'accès à la page** avec
un 403 « vous n'avez pas l'autorisation d'accéder à cette page ».

Le sous-menu est enregistré en **priorité 1** :

```php
add_action( 'admin_menu', function () {
    add_submenu_page( 'wf-restaurant', "Aujourd'hui", "📅 Aujourd'hui", … );
}, 1 );          // ← avant tout le reste
```

Or c'est `add_menu_page()`, exécuté en **priorité 10**, qui remplit
`$admin_page_hooks['wf-restaurant']`. En priorité 1 ce tableau est encore vide,
donc `get_plugin_page_hookname()` fabrique le nom de hook
`admin_page_wf-restaurant-today` au lieu de
`mon-restaurant_page_wf-restaurant-today`. Au chargement de la page,
`$admin_page_hooks` est cette fois rempli : WordPress recalcule le **bon** nom,
ne le trouve pas dans `$_registered_pages`, et `user_can_access_admin_page()`
renvoie `false`.

Les quatre autres pages, enregistrées après le parent, ont le bon nom — d'où un
bug qui ne touche que celle-là.

### Reproduit avec le code de WordPress

Plutôt que de raisonner sur la doc, j'ai récupéré les vraies fonctions de
`wp-admin/includes/plugin.php` (`add_menu_page`, `add_submenu_page`,
`get_plugin_page_hookname`, `get_admin_page_parent`, `get_plugin_page_hook`,
`user_can_access_admin_page`) et rejoué les deux scénarios :

```
SCÉNARIO ACTUEL (priorité 1)
  Hooks enregistrés : admin_page_wf-restaurant-today   ← mauvais nom
  wf-restaurant          OK
  wf-restaurant-today    ÉCHEC  user_can_access_admin_page() = false → wp_die 403
  wf-restaurant-menu     OK
  wf-restaurant-resa     OK
  wf-restaurant-app      OK

SCÉNARIO CORRIGÉ (priorité 11 + $position = 0)
  Hooks enregistrés : mon-restaurant_page_wf-restaurant-today
  wf-restaurant-today    OK
  … les cinq pages passent
```

### Le correctif

Priorité **11** — après `wf_resto_menu()` — et le `$position = 0` de
`add_submenu_page()` (WP 5.3+) pour garder l'onglet en tête du sous-menu, ce que
la priorité 1 cherchait à obtenir.

---

## 2. Le bug des icônes

Le plugin se sert des émoji comme système d'icônes : `📅` et `🎨` dans les
libellés de menu, `🍽️`, `📷`, `✅`, `✕`, `👻`, `📝`, `🔔`, `🚫`, `📞`, `🔁`,
`⚠`, `🛒`, `⠿` dans les écrans.

**WordPress ne les laisse pas tels quels** : le script `wp-emoji-release.min.js`
remplace chaque émoji du DOM par une balise
`<img class="emoji" src="https://s.w.org/images/core/emoji/…">`. Dès que ce
domaine ne répond pas — extension de confidentialité, bloqueur, filtrage
réseau, poste hors ligne — **chaque émoji devient une icône cassée**. Ce qui
correspond exactement à ta capture : une image cassée juste avant « Menu » et la
description « Une carte par plat… », c'est-à-dire le `🍽️` du titre `<h1>` de la
page Menu.

**Vérification en 5 secondes** : clic droit sur l'icône cassée → Inspecter. Si
tu vois `<img class="emoji" src="https://s.w.org/…">`, c'est bien ça.

### Le correctif

Deux niveaux, parce que les libellés de menu s'affichent sur **toutes** les
pages de l'admin, pas seulement les nôtres :

- **Sur les écrans du plugin** (`?page=wf-restaurant*`), le remplacement émoji
  est retiré (`print_emoji_detection_script`). Le navigateur affiche alors
  l'émoji de la police système : aucune requête réseau, rien à casser. Ton
  design émoji reste intact.
- **Dans les libellés de menu**, `📅` et `🎨` deviennent des **dashicons**
  (`dashicons-calendar-alt`, `dashicons-art`), servies localement par
  WordPress, donc insensibles au problème.

C'est le seul point de cette revue dont je n'ai pas la preuve directe : je n'ai
pas accès à ton install. Mais le correctif est bon dans tous les cas, puisqu'il
supprime une dépendance réseau pour afficher une icône.

---

## 3. Les autres corrections embarquées dans la 1.16.0

**Une fermeture exceptionnelle programmée neutralisait le bouton Ouvert/Fermé.**
`wf_resto_is_open_now()` testait la case « active » avant de regarder les dates :

```php
if ( ! empty( $s['exception']['active'] ) ) {   // ← vrai même hors période
    if ( $ge && $le ) { $open = false; … }
} elseif ( 'open' === $s['override'] ) { … }    // ← jamais atteint
```

Une fermeture saisie pour les vacances de février rendait donc le bouton
manuel sans effet dès janvier. C'est maintenant la **période** qui décide, pas
la case.

**« Aujourd'hui » était calculé dans le fuseau de WordPress, pas du restaurant.**
`current_time( 'Y-m-d' )` suit `Réglages → Général → Fuseau`, resté sur UTC sur
beaucoup d'installations, alors que les horaires sont calculés avec `$s['tz']`
(Europe/Paris). Après minuit à Paris, les deux ne désignaient plus le même jour
et l'écran listait les réservations de la veille. Trois helpers —
`wf_resto_tz()`, `wf_resto_today()`, `wf_resto_now_min()` — donnent maintenant
une seule horloge, celle du restaurant, utilisée par l'écran du jour, les
créneaux et l'envoi de réservation.

**Le délai minimum avant réservation n'était appliqué que côté client.** Le JS
des créneaux masque les heures passées ; `wf_resa_submit()`, présenté en
commentaire comme « autorité serveur », ne le revérifiait pas. Un POST rejoué
pouvait réserver une heure déjà passée. Le contrôle est maintenant fait aussi
côté serveur.

**Le badge des demandes en attente plafonnait à 50.** `wf_resa_count_new()`
faisait `posts_per_page => 50` puis `count()`.

**`<img src="">` sur les plats sans photo.** Le CSS masquait bien l'image
(`.is-empty img{visibility:hidden}`), mais le navigateur déclenchait quand même
une requête vers l'URL de la page — et l'icône cassée réapparaissait si le CSS
tardait. Remplacé par un GIF transparent en `data:`.

---

## 4. Trouvé, pas corrigé — ton arbitrage

**Les services qui passent minuit ne fonctionnent pas.** Partout où une plage
est lue, le code fait `if ( $c <= $o ) { continue; }`. Un service 19:00 → 01:00
produit donc **zéro créneau** réservable, et le badge annonce « fermé » toute la
soirée. Bloquant pour une brasserie ou un bar. Le correctif demande de traiter
la plage comme deux morceaux (19:00→24:00 et 00:00→01:00) dans
`wf_resto_is_open_now()`, `wf_resa_gen_slots()` et le JS de l'élément Horaires —
trois endroits, d'où le fait que je ne l'aie pas fait sans ton feu vert.

**Le tableau des horaires ne rafraîchit jamais « aujourd'hui ».** Le badge est
recalculé toutes les 60 s en JS, mais la ligne surlignée du tableau est produite
en PHP et jamais retouchée. Derrière un cache de page — et le plugin en purge
plusieurs, donc il y en a — le surlignage reste figé sur le jour de la mise en
cache. À traiter dans `update()` : retirer `wf-rh-today` et le reposer sur la
ligne du jour.

**Le surlignage par défaut est invisible sur fond clair.** `table_today_bg` vaut
`rgba(255,255,255,0.08)` : du blanc à 8 % sur du blanc. Si tu me dis que
« aujourd'hui ne marche pas » visait le tableau et non la page admin, c'est
probablement ça, et il suffit de changer la valeur par défaut.

**Le champ `timezone` est défini mais absent des onglets** de l'élément
Horaires : il reste figé sur `Europe/Paris` et personne ne peut le changer. Soit
le remettre dans un onglet, soit le supprimer — c'est le défaut « champ appelé
mais jamais atteignable » déjà vu sur les autres éléments.

**Identifiants DOM dupliqués.** Les éléments Horaires et Bandeau construisent
leur `id` avec `md5()` des réglages, sans passer par `wf_uid()`. Deux badges
identiques sur une page partagent le même `id` : le HTML devient invalide et le
script ne pilote que le premier. C'est exactement ce que la v6.27.0 a corrigé
sur les 115 éléments de `wf-yoo-elements`.

**`wf_resa_client_stats_map()` charge 1000 réservations à chaque affichage** de
l'écran « Aujourd'hui », pour calculer les statistiques d'une poignée de clients
attendus le jour même. À restreindre aux téléphones du jour.

**`🌶` dans l'élément Menu** est un émoji sur le **site public** : même
fragilité que ci-dessus, et là je n'ai pas touché au front. Un badge texte ou
une petite SVG serait plus sûr.

**Le clic sur « Mon restaurant » ouvre « Aujourd'hui »**, pas « Réglages » —
conséquence de la position 0, que j'ai conservée puisque c'est visiblement
l'intention. À dire si tu préfères l'inverse.

**Aucune traduction.** Le `Text Domain: wf-restaurant` est déclaré mais il n'y a
pas un seul `__()` dans les 2318 lignes, ni de `load_plugin_textdomain()`. Sans
importance tant que la clientèle est française, bloquant le jour où un client ne
l'est pas.

---

## 5. Idées

### Ce qui rapporterait le plus, tout de suite

**Le balisage schema.org.** Le plugin connaît les horaires, la carte et les prix
— et n'émet aucun JSON-LD. Un bloc `Restaurant` avec
`openingHoursSpecification`, `hasMenu`, `servesCuisine`, `priceRange` et
`acceptsReservations` donne à Google de quoi afficher les horaires et le menu
directement dans les résultats. Pour un restaurant c'est le meilleur rapport
travail / visibilité de toute cette liste, et le savoir-faire existe déjà dans
`wf_testimonial_schema()`.

**Le QR code de la carte.** Déjà noté comme idée dans la passation, jamais
commencé, et le contexte s'y prête : la carte est déjà pilotable au téléphone,
il manque juste un QR imprimable pointant vers la page menu.

**Fermetures récurrentes et jours fériés.** Aujourd'hui une fermeture
exceptionnelle est une plage de dates unique. « Fermé tous les lundis midi »,
les congés annuels et les fériés français doivent être ressaisis à la main.

### Réservations

- **Liste d'attente** quand un service est complet, avec relance automatique dès
  qu'une place se libère (le no-show est déjà suivi, la brique existe).
- **Blocage par service** plutôt que par journée entière : « fermé ce midi »
  demande aujourd'hui de bloquer tout le jour.
- **Capacité par table** plutôt qu'un total de couverts, pour éviter d'accepter
  4 × 2 couverts quand il ne reste que des tables de 6.
- **Confirmation en un clic par le client** (lien signé dans l'e-mail) : le
  mécanisme de jeton HMAC de `wf_resa_action_handler()` est déjà là, il ne sert
  que côté restaurateur.
- **Export CSV et impression du service**, pour le classeur de la salle.
- **Heure d'envoi du rappel J-1** : le cron est programmé à « activation + 10
  minutes », donc à une heure arbitraire. Un réglage vaudrait mieux.

### Carte

- **Ardoise du jour** : un élément qui n'affiche que les plats marqués « plat du
  jour », avec la date.
- **Allergènes** : les ingrédients sont déjà saisis, il manque la liste
  réglementaire des 14 allergènes et son affichage.
- **Menus / formules** (entrée + plat + dessert à prix fixe), qui n'existent pas
  aujourd'hui.

### Confort du restaurateur

- **Écran « Semaine »** en complément d'« Aujourd'hui » : la charge à venir en un
  coup d'œil.
- **Statistiques simples** : couverts par jour, taux de no-show, plats les plus
  demandés.
- **Historique par client** déjà calculé mais seulement affiché en compteur ;
  une fiche client tiendrait en peu de code.

---

## Ce qui a été vérifié, et ce qui ne l'a pas été

**Vérifié :** analyse syntaxique PHP des 6 fichiers (PHP 8.4, 0 erreur), les 5
`element.json` valides, tous les champs des onglets définis, `icon` et
`iconSmall` présents partout, et le mécanisme de menu rejoué avec le vrai code
de WordPress. Sécurité : chaque `admin_post_*` contrôle la capacité **et** le
nonce, les trois points d'entrée AJAX publics vérifient le nonce, les sorties
sont échappées — rien à signaler de ce côté.

**Pas vérifié :** le rendu réel dans WordPress. Ni l'admin, ni les éléments dans
le builder YOOtheme, ni les envois d'e-mails, ni les notifications push. Le
diagnostic du 403 est démontré par le code de WordPress lui-même ; celui des
icônes reste une hypothèse, très probable mais à confirmer par l'inspecteur.
