<?php
// phpBlog version
$phpblog_version = "2.4";

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
$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

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

function post_author($author_id)
{
    // Rendre $connect accessible (meilleure pratique : passer $connect en paramètre)
    global $connect; 
    
    $author = '-';
    
    $stmt = mysqli_prepare($connect, "SELECT username FROM `users` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $author_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($rowauthp = mysqli_fetch_assoc($result)) {
        $author   = $rowauthp['username'];
    }
    mysqli_stmt_close($stmt);
 
    return $author;
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
            if ($current_page == 'blog.php' || $current_page == 'category.php') {
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
						<a href="#" class="nav-link link-dark dropdown-toggle" data-bs-toggle="dropdown">
							<i class="fas fa-user"></i> Profile <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
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
$run   = mysqli_query($connect, "SELECT * FROM `posts` WHERE active='Yes' ORDER BY id DESC LIMIT 6");
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
    // Requête simple sans variable externe
    $runq = mysqli_query($connect, "SELECT * FROM `categories` ORDER BY category ASC");
    while ($row = mysqli_fetch_assoc($runq)) {
        $category_id = (int)$row['id']; // Assurer que c'est un entier
        
        // Requête préparée pour compter les posts
        $stmt_post_count = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM `posts` WHERE category_id = ? AND active = 'Yes'");
        mysqli_stmt_bind_param($stmt_post_count, "i", $category_id);
        mysqli_stmt_execute($stmt_post_count);
        $postc_result = mysqli_stmt_get_result($stmt_post_count);
        $postc_row = mysqli_fetch_assoc($postc_result);
		$posts_count = $postc_row['count'];
        mysqli_stmt_close($stmt_post_count);

        // Utiliser htmlspecialchars
        echo '
							<a href="category?name=' . htmlspecialchars($row['slug']) . '">
								<li class="list-group-item d-flex justify-content-between align-items-center">
									' . htmlspecialchars($row['category']) . '
									<span class="badge bg-secondary rounded-pill">' . $posts_count . '</span>
								</li>
							</a>
		';
    }
?>
						</ul>
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
    $run   = mysqli_query($connect, "SELECT * FROM `posts` WHERE active='Yes' ORDER BY views DESC, id DESC LIMIT 4");
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
											<i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '<br />
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
    // Requête simple sans variable externe
    $query = mysqli_query($connect, "SELECT * FROM `comments` WHERE approved='Yes' ORDER BY `id` DESC LIMIT 4");
    $cmnts = mysqli_num_rows($query);
    if ($cmnts == "0") {
        echo "There are no comments";
    } else {
        while ($row = mysqli_fetch_array($query)) {
			
			$badge = '';
			$acuthor_id = $row['user_id']; // ID ou nom de l'invité
            $acuthor_name = '';
			$acavatar = 'assets/img/avatar.png'; // Défaut

            if ($row['guest'] == 'Yes') {
                $acuthor_name = $acuthor_id; // C'est le nom de l'invité
				$badge = ' <span class="badge bg-secondary">Guest</span>';
            } else {
                // Requête préparée pour obtenir les infos utilisateur
                $stmt_comment_author = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
                mysqli_stmt_bind_param($stmt_comment_author, "s", $acuthor_id); // "s" car user_id est VARCHAR
                mysqli_stmt_execute($stmt_comment_author);
                $result_comment_author = mysqli_stmt_get_result($stmt_comment_author);
                
                if (mysqli_num_rows($result_comment_author) > 0) {
                    $rowch = mysqli_fetch_assoc($result_comment_author);
                    $acavatar = $rowch['avatar'];
                    $acuthor_name = $rowch['username'];
                }
                mysqli_stmt_close($stmt_comment_author);
            }
			
            // Requête préparée pour obtenir les infos du post
            $post_id_comment = (int)$row['post_id'];
            $stmt_comment_post = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE active='Yes' AND id=?");
            mysqli_stmt_bind_param($stmt_comment_post, "i", $post_id_comment);
            mysqli_stmt_execute($stmt_comment_post);
            $result_comment_post = mysqli_stmt_get_result($stmt_comment_post);

            while ($row2 = mysqli_fetch_array($result_comment_post)) {
                // Utiliser htmlspecialchars
				echo '
								<div class="mb-2 d-flex flex-start align-items-center bg-light rounded border">
									<a href="post?name=' . htmlspecialchars($row2['slug']) . '#comments" class="ms-2">
										<img class="rounded-circle shadow-1-strong me-2"
										src="' . htmlspecialchars($acavatar) . '" alt="' . htmlspecialchars($acuthor_name) . '" 
										width="60" height="60" />
									</a>
									<div class="mt-1 mb-1 ms-1 me-1">
										<h6 class="text-primary mb-1">
											<a href="post?name=' . htmlspecialchars($row2['slug']) . '#comments">' . htmlspecialchars($acuthor_name) . '</a>
										</h6>
										<p class="text-muted small mb-0">
											on <a href="post?name=' . htmlspecialchars($row2['slug']) . '#comments">' . htmlspecialchars($row2['title']) . '</a><br />
											<i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '
										</p>
									</div>
								</div>
';
            }
            mysqli_stmt_close($stmt_comment_post);
        }
    }
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
							<strong><i class="fab fa-linkedin"></i>&nbsp; LinkedIn</strong></a>
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