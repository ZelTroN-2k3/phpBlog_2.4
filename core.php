<?php
// phpBlog version
$phpblog_version = "2.4+";

$configfile = 'config.php';
if (!file_exists($configfile)) {
    echo '<meta http-equiv="refresh" content="0; url=install/index.php" />';
    exit();
}

// Set longer maxlifetime of the session (7 days)
@ini_set( "session.gc_maxlifetime", '604800');

// Set longer cookie lifetime of the session (7 days)
@ini_set( "session.cookie_lifetime", '604800');

session_start();
include "config.php";

// Data Sanitization
// Note : FILTER_SANITIZE_SPECIAL_CHARS n'est pas la meilleure protection universelle.
// Il est préférable de valider/assainir les entrées spécifiques au moment de leur utilisation.
// Mais nous le gardons pour la cohérence avec votre code existant.
$_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
//$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

if (!isset($_SESSION['sec-username'])) {
    $logged = 'No';
} else {
    
    $username = $_SESSION['sec-username'];
    
    // Requête préparée pour la vérification de session
    $stmt_user_check = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? LIMIT 1");
    mysqli_stmt_bind_param($stmt_user_check, "s", $username);
    mysqli_stmt_execute($stmt_user_check);
    $querych = mysqli_stmt_get_result($stmt_user_check);
    
    if (mysqli_num_rows($querych) == 0) {
        $logged = 'No';
        // Détruire la session invalide
        unset($_SESSION['sec-username']);
    } else {
        $rowu   = mysqli_fetch_assoc($querych); // Utiliser fetch_assoc pour la cohérence
        $logged = 'Yes';
    }
    mysqli_stmt_close($stmt_user_check);
}

function short_text($text, $length)
{
    $maxTextLenght = $length;
    $aspace        = " ";
    if (strlen($text) > $maxTextLenght) {
        $text = substr(trim($text), 0, $maxTextLenght);
        $text = substr($text, 0, strlen($text) - strpos(strrev($text), $aspace));
        $text = $text . '...';
    }
    return $text;
}

function emoticons($text)
{
    // ... (votre fonction emoticons reste inchangée) ...
    $icons = array(
        ':)' => '🙂',
        ':-)' => '🙂',
        ':}' => '🙂',
        ':D' => '😀',
        ':d' => '😁',
        ':-D ' => '😂',
        ';D' => '😂',
        ';d' => '😂',
        ';)' => '😉',
        ';-)' => '😉',
        ':P' => '😛',
        ':-P' => '😛',
        ':-p' => '😛',
        ':p' => '😛',
        ':-b' => '😛',
        ':-Þ' => '😛',
        ':(' => '🙁',
        ';(' => '😓',
        ':\'(' => '😓',
        ':o' => '😮',
        ':O' => '😮',
        ':0' => '😮',
        ':-O' => '😮',
        ':|' => '😐',
        ':-|' => '😐',
        ' :/' => ' 😕',
        ':-/' => '😕',
        ':X' => '😷',
        ':x' => '😷',
        ':-X' => '😷',
        ':-x' => '😷',
        '8)' => '😎',
        '8-)' => '😎',
        'B-)' => '😎',
        ':3' => '😊',
        '^^' => '😊',
        '^_^' => '😊',
        '<3' => '😍',
        ':*' => '😘',
        'O:)' => '😇',
        '3:)' => '😈',
        'o.O' => '😵',
        'O_o' => '😵',
        'O_O' => '😵',
        'o_o' => '😵',
        '0_o' => '😵',
        'T_T' => '😵',
        '-_-' => '😑',
        '>:O' => '😆',
        '><' => '😆',
        '>:(' => '😣',
        ':v' => '🙃',
        '(y)' => '👍',
        ':poop:' => '💩',
        ':|]' => '🤖'
    );
    return strtr($text, $icons);
}

function format_comment_with_code($text)
{
    // 1. Protéger les blocs de code dans un tableau temporaire.
    $code_blocks = [];
    $i = 0;

    // 2. Remplacer [code=lang]...[/code] par un placeholder
    // (Ex: [code=php]...[/code])
    $text = preg_replace_callback(
        '/\[code=([a-zA-Z0-9_-]+)\](.*?)\[\/code\]/s',
        function ($matches) use (&$code_blocks, &$i) {
            $lang = htmlspecialchars($matches[1]); // Sécurise la classe de langue
            $code_content = htmlspecialchars($matches[2]); // Échappe le code à l'intérieur
            $placeholder = "---CODEBLOCK{$i}---";
            // Crée le bloc HTML final
            $code_blocks[$placeholder] = '<pre><code class="language-' . $lang . '">' . $code_content . '</code></pre>';
            $i++;
            return $placeholder;
        },
        $text
    );

    // 3. Remplacer [code]...[/code] (sans langue spécifiée) par un placeholder
    $text = preg_replace_callback(
        '/\[code\](.*?)\[\/code\]/s',
        function ($matches) use (&$code_blocks, &$i) {
            $code_content = htmlspecialchars($matches[1]); // Échappe le code
            $placeholder = "---CODEBLOCK{$i}---";
            $code_blocks[$placeholder] = '<pre><code>' . $code_content . '</code></pre>';
            $i++;
            return $placeholder;
        },
        $text
    );

    // 4. Maintenant que le code est protégé, on sécurise TOUT le reste.
    $text = htmlspecialchars($text); // Sécurise tout autre HTML (ex: <script>)
    $text = emoticons($text);      // Applique les émoticônes sur le texte normal
    $text = nl2br($text);           // Ajoute les sauts de ligne <br> au texte normal

    // 5. Ré-injecter les blocs de code (qui sont déjà formatés et sécurisés)
    if (!empty($code_blocks)) {
        $text = str_replace(array_keys($code_blocks), array_values($code_blocks), $text);
    }

    return $text;
}

// --- NOUVELLE FONCTION POUR LES COMMENTAIRES ---
function display_comments($post_id, $parent_id = 0, $level = 0) {
    global $connect, $settings, $logged, $rowu; // Rendre les variables globales accessibles

    // Ajuster la marge pour l'indentation
    // Limiter la profondeur pour éviter les abus (max 5 niveaux)
    $margin_left = ($level > 5) ? (5 * 30) : ($level * 30); // 30px par niveau

    $stmt_comments = mysqli_prepare($connect, "SELECT * FROM comments WHERE post_id=? AND parent_id = ? AND approved='Yes' ORDER BY created_at ASC");
    mysqli_stmt_bind_param($stmt_comments, "ii", $post_id, $parent_id);
    mysqli_stmt_execute($stmt_comments);
    $q = mysqli_stmt_get_result($stmt_comments);
    
    while ($comment = mysqli_fetch_array($q)) {
        // --- Bloc d'affichage d'un commentaire (copié de post.php) ---
        $aauthor_id = $comment['user_id'];
        $aauthor_name = 'Guest';
        $comment_badge = ''; // Initialiser le badge
        
        if ($comment['guest'] == 'Yes') {
            $aavatar = 'assets/img/avatar.png';
            $arole   = '<span class="badge bg-secondary">Guest</span>';
            $aauthor_name = htmlspecialchars($comment['user_id']); // Nom de l'invité
        } else {
            // Utiliser une requête préparée pour obtenir les infos de l'utilisateur
            $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt_user, "i", $aauthor_id);
            mysqli_stmt_execute($stmt_user);
            $querych = mysqli_stmt_get_result($stmt_user);
            
            if (mysqli_num_rows($querych) > 0) {
                $rowch = mysqli_fetch_assoc($querych);
                $aavatar = $rowch['avatar'];
                $aauthor_name = $rowch['username'];
                if ($rowch['role'] == 'Admin') {
                    $arole = '<span class="badge bg-danger">Administrator</span>';
                } elseif ($rowch['role'] == 'Editor') {
                    $arole = '<span class="badge bg-warning">Editor</span>';
                } else {
                    $arole = '<span class="badge bg-info">User</span>';
                }
                
                // --- NOUVELLE LIGNE ---
                // Appeler la fonction de badge UNIQUEMENT pour les utilisateurs enregistrés
                $comment_badge = get_user_comment_badge($aauthor_id);
                // --- FIN NOUVELLE LIGNE ---
                
            }
            mysqli_stmt_close($stmt_user);
        }
        
        echo '
        <div class="comment-container" style="margin-left: ' . $margin_left . 'px;" id="comment-' . $comment['id'] . '">
            <div class="row d-flex justify-content-center bg-white rounded border mt-3 mb-3 ms-1 me-1">
                <div class="mb-2 d-flex flex-start align-items-center">
                    <img class="rounded-circle shadow-1-strong mt-1 me-3"
                        src="' . htmlspecialchars($aavatar) . '" alt="' . htmlspecialchars($aauthor_name) . '" 
                        width="50" height="50" />
                    <div class="mt-1 mb-1">
                        <h6 class="fw-bold mt-1 mb-1">
                            <i class="fa fa-user"></i> ' . htmlspecialchars($aauthor_name) . ' ' . $arole . ' ' . $comment_badge . ' </h6>
                        <p class="small mb-0">
                            <i><i class="fas fa-calendar"></i> ' . date($settings['date_format'] . ' H:i', strtotime($comment['created_at'])) . '</i>
                        </p>
                    </div>
                </div>
                <hr class="my-0" />
                <p class="mt-1 mb-1 pb-1">
                    ' . format_comment_with_code(html_entity_decode($comment['comment'])) . '
                </p>
                <hr class="my-0" />
                <div class="p-2">
                    <button class="btn btn-sm btn-link" onclick="replyToComment(' . $comment['id'] . ')">
                        <i class="fas fa-reply"></i> Répondre
                    </button>
                ';
        
                // AJOUT : Bouton Modifier si l'utilisateur est l'auteur
                if ($logged == 'Yes' && $comment['guest'] == 'No' && $rowu['id'] == $comment['user_id']) {
                    echo '
                    <a href="edit-comment.php?id=' . $comment['id'] . '" class="btn btn-sm btn-link text-primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>';
                }
                
                echo '
                </div>
            </div>
        ';
        // --- Fin du bloc d'affichage ---
        
        // Appel récursif pour afficher les enfants de ce commentaire
        display_comments($post_id, $comment['id'], $level + 1);
        
        echo '</div>'; // Fermer le comment-container
    }
    mysqli_stmt_close($stmt_comments);
}
// --- FIN NOUVELLE FONCTION ---

// --- NOUVELLE FONCTION POUR L'AJAX ---
function render_comment_html($comment_id, $margin_left = 0) {
    global $connect, $settings, $logged, $rowu;
    
    // 1. Récupérer le commentaire
    $stmt_comment = mysqli_prepare($connect, "SELECT * FROM comments WHERE id=? AND approved='Yes' LIMIT 1");
    mysqli_stmt_bind_param($stmt_comment, "i", $comment_id);
    mysqli_stmt_execute($stmt_comment);
    $q = mysqli_stmt_get_result($stmt_comment);
    $comment = mysqli_fetch_array($q);
    mysqli_stmt_close($stmt_comment);

    if (!$comment) {
        return ""; // Retourne une chaîne vide si le commentaire n'est pas trouvé
    }

    // 2. Récupérer les infos de l'auteur
    $aauthor_id = $comment['user_id'];
// 2. Récupérer les infos de l'auteur
    $aauthor_id = $comment['user_id'];
    $aauthor_name = 'Guest';
    $comment_badge = ''; // Initialiser le badge
    
    if ($comment['guest'] == 'Yes') {
        $aavatar = 'assets/img/avatar.png';
        $arole   = '<span class="badge bg-secondary">Guest</span>';
        $aauthor_name = htmlspecialchars($comment['user_id']); // Nom de l'invité
    } else {
        $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt_user, "i", $aauthor_id);
        mysqli_stmt_execute($stmt_user);
        $querych = mysqli_stmt_get_result($stmt_user);
        
        if (mysqli_num_rows($querych) > 0) {
            $rowch = mysqli_fetch_assoc($querych);
            $aavatar = $rowch['avatar'];
            $aauthor_name = $rowch['username'];
            if ($rowch['role'] == 'Admin') {
                $arole = '<span class="badge bg-danger">Administrator</span>';
            } elseif ($rowch['role'] == 'Editor') {
                $arole = '<span class="badge bg-warning">Editor</span>';
            } else {
                $arole = '<span class="badge bg-info">User</span>';
            }
            
            // --- NOUVELLE LIGNE ---
            // Appeler la fonction de badge UNIQUEMENT pour les utilisateurs enregistrés
            $comment_badge = get_user_comment_badge($aauthor_id);
            // --- FIN NOUVELLE LIGNE ---
        }
        mysqli_stmt_close($stmt_user);
    }
    
    // 3. Construire le HTML (on utilise ob_start pour "capturer" l'echo)
    ob_start();
    ?>
    <div class="comment-container" style="margin-left: <?php echo $margin_left; ?>px; opacity: 0; transition: opacity 0.5s ease;" id="comment-<?php echo $comment['id']; ?>">
        <div class="row d-flex justify-content-center bg-white rounded border mt-3 mb-3 ms-1 me-1">
            <div class="mb-2 d-flex flex-start align-items-center">
                <img class="rounded-circle shadow-1-strong mt-1 me-3"
                    src="<?php echo htmlspecialchars($aavatar); ?>" alt="<?php echo htmlspecialchars($aauthor_name); ?>" 
                    width="50" height="50" />
                <div class="mt-1 mb-1">
                    <h6 class="fw-bold mt-1 mb-1">
                        <i class="fa fa-user"></i> <?php echo htmlspecialchars($aauthor_name); ?> <?php echo $arole; ?> <?php echo $comment_badge; ?> </h6>
                    <p class="small mb-0">
                        <i><i class="fas fa-calendar"></i> <?php echo date($settings['date_format'] . ' H:i', strtotime($comment['created_at'])); ?></i>
                    </p>
                </div>
            </div>
            <hr class="my-0" />
            <p class="mt-1 mb-1 pb-1">
                <?php echo format_comment_with_code(html_entity_decode($comment['comment'])); ?>
            </p>
            <hr class="my-0" />
                <div class="p-2">
                    <button class="btn btn-sm btn-link" onclick="replyToComment(<?php echo $comment['id']; ?>)">
                        <i class="fas fa-reply"></i> Répondre
                    </button>
                    <?php
                    // AJOUT : Bouton Modifier si l'utilisateur est l'auteur
                    if ($logged == 'Yes' && $comment['guest'] == 'No' && $rowu['id'] == $comment['user_id']) {
                        echo '
                        <a href="edit-comment.php?id=' . $comment['id'] . '" class="btn btn-sm btn-link text-primary">
                            <i class="fas fa-edit"></i> Modifier
                        </a>';
                    }
                    ?>
                </div>
        </div>
        </div>
    
    <?php
    // 4. Retourner le HTML capturé
    return ob_get_clean();
}
// --- FIN NOUVELLE FONCTION AJAX ---

function post_author($author_id)
{
    // Rendre $connect accessible (meilleure pratique : passer $connect en paramètre)
    global $connect; 
    
    $author_name = '-';
    $author_username = ''; // Pour le lien
    
    $stmt = mysqli_prepare($connect, "SELECT username FROM `users` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $author_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowauthp = mysqli_fetch_assoc($result)) {
        $author_name   = $rowauthp['username'];
        $author_username = $rowauthp['username']; // Stocker le nom d'utilisateur brut pour l'URL
    }
    mysqli_stmt_close($stmt);
 
    // MODIFICATION : Retourner un lien HTML au lieu d'un simple texte
    if ($author_username) {
        return '<a href="author.php?username=' . urlencode($author_username) . '">' . htmlspecialchars($author_name) . '</a>';
    } else {
        return htmlspecialchars($author_name); // Retourner le nom simple si l'auteur n'est pas trouvé
    }
}

function post_title($post_id)
{
    global $connect;
    
    $title = '-';
    
    $stmt = mysqli_prepare($connect, "SELECT title FROM `posts` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowtitlep = mysqli_fetch_assoc($result)) {
        $title     = $rowtitlep['title'];
    }
    mysqli_stmt_close($stmt);
 
    return $title;
}

function post_category($category_id)
{
    global $connect;
    
    $category = '-';

    $stmt = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowcat = mysqli_fetch_assoc($result)) {
        $category = $rowcat['category'];
    }
    mysqli_stmt_close($stmt);
 
    return $category;
}

function post_slug($post_id)
{
    global $connect;
    
    $post_slug = '';

    $stmt = mysqli_prepare($connect, "SELECT slug FROM `posts` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowpost = mysqli_fetch_assoc($result)) {
        $post_slug = $rowpost['slug'];
    }
    mysqli_stmt_close($stmt);
 
    return $post_slug;
}

function post_categoryslug($category_id)
{
    global $connect;
    
    $category_slug = '';

    $stmt = mysqli_prepare($connect, "SELECT slug FROM `categories` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowcat = mysqli_fetch_assoc($result)) {
        $category_slug = $rowcat['slug'];
    }
    mysqli_stmt_close($stmt);
 
    return $category_slug;
}

function post_commentscount($post_id)
{
    global $connect;
    
    $comments_count = '0';

    $stmt = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM `comments` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $comments_count = $row['count'];
    mysqli_stmt_close($stmt);
 
    return $comments_count;
}

function get_post_like_count($post_id)
{
    global $connect;
    $count = 0;
    
    $stmt = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM `post_likes` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $count = $row['count'];
    }
    mysqli_stmt_close($stmt);
    return $count;
}

function check_user_has_liked($post_id)
{
    global $connect, $logged, $rowu;
    
    if ($logged == 'Yes') {
        $user_id = $rowu['id'];
        $stmt = mysqli_prepare($connect, "SELECT id FROM `post_likes` WHERE post_id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
    } else {
        $session_id = session_id();
        $stmt = mysqli_prepare($connect, "SELECT id FROM `post_likes` WHERE post_id=? AND session_id=?");
        mysqli_stmt_bind_param($stmt, "is", $post_id, $session_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $has_liked = (mysqli_num_rows($result) > 0);
    mysqli_stmt_close($stmt);
    
    return $has_liked;
}

// Récupère un badge HTML basé sur le nombre de commentaires d'un utilisateur.
function get_user_comment_badge($user_id)
{
    global $connect;
    
    // 1. Compter les commentaires approuvés de l'utilisateur
    $count = 0;
    $stmt_count = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM comments WHERE user_id = ? AND guest = 'No' AND approved = 'Yes'");
    mysqli_stmt_bind_param($stmt_count, "i", $user_id);
    mysqli_stmt_execute($stmt_count);
    $result = mysqli_stmt_get_result($stmt_count);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $count = (int)$row['count'];
    }
    mysqli_stmt_close($stmt_count);

    // 2. Définir les seuils et les badges
    if ($count >= 50) {
        return '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-star"></i> Vétéran</span>';
    } elseif ($count >= 20) {
        return '<span class="badge bg-success ms-1"><i class="fas fa-comments"></i> Fidèle</span>';
    } elseif ($count >= 5) {
        return '<span class="badge bg-info ms-1"><i class="fas fa-comment-dots"></i> Actif</span>';
    } elseif ($count >= 1) {
        return '<span class="badge bg-secondary ms-1"><i class="fas fa-comment"></i> Pipelette</span>';
    }

    return ''; // Pas de badge si 0 commentaire
}


// Calcule le temps de lecture estimé pour un texte.
function get_reading_time($content, $wpm = 200) {
    // 1. Supprimer le HTML
    $text = strip_tags(html_entity_decode($content));
    
    // 2. Compter les mots
    $word_count = str_word_count($text);
    
    // 3. Calculer le temps
    $minutes = ceil($word_count / $wpm);
    
    // 4. Gérer le cas de "0 minute" (articles très courts)
    if ($minutes < 1) {
        $minutes = 1;
    }
    
    // 5. Retourner la chaîne formatée
    return '<i class="far fa-clock"></i> Lecture : ' . $minutes . ' min';
}

function head()
{
    // Rendre $connect, $logged, $rowu, $settings accessibles
    global $connect, $logged, $rowu, $settings;
?>
<!DOCTYPE html>
<html lang="en">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
	$current_page = basename($_SERVER['SCRIPT_NAME']);
    $pagetitle   = '';
    $description = '';

    // SEO Titles, Descriptions and Sharing Tags
    if ($current_page == 'contact.php') {
        $pagetitle   = 'Contact';
		$description = 'If you have any questions do not hestitate to send us a message.';
		
    } else if ($current_page == 'gallery.php') {
        $pagetitle   = 'Gallery';
		$description = 'View all images from the Gallery.';
		
    } else if ($current_page == 'blog.php') {
        $pagetitle   = 'Blog';
		$description = 'View all blog posts.';
        
    } else if ($current_page == 'profile.php') {
        $pagetitle   = 'Profile';
		$description = 'Manage your account settings.';
		
    } else if ($current_page == 'my-comments.php') {
        $pagetitle   = 'My Comments';
		$description = 'Manage your comments.';
		
    } else if ($current_page == 'login.php') {
        $pagetitle   = 'Sign In';
		$description = 'Login into your account.';
		
    } else if ($current_page == 'unsubscribe.php') {
        $pagetitle   = 'Unsubscribe';
		$description = 'Unsubscribe from Newsletter.';
		
    } else if ($current_page == 'error404.php') {
        $pagetitle   = 'Error 404';
		$description = 'Page is not found.';
		
    } else if ($current_page == 'search.php') {
		
		if (!isset($_GET['q'])) {
			echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
		}
		
		$word        = $_GET['q']; // Déjà filtré par FILTER_SANITIZE_SPECIAL_CHARS au début
        $pagetitle   = 'Search';
		$description = 'Search results for ' . $word . '.';
		
    } else if ($current_page == 'post.php') {
        $slug = $_GET['name'] ?? ''; // Utiliser l'opérateur Null Coalescing
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        // Requête préparée
        $stmt_post_seo = mysqli_prepare($connect, "SELECT title, slug, image, content FROM `posts` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_post_seo, "s", $slug);
        mysqli_stmt_execute($stmt_post_seo);
        $runpt = mysqli_stmt_get_result($stmt_post_seo);
        
        if (mysqli_num_rows($runpt) == 0) {
            mysqli_stmt_close($stmt_post_seo);
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        $rowpt = mysqli_fetch_assoc($runpt);
        mysqli_stmt_close($stmt_post_seo);
        
        $pagetitle   = $rowpt['title'];
		$description = short_text(strip_tags(html_entity_decode($rowpt['content'])), 150);
		
        // Utiliser htmlspecialchars pour la sécurité dans les balises meta
		echo '
		<meta property="og:title" content="' . htmlspecialchars($rowpt['title']) . '" />
		<meta property="og:description" content="' . htmlspecialchars(short_text(strip_tags(html_entity_decode($rowpt['content'])), 150)) . '" />
		<meta property="og:image" content="' . htmlspecialchars($rowpt['image']) . '" />
		<meta property="og:type" content="article"/>
		<meta property="og:url" content="' . htmlspecialchars($settings['site_url'] . '/post?name=' . $rowpt['slug']) . '" />
		<meta name="twitter:card" content="summary_large_image"></meta>
		<meta name="twitter:title" content="' . htmlspecialchars($rowpt['title']) . '" />
		<meta name="twitter:description" content="' . htmlspecialchars(short_text(strip_tags(html_entity_decode($rowpt['content'])), 150)) . '" />
		<meta name="twitter:image" content="' . htmlspecialchars($rowpt['image']) . '" />
		<meta name="twitter:url" content="' . htmlspecialchars($settings['site_url'] . '/post?name=' . $rowpt['slug']) . '" />
		';
		
    } else if ($current_page == 'page.php') {
        $slug = $_GET['name'] ?? '';
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
            exit;
        }
        
        // Requête préparée
        $stmt_page_seo = mysqli_prepare($connect, "SELECT title, content FROM `pages` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_page_seo, "s", $slug);
        mysqli_stmt_execute($stmt_page_seo);
        $runpp = mysqli_stmt_get_result($stmt_page_seo);
        
        if (mysqli_num_rows($runpp) == 0) {
            mysqli_stmt_close($stmt_page_seo);
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
            exit;
        }
        $rowpp = mysqli_fetch_assoc($runpp);
        mysqli_stmt_close($stmt_page_seo);
        
        $pagetitle   = $rowpp['title'];
		$description = short_text(strip_tags(html_entity_decode($rowpp['content'])), 150);
		
    } else if ($current_page == 'category.php') {
        $slug = $_GET['name'] ?? '';
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        // Requête préparée
        $stmt_cat_seo = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_cat_seo, "s", $slug);
        mysqli_stmt_execute($stmt_cat_seo);
        $runct = mysqli_stmt_get_result($stmt_cat_seo);
        
        if (mysqli_num_rows($runct) == 0) {
            mysqli_stmt_close($stmt_cat_seo);
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        $rowct = mysqli_fetch_assoc($runct);
        mysqli_stmt_close($stmt_cat_seo);
        
        $pagetitle   = $rowct['category'];
		$description = 'View all blog posts from ' . $rowct['category'] . ' category.';
    
    } else if ($current_page == 'tag.php') {
        $slug = $_GET['name'] ?? '';
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        // Requête préparée
        $stmt_tag_seo = mysqli_prepare($connect, "SELECT name FROM `tags` WHERE slug=?");
        mysqli_stmt_bind_param($stmt_tag_seo, "s", $slug);
        mysqli_stmt_execute($stmt_tag_seo);
        $runtag = mysqli_stmt_get_result($stmt_tag_seo);
        
        if (mysqli_num_rows($runtag) == 0) {
            mysqli_stmt_close($stmt_tag_seo);
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        $rowtag = mysqli_fetch_assoc($runtag);
        mysqli_stmt_close($stmt_tag_seo);
        
        $pagetitle   = 'Articles tagués : ' . $rowtag['name'];
		$description = 'Voir tous les articles avec le tag ' . $rowtag['name'];
    }
    
    // Utiliser htmlspecialchars pour le titre et la description
    if ($current_page == 'index.php') {
        echo '
		<title>' . htmlspecialchars($settings['sitename']) . '</title>
		<meta name="description" content="' . htmlspecialchars($settings['description']) . '" />';
    } else {
        echo '
		<title>' . htmlspecialchars($pagetitle) . ' - ' . htmlspecialchars($settings['sitename']) . '</title>
		<meta name="description" content="' . htmlspecialchars($description) . '" />';
    }
?>
        
        <meta name="author" content="Antonov_WEB" />
		<meta name="generator" content="phpBlog" />
        <meta name="robots" content="index, follow, all" />
        <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

		<link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" type="text/css" rel="stylesheet"/>
<?php
if($settings['theme'] != "Bootstrap 5") {
    // Utiliser htmlspecialchars pour sécuriser la variable au cas où
    echo '
        <link href="https://bootswatch.com/5/'. htmlspecialchars(strtolower($settings['theme'])) .'/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	';
}
?>
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
		
		<link href="assets/css/phpblog.css" rel="stylesheet">
		<script src="assets/js/phpblog.js"></script>
<?php
if ($current_page == 'post.php') {
?>
        <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials.css" />
        <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials-theme-classic.css" />
        <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials.min.js"></script>
<?php
}
?>
<?php
if ($current_page == 'post.php' || $current_page == 'tag.php') { // MODIF : Ajout de tag.php
?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/javascript.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/css.min.js"></script>
        <script>
            // Initialise la coloration après le chargement de la page
            document.addEventListener('DOMContentLoaded', (event) => {
                hljs.highlightAll();
            });
        </script>
        <?php
}
?>
	
        <style>
<?php
if($settings['background_image'] != "") {
    // Échapper l'URL pour la sécurité dans le CSS
    echo 'body {
        background: url("' . htmlspecialchars($settings['background_image']) . '") no-repeat center center fixed;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
    }';
}
?>
        </style>
        
<?php
// Le décodage Base64 est conservé car c'est ainsi que vous l'avez stocké
echo base64_decode($settings['head_customcode']);
?>

</head>

<body <?php 
if ($settings['rtl'] == "Yes") {
	echo 'dir="rtl"';
}
?>>

<?php
if ($logged == 'Yes' && ($rowu['role'] == 'Admin' || $rowu['role'] == 'Editor')) {
?>
	<div class="nav-scroller bg-dark shadow-sm">
		<nav class="nav" aria-label="Secondary navigation">
<?php
if ($rowu['role'] == 'Admin') {
?>
			<a class="nav-link text-white" href="admin/dashboard.php">ADMIN MENU</a>
<?php
} else {
?>
			<a class="nav-link text-white" href="admin/dashboard.php">EDITOR MENU</a>
<?php
}
?>
			<a class="nav-link text-secondary" href="admin/dashboard.php">
				<i class="fas fa-columns"></i> Dashboard
			</a>
			<a class="nav-link text-secondary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="fas fa-tasks"></i> Manage
			</a>
				<ul class="dropdown-menu bg-dark">
<?php
if ($rowu['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="admin/settings.php">
							Site Settings
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/menu_editor.php">
							Menu
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/widgets.php">
							Widgets
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/users.php">
							Users
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/newsletter.php">
							Newsletter
						</a>
					</li>
<?php
}
?>
					<li>
						<a class="dropdown-item text-white" href="admin/files.php">
							Files
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/posts.php">
							Posts
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/gallery.php">
							Gallery
						</a>
					</li>
<?php
if ($rowu['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="admin/pages.php">
							Pages
						</a>
					</li>
<?php
}
?>
				</ul>
<?php
if ($rowu['role'] == 'Admin') {
    // Requête simple sans variable externe, pas besoin de préparer
	$msgcount_query  = mysqli_query($connect, "SELECT id FROM messages WHERE viewed = 'No'");
	$unread_messages = mysqli_num_rows($msgcount_query);
?>
			
			<a class="nav-link text-secondary" href="admin/messages.php">
				<i class="fas fa-envelope"></i> Messages
				<span class="badge text-bg-light rounded-pill align-text-bottom"><?php
	echo $unread_messages; 
?> </span>
			</a>
			<a class="nav-link text-secondary" href="admin/comments.php">
				<i class="fas fa-comments"></i> Comments
			</a>
<?php
}
?>
			<a class="nav-link text-secondary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="far fa-plus-square"></i> New
			</a>
				<ul class="dropdown-menu bg-dark">
					<li>
						<a class="dropdown-item text-white" href="admin/add_post.php">
							Add Post
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/add_image.php">
							Add Image
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="admin/upload_file.php">
							Upload File
						</a>
					</li>
<?php
if ($rowu['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="admin/add_page.php">
							Add Page
						</a>
					</li>
<?php
}
?>
				</ul>
		</nav>
	</div>
<?php
}
?>
	
	<header class="py-3 border-bottom bg-primary">
		<div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> d-flex flex-wrap justify-content-center">
			<a href="<?php echo htmlspecialchars($settings['site_url']); ?>" class="d-flex align-items-center text-white mb-3 mb-md-0 me-md-auto text-decoration-none">
				<span class="fs-4"><b><i class="far fa-newspaper"></i> <?php
		echo htmlspecialchars($settings['sitename']);
?></b></span>
			</a>
			
			<form class="col-12 col-lg-auto mb-3 mb-lg-0" action="search" method="GET">
				<div class="input-group">
					<input type="search" class="form-control" placeholder="Search" name="q" value="<?php
if (isset($_GET['q'])) {
    // Utiliser htmlspecialchars pour la valeur de l'input
	echo htmlspecialchars($_GET['q']);
}
?>" required />
					<span class="input-group-btn">
						<button class="btn btn-dark" type="submit"><i class="fa fa-search"></i></button>
					</span>
				</div>
			</form>
		</div>
	</header>
	
	<nav class="navbar nav-underline navbar-expand-lg py-2 bg-light border-bottom">
		<div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?>">
			<button class="navbar-toggler mx-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span> Navigation
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav me-auto">
<?php
    // Requête simple sans variable externe
	$runq = mysqli_query($connect, "SELECT * FROM `menu` ORDER BY id ASC"); // Supposant que l'ordre est géré par ID
    while ($row = mysqli_fetch_assoc($runq)) {

        if ($row['path'] == 'blog') {
			
            echo '	<li class="nav-item link-body-emphasis dropdown">
						<a href="blog" class="nav-link link-dark dropdown-toggle px-2';
            if ($current_page == 'blog.php' || $current_page == 'category.php' || $current_page == 'tag.php') {
                echo ' active';
            }
            // Utiliser htmlspecialchars pour les icônes et le texte
            echo '" data-bs-toggle="dropdown">
							<i class="fa ' . htmlspecialchars($row['fa_icon']) . '"></i> ' . htmlspecialchars($row['page']) . ' 
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a class="dropdown-item" href="blog">View all posts</a></li>';
            
            // Requête simple sans variable externe
            $run2 = mysqli_query($connect, "SELECT * FROM `categories` ORDER BY category ASC");
            while ($row2 = mysqli_fetch_array($run2)) {
                // Utiliser htmlspecialchars
                echo '		<li><a class="dropdown-item" href="category?name=' . htmlspecialchars($row2['slug']) . '"><i class="fas fa-chevron-right"></i> ' . htmlspecialchars($row2['category']) . '</a></li>';
            }
            echo '		</ul>
					</li>';
		
        } else {

			echo '	<li class="nav-item link-body-emphasis">
						<a href="' . htmlspecialchars($row['path']) . '" class="nav-link link-dark px-2';
            
            $current_slug = $_GET['name'] ?? '';
            if ($current_page == 'page.php'
				&& ($current_slug == ltrim(strstr($row['path'], '='), '='))
			) {
                echo ' active';
			
            } else if ($current_page != 'page.php' && $current_page == $row['path'] . '.php') {
                echo ' active';
            }
            // Utiliser htmlspecialchars
            echo '">
							<i class="fa ' . htmlspecialchars($row['fa_icon']) . '"></i> ' . htmlspecialchars($row['page']) . '
						</a>
					</li>';
        }
    }
?>
				</ul>
				<ul class="navbar-nav d-flex">
      
<?php
    if ($logged == 'No') {
?>
					<li class="nav-item">
						<a href="login" class="btn btn-primary px-2">
							<i class="fas fa-sign-in-alt"></i> Sign In &nbsp;|&nbsp; Register
						</a>
					</li>
<?php
    } else {
?>
					<li class="nav-item dropdown">
						<a href="#" class="nav-link link-dark dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
							<img src="<?php echo htmlspecialchars($rowu['avatar']); ?>" alt="Avatar" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 5px;">
							Profile <span class="caret"></span>
						</a>
                            <ul class="dropdown-menu">
							<li>
								<a class="dropdown-item <?php
if ($current_page == 'my-favorites.php') { 
	echo ' active';
}
?>" href="my-favorites.php"> 
                                    <i class="fa fa-bookmark"></i> Mes Favoris
								</a>
							</li>
							<li>
								<a class="dropdown-item <?php
if ($current_page == 'my-comments.php') {
	echo ' active';
}
?>" href="my-comments">
									<i class="fa fa-comments"></i> My Comments
								</a>
							</li>
							<li>
								<a class="dropdown-item<?php
if ($current_page == 'profile.php') {
	echo ' active';
}
?>" href="profile">
									<i class="fas fa-cog"></i> Settings
								</a>
							</li>
							<li role="separator" class="divider"></li>
							<li>
								<a class="dropdown-item" href="logout">
									<i class="fas fa-sign-out-alt"></i> Logout
								</a>
							</li>
						</ul>
					</li>
<?php
    }
?>
				</ul>
			</div>
		</div>
	</nav>
    
<?php
if ($settings['latestposts_bar'] == 'Enabled') {
?>
    <div class="pt-2 bg-light">
        <div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> d-flex justify-content-center">
            <div class="col-md-2">
                <h5>
                    <span class="badge bg-danger">
                        <i class="fa fa-info-circle"></i> Latest: 
                    </span>
                </h5>
            </div>
            <div class="col-md-10">
                <marquee behavior="scroll" direction="right" scrollamount="6">
                    
<?php
// Requête simple sans variable externe
$run = mysqli_query($connect, "SELECT * FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT 6");
$count = mysqli_num_rows($run);
if ($count <= 0) {
    echo 'There are no published posts';
} else {
    while ($row = mysqli_fetch_assoc($run)) {
        // Utiliser htmlspecialchars
        echo '<a href="post?name=' . htmlspecialchars($row['slug']) . '">' . htmlspecialchars($row['title']) . '</a>
        &nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;';
    }
}
?>
                </marquee>
            </div>
        </div>
    </div>
<?php
}
?>
	
    <div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> mt-3">
	
<?php
// Requête simple sans variable externe
$run = mysqli_query($connect, "SELECT * FROM `widgets` WHERE position = 'header' ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($run)) {
    echo '
		<div class="card mb-3">
			<div class="card-header">' . htmlspecialchars($row['title']) . '</div>
			<div class="card-body">
				' . html_entity_decode($row['content']) . '
			</div>
		</div>
	';
}
?>
	
        <div class="row">
<?php
}

function sidebar() {
	
    global $connect, $settings; // Rendre $connect et $settings accessibles
?>
			<div id="sidebar" class="col-md-4">

				<div class="card">
					<div class="card-header"><i class="fas fa-list"></i> Categories</div>
					<div class="card-body">
						<ul class="list-group">
<?php
    // --- MODIFICATION : Requête unique pour les catégories ---
    $categories_query = mysqli_query($connect, "
        SELECT 
            c.category, c.slug, COUNT(p.id) AS posts_count
        FROM `categories` c
        LEFT JOIN posts p ON c.id = p.category_id AND p.active = 'Yes' AND p.publish_at <= NOW()
        GROUP BY c.id
        ORDER BY c.category ASC
    ");
    
    while ($row = mysqli_fetch_assoc($categories_query)) {
        // Plus besoin de requête N+1 ici
        echo '
							<a href="category?name=' . htmlspecialchars($row['slug']) . '">
								<li class="list-group-item d-flex justify-content-between align-items-center">
									' . htmlspecialchars($row['category']) . '
									<span class="badge bg-secondary rounded-pill">' . $row['posts_count'] . '</span>
								</li>
							</a>
		';
    }
    // --- FIN MODIFICATION ---
?>
						</ul>
					</div>
				</div>
				
				<div class="card mt-3">
					<div class="card-header"><i class="fas fa-tags"></i> Popular Tags</div>
					<div class="card-body">
						<div class="d-flex flex-wrap">
<?php
    // Requête pour récupérer les tags les plus utilisés
    $stmt_tags = mysqli_prepare($connect, "
        SELECT 
            t.name, t.slug, COUNT(pt.tag_id) AS tag_count
        FROM tags t
        JOIN post_tags pt ON t.id = pt.tag_id
        JOIN posts p ON pt.post_id = p.id
        WHERE p.active = 'Yes' AND p.publish_at <= NOW()
        GROUP BY pt.tag_id
        ORDER BY tag_count DESC, t.name ASC
        LIMIT 15
    ");
    mysqli_stmt_execute($stmt_tags);
    $result_tags = mysqli_stmt_get_result($stmt_tags);

    if (mysqli_num_rows($result_tags) == 0) {
        echo '<div class="alert alert-info p-2">No tags found.</div>';
    } else {
        while ($row_tag = mysqli_fetch_assoc($result_tags)) {
            echo '
                <a href="tag.php?name=' . htmlspecialchars($row_tag['slug']) . '" class="btn btn-outline-secondary btn-sm m-1">
                    <i class="fas fa-tag"></i> ' . htmlspecialchars($row_tag['name']) . '
                    <span class="badge bg-dark ms-1">' . $row_tag['tag_count'] . '</span>
                </a>
            ';
        }
    }
    mysqli_stmt_close($stmt_tags);
?>
						</div>
					</div>
				</div>
				
				<div class="card mt-3">
					<div class="card-header">
						<ul class="nav nav-tabs card-header-tabs nav-justified">
							<li class="nav-item active">
								<a class="nav-link active" href="#popular" data-bs-toggle="tab">
									Popular Posts
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" href="#commentss" data-bs-toggle="tab">
									Recent Comments
								</a>
							</li>
						</ul>
					</div>
					<div class="card-body">
						<div class="tab-content">
							<div id="popular" class="tab-pane fade show active">
<?php
    // Requête simple sans variable externe
    $run = mysqli_query($connect, "SELECT * FROM posts WHERE active='Yes' AND publish_at <= NOW() ORDER BY views DESC, id DESC LIMIT 4");
    $count = mysqli_num_rows($run);
    if ($count <= 0) {
        echo '<div class="alert alert-info">There are no published posts</div>';
    } else {
        while ($row = mysqli_fetch_assoc($run)) {
            
            $image = "";
            if($row['image'] != "") {
                // Utiliser htmlspecialchars
                $image = '<img class="rounded shadow-1-strong me-1"
							src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" width="70"
							height="70" />';
			} else {
                $image = '<svg class="bd-placeholder-img rounded shadow-1-strong me-1" width="70" height="70" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No Image" preserveAspectRatio="xMidYMid slice" focusable="false">
                <title>Image</title><rect width="70" height="70" fill="#55595c"/>
                <text x="0%" y="50%" fill="#eceeef" dy=".1em">No Image</text></svg>';
            }
            // Utiliser htmlspecialchars
            echo '       
								<div class="mb-2 d-flex flex-start align-items-center bg-light rounded">
									<a href="post?name=' . htmlspecialchars($row['slug']) . '" class="ms-1">
										' . $image . '
									</a>
									<div class="mt-2 mb-2 ms-1 me-1">
										<h6 class="text-primary mb-1">
											<a href="post?name=' . htmlspecialchars($row['slug']) . '">' . htmlspecialchars($row['title']) . '</a>
										</h6>
										<p class="text-muted small mb-0">
											<i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($row['created_at'])) . '<br />
                                            <i class="fa fa-comments"></i> Comments: 
												<a href="post?name=' . htmlspecialchars($row['slug']) . '#comments">
													<b>' . post_commentscount($row['id']) . '</b>
												</a>
										</p>
									</div>
								</div>
';
        }
    }
?>
							</div>
							<div id="commentss" class="tab-pane fade">
<?php
    // --- MODIFICATION : Requête unique pour les commentaires récents ---
    $comments_query = mysqli_query($connect, "
        SELECT 
            c.id, c.user_id, c.guest, c.created_at,
            p.title AS post_title, p.slug AS post_slug,
            u.username AS user_username, u.avatar AS user_avatar
        FROM `comments` c
        JOIN `posts` p ON c.post_id = p.id
        LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
        WHERE c.approved='Yes' AND p.active='Yes'
        ORDER BY c.id DESC 
        LIMIT 4
    ");
    
    $cmnts = mysqli_num_rows($comments_query);
    if ($cmnts == "0") {
        echo "There are no comments";
    } else {
        while ($row = mysqli_fetch_assoc($comments_query)) {
			
			$acavatar = 'assets/img/avatar.png'; // Défaut
            $acuthor_name = 'Guest';

            if ($row['guest'] == 'Yes') {
                $acuthor_name = $row['user_id']; // C'est le nom de l'invité
            } else if ($row['user_username']) {
                // L'utilisateur a été trouvé
                $acavatar = $row['user_avatar'];
                $acuthor_name = $row['user_username'];
            }
            // Si $row['guest'] == 'No' mais que $row['user_username'] est NULL (utilisateur supprimé),
            // $acuthor_name restera 'Guest' (ou vous pourriez mettre 'Utilisateur supprimé')
			
            // Plus besoin de requêtes N+1 ici
            echo '
								<div class="mb-2 d-flex flex-start align-items-center bg-light rounded border">
									<a href="post?name=' . htmlspecialchars($row['post_slug']) . '#comments" class="ms-2">
										<img class="rounded-circle shadow-1-strong me-2"
										src="' . htmlspecialchars($acavatar) . '" alt="' . htmlspecialchars($acuthor_name) . '" 
										width="60" height="60" />
									</a>
									<div class="mt-1 mb-1 ms-1 me-1">
										<h6 class="text-primary mb-1">
											<a href="post?name=' . htmlspecialchars($row['post_slug']) . '#comments">' . htmlspecialchars($acuthor_name) . '</a>
										</h6>
										<p class="text-muted small mb-0">
											on <a href="post?name=' . htmlspecialchars($row['post_slug']) . '#comments">' . htmlspecialchars($row['post_title']) . '</a><br />
											<i class="fas fa-calendar"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '
										</p>
									</div>
								</div>
';
        }
    }
    // --- FIN MODIFICATION ---
?>
                            </div>
                        </div>
                    </div>
                </div>
				
				<div class="p-4 mt-3 bg-body-tertiary rounded text-dark">
					<h6><i class="fas fa-envelope-open-text"></i> Subscribe</h6><hr />
					
					<p class="mb-3">Get the latest news and exclusive offers</p>
					
					<form action="" method="POST">
						<div class="input-group">
							<input type="email" class="form-control" placeholder="E-Mail Address" name="email" required />
							<span class="input-group-btn">
								<button class="btn btn-primary" type="submit" name="subscribe">Subscribe</button>
							</span>
						</div>
					</form>
<?php
    if (isset($_POST['subscribe'])) {
        $email = $_POST['email']; // $_POST est déjà filtré au début du script
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo '<div class="alert alert-danger">The entered E-Mail Address is invalid</div>';
        } else {
            // Requête préparée pour vérifier l'existence
            $stmt_sub_check = mysqli_prepare($connect, "SELECT email FROM `newsletter` WHERE email=? LIMIT 1");
            mysqli_stmt_bind_param($stmt_sub_check, "s", $email);
            mysqli_stmt_execute($stmt_sub_check);
            $result_sub_check = mysqli_stmt_get_result($stmt_sub_check);
            
            if (mysqli_num_rows($result_sub_check) > 0) {
                echo '<div class="alert alert-warning">This E-Mail Address is already subscribed.</div>';
            } else {
                // Requête préparée pour l'insertion
                $stmt_sub_insert = mysqli_prepare($connect, "INSERT INTO `newsletter` (email) VALUES (?)");
                mysqli_stmt_bind_param($stmt_sub_insert, "s", $email);
                mysqli_stmt_execute($stmt_sub_insert);
                mysqli_stmt_close($stmt_sub_insert);
                echo '<div class="alert alert-success">You have successfully subscribed to our newsletter.</div>';
            }
            mysqli_stmt_close($stmt_sub_check);
        }
    }
?>
				</div>

<?php
    // Requête simple sans variable externe
    $run = mysqli_query($connect, "SELECT * FROM `widgets` WHERE position = 'sidebar' ORDER BY id ASC");
    while ($row = mysqli_fetch_assoc($run)) {
        echo '	
				<div class="card mt-3">
					  <div class="card-header">' . htmlspecialchars($row['title']) . '</div>
					  <div class="card-body">
						' . html_entity_decode($row['content']) . '
					  </div>
				</div>
';
    }
?>
			</div>
		
<?php
}

function footer()
{
    global $phpblog_version, $connect, $settings;
?>
		</div>
<?php
// Requête simple sans variable externe
$run = mysqli_query($connect, "SELECT * FROM `widgets` WHERE position = 'footer' ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($run)) {
	echo '		
				<div class="card mt-3">
					<div class="card-header">' . htmlspecialchars($row['title']) . '</div>
					<div class="card-body">
						' . html_entity_decode($row['content']) . '
					</div>
				</div>
	';
}
?>
	</div>
	
	<footer class="footer border-top bg-dark text-light px-4 py-3 mt-3">
		<div class="row">
			<div class="col-md-2 mb-3">
				<p class="d-block">&copy; <?php
		echo date("Y") .' '. htmlspecialchars($settings['sitename']);
?></p>
				<p><a href="rss" target="_blank"><i class="fas fa-rss-square"></i> RSS Feed</a></p>
				<p><a href="sitemap" target="_blank"><i class="fas fa-sitemap"></i> XML Sitemap</a></p>
				<p class="d-block small">
					<a href="https://codecanyon.net/item/phpblog-powerful-blog-cms/5979801?ref=Antonov_WEB" target="_blank"><i>Powered by <b>phpBlog v<?php echo htmlspecialchars($phpblog_version); ?></b></i></a>
				</p>
			</div>
			<div class="col-md-6 mb-3">
				<h5><i class="fa fa-info-circle"></i> About</h5>
<?php
	echo htmlspecialchars($settings['description']);
?>
			</div>
			<div class="col-md-4 mb-3">
				<h5><i class="fa fa-envelope"></i> Contact</h5>
					<div class="col-12">
						<a href="mailto:<?php
    echo htmlspecialchars($settings['email']);
?>" target="_blank" class="btn btn-secondary">
							<strong><i class="fa fa-envelope"></i><span>&nbsp; <?php
    echo htmlspecialchars($settings['email']);
?></span></strong></a>
<?php
    if ($settings['facebook'] != '') {
?>
						<a href="<?php
        echo htmlspecialchars($settings['facebook']);
?>" target="_blank" class="btn btn-primary">
							<strong><i class="fab fa-facebook-square"></i>&nbsp; Facebook</strong></a>
<?php
    }
    if ($settings['instagram'] != '') {
?>
						<a href="<?php
        echo htmlspecialchars($settings['instagram']);
?>" target="_blank" class="btn btn-warning">
							<strong><i class="fab fa-instagram"></i>&nbsp; Instagram</strong></a>
<?php
    }
    if ($settings['twitter'] != '') {
?>
						<a href="<?php
        echo htmlspecialchars($settings['twitter']);
?>" target="_blank" class="btn btn-info">
							<strong><i class="fab fa-twitter-square"></i>&nbsp; Twitter</strong></a>
<?php
    }
    if ($settings['youtube'] != '') {
?>	
						<a href="<?php
        echo htmlspecialchars($settings['youtube']);
?>" target="_blank" class="btn btn-danger">
							<strong><i class="fab fa-youtube-square"></i>&nbsp; YouTube</strong></a>
<?php
    }
	if ($settings['linkedin'] != '') {
?>	
						<a href="<?php
        echo htmlspecialchars($settings['linkedin']);
?>" target="_blank" class="btn btn-primary">
							<strong><i class.="fab fa-linkedin"></i>&nbsp; LinkedIn</strong></a>
<?php
    }
?>    
					</div>
					<div class="scroll-btn"><div class="scroll-btn-arrow"></div></div>
			</div>
		</div>
	</footer>
</body>

</html>
<?php
}
?>