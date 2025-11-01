<?php
// MODIFICATION : Inclure core.php pour avoir accès aux fonctions et à la session
include '../core.php'; 
// session_start() est déjà dans core.php

if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    
    // Use prepared statement for session check
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username=? AND (role='Admin' OR role='Editor')");
    mysqli_stmt_bind_param($stmt, "s", $uname);
    mysqli_stmt_execute($stmt);
    $suser = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($suser);
    mysqli_stmt_close($stmt);

    if ($count <= 0) {
        echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '" />';
        exit;
    }
    $user = mysqli_fetch_assoc($suser);
} else {
    echo '<meta http-equiv="refresh" content="0; url=../login" />';
    exit;
}

// --- NOUVEL AJOUT : Validation CSRF pour les actions GET ---
$csrf_token = $_SESSION['csrf_token'];
if (isset($_GET['delete-id']) || isset($_GET['up-id']) || isset($_GET['down-id']) || isset($_GET['delete_bgrimg']) || isset($_GET['unsubscribe']) || isset($_GET['approve-comment']) || isset($_GET['delete-comment'])) {
    validate_csrf_token_get();
}
// --- FIN AJOUT ---


if (basename($_SERVER['SCRIPT_NAME']) != 'add_post.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'posts.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'add_page.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'pages.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'add_widget.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'widgets.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'add_image.php' 
 && basename($_SERVER['SCRIPT_NAME']) != 'gallery.php'
 && basename($_SERVER['SCRIPT_NAME']) != 'settings.php'
 && basename($_SERVER['SCRIPT_NAME']) != 'newsletter.php') {
}

if ($user['role'] == "Editor" && 
		(
		 basename($_SERVER['SCRIPT_NAME']) != 'dashboard.php' &&
		 basename($_SERVER['SCRIPT_NAME']) != 'add_post.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'posts.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'add_image.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'gallery.php' && 
		 basename($_SERVER['SCRIPT_NAME']) != 'upload_file.php' &&
		 basename($_SERVER['SCRIPT_NAME']) != 'files.php'
		)
	) {
    echo '<meta http-equiv="refresh" content="0; url=dashboard.php" />';
    exit;
}

function byte_convert($size)
{
    if ($size < 1024)
        return $size . ' Byte';
    if ($size < 1048576)
        return sprintf("%4.2f KB", $size / 1024);
    if ($size < 1073741824)
        return sprintf("%4.2f MB", $size / 1048576);
    if ($size < 1099511627776)
        return sprintf("%4.2f GB", $size / 1073741824);
    else
        return sprintf("%4.2f TB", $size / 1073741824);
}

function generateSeoURL($string, $random_numbers = 1, $wordLimit = 8) { 
    $separator = '-'; 
     
    if($wordLimit != 0){ 
        $wordArr = explode(' ', $string); 
        $string = implode(' ', array_slice($wordArr, 0, $wordLimit)); 
    } 
 
    $quoteSeparator = preg_quote($separator, '#'); 
 
    $trans = array( 
        '&.+?;'                 => '', 
        '[^\w\d _-]'            => '', 
        '\s+'                   => $separator, 
        '('.$quoteSeparator.')+'=> $separator 
    ); 
 
    $string = strip_tags($string); 
    foreach ($trans as $key => $val){ 
        $string = preg_replace('#'.$key.'#iu', $val, $string); 
    } 
 
    $string = strtolower($string); 
	if ($random_numbers == 1) {
		$string = $string . '-' . rand(10000, 99999); 
	}
 
    return trim(trim($string, $separator)); 
}

// Variable pour la page active (utilisée dans la sidebar)
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>phpBlog - Admin Panel</title>
    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
    <meta name="author" content="Antonov_WEB" />
    <link rel="shortcut icon" href="../assets/img/favicon.png" />

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/adminlte/dist/css/adminlte.min.css">
    
    <link rel="stylesheet" href="assets/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    
    <link rel="stylesheet" href="assets/adminlte/plugins/summernote/summernote-bs4.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
    
    <script src="assets/adminlte/plugins/jquery/jquery.min.js"></script>

    <style>
        /* Correction pour que la table s'affiche correctement */
        .dataTables_wrapper .row:first-child {
            padding-top: 0.85em;
        }
        .dashboard-member-activity-avatar {
          width: 64px;
          height: 64px;
          border-radius: 50%;
          object-fit: cover;
        }
        /* Style pour Tagify */
        .tagify{
            --tag-bg: #007bff;
            --tag-text-color: #ffffff;
            border: 1px solid #ced4da;
        }
        .tagify__input{
            font-size: 1rem;
            line-height: 1.5;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo $settings['site_url']; ?>" class="nav-link" target="_blank"><i class="fas fa-eye"></i> Visit Site</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="../logout" role="button">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="dashboard.php" class="brand-link">
            <i class="fas fa-toolbox brand-image img-circle elevation-3" style="opacity: .8; padding-left: 10px; padding-top: 10px;"></i>
            <span class="brand-text font-weight-light">phpBlog Admin</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="../<?php echo htmlspecialchars($user['avatar']); ?>" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="../profile" target="_blank" class="d-block"><?php echo htmlspecialchars($user['username']); ?></a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php if ($current_page == 'dashboard.php') echo 'active'; ?>">
                            <i class="nav-icon fas fa-columns"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    
                    <?php if ($user['role'] == "Admin"): ?>
                    <li class="nav-header">ADMINISTRATION</li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?php if ($current_page == 'settings.php') echo 'active'; ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="menu_editor.php" class="nav-link <?php if (in_array($current_page, ['menu_editor.php', 'add_menu.php'])) echo 'active'; ?>">
                            <i class="nav-icon fas fa-bars"></i>
                            <p>Menu</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="messages.php" class="nav-link <?php if (in_array($current_page, ['messages.php', 'read_message.php'])) echo 'active'; ?>">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Messages</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link <?php if (in_array($current_page, ['users.php', 'add_user.php'])) echo 'active'; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="newsletter.php" class="nav-link <?php if ($current_page == 'newsletter.php') echo 'active'; ?>">
                            <i class="nav-icon far fa-envelope"></i>
                            <p>Newsletter</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-header">CONTENT</li>
                    
                    <?php
                    // Section POSTS (Treeview)
                    $posts_pages = ['add_post.php', 'posts.php', 'categories.php', 'add_category.php', 'comments.php'];
                    $is_posts_open = in_array($current_page, $posts_pages);
                    ?>
                    <li class="nav-item <?php if ($is_posts_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_posts_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-list"></i>
                            <p>
                                Posts
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_post.php" class="nav-link <?php if ($current_page == 'add_post.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Post</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="posts.php" class="nav-link <?php if ($current_page == 'posts.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Posts</p>
                                </a>
                            </li>
                            <?php if ($user['role'] == "Admin"): ?>
                            <li class="nav-item">
                                <a href="categories.php" class="nav-link <?php if (in_array($current_page, ['categories.php', 'add_category.php'])) echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Categories</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="comments.php" class="nav-link <?php if ($current_page == 'comments.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Comments</p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <?php if ($user['role'] == "Admin"): ?>
                    <?php
                    // Section PAGES (Treeview)
                    $pages_pages = ['add_page.php', 'pages.php'];
                    $is_pages_open = in_array($current_page, $pages_pages);
                    ?>
                    <li class="nav-item <?php if ($is_pages_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_pages_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>
                                Pages
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_page.php" class="nav-link <?php if ($current_page == 'add_page.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Page</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="pages.php" class="nav-link <?php if ($current_page == 'pages.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Pages</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <?php
                    // Section GALLERY (Treeview)
                    $gallery_pages = ['add_image.php', 'gallery.php', 'albums.php', 'add_album.php'];
                    $is_gallery_open = in_array($current_page, $gallery_pages);
                    ?>
                    <li class="nav-item <?php if ($is_gallery_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_gallery_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-images"></i>
                            <p>
                                Gallery
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_image.php" class="nav-link <?php if ($current_page == 'add_image.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Image</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="gallery.php" class="nav-link <?php if ($current_page == 'gallery.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Images</p>
                                </a>
                            </li>
                            <?php if ($user['role'] == "Admin"): ?>
                            <li class="nav-item">
                                <a href="albums.php" class="nav-link <?php if (in_array($current_page, ['albums.php', 'add_album.php'])) echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Albums</p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <?php if ($user['role'] == "Admin"): ?>
                    <?php
                    // Section WIDGETS (Treeview)
                    $widgets_pages = ['add_widget.php', 'widgets.php'];
                    $is_widgets_open = in_array($current_page, $widgets_pages);
                    ?>
                    <li class="nav-item <?php if ($is_widgets_open) echo 'menu-is-opening menu-open'; ?>">
                        <a href="#" class="nav-link <?php if ($is_widgets_open) echo 'active'; ?>">
                            <i class="nav-icon fas fa-archive"></i>
                            <p>
                                Widgets
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="add_widget.php" class="nav-link <?php if ($current_page == 'add_widget.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Widget</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="widgets.php" class="nav-link <?php if ($current_page == 'widgets.php') echo 'active'; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Widgets</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a href="files.php" class="nav-link <?php if (in_array($current_page, ['files.php', 'upload_file.php'])) echo 'active'; ?>">
                            <i class="nav-icon fas fa-folder-open"></i>
                            <p>Files</p>
                        </a>
                    </li>
                    
                </ul>
            </nav>
            </div>
        </aside>

    <div class="content-wrapper">