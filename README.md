# LMDBCRM pour [Dolibarr ERP & CRM](https://www.dolibarr.org)

Divers widgets et fonctionnalités CRM pour compléter votre Dolibarr préféré.

## Fonctionnalités
- Widget podium des 3 meilleurs commerciaux basé sur le nombre de propositions signées des 30 derniers jours.
- Widget podium des 3 meilleurs commerciaux basé sur le Chiffre d'affaire signé des 30 derniers jours.
- Widget Taux de conversion de l'uilisateur vs la société.
- Widget graphique des taux de conversion utilisateur / entreprise avec filtres de période.
- Widget graphique du CA signé par mois (exercice en cours et deux exercices précédents).
- Page de classement des commerciaux avec filtres multi-utilisateurs et recherche textuelle.
- Affichage des photos utilisateurs et des volumes signés pour motiver les équipes.
- Compatibilité Multicompany : le podium respecte le périmètre entité de l'utilisateur connecté.
- Traductions fournies : en_US, fr_FR, de_DE, it_IT, es_ES.

## Compatibilité Dolibarr
- Version minimale : Dolibarr 21.0 (module testé sur 21.x et supérieur).
- PHP minimal : 7.2.

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
- Sales rep ranking page with multi-user filters and keyword search.
- Displays user pictures and signed volumes to motivate teams.
- Multicompany-compatible: the podium respects the entity scope of the logged-in user.
- Provided translations: en_US, fr_FR, de_DE, it_IT, es_ES.

## Dolibarr compatibility
- Minimum version: Dolibarr 21.0 (module tested on 21.x and above).
- Minimum PHP version: 7.2.

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
