# Journal des modifications / ChangeLog - LMDBCRM

## 1.5.0 — 28/09/2026

- Filtre permanent du classement et de son sélecteur : utilisateurs internes actifs, ayant accès à l’entité courante et le droit natif de créer/modifier les propositions commerciales, y compris par groupe, avec chargement sous le nom natif historique `propale`. / Permanent ranking and selector filter: active internal users with access to the current entity and native proposal creation permission, including group grants loaded under the historical native name `propale`.

- Permissions natives indépendantes pour le classement commercial et les widgets : lecture personnelle avec classement anonymisé, ou lecture complète dans le périmètre commercial et Multicompany autorisé. Protection serveur des accès, diagnostics et caches, sans attribution automatique. / Independent native permissions for rankings and widgets: personal read with anonymized rankings, or full read within authorized sales and Multicompany scopes. Server-side access, diagnostics and cache protection, without automatic grants.
- Nouveau widget de CA signé mensuel par entité partagée, limité à l’exercice courant, disponible dans le catalogue sans placement automatique. Correction du calcul de fin d’exercice. / New monthly signed revenue widget with one curve per shared entity for the current fiscal year, available in the catalogue without automatic placement. Fixed fiscal year end calculation.
- Correction des tableaux de podium vides et de la condition de menu rejetée par l’évaluateur natif Dolibarr 24 ; le filtrage des utilisateurs internes et les contrôles serveur sont conservés. / Fixed empty podium tables and the menu condition rejected by the Dolibarr 24 native evaluator; internal-user filtering and server checks are preserved.
- Après déploiement, réactiver le module dans chaque entité pour actualiser les droits, le menu et le catalogue. Les anciennes attributions restreintes/complètes migrent vers les droits correspondants, sans élargissement au niveau complet ; les placements existants sont conservés. Attribuer manuellement les droits aux autres utilisateurs/groupes. / After deployment, reactivate the module in each entity to update permissions, menu and catalogue. Existing restricted/full grants migrate to corresponding rights without elevation to full access; existing widget positions are retained. Grant rights manually to other users/groups.
- Socle Dolibarr 20 / PHP 8.0 conservé ; tests automatisés de permissions, de rendu natif et d’agrégations MariaDB sur Dolibarr 20/24. Les essais automatisés ne remplacent pas une recette sur instance Multicompany déployée. / Dolibarr 20 / PHP 8.0 baseline retained; automated permissions, native rendering and MariaDB aggregation tests on Dolibarr 20/24. Automated checks do not replace acceptance testing on a deployed Multicompany instance.

## 1.4 - 02/06/2026
- Ajout du widget `lmdbcrm_orders_delivered_to_bill.php` listant les dernières commandes clients livrées non facturées, avec badge indiquant le total et lien vers la liste préfiltrée. / Added the `lmdbcrm_orders_delivered_to_bill.php` widget listing the latest delivered unbilled customer orders, with a total badge and a link to the prefiltered list.
- Alignement du socle déclaré sur Dolibarr 20.0 et PHP 8.0. / Aligned declared compatibility baseline to Dolibarr 20.0 and PHP 8.0.

## 1.3 - 08/01/2026
- Ajout du widget `lmdbcrm_graph_signedquotes.php` affichant l'évolution des devis signés avec filtre de période. / Added the `lmdbcrm_graph_signedquotes.php` widget showing signed quotes evolution with a period filter.
- Centralisation du CSS responsive des DolGraph pour éviter le scroll horizontal sur mobile. / Centralized responsive DolGraph CSS to prevent horizontal scrolling on mobile.

## 1.2.1 - 08/01/2026
- Ajustement des légendes du graphique du CA signé pour afficher les années N, N-1 et N-2 sur l'année de fin d'exercice. / Adjusted signed turnover graph legends to show years N, N-1, and N-2 based on fiscal year end.
- Utilisation explicite de la date de signature pour l'agrégation du CA signé (sans date de validation). / Explicitly use the signature date for signed turnover aggregation (no validation date).
- Affichage des abscisses avec le mois uniquement (sans année). / Display x-axis labels with month only (no year).

## 1.2.0 - 26/12/2025
- Nouveau widget graphique `lmdbcrm_graph_signedturnover.php` affichant le CA signé par mois et superposant l'exercice en cours et les deux exercices précédents, avec sorties de debug optionnelles. / New graph widget `lmdbcrm_graph_signedturnover.php` showing signed revenue per month overlaying current and previous two fiscal years, with optional debug outputs.

## 1.1.0 - 16/12/2025
- Ajout du widget `lmdbcrm_graph_conversionrates.php` comparant les taux de conversion utilisateur vs entreprise avec filtres de période. / Added the `lmdbcrm_graph_conversionrates.php` widget comparing user vs company conversion rates with period filters.
- Corrections et améliorations du classement commercial (`commercial_ranking.php`) : requête compatible Multicompany, filtrage par période renforcé et ajout d'une recherche textuelle sur les utilisateurs. / Fixes and enhancements to the sales ranking (`commercial_ranking.php`): Multicompany-safe query, strengthened period filtering, and added text search on users.

## 1.0.0 - 15/12/2025
- Création initiale du module LMDBCRM. / Initial creation of the LMDBCRM module.
- Widget podium des 3 meilleurs commerciaux basé sur le nombre de propositions signées des 30 derniers jours. / Podium widget: Top 3 sales reps based on the number of proposals signed over the last 30 days.
- Widget podium des 3 meilleurs commerciaux basé sur le Chiffre d'affaire signé des 30 derniers jours. / Podium widget: Top 3 sales reps based on signed revenue (turnover) over the last 30 days.
- Widget Taux de conversion de l'uilisateur vs la société. / Conversion rate widget: User vs. company benchmark.
- Support Multicompany et traductions en_US, fr_FR, de_DE, it_IT, es_ES. / Multicompany support and translations en_US, fr_FR, de_DE, it_IT, es_ES.
