# Recette des conversions — 27 octobre 2026

## Environnement et provenance

Essais réels effectués dans le navigateur intégré, sur
`https://develop.lesmetiersdubatiment.fr`, avec le compte interne `codex`,
dans TEST 1. Dolibarr affiche **25.0.0-alpha**, la page Compatibilité indique
**PHP 8.3.36**, le catalogue des modules **LMDBCRM 1.6.0** et **LMDB Lead 1.0.0**.
Multicompany est actif ; sa version exacte n’a pas été relevée.

Le nouveau titre du widget, la formule et la colonne leads du classement,
les onglets Compatibilité/À propos et la source « Leads convertis et perdus »
confirment que l’instance sert le nouveau parcours de conversion.
Le SHA du checkout serveur n’est pas exposé par ces pages et n’a pas été
vérifié dans cPanel. Ces observations ne prouvent pas l’identité binaire
de tout le déploiement avec les commits GitHub.

Références fonctionnelles préparées : CRM `9d343b156279f5e7426f094558bf98e564964283`
et projection Lead `bc602c5224637dabbd726b669d9e5b5df823e27b`.
Pendant cette recette, la branche Lead a reçu un correctif distinct
d’affectation au créateur, puis son alignement en 1.0.0. Les créations
observées ici restaient sans affectation en mode Manuel : cette recette ne
valide donc pas le déploiement ni le comportement de ce correctif distinct.

## Résultats observés

| Scénario réel | Résultat |
|---|---|
| Stock initial annuel : quatre convertis, un perdu, nombreux autres états | 80 % personnel et entreprise. |
| Création du 14 septembre uniquement : une perte, aucun converti | 0 % personnel et entreprise, avec un graphique calculé. |
| Création du 11 octobre uniquement : deux convertis | 100 % personnel et entreprise ; bornes journalières inclusives. |
| 27 octobre avant le jeu dédié : non qualifié et archives, aucun état retenu | Message « Aucun lead converti ou perdu pour cette période », sans faux 0 %. |
| Jeu dédié : trois convertis affectés, une perte affectée, une perte non affectée | Entreprise 60 % ; personnel 75 %. Le non-affecté contribue uniquement à l’entreprise. |
| Affectation de cette perte à codex après sa clôture | Personnel et entreprise 60 %, sans changement de date de création ni de statut. |
| Une perte liée à deux devis, une autre au même devis | Chaque lead compte une fois ; les 60 % restent calculés sur cinq leads. |
| Classement filtré au 27 octobre | Colonne codex à 60,00 %, malgré zéro devis de ce commercial sur cette période. |
| Tri SQL du taux, descendant puis ascendant | Le taux disponible de codex est premier ; les trois taux indisponibles sont en dernier dans les deux sens. |
| Réouverture d’une perte en À traiter | Taux personnel et entreprise à 75 % : la perte réouverte sort du dénominateur. |
| Disqualification de ce lead réouvert | 75 % conservés ; la disqualification reste exclue. |
| Archivage d’un converti | 66,67 % : le converti archivé sort du numérateur et du dénominateur. |
| Archivage des autres convertis, une perte restante | 0 % calculé. |
| Archivage de la dernière perte | Absence de données sur le 27 octobre. |
| Désactivation temporaire de Lead | Ancien widget sur les devis : taux entreprise 92,31 %. Classement historique sur les devis. |
| Réactivation de Lead | Widget et colonne leads rétablis ; taux annuel initial 80 %. |
| Réglages CRM | Onglets internes Paramètres, Compatibilité et À propos fonctionnels ; catalogue avec une seule entrée de configuration, version 1.6.0 issue du descripteur. |

Dans le classement sans filtre de dates, les mêmes métriques ont été relevées
avant les associations, après les associations et en mode devis :

| Commercial | Devis réalisés | Devis signés | Montant chiffré | Montant signé |
|---|---:|---:|---:|---:|
| Pierre Ardoin | 20 | 17 | 17 125 € | 15 425 € |
| Utilisateur Test | 2 | 1 | 0 € | 0 € |
| 3 Utilisateur | 0 | 0 | 0 € | 0 € |
| Codex OPEN AI | 0 | 0 | 0 € | 0 € |

Le mode devis conserve respectivement les taux historiques 85 %, 50 %,
0 % et 0 %. Aucun nouveau devis, montant ou document commercial n’a été créé
ou validé pendant cette recette.

## Jeu de test et restitution

Les cinq leads dédiés portent le préfixe `RECETTE CRM 1.6 — 2026-10-27` :

| Lead | Identifiant | Usage |
|---|---:|---|
| LE2610-000035 | 42 | Converti A |
| LE2610-000036 | 43 | Converti B |
| LE2610-000037 | 44 | Converti C |
| LE2610-000038 | 45 | Perdu D, initialement non affecté, puis réouvert/disqualifié |
| LE2610-000039 | 46 | Perdu E avec deux devis liés |

Les conversions proviennent de l’association à la commande fictive existante
`CO2610-0011` (63), validée avant les essais. Les pertes ont utilisé les devis
existants `PR2607-0004` (27) et `2026PA013` (19), sans modifier leurs états.
Les parcours natifs de création, affectation, association, qualification,
réouverture, dissociation et archivage ont été utilisés avec leurs formulaires
et confirmations CSRF.

Les six liaisons commerciales ajoutées par la recette ont été retirées.
Les cinq leads restent **archivés**, avec leur historique ; aucune suppression
définitive n’a été effectuée. La période annuelle et TEST 1 ont été rétablis.
Le taux annuel final est identique au taux initial : 80 %.

Lead a été remis dans son état actif initial. Ses huit réglages relevés sont
conservés : seuil de tentatives 3, alternatives `keep`, conversion
`order_or_invoice`, devis `validated`, affectation `manual`, priorité `neutral`,
exécuteurs de signature et de rappel 7. Les automatismes Agenda
`LMDBLEAD_LEAD_CREATE` et `LMDBLEAD_LEAD_UPDATE` étaient désactivés avant les
créations et n’ont pas été modifiés. Aucun envoi d’email n’a été demandé ;
les fiches de test ne contenaient aucun contact ni événement Agenda.
Cette vérification n’est pas un audit des journaux SMTP.

## Limites explicites

- TEST 2 a été consulté sans modifier ses modules : la source y indique les
  propositions et le classement refuse l’accès. Retour à TEST 1 confirmé.
  Lead n’y étant pas actif, cela **ne valide pas** deux entités actives,
  les partages ou l’isolation SQL de deux populations de leads.
- Le compte réel utilisé dispose des droits complets. Lecture personnelle,
  refus croisés CRM/Lead, restrictions commerciales, exigence d’export et
  invalidation après révocation restent couverts par les tests automatisés,
  avec des permissions simulées ; aucun de ces profils n’a été modifié ici.
- Aucun essai navigateur avec Devis désactivé, contrat Lead absent ou en erreur,
  lead annulé, autre commercial après réaffectation, cache natif, mobile,
  autre langue, concurrence ou autre version Dolibarr/PHP.
- Les résultats PHP/MariaDB et la matrice CI 20–25 sont des preuves distinctes
  de cette seule instance v25 alpha. PHPStan n’a pas été relancé pour ce
  compte rendu documentaire ; les analyses du patch fonctionnel restent celles
  indiquées dans les PR.

Des captures de recette sont conservées localement hors Git. Elles ne sont
pas livrées avec le module.
