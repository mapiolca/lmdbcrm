# Journal des modifications / ChangeLog - LMDBCRM

## 1.3 - 08/01/2026
- Correction du filtre de période du widget des devis signés pour gérer les plages multi-années avec une période par défaut sur l'année en cours. / Fix period filter of the signed quotes widget to handle multi-year ranges with a default period set to the current year.

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

