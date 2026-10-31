# Note de cadrage – My Label Manager

| | |
|---|---|
| Version | 1.0 – 31 octobre 2026 |
| Porteur et développeur | @cpuchalver |
| Statut | Validée pour lancer le Lot 0 |
| Suivi | [Issues](https://github.com/cpuchalver/my-label-manager/issues) · [Jalons](https://github.com/cpuchalver/my-label-manager/milestones) |

## 1. Vision

> Un back-office tout-en-un pour labels indépendants : catalogue centralisé, ventes Shopify, Bandcamp et distributeurs consolidées, relevés de royalties et de reversements calculés automatiquement, et un accès direct pour les partenaires.

My Label Manager est un **produit SaaS** destiné aux labels de musique indépendants. Le label du porteur de projet en est le premier utilisateur et le terrain de validation.

## 2. Problème à résoudre

Un label indépendant gère aujourd'hui son activité de façon éclatée :

- les ventes sont dispersées entre sa boutique Shopify, Bandcamp et les relevés de ses distributeurs, chacun avec son format ;
- les royalties et reversements sont calculés à la main dans des tableurs, avec un risque d'erreur et un temps important à chaque période ;
- les partenaires (artistes, labels, distributeurs) reçoivent leurs relevés par e-mail et n'ont aucune visibilité en dehors de ces envois.

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
| Partenaires : distributeurs | Consulter les relevés qui les concernent | Portail partenaires |
| Éditeur de la plateforme | Administrer les labels clients | Console plateforme |

Point clé : **royalties artistes et reversements aux labels ou distributeurs reposent sur le même mécanisme**. Un moteur de relevés unique sert tous les partenaires.

## 4. Objectifs mesurables

| Objectif | Indicateur | Cible |
|---|---|---|
| Centraliser | Part des ventes du label importées automatiquement | 100 % des canaux actifs (Shopify, Bandcamp, distributeurs) |
| Fiabiliser | Ventes rapprochées automatiquement du catalogue | ≥ 95 % |
| Gagner du temps | Durée de production des relevés d'une période | Moins d'une heure, contre plusieurs jours aujourd'hui |
| Exactitude | Écart entre relevés calculés et relevés historiques validés | Zéro écart non expliqué |
| Adoption partenaires | Partenaires actifs sur le portail six mois après ouverture | ≥ 50 % |
| Marché | Labels pilotes utilisant l'outil | 2 à 3 en mai 2027 |

## 5. Périmètre

### Dans le MVP (Lots 0 à 3, fin avril 2027)

- Socle SaaS multi-tenant : un label = un espace isolé, utilisateurs, rôles.
- Catalogue : releases, titres, formats, merch, identifiants UPC, ISRC, SKU, numéro de catalogue, partenaires rattachés.
- Ventes : connecteurs Shopify et Bandcamp, imports CSV des distributeurs, rapprochement avec le catalogue, file des ventes non rapprochées.
- Contrats paramétrables : taux par canal et format, base brute ou nette, avances recoupables, splits.
- Moteur de relevés : périodes, calcul, génération PDF et export, suivi des paiements.
- Portail partenaires : consultation des relevés et des ventes.
- Tableau de bord simple.

### Après le MVP

- Stock et logistique : mouvements tracés, inventaires, synchronisation des quantités vers les canaux (Lot 4).
- Ventes in-app (Lot 4).
- Abonnements, facturation et onboarding libre-service (Lot 5).

### Hors périmètre

- Distribution numérique vers les plateformes de streaming (le label garde son distributeur).
- Comptabilité générale et déclarations fiscales.
- Gestion des droits d'auteur et éditions musicales (SACEM et équivalents).
- Application mobile native.

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

- **Nouveau projet** Laravel 12 + Filament 5 + Livewire 4. La démo Filament (`cpuchalver/filament-demo`) sert de référence d'exemples, pas de base de code : elle contient des mécanismes propres à une démo (réinitialisation horaire de la base, identifiants préremplis, absence de contrôle d'accès).
- Qualité : Pest, PHPStan niveau 8, Pint, CI GitHub Actions.
- Multi-tenancy Filament dès le premier jour.
- Panels : application label, portail partenaires, console plateforme.
- Couche « canaux de vente » générique : chaque source (Shopify, Bandcamp, CSV distributeurs, plus tard in-app) produit des ventes dans un format normalisé unique.
- Synchronisations par tâches planifiées et files d'attente.

## 8. Découpage et calendrier

Hypothèse : 25 heures par semaine en moyenne, démarrage début novembre 2026.

| Lot | Contenu | Durée | Fin visée |
|---|---|---|---|
| 0 – Cadrage et fondations | Cadrage, entretiens marché, socle multi-tenant, CI | 3 à 4 sem. | fin novembre 2026 |
| 1 – Centraliser | Catalogue et partenaires, Shopify, Bandcamp, imports CSV, rapprochement, tableau de bord | 6 à 8 sem. | fin janvier 2027 |
| 2 – Calculer | Contrats, moteur de relevés, paiements | 6 à 8 sem. | fin mars 2027 |
| 3 – Partager | Portail partenaires | 3 à 4 sem. | fin avril 2027 |
| 4 – Stock et ventes directes | Mouvements, inventaires, ventes in-app, synchro quantités | 4 à 6 sem. | mi-juin 2027 |
| 5 – Commercialisation SaaS | Abonnements, onboarding, support | 6 à 8 sem. | fin août 2027 |

Jalons clés :

- **Fin avril 2027** : MVP complet en production pour le label pilote.
- **Mai 2027** : 2 à 3 labels pilotes, configurés à la main, sans facturation.
- **Automne 2027** : ouverture commerciale, en intégrant une marge de 20 % sur les estimations.

## 9. Risques principaux

| Risque | Impact | Parade |
|---|---|---|
| Diversité des contrats réels plus grande que prévu | Allonge le Lot 2 | Collecter dès le Lot 0 des contrats et relevés existants ; moteur paramétrable, pas de règles codées en dur |
| Accès à l'API Bandcamp incertain pour un SaaS | Fonction phare compromise | Clarifier les conditions d'accès au Lot 0 ; prévoir l'import CSV du rapport Bandcamp en repli |
| Formats hétérogènes des relevés distributeurs | Rapprochement incomplet | Modèles de mapping CSV réutilisables, file des ventes non rapprochées |
| Erreur de calcul dans un relevé | Perte de confiance des partenaires | Tests exhaustifs, traçabilité de chaque montant jusqu'aux ventes sources, validation sur l'historique |
| Fuite de données entre labels ou partenaires | Grave, contractuel et RGPD | Multi-tenancy native, policies systématiques, tests de cloisonnement dédiés |
| Développeur unique : dispersion, fatigue, indisponibilité | Retards, arrêt | Un lot à la fois, rythme régulier, CI et documentation à jour |
| Marché déjà occupé par des outils de royalties | Différenciation faible | Étude de la concurrence et entretiens au Lot 0 ; miser sur le tout-en-un et l'intégration Shopify + Bandcamp |

## 10. Prochaines actions (Lot 0)

- [ ] Rassembler 3 à 5 contrats types et les derniers relevés produits par le label.
- [ ] Exporter un échantillon de ventes Shopify, Bandcamp et de chaque distributeur.
- [ ] Clarifier les conditions d'accès à l'API Bandcamp.
- [ ] Mener 5 à 10 entretiens avec des labels indépendants.
- [ ] Étudier 3 à 5 outils concurrents et positionner l'offre.
- [ ] Initialiser le projet Laravel + Filament avec CI et multi-tenancy (issue #2).
- [ ] Rédiger les spécifications détaillées du Lot 1 (catalogue et ventes).

## 11. Questions ouvertes

- Grille tarifaire et modèle d'abonnement (par label, par volume de ventes, par nombre de partenaires).
- Devises à gérer dès le MVP et règles de conversion.
- Fréquence des relevés proposée par défaut (trimestrielle, semestrielle).
- Reprise de l'historique : sur combien de périodes passées ?
