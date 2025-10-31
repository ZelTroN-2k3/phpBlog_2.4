# phpBlog v2.4
phpBlog - News, Blog & Magazine CMS

## Améliorations (Version 2.4+)

Cette version du phpBlog 2.4 a été largement améliorée pour inclure des fonctionnalités modernes, des correctifs de sécurité critiques et des optimisations de performance majeures.

---

### 🚀 Fonctionnalités

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

### 🔒 Sécurité et Modernisation

* **Sécurité des Mots de Passe :** Le stockage des mots de passe a été migré de `sha256` (obsolète) vers les fonctions PHP modernes et sécurisées `password_hash()` et `password_verify()` (`login.php`, `profile.php`, `install/done.php`).
* **Modernisation de la Base de Données :** Les colonnes `date` (VARCHAR) et `time` (VARCHAR) ont été remplacées par une seule colonne `created_at` (DATETIME) pour les articles, commentaires, messages et fichiers, garantissant l'intégrité des données et simplifiant les requêtes.

---

### ⚡️ Performance et Optimisation

* **Correction des Requêtes N+1 :** Optimisation majeure des requêtes SQL dans la barre latérale et le tableau de bord pour réduire drastiquement le nombre d'appels à la base de données.
    * La liste des catégories et le comptage des articles sont désormais effectués en **1 seule requête** (au lieu de N+1) (`core.php`).
    * La liste des commentaires récents (sidebar et dashboard) récupère les auteurs et les articles en **1 seule requête** (au lieu de 2N+1) (`core.php`, `admin/dashboard.php`).

---

### 🐞 Corrections de Bugs

* Correction d'un bug d'affichage où les avatars d'utilisateurs de grande taille déformaient le widget "Recent Comments" dans le tableau de bord (`admin/header.php`).
* Correction d'une faute de frappe (`&;`) dans la barre de défilement "Latest Posts" (`core.php`).