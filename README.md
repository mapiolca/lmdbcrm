# LMDBCRM pour [Dolibarr ERP & CRM](https://www.dolibarr.org)

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

## Permissions du classement et des widgets

Les permissions LMDBCRM se règlent dans les fiches natives **Utilisateurs / Groupes**. Le classement et les sept widgets (y compris les commandes livrées non facturées) sont indépendants :

| Ensemble | Aperçu masqué | Lecture complète |
|---|---|---|
| Classement des commerciaux | `ranking.readmasked` | `ranking.read` |
| Widgets CRM | `widgets.readmasked` | `widgets.read` |

Sans aucun droit de l’ensemble, son menu/widget est absent et son accès direct est refusé. L’aperçu masqué conserve uniquement les titres et des formes neutres : aucune identité, aucun rang réel, montant, compteur, lien métier ou série graphique n’est chargé. La lecture complète suffit seule et prend priorité lorsque les deux droits sont accordés, y compris via des groupes différents.

Ces permissions concernent les utilisateurs internes. Elles s’ajoutent à la lecture native des devis (classement et six widgets) ou des commandes (widget des commandes). Les lectures complètes respectent les entités partagées et, sans le droit natif de voir tous les clients, les tiers affectés au commercial. Les indicateurs « entreprise » représentent alors le périmètre accessible, pas nécessairement toute l’entreprise.

**Après mise à jour :** désactiver/réactiver LMDBCRM depuis la gestion native des modules pour enregistrer les quatre droits, puis les attribuer explicitement aux utilisateurs ou groupes concernés. Aucun droit n’est accordé automatiquement, même aux administrateurs. Les identifiants `45001101` à `45001104`, les attributions explicites et les placements de widgets restent stables lors des réactivations. Aucun droit sur les autres modules n’est modifié.

Les aperçus utilisent un cache natif séparé, alimenté exclusivement par des formes neutres. Les affichages complets sont recalculés et leur ancien cache est invalidé à chaque rendu pour prendre en compte un changement de droits, de périmètre ou de filtre ; cela concerne uniquement les widgets LMDBCRM. Une erreur d’invalidation empêche l’affichage et est journalisée. La configuration globale du cache est conservée.

Les changements de droits prennent effet lors d’une nouvelle requête après leur rechargement natif par Dolibarr ; ils ne retirent pas rétroactivement un contenu déjà téléchargé dans un navigateur.

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

Configure LMDBCRM rights in the native **Users / Groups** permission screens. Ranking (`ranking.readmasked`, `ranking.read`) and all seven CRM widgets (`widgets.readmasked`, `widgets.read`) form two independent groups. No right means no access; masked preview shows only titles and fixed neutral shapes; full read takes precedence if both rights are granted.

Preview mode does not fetch or transmit real names, rankings, amounts, counts, business links or chart data. These interfaces are for internal users and also require the native proposal/order read permission. Full results respect shared entities and customer assignments; company indicators are limited to the viewer’s accessible scope.

**Upgrade:** disable/re-enable LMDBCRM through the native module manager to register the four rights, then explicitly assign them to users or groups. No automatic grants are made, including to administrators. Permission IDs `45001101`–`45001104`, explicit assignments and personalised widget positions survive reactivation.

Masked previews have a separate native cache containing synthetic content only. Full CRM widgets invalidate their own previous cache and render fresh authorised results without writing a new full-data cache. The global cache configuration is preserved; a failed invalidation suppresses output and is logged. Changes apply to subsequent requests after native permission reloading, not to content already downloaded.

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
