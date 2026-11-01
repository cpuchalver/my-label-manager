# Note de cadrage – My Label Manager

| | |
|---|---|
| Version | 1.7 – 31 octobre 2026 (distributeurs digitaux et physiques, comme canaux de vente) |
| Porteur et développeur | @cpuchalver |
| Statut | Validée pour lancer le Lot 0 |
| Suivi | [Issues](https://github.com/cpuchalver/my-label-manager/issues) · [Jalons](https://github.com/cpuchalver/my-label-manager/milestones) |

## 1. Vision

> Un back-office tout-en-un pour labels indépendants : catalogue centralisé, ventes Shopify, Bandcamp et distributeurs (digitaux et physiques) consolidées, relevés de royalties et de reversements calculés automatiquement, et un accès direct pour les partenaires.

My Label Manager est un **produit SaaS** destiné aux labels de musique indépendants. Le label du porteur de projet en est le premier utilisateur et le terrain de validation.

## 2. Problème à résoudre

Un label indépendant gère aujourd'hui son activité de façon éclatée :

- les ventes sont dispersées entre sa boutique Shopify, Bandcamp et les relevés de ses distributeurs, digitaux (streaming, téléchargement) et physiques (disquaires, revendeurs), chacun avec son format ;
- les royalties et reversements sont calculés à la main dans des tableurs, avec un risque d'erreur et un temps important à chaque période ;
- les partenaires (artistes, labels, autres fournisseurs) reçoivent leurs relevés par e-mail et n'ont aucune visibilité en dehors de ces envois.

Les trois douleurs prioritaires retenues sont, dans l'ordre :

1. **Une gestion simple et centralisée** : toutes les ventes et tout le catalogue au même endroit.
2. **Des royalties justes et rapides** à produire.
3. **Un accès en ligne pour les partenaires** à leurs relevés.

## 3. Utilisateurs cibles

| Profil | Besoin principal | Espace |
|---|---|---|
| Équipe du label (gérant, comptable) | Centraliser, calculer, payer | Application label |
| Partenaires : artistes | Consulter leurs relevés et ventes | Portail partenaires |
| Partenaires : labels (distribués ou en dépôt-vente) | Consulter les ventes de leurs références et ce qui leur est dû | Portail partenaires |
| Éditeur de la plateforme | Administrer les labels clients | Console plateforme |

Point clé : **le fournisseur possède le produit, le label possède la vente.** Chaque produit appartient au catalogue d'un fournisseur (le label lui-même, un label distribué, un artiste en dépôt-vente…) ; toutes les ventes appartiennent au label, qui distribue les relevés et documents à ses artistes et fournisseurs. Royalties artistes et reversements aux fournisseurs reposent sur le même mécanisme : un moteur de relevés unique sert tous les partenaires.

## 4. Objectifs mesurables

| Objectif | Indicateur | Cible | Lot |
|---|---|---|---|
| Centraliser le catalogue | Références du label saisies ou importées avec leurs partenaires | 100 % du catalogue actif | 1 (MVP) |
| Ouvrir l'accès aux partenaires | Partenaires actifs sur le portail six mois après ouverture | ≥ 50 % | 1 (MVP) |
| Marché | Labels pilotes utilisant l'outil | 2 à 3 en février 2027 | 1 (MVP) |
| Centraliser les ventes | Part des ventes du label importées automatiquement | 100 % des canaux actifs (Shopify, Bandcamp, distributeurs digitaux et physiques) | 2 |
| Fiabiliser | Ventes rapprochées automatiquement du catalogue | ≥ 95 % | 2 |
| Gagner du temps | Durée de production des relevés d'une période | Moins d'une heure, contre plusieurs jours aujourd'hui | 3 |
| Exactitude | Écart entre relevés calculés par l'outil et relevés produits à la main en parallèle sur les premiers mois | Zéro écart non expliqué | 3 |

## 5. Périmètre

### Dans le MVP (Lots 0 et 1, fin janvier 2027)

Le MVP se limite volontairement à trois briques, pour mettre un outil en service le plus tôt possible :

- **Socle SaaS** : multi-tenant (un label = un espace isolé), utilisateurs, invitations, rôles et permissions.
- **Devises et taux de conversion**, dès le départ (voir ci-dessous).
- **Catalogue** : releases, titres, formats, merch, identifiants UPC, ISRC, SKU, numéro de catalogue, fournisseurs propriétaires des produits et artistes crédités.
- **Portail partenaires** (artistes et fournisseurs) : chaque partenaire consulte **ses références du catalogue et ses contrats**, avec notification à chaque nouveau contrat déposé. Pas de relevés dans le portail du MVP.

Les relevés sont **mensuels**. Jusqu'au Lot 3, ils restent produits et envoyés comme aujourd'hui, hors de l'outil. Au Lot 3, l'outil les calcule chaque mois et les publie directement sur le portail.

L'outil démarre **sans reprise de l'historique** : seuls le catalogue actif et les partenaires sont saisis ou importés. Les ventes et relevés antérieurs à la mise en service restent dans les outils actuels du label.

#### Gestion des devises

- Chaque label choisit une **devise de référence** (comptabilité et tableaux de bord).
- Chaque partenaire a une **devise de relevé** ; chaque montant (prix, vente, avance, paiement) est stocké avec sa devise d'origine.
- **Taux de conversion** récupérés automatiquement chaque jour (référence BCE), avec possibilité de saisie ou de surcharge manuelle par le label.
- **Historique des taux** conservé ; une vente est convertie au taux de sa date, et ce taux est figé pour qu'un relevé ne change jamais après coup.
- Montants stockés en décimal exact (jamais en flottant), arrondis selon la devise au moment de la conversion.

### Après le MVP

- Ventes centralisées : connecteurs Shopify et Bandcamp, imports CSV des relevés des distributeurs digitaux et physiques, rapprochement avec le catalogue, tableau de bord (Lot 2).
- Contrats paramétrables et moteur de relevés : calcul, avances, splits, paiements, publication automatique des relevés sur le portail (Lot 3).
- Stock et logistique, ventes in-app, synchronisation des quantités vers les canaux (Lot 4).
- Abonnements, facturation et onboarding libre-service (Lot 5).

### Hors périmètre

- Distribution numérique vers les plateformes de streaming.
- Accès des **distributeurs** au portail : les distributeurs, digitaux comme physiques, ne sont ni des partenaires ni des fournisseurs, mais des canaux de vente dont on importe les relevés. Le stock confié aux distributeurs physiques est suivi avec le stock (Lot 4).
- Comptabilité générale et déclarations fiscales.
- Gestion des droits d'auteur et éditions musicales (SACEM et équivalents).
- Application mobile native.
- Reprise de l'historique des ventes et des relevés antérieurs à la mise en service.

## 6. Organisation et ressources

- **Un seul développeur**, également porteur du projet, disponible **20 à 30 heures par semaine**.
- Le label du porteur sert de **client pilote et de recette permanente**.
- Méthode : un lot à la fois, chacun livrant quelque chose d'utilisable. Suivi dans les issues et jalons GitHub.

Règles de conduite liées au mode solo :

- s'appuyer au maximum sur les briques natives de Filament (multi-tenancy, panels, imports, notifications, widgets) ;
- acheter plutôt que coder : Laravel Cashier, hébergement géré (Laravel Cloud ou Forge) ;
- tests automatisés exhaustifs sur le moteur de relevés et le rapprochement des ventes ;
- documentation minimale tenue à jour et CI fiable pour pouvoir reprendre après une pause.

## 7. Choix techniques de départ

- **Nouveau projet** Laravel 13 (PHP 8.4 minimum) + Filament 5 + Livewire 4. La démo Filament (`cpuchalver/filament-demo`) sert de référence d'exemples, pas de base de code : elle contient des mécanismes propres à une démo (réinitialisation horaire de la base, identifiants préremplis, absence de contrôle d'accès).
- Qualité : Pest, PHPStan niveau 8, Pint, CI GitHub Actions.
- Multi-tenancy Filament dès le premier jour.
- Panels : application label, portail partenaires, console plateforme.
- Couche « canaux de vente » générique : chaque source (Shopify, Bandcamp, CSV des distributeurs digitaux et physiques, plus tard in-app) produit des ventes dans un format normalisé unique.
- Synchronisations par tâches planifiées et files d'attente.
- Temps réel avec Laravel Reverb (WebSocket) dès le socle : tableau de bord et notifications en direct.
- Montants modélisés dès le premier jour comme couple montant + devise (objet valeur, stockage décimal exact), avec une table des taux de change datés et une tâche planifiée de mise à jour quotidienne.

## 8. Découpage et calendrier

Hypothèse : 25 heures par semaine en moyenne, démarrage début novembre 2026.

| Lot | Contenu | Durée | Fin visée |
|---|---|---|---|
| 0 – Cadrage et fondations | Cadrage, entretiens marché, socle SaaS multi-tenant, devises et taux, CI | 3 à 4 sem. | fin novembre 2026 |
| 1 – MVP | Catalogue et partenaires, portail partenaires (références et contrats) | 5 à 7 sem. | fin janvier 2027 |
| 2 – Centraliser les ventes | Shopify, Bandcamp, imports CSV, rapprochement, tableau de bord | 6 à 8 sem. | fin mars 2027 |
| 3 – Calculer | Contrats, moteur de relevés mensuels, paiements, publication des relevés sur le portail | 6 à 8 sem. | fin mai 2027 |
| 4 – Stock et ventes directes | Mouvements, inventaires, ventes in-app, synchro quantités | 4 à 6 sem. | début juillet 2027 |
| 5 – Commercialisation SaaS | Abonnements, onboarding, support | 6 à 8 sem. | fin septembre 2027 |

Jalons clés :

- **Fin janvier 2027** : MVP en production pour le label pilote ; ses partenaires accèdent au portail.
- **Février 2027** : présentation du MVP à 2 ou 3 labels pilotes, configurés à la main, sans facturation.
- **Fin mai 2027** : relevés mensuels calculés automatiquement, la douleur principale est résolue.
- **Fin 2027** : ouverture commerciale, en intégrant une marge de 20 % sur les estimations.

## 9. Risques principaux

| Risque | Impact | Parade |
|---|---|---|
| Diversité des contrats réels plus grande que prévu | Allonge le Lot 3 | Collecter dès le Lot 0 des contrats et relevés existants ; moteur paramétrable, pas de règles codées en dur |
| Accès à l'API Bandcamp incertain pour un SaaS | Fonction phare compromise | Clarifier les conditions d'accès au Lot 0 ; prévoir l'import CSV du rapport Bandcamp en repli |
| Erreur de calcul dans un relevé | Perte de confiance des partenaires | Tests exhaustifs, traçabilité de chaque montant jusqu'aux ventes sources, double production (outil et méthode actuelle) sur les deux ou trois premiers mois |
| Conversion de devises incohérente (taux manquant, arrondis) | Écarts dans les relevés | Taux datés et figés, surcharge manuelle, alerte si un taux manque, tests d'arrondi par devise |
| Formats hétérogènes des relevés des distributeurs | Rapprochement incomplet | Modèles de mapping CSV réutilisables par distributeur, file des ventes non rapprochées |
| Fuite de données entre labels ou partenaires | Grave, contractuel et RGPD | Multi-tenancy native, policies systématiques, tests de cloisonnement dédiés |
| Développeur unique : dispersion, fatigue, indisponibilité | Retards, arrêt | Un lot à la fois, rythme régulier, CI et documentation à jour |
| Marché déjà occupé par des outils de royalties | Différenciation faible | Étude de la concurrence et entretiens au Lot 0 ; miser sur le tout-en-un et l'intégration Shopify + Bandcamp |

## 10. Prochaines actions (Lot 0)

- [ ] Rassembler 3 à 5 contrats types et un exemple de relevé mensuel par type de partenaire (modèle pour le Lot 3, pas de reprise).
- [ ] Exporter un échantillon de ventes Shopify, Bandcamp et de chaque distributeur (digital et physique).
- [ ] Clarifier les conditions d'accès à l'API Bandcamp.
- [ ] Mener 5 à 10 entretiens avec des labels indépendants.
- [ ] Étudier 3 à 5 outils concurrents et positionner l'offre.
- [ ] Initialiser le projet Laravel + Filament avec CI, multi-tenancy et gestion des devises (issue #2).
- [ ] Lister les devises utilisées par le label pilote et ses partenaires.
- [ ] Rédiger les spécifications détaillées du MVP (catalogue et portail partenaires).

## 11. Décisions prises

| Date | Décision |
|---|---|
| 31/10/2026 | MVP limité au socle SaaS, au catalogue et au portail partenaires. |
| 31/10/2026 | Multi-devises et taux de conversion gérés dès le socle (Lot 0). |
| 31/10/2026 | Relevés mensuels pour tous les partenaires. |
| 31/10/2026 | Pas de reprise de l'historique des ventes et relevés. |
| 31/10/2026 | Portail du MVP limité aux références et aux contrats ; les relevés y arrivent au Lot 3, calculés par l'outil. |
| 31/10/2026 | Distributeurs digitaux et physiques gérés comme canaux de vente (import de leurs relevés au Lot 2, stock en dépôt chez les physiques au Lot 4), sans accès au portail. |
| 31/10/2026 | Grille tarifaire reportée au Lot 5 ; les labels pilotes utilisent l'outil sans facturation. |

## 12. Questions ouvertes

Aucune question bloquante pour le Lot 0 et le MVP. À traiter plus tard :

- Grille tarifaire et modèle d'abonnement (par label, par volume de ventes, par nombre de partenaires) : au Lot 5, nourrie par les retours des labels pilotes. Pendant les entretiens du Lot 0, noter simplement ce que les labels paient aujourd'hui pour leurs outils.
