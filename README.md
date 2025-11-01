# phpBlog v2.4
phpBlog - News, Blog & Magazine CMS

## Améliorations (Version 2.4+)

Cette version du phpBlog 2.4 a été largement améliorée pour inclure des fonctionnalités modernes, des correctifs de sécurité critiques et des optimisations de performance majeures.

---

### 🚀 Fonctionnalités (Base v2.4+)

* **Système de Tags Complet :** Ajout d'un système de tags (mots-clés).
    * Intégration de **Tagify** dans l'administration pour une saisie facile des tags (`admin/add_post.php`, `admin/posts.php`).
    * Affichage des tags cliquables sur les articles (`post.php`).
    * Nouvelle page `tag.php` pour lister tous les articles associés à un tag spécifique.
    * Ajout d'un widget "Nuage de Tags Populaires" dans la barre latérale (`core.php`).

* **Réponses aux Commentaires :** Les utilisateurs peuvent désormais répondre à des commentaires spécifiques, créant des discussions imbriquées.
    * La page `my-comments.php` a été mise à jour pour refléter cette structure.

* **Coloration Syntaxique :** Ajout de **Highlight.js** pour une belle coloration du code.
    * Fonctionne pour les articles via les balises `<pre><code>`.
    * Fonctionne pour les commentaires via un BBCode personnalisé `[code=php]...[/code]`.

* **Liens de Téléchargement :** Les articles peuvent maintenant inclure des liens de téléchargement directs (.zip, .rar) et des liens GitHub, avec des icônes correspondantes (`admin/add_post.php`, `post.php`).

* **Sitemap SEO :** Le fichier `sitemap.php` a été entièrement reconstruit pour inclure dynamiquement tous les articles, pages et catégories, améliorant considérablement le référencement.

* **Interface Utilisateur :** L'avatar de l'utilisateur remplace l'icône "Profil" générique dans la barre de navigation principale (`core.php`).

---

### ⚡️ Performance et Optimisation (Base v2.4+)

* **Correction des Requêtes N+1 :** Optimisation majeure des requêtes SQL dans la barre latérale et le tableau de bord pour réduire drastiquement le nombre d'appels à la base de données.
    * La liste des catégories et le comptage des articles sont désormais effectués en **1 seule requête** (au lieu de N+1) (`core.php`).
    * La liste des commentaires récents (sidebar et dashboard) récupère les auteurs et les articles en **1 seule requête** (au lieu de 2N+1) (`core.php`, `admin/dashboard.php`).

---

### ✨ Engagement des Utilisateurs

Ces fonctionnalités ont été ajoutées pour augmenter l'engagement des utilisateurs et améliorer l'expérience de lecture et de rédaction.

* **Système de Favoris :** Les utilisateurs connectés peuvent enregistrer des articles dans une liste personnelle (`my-favorites.php`) via un bouton AJAX sur la page de l'article (`post.php`).
* **Profils Auteurs Publics :** Une nouvelle page `author.php` affiche la biographie et tous les articles d'un auteur. Les noms d'auteurs sur le site sont désormais cliquables.
* **Badges de Commentaires :** Un système de "gamification" qui affiche des badges (ex: "Pipelette", "Actif", "Fidèle") à côté du nom des utilisateurs en fonction de leur nombre de commentaires (`core.php`).

---

### 🎨 Interface Utilisateur (UI/UX)

* **Mode Sombre (Dark Mode) :** Un bouton de bascule (lune/soleil) a été ajouté à la barre de navigation. Le site respecte la préférence système de l'utilisateur (clair/sombre) et sauvegarde le choix dans le `localStorage` du navigateur (`core.php`, `assets/css/phpblog.css`).

---

### 🔧 Administration (Tableau de bord)

Le tableau de bord a été modernisé pour être plus utile et visuel.

* **Statistiques Exploitables :** Remplacement de l'ancienne liste de statistiques par des cartes d'action rapide (Articles Publiés, Ébauches, Commentaires en attente, Messages non lus) (`admin/dashboard.php`).
* **Graphique des Vues :** Ajout d'un graphique à barres (Chart.js) affichant les 5 articles les plus populaires en fonction de leurs vues (`admin/dashboard.php`, `admin/header.php`).
* **Aperçu Rapide :** Ajout d'un widget affichant la version du blog, le nombre total d'utilisateurs et le thème actif (`admin/dashboard.php`).
* **Création d'Utilisateurs :** Les administrateurs peuvent désormais créer de nouveaux utilisateurs (Admin, Éditeur, Utilisateur) directement depuis le panneau d'administration (`admin/add_user.php`, `admin/users.php`).
* **Système d'Ébauches (Drafts) :** Les administrateurs peuvent désormais enregistrer des articles en tant que "Ébauche", "Publié" ou "Inactif", améliorant le flux de travail de rédaction (`admin/add_post.php`, `admin/posts.php`).
* **Temps de Lecture Estimé :** Affiche une estimation du temps de lecture (ex: "Lecture : 4 min") sur toutes les listes d'articles et les pages d'articles (`core.php`, `index.php`, `blog.php`, etc.).

---

### 🔒 Sécurité (Renforcement)

Des mesures de sécurité critiques ont été ajoutées pour protéger le site et ses utilisateurs.

* **Protection CSRF (Cross-Site Request Forgery) :** Tous les formulaires (publics et admin) ainsi que toutes les actions de suppression/modification (liens GET) sont désormais protégés par des jetons de session uniques (`core.php`, `admin/header.php`, et tous les fichiers de formulaire).
* **Limitation des Tentatives de Connexion :** Le formulaire de connexion (`login.php`) bloque désormais les tentatives de connexion pendant 5 minutes après 5 échecs pour empêcher les attaques par force brute.
* **Sécurité des Mots de Passe (Base v2.4+) :** Le stockage des mots de passe a été migré de `sha256` (obsolète) vers les fonctions PHP modernes et sécurisées `password_hash()` et `password_verify()` (`login.php`, `profile.php`, `install/done.php`).

---

### 🐞 Corrections de Bugs

* Correction d'un bug d'affichage où les avatars d'utilisateurs de grande taille déformaient le widget "Recent Comments" dans le tableau de bord (`admin/header.php`).
* Correction d'une faute de frappe (`&;`) dans la barre de défilement "Latest Posts" (`core.php`).
* Correction d'un bug d'affichage (HTML échappé) sur la page de recherche `search.php` lors de l'affichage du nom de l'auteur.
* Correction d'une erreur `Fatal error: Cannot redeclare short_text()` dans `admin/header.php` lors de l'inclusion de `core.php`.
* Correction d'une erreur de chemin (`Not Found ... /admin/install/index.php`) lors de l'inclusion de `core.php` depuis le dossier `/admin`.