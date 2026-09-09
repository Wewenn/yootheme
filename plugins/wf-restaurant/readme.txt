=== WF Restaurant ===
Version: 1.0.0
Requires PHP: 7.4+
Auteur: WeFrame Studio

Éléments YOOtheme pour restaurateurs + une interface mobile ultra-simple pour piloter
le statut du restaurant sans ouvrir le builder.

== Éléments YOOtheme (groupe WeFrame) ==
- Horaires / Statut restaurant : bandeau « Ouvert / Fermé » calculé en direct (couleurs,
  heure suivante, table des horaires). NOUVEAU : champ « Source des horaires ».
    • « Cet élément » : horaires fixes saisis dans le builder (comportement classique).
    • « Réglages du restaurant » : lit le statut central -> pilotable au téléphone.
- Menu restaurant (+ items) : cartes de plats, prix, badges.

== Interface mobile : WeFrame > Mon restaurant ==
Trois réglages, pensés pour le téléphone :
1. Ouvert / Fermé maintenant — « Suivre horaires » (auto) ou forcer Ouvert / Fermé.
2. Fermeture exceptionnelle — dates + message (congés, imprévu). Prioritaire sur tout.
3. Horaires de la semaine — midi & soir, par jour, + fuseau horaire.
Chaque changement vide les caches -> les bandeaux « statut » du site se mettent à jour.

== Comment ça marche (important) ==
- Le bandeau ne réécrit PAS le builder : il LIT un statut central (option WordPress).
- Vous changez le statut sur votre téléphone -> tous les bandeaux en mode « Réglages du
  restaurant » se mettent à jour d'un coup. Robuste, aucune corruption des pages.
- Placez l'élément « Horaires / Statut » (mode « Réglages du restaurant ») où vous voulez.
- Sans YOOtheme, un shortcode de secours existe : [wf_statut_resto].

== Rôle « Restaurateur » ==
- À l'activation, le plugin crée un rôle « Restaurateur » qui ne voit QUE la page
  « Mon restaurant » (interface épurée). Créez un compte à ce rôle pour votre client :
  il se connecte sur son téléphone et ne voit que l'essentiel.
- Un administrateur garde l'accès complet (aucun risque de blocage).
- Astuce client : « Ajouter à l'écran d'accueil » -> la page devient comme une appli.

== Notes ==
- Mono-établissement (1 site = 1 restaurant). Pour du multi-vendeur, voir la solution WCFM.
- Ces éléments ont été SORTIS de WF YOOtheme Elements : mettez à jour les deux plugins
  ensemble pour éviter un doublon d'éléments.

== v1.1.0 ==
- MENU pilotable au téléphone : WeFrame > Menu. Ajoutez/retirez des plats (nom, prix,
  description), cochez « Plat du jour » ou « Épuisé » (grisé + prix barré sur le site).
  L'élément « Menu Restaurant » a un champ « Source des plats » -> « Réglages du restaurant ».
- RÉSERVATION de table (nouvel élément) : le client laisse prénom, nom, téléphone, nombre de
  personnes, jour et heure. La demande est STOCKÉE SUR LE SITE (pas seulement un e-mail) et
  visible dans WeFrame > Réservations (à venir / passées / toutes ; confirmer / refuser /
  supprimer ; clic-pour-appeler). E-mail de notification au restaurant (adresse réglable).
  Anti-spam : nonce + honeypot + limitation. Un badge indique les nouvelles demandes.
- BANDEAU ANNONCE (nouvel élément) : message ponctuel activable depuis « Mon restaurant »
  (« terrasse ouverte », promo…), lien optionnel, fermable par le visiteur.
- Le rôle « Restaurateur » voit désormais : Réglages, Menu, Réservations.

== v1.2.0 ==
- CRÉNEAUX horaires : le client ne saisit plus une heure libre, il CHOISIT un créneau.
  Les créneaux sont générés depuis vos horaires (intervalle réglable : 15/20/30/45/60 min ;
  dernière place réglable X min avant la fermeture). Regroupés Midi / Soir.
- CAPACITÉ par service : couverts max au MIDI et au SOIR (+ option max par créneau). Quand
  un service atteint la limite, ses créneaux ne sont plus proposés (« complet »). Vérifié en
  direct à l'affichage ET re-vérifié à l'envoi (anti-surbooking, même en cas d'envoi simultané).
- Réglage dans WeFrame > Mon restaurant > « Réservations — créneaux & capacité ».
- La fermeture exceptionnelle et l'horizon (jours à l'avance) bloquent aussi les créneaux.
- Le back-office affiche le service (midi/soir) de chaque réservation.
