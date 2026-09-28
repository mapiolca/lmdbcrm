# LMDBCRM pour [Dolibarr ERP & CRM](https://www.dolibarr.org)

**Version : 1.5.0.** Voir [ChangeLog.md](ChangeLog.md).

Divers widgets et fonctionnalités CRM pour compléter votre Dolibarr préféré.

## Fonctionnalités
- Widget podium des 3 meilleurs commerciaux basé sur le nombre de propositions signées des 30 derniers jours.
- Widget podium des 3 meilleurs commerciaux basé sur le Chiffre d'affaire signé des 30 derniers jours.
- Widget Taux de conversion de l'uilisateur vs la société.
- Widget graphique des taux de conversion utilisateur / entreprise avec filtres de période.
- Widget graphique du CA signé par mois (exercice en cours et deux exercices précédents).
- Widget graphique des devis signés (lmdbcrm_graph_signedquotes.php) avec filtre de période.
- Widget des dernières commandes clients livrées non facturées, avec lien vers la liste préfiltrée.
- CSS responsive global pour les widgets graphiques DolGraph (mobile sans scroll horizontal).
- Page de classement des commerciaux avec filtres multi-utilisateurs et recherche textuelle.
- Affichage des photos utilisateurs et des volumes signés pour motiver les équipes.
- Compatibilité Multicompany : le podium respecte le périmètre entité de l'utilisateur connecté.
- Traductions fournies : en_US, fr_FR, de_DE, it_IT, es_ES.

## Compatibilité Dolibarr
- Version minimale : Dolibarr 20.0. Les contrôles automatisés et les limites des essais sont décrits dans [test/README.md](test/README.md).
- PHP minimal : 8.0.

## CA signé par mois multi-entités

Un widget supplémentaire, **CA signé par mois multi-entités**, est proposé lorsque Multicompany est actif et que `getEntity('propal')` autorise plusieurs entités. Après mise à jour, réactiver le module pour enregistrer ce widget, puis l’ajouter depuis le catalogue natif : il n’est pas placé automatiquement et les widgets existants sont conservés.

Chaque entité autorisée possède sa courbe et son nom dans la légende. Les couleurs sont attribuées par DolGraph selon la palette du thème actif ; elles peuvent se répéter si cette palette ne suffit pas au nombre d’entités. La période commune est l’exercice courant de l’entité consultée ; aucun exercice précédent ni courbe globale supplémentaire n’est affiché. Les montants HT des devis signés ou facturés sont ventilés selon leur date de signature. Les mois et entités sans devis restent à zéro ; une période entièrement vide affiche le message natif d’absence de données. Une erreur de lecture est signalée séparément.

Les droits `widgets.read` et `widgets.readall` gardent leur sens : seuls ses devis en lecture personnelle, ou tous les devis dans le périmètre commercial natif en lecture complète. Le partage n’accorde aucun droit supplémentaire. Les libellés d’entité sont limités au périmètre partagé des devis. Un retrait de partage entre chargement et rendu invalide le contenu déjà chargé.

## Permissions du classement et des widgets

Les droits natifs utilisateurs/groupes apparaissent dans cet ordre :

1. **Lire le classement des commerciaux (uniquement ses données en clair)** — `ranking.read`.
2. **Lire toutes les données du classement des commerciaux en clair** — `ranking.readall`.
3. **Lire les widgets CRM (uniquement ses données en clair)** — `widgets.read`.
4. **Lire toutes les données des widgets CRM en clair** — `widgets.readall`.

Les deux ensembles sont indépendants. Sans droit : accès refusé. Le droit complet suffit seul et prend priorité lorsque les deux sont attribués. À chaque activation/réactivation, Dolibarr attribue nativement les quatre droits LMDBCRM aux administrateurs dans l’entité active. Les utilisateurs ordinaires et les groupes restent soumis à une attribution manuelle. Les contrôles serveur utilisent toujours `hasRight()` ; les droits sources et restrictions de données restent applicables.

La lecture personnelle affiche sa propre ligne en clair, à sa position réelle dans le périmètre accessible. Les autres commerciaux et leurs valeurs sont remplacés côté serveur par des libellés et formes neutres ; leurs identités et valeurs ne sont pas envoyées au navigateur. Le classement personnel est fixé au nombre de devis signés décroissant, avec départage stable par identifiant utilisateur ; les filtres d’identité et le tri transmis dans l’URL sont ignorés. Les filtres de dates restent disponibles. Les podiums conservent le top 3 anonymisé et ajoutent sa propre ligne lorsqu’elle se trouve plus bas. Sans devis signé, un message signale l’absence de classement personnel.

Les graphiques montrent uniquement les séries de l’utilisateur ; les comparatifs entreprise sont masqués et ne sont pas interrogés. Les propositions personnelles sont celles dont l’utilisateur est auteur. Pour les commandes livrées non facturées, le périmètre personnel correspond aux tiers affectés commercialement à l’utilisateur.

Les utilisateurs doivent rester internes et posséder la lecture native des devis (classement et sept widgets) ou des commandes (widget des commandes livrées). Les entités accessibles et restrictions commerciales natives s’appliquent avant les agrégations, même avec `readall`. Une position n’est donc pas nécessairement celle de toute l’entreprise.

Tous les rendus de widgets, personnels ou complets, invalident leur ancien cache HTML et sont recalculés sans écrire de nouveau cache métier. Les formes neutres ne contiennent aucune valeur réelle. Un changement de niveau de permission entre chargement et rendu refuse le contenu déjà chargé. Les droits s’appliquent après leur rechargement natif lors d’une nouvelle requête. Après mise à jour du descripteur, réactiver LMDBCRM pour actualiser la condition du menu enregistrée en base : elle utilise les appels `hasRight()` compatibles avec l’évaluateur natif, tandis que le type de menu « Interne » et la page protègent l’accès des utilisateurs externes.

**Après mise à jour :** réactiver LMDBCRM dans chaque entité concernée pour enregistrer les nouveaux droits et migrer les attributions existantes. Les offsets 1 à 4 restent réservés ; les nouveaux IDs sont `45001105` à `45001108` dans l’ordre ci-dessus. La migration conserve la distinction entre accès restreint et complet :

| Ancien ID et droit | Nouveau ID et droit |
|---|---|
| 45001101 — classement masqué | 45001105 — classement personnel |
| 45001102 — classement complet | 45001106 — classement complet |
| 45001103 — widgets masqués | 45001107 — widgets personnels |
| 45001104 — widgets complets | 45001108 — widgets complets |

La migration traite utilisateurs et groupes dans l’entité active, sans doublon, et retire les anciennes attributions pour ne pas rétablir un droit ultérieurement révoqué. Les placements des widgets sont conservés. Les anciens accès masqués des utilisateurs ordinaires et groupes ne deviennent jamais des accès complets ; les administrateurs reçoivent les droits complets par défaut lors de l’activation. Après migration, les réglages restent administrables dans les écrans natifs.

## Installation
### Depuis un paquet ZIP
1. Télécharger l'archive du module (ex. `module_lmdbcrm-x.y.z.zip`).
2. Dans Dolibarr : `Accueil -> Configuration -> Modules/Applications -> Déployer module externe`.
3. Importer l'archive puis vérifier que le déploiement s'est déroulé correctement.

### Depuis un dépôt Git
1. Cloner le dépôt dans le dossier `htdocs/custom` :
   ```bash
   cd /chemin/vers/dolibarr/htdocs/custom
   git clone git@github.com:gitlogin/lmdbcrm.git lmdbcrm
   ```
2. Vérifier que les droits du dossier permettent la lecture par le serveur web.

## Activation
1. Se connecter en administrateur Dolibarr.
2. Ouvrir `Configuration -> Modules/Applications`.
3. Activer "LMDBCRM" dans la famille "Les Métiers du Bâtiment".

## Paramétrage
- Page de configuration : `Configuration -> Modules/Applications -> LMDBCRM`.
- Le module crée automatiquement les répertoires nécessaires lors de l'activation.
- Aucune constante spécifique n'est requise par défaut.

## Permissions
- L'affichage du widget respecte les droits standards Dolibarr : seuls les utilisateurs autorisés peuvent consulter les propositions.
- Les boutons d'action sont masqués pour les utilisateurs sans droits suffisants.

## Traductions
- Les fichiers de langues sont stockés dans `langs/`.
- Pour ajouter ou ajuster des traductions, éditer les fichiers correspondant aux locales souhaitées.

## Mise à jour
1. Désactiver temporairement le module.
2. Déployer la nouvelle version (ZIP ou Git pull).
3. Réactiver le module pour appliquer les éventuelles mises à jour de structure.

## Support et contributions
- Suggestions et rapports de bug : ouvrir un ticket sur le dépôt GitHub.
- Contributions bienvenues via pull request en respectant les normes de développement Dolibarr.

## Licence
- Code : GPLv3 ou version ultérieure (voir fichier `COPYING`).
- Documentation : GFDL 1.3 (voir [licence](https://www.gnu.org/licenses/fdl-1.3.en.html)).

---

# LMDBCRM for [Dolibarr ERP & CRM](https://www.dolibarr.org)

**Version: 1.5.0.** See [ChangeLog.md](ChangeLog.md).

Various CRM widgets and features to complement your favorite Dolibarr.
## Features
- Podium widget: Top 3 sales reps based on the number of proposals signed over the last 30 days.
- Podium widget: Top 3 sales reps based on signed revenue (turnover) over the last 30 days.
- Conversion rate widget: User vs. company benchmark.
- Conversion rate graph widget with period filters comparing user vs company.
- Signed revenue line chart widget overlaying current and previous two fiscal years.
- Signed quotes chart widget (lmdbcrm_graph_signedquotes.php) with a period filter.
- Latest delivered unbilled customer orders widget, with a link to the prefiltered order list.
- Global responsive CSS for DolGraph widgets (mobile without horizontal scrolling).
- Sales rep ranking page with multi-user filters and keyword search.
- Displays user pictures and signed volumes to motivate teams.
- Multicompany-compatible: the podium respects the entity scope of the logged-in user.
- Provided translations: en_US, fr_FR, de_DE, it_IT, es_ES.

## Dolibarr compatibility
- Minimum version: Dolibarr 20.0. See [test/README.md](test/README.md) for automated checks and their limits.
- Minimum PHP version: 8.0.

## Ranking and widget permissions

Native rights are ordered as: personal ranking (`ranking.read`), full ranking (`ranking.readall`), personal widgets (`widgets.read`), full widgets (`widgets.readall`). The two groups are independent; full read takes precedence and is sufficient alone. No right means no access. On each activation/reactivation, Dolibarr natively grants all four LMDBCRM rights to administrators in the active entity. Standard users and groups still require manual assignment. Server checks continue to use `hasRight()`; source permissions and data scopes still apply.

Personal reading displays the viewer’s own values and actual position within the accessible scope. Other names and values are hidden server-side. The personal ranking uses signed proposal count descending with user ID as stable tie-breaker; identity filters and custom sorting are ignored, while date filters remain available. Podiums retain the anonymous top three and include the viewer even below third place. Personal charts query only the viewer’s series, not company totals. Personal proposals are authored by the viewer; personal orders belong to their assigned customers.

Native proposal/order rights, internal-user restrictions, commercial assignments and entity sharing still apply, including with full read. All widget HTML caches are invalidated before rendering; personal data is never put into a reusable preview cache.

**Upgrade:** reactivate LMDBCRM in each relevant entity. Legacy offsets 1–4 remain reserved. User/group assignments migrate once from IDs `45001101`–`45001104` to `45001105`–`45001108`, respectively: masked access becomes personal access, full access remains full. Old assignments are removed so subsequent reactivation cannot restore a revoked right. Existing widget positions and other entities remain untouched. Manage later changes in native user/group permission screens.

## Installation
### From a ZIP package
1. Download the module archive (e.g., `module_lmdbcrm-x.y.z.zip`).
2. In Dolibarr: `Home -> Setup -> Modules/Applications -> Deploy external module`.
3. Upload the archive and confirm deployment completes successfully.

### From a Git repository
1. Clone the repository into `htdocs/custom`:
   ```bash
   cd /path/to/dolibarr/htdocs/custom
   git clone git@github.com:gitlogin/lmdbcrm.git lmdbcrm
   ```
2. Ensure the directory permissions allow the web server to read the files.

## Activation
1. Log in as a Dolibarr administrator.
2. Go to `Setup -> Modules/Applications`.
3. Enable "LMDBCRM" under the "Les Métiers du Bâtiment" category.

## Configuration
- Configuration page: `Setup -> Modules/Applications -> LMDBCRM`.
- The module automatically creates required directories during activation.
- No specific constants are required by default.

## Permissions
- The widget display follows Dolibarr permissions: only authorized users can view proposals.
- Action buttons are hidden for users without sufficient rights.

## Translations
- Language files are stored in `langs/`.
- To add or adjust translations, edit the files for the desired locales.

## Upgrade
1. Temporarily disable the module.
2. Deploy the new version (ZIP or Git pull).
3. Re-enable the module to apply any structural updates.

## Support and contributions
- Suggestions and bug reports: open an issue on the GitHub repository.
- Contributions are welcome via pull requests following Dolibarr development standards.

## License
- Code: GPLv3 or any later version (see `COPYING`).
- Documentation: GFDL 1.3 (see the [license](https://www.gnu.org/licenses/fdl-1.3.en.html)).

### Monthly signed turnover by entity

The additional widget is available only with Multicompany enabled and multiple entities in the native proposal-sharing scope (`getEntity('propal')`). Reactivate LMDBCRM after upgrading, then add it from the native widget catalogue; it is not placed automatically. Existing placements are preserved.

Line colours come directly from DolGraph and the active theme palette; colours may repeat when there are more entities than palette entries. Each authorised entity has its own labelled line over the viewing entity’s current fiscal year, with no historical or additional total series. Signed/billed proposals use their signature date and amount excluding tax. Missing months/entities are zero; an entirely empty period uses the native empty-state message, while a query failure remains an error. Personal/full CRM rights and native commercial restrictions still apply before aggregation. A sharing change between loading and rendering invalidates the loaded result.

### Commerciaux éligibles au classement / Eligible ranked sales representatives

Le classement et son sélecteur conservent uniquement les utilisateurs internes actifs ayant accès à l’entité courante et le droit natif **Créer/modifier les propositions commerciales** (`propal.creer`). Les droits directs et hérités des groupes sont chargés par `User::loadRights('propale')` (nom historique natif), puis testés par `hasRight()`. Avec Multicompany, `DaoMulticompany::verifyRight()` contrôle séparément l’accès à l’entité, y compris en mode transverse. Le partage des devis ne suffit pas à rendre leur auteur éligible. Les positions sont recalculées parmi ces commerciaux ; leurs devis restent limités au périmètre de lecture et de partage autorisé. Aucun réglage ni droit n’est modifié. Cette règle concerne la page de classement et son sélecteur, sans modifier les populations des widgets.

The ranking and its selector retain only active internal users who can access the current entity and have the native **Create/modify commercial proposals** permission (`propal.creer`). Native effective permissions include direct and group grants, loaded with the historical native module name `propale` before checking `hasRight('propal', 'creer')`. Multicompany entity access is checked separately, including transverse mode; proposal sharing alone does not qualify an author. Positions are calculated among eligible representatives and source proposals retain their authorised visibility/sharing scope. No assignments or settings are changed. This rule applies to the ranking page and selector; widget populations are unchanged.
