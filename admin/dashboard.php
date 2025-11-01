<?php
include "header.php";

// --- LOGIQUE DE MODÉRATION RAPIDE ---
if ($user['role'] == "Admin") {
    
    // Gérer l'approbation
    if (isset($_GET['approve-comment'])) {
        validate_csrf_token_get(); // Valider le token
        $comment_id = (int)$_GET['approve-comment'];
        
        $stmt_approve = mysqli_prepare($connect, "UPDATE `comments` SET approved='Yes' WHERE id=?");
        mysqli_stmt_bind_param($stmt_approve, "i", $comment_id);
        mysqli_stmt_execute($stmt_approve);
        mysqli_stmt_close($stmt_approve);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">'; 
        exit;
    }
    
    // Gérer la suppression
    if (isset($_GET['delete-comment'])) {
        validate_csrf_token_get(); // Valider le token
        $comment_id = (int)$_GET['delete-comment'];
        
        $stmt_delete = mysqli_prepare($connect, "DELETE FROM `comments` WHERE id=?");
        mysqli_stmt_bind_param($stmt_delete, "i", $comment_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php">';
        exit;
    }
}
// --- FIN DE LA LOGIQUE ---


// Variable de version (comme dans core.php)
$phpblog_version = "2.4+"; 

// --- REQUÊTES POUR LES STATISTIQUES EXPLOITABLES ---

// 1. Cartes de statistiques
$query_posts_published = mysqli_query($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Yes'");
$count_posts_published = mysqli_fetch_assoc($query_posts_published)['count'];

$query_posts_drafts = mysqli_query($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Draft'");
$count_posts_drafts = mysqli_fetch_assoc($query_posts_drafts)['count'];

$count_comments_pending = 0;
if ($user['role'] == "Admin") {
    $query_comments_pending = mysqli_query($connect, "SELECT COUNT(id) AS count FROM comments WHERE approved='No'");
    $count_comments_pending = mysqli_fetch_assoc($query_comments_pending)['count'];
}

$count_messages_unread = 0;
if ($user['role'] == "Admin") {
    $query_messages_unread = mysqli_query($connect, "SELECT COUNT(id) AS count FROM messages WHERE viewed = 'No'");
    $count_messages_unread = mysqli_fetch_assoc($query_messages_unread)['count'];
}

$query_total_users = mysqli_query($connect, "SELECT COUNT(id) AS count FROM users");
$count_total_users = mysqli_fetch_assoc($query_total_users)['count'];

// 2. Graphique Top 5 Articles
$query_top_posts = mysqli_query($connect, "SELECT title, views FROM posts WHERE active='Yes' AND views > 0 ORDER BY views DESC LIMIT 5");
$chart_top_posts_titles = [];
$chart_top_posts_views = [];
while ($row = mysqli_fetch_assoc($query_top_posts)) {
    $chart_top_posts_titles[] = short_text($row['title'], 30); 
    $chart_top_posts_views[] = $row['views'];
}
$chart_top_posts_labels_json = json_encode($chart_top_posts_titles);
$chart_top_posts_data_json = json_encode($chart_top_posts_views);

// 3. Graphique des publications par mois (12 derniers mois)
$query_posts_per_month = mysqli_query($connect, "
    SELECT 
        DATE_FORMAT(publish_at, '%Y-%m') AS post_month,
        COUNT(id) AS post_count
    FROM posts
    WHERE publish_at > DATE_SUB(NOW(), INTERVAL 12 MONTH) AND active = 'Yes'
    GROUP BY post_month
    ORDER BY post_month ASC
    LIMIT 12
");
$chart_months_labels = [];
$chart_months_data = [];
while ($row_month = mysqli_fetch_assoc($query_posts_per_month)) {
    $chart_months_labels[] = $row_month['post_month'];
    $chart_months_data[] = $row_month['post_count'];
}
$chart_months_labels_json = json_encode($chart_months_labels);
$chart_months_data_json = json_encode($chart_months_data);

// 4. Graphique des articles par catégorie
$query_cats = mysqli_query($connect, "
    SELECT c.category, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.active = 'Yes'
    GROUP BY c.id
    HAVING post_count > 0
    ORDER BY post_count DESC
");
$chart_cat_labels = [];
$chart_cat_data = [];
while ($row_cat = mysqli_fetch_assoc($query_cats)) {
    $chart_cat_labels[] = $row_cat['category'];
    $chart_cat_data[] = $row_cat['post_count'];
}
$chart_cat_labels_json = json_encode($chart_cat_labels);
$chart_cat_data_json = json_encode($chart_cat_data);

// 5. Informations Système
$php_version = phpversion();
$db_version_query = mysqli_query($connect, "SELECT VERSION() as version");
$db_version = mysqli_fetch_assoc($db_version_query)['version'];
$max_upload = ini_get('upload_max_filesize');

// 6. Widget "Contenu en un coup d'œil"
$query_pages_count = mysqli_query($connect, "SELECT COUNT(id) AS count FROM pages");
$count_pages = mysqli_fetch_assoc($query_pages_count)['count'];

$query_comments_total = mysqli_query($connect, "SELECT COUNT(id) AS count FROM comments");
$count_comments_total = mysqli_fetch_assoc($query_comments_total)['count'];

$query_categories_count = mysqli_query($connect, "SELECT COUNT(id) AS count FROM categories");
$count_categories = mysqli_fetch_assoc($query_categories_count)['count'];

$query_tags_count = mysqli_query($connect, "SELECT COUNT(id) AS count FROM tags");
$count_tags = mysqli_fetch_assoc($query_tags_count)['count'];

// 7. "Derniers utilisateurs"
$query_latest_users = mysqli_query($connect, "SELECT id, username, avatar FROM users ORDER BY id DESC LIMIT 5");
// --- FIN DES REQUÊTES ---

?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div><div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div></div></div></div>
<section class="content">
    <div class="container-fluid">
        
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Shortcuts</h3>
            </div>
            <div class="card-body text-center">
                <a href="add_post.php" class="btn btn-app bg-primary"><i class="fas fa-edit"></i> Write Post</a>
                <?php if ($user['role'] == "Admin"): ?>
                <a href="settings.php" class="btn btn-app bg-secondary"><i class="fas fa-cogs"></i> Settings</a>
                <a href="messages.php" class="btn btn-app bg-info"><i class="fas fa-envelope"></i> Messages</a>
                <a href="menu_editor.php" class="btn btn-app bg-secondary"><i class="fas fa-bars"></i> Menu</a>
                <a href="add_page.php" class="btn btn-app bg-primary"><i class="fas fa-file-alt"></i> Add Page</a>
                <?php endif; ?>
                <a href="add_image.php" class="btn btn-app bg-success"><i class="fas fa-camera-retro"></i> Add Image</a>
                <?php if ($user['role'] == "Admin"): ?>
                <a href="widgets.php" class="btn btn-app bg-secondary"><i class="fas fa-archive"></i> Widgets</a>
                <a href="add_user.php" class="btn btn-app bg-warning"><i class="fas fa-user-plus"></i> Add User</a>
                <?php endif; ?>
                <a href="upload_file.php" class="btn btn-app bg-success"><i class="fas fa-upload"></i> Upload File</a>
                <a href="<?php echo $settings['site_url']; ?>" class="btn btn-app bg-info"><i class="fas fa-eye"></i> Visit Site</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Articles Publiés</span>
                        <span class="info-box-number"><?php echo $count_posts_published; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-pencil-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ébauches</span>
                        <span class="info-box-number"><?php echo $count_posts_drafts; ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($user['role'] == "Admin"): ?>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-comments"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Commentaires en attente</span>
                        <span class="info-box-number"><?php echo $count_comments_pending; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-envelope"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Messages non lus</span>
                        <span class="info-box-number"><?php echo $count_messages_unread; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Top 5 Articles les plus vus</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($chart_top_posts_titles)): ?>
                            <div class="alert alert-info">Pas encore assez de données pour afficher un graphique.</div>
                        <?php else: ?>
                            <canvas id="popularPostsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line"></i> Publications (12 derniers mois)</h3>
                    </div>
                    <div class="card-body">
                         <?php if (empty($chart_months_labels)): ?>
                            <div class="alert alert-info">Pas encore de données pour ce graphique.</div>
                        <?php else: ?>
                            <canvas id="postsPerMonthChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Répartition des catégories</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($chart_cat_labels)): ?>
                            <div class="alert alert-info">Aucun article n'a encore été classé.</div>
                        <?php else: ?>
                            <canvas id="postsPerCategoryChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-database"></i> Contenu en un coup d'œil</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <a href="posts.php"><i class="fas fa-file-alt"></i> Articles</a>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count_posts_published; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <a href="pages.php"><i class="fas fa-file-lines"></i> Pages</a>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count_pages; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <a href="categories.php"><i class="fas fa-list-ol"></i> Catégories</a>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count_categories; ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <a href="comments.php"><i class="fas fa-comments"></i> Commentaires</a>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count_comments_total; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <a href="posts.php"><i class="fas fa-tags"></i> Tags</a>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count_tags; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <a href="users.php"><i class="fas fa-users"></i> Utilisateurs</a>
                                        <span class="badge bg-primary rounded-pill"><?php echo $count_total_users; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php if ($user['role'] == "Admin"): ?>
                        <hr class="my-2">
                        <p class="card-text mb-0">
                            <small class="text-muted">
                                Vous avez <span class="badge bg-warning text-dark"><?php echo $count_posts_drafts; ?></span> brouillon(s) et 
                                <a href="#moderation"><span class="badge bg-info"><?php echo $count_comments_pending; ?></span> commentaire(s) en attente</a>.
                            </small>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?php if ($user['role'] == "Admin"): ?>
                <div class="card card-outline card-danger" id="moderation">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-gavel"></i> Modération Rapide (Commentaires)</h3>
                    </div>
                    
                    <?php
                    // NOUVELLE REQUÊTE : Cibler uniquement les commentaires en attente
                    $query_pending = mysqli_query($connect, "
                        SELECT c.*, p.title AS post_title, p.slug AS post_slug,
                               u.username AS user_username, u.avatar AS user_avatar
                        FROM `comments` c
                        JOIN `posts` p ON c.post_id = p.id
                        LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
                        WHERE c.approved = 'No'
                        ORDER BY c.id DESC
                        LIMIT 10
                    ");
                    $cmnts_pending = mysqli_num_rows($query_pending);
                    
                    if ($cmnts_pending == "0"): 
                    ?>
                        <div class="card-body"> 
                            <div class="alert alert-default text-center m-0 p-3">Aucun commentaire en attente.</div>
                        </div>
                    <?php else: ?>
                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                            <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php
                            while ($row = mysqli_fetch_assoc($query_pending)) {
                                $post_title = $row['post_title'] ?: 'N/A';
                                $avatar = 'assets/img/avatar.png'; 
                                $author_name = 'Guest'; 
                                if ($row['guest'] == 'Yes') {
                                    $author_name = $row['user_id'];
                                } else if ($row['user_username']) {
                                    $avatar = $row['user_avatar'];
                                    $author_name = $row['user_username'];
                                }
                            ?>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="../<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="img-size-50 img-circle">
                                    </div>
                                    <div class="product-info">
                                        <span class="product-title">
                                            <?php echo htmlspecialchars($author_name); ?>
                                            <?php if ($row['guest'] == "Yes") echo '<span class="badge badge-info float-right"><i class="fas fa-user"></i> Guest</span>'; ?>
                                        </span>
                                        <span class="product-description">
                                            Sur: <a href="../post?name=<?php echo htmlspecialchars($row['post_slug']); ?>#comment-<?php echo $row['id']; ?>" target="_blank"><?php echo htmlspecialchars(short_text($post_title, 40)); ?></a>
                                        </span>
                                        <p class="mt-1 mb-1 text-muted"><?php echo htmlspecialchars(short_text(html_entity_decode($row['comment']), 100)); ?></p>
                                        <div>
                                            <a href="?approve-comment=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-success btn-xs"><i class="fas fa-check"></i> Approuver</a>
                                            <a href="?delete-comment=<?php echo $row['id']; ?>&token=<?php echo $csrf_token; ?>" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i> Supprimer</a>
                                            <a href="comments.php?edit-id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-xs"><i class="fas fa-edit"></i> Modifier</a>
                                        </div>
                                    </div>
                                </li>
                            <?php
                            }
                            ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="comments.php" class="uppercase">Voir tous les commentaires</a>
                        </div>
                    <?php endif; // End if ($cmnts_pending > 0) ?>
                </div>
                <?php else: // Si c'est un Éditeur, afficher les commentaires récents ?>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Recent Comments</h3></div>
                    <div class="card-body">
                        <?php
                        $query_editor = mysqli_query($connect, "
                            SELECT c.*, p.title AS post_title,
                                   u.username AS user_username, u.avatar AS user_avatar
                            FROM `comments` c
                            JOIN `posts` p ON c.post_id = p.id
                            LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
                            ORDER BY c.id DESC
                            LIMIT 4
                        ");
                        if (mysqli_num_rows($query_editor) == 0) {
                            echo '<div class="alert alert-info">There are no posted comments.</div>';
                        } else {
                            while ($row = mysqli_fetch_assoc($query_editor)) {
                                $avatar = 'assets/img/avatar.png'; $author_name = 'Guest';
                                if ($row['guest'] == 'Yes') { $author_name = 'Guest'; }
                                else if ($row['user_username']) { $avatar = $row['user_avatar']; $author_name = $row['user_username']; }
                        ?>
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <img src="../<?php echo htmlspecialchars($avatar); ?>" class="dashboard-member-activity-avatar" />
                            </div>
                            <div class="col-md-10">
                                <span class="blue"><strong><?php echo htmlspecialchars($author_name); ?></strong> le <?php echo date($settings['date_format'], strtotime($row['created_at'])); ?></span><br />
                                <?php if ($row['approved'] == "Yes") echo '<strong>Status:</strong> <span class="badge bg-success">Approved</span>'; else echo '<strong>Status:</strong> <span class="badge bg-warning">Pending</span>'; ?>
                                <p><?php echo htmlspecialchars(short_text(html_entity_decode($row['comment']), 100)); ?></p>
                            </div>
                        </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; // Fin du if ($user['role'] == "Admin") ?>
            </div>
            
            <div class="col-md-6">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-server"></i> Informations Système</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Version phpBlog
                                <span class="badge bg-primary rounded-pill"><?php echo $phpblog_version; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Thème actif
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($settings['theme']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Version PHP
                                <span class="badge bg-info"><?php echo $php_version; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Version MySQL
                                <span class="badge bg-info"><?php echo short_text($db_version, 15); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Max Upload Size
                                <span class="badge bg-warning text-dark"><?php echo $max_upload; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($user['role'] == "Admin"): ?>
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-clock"></i> Derniers utilisateurs inscrits</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                        <?php
                        if (mysqli_num_rows($query_latest_users) == 0) {
                            echo '<li class="list-group-item">Aucun utilisateur trouvé.</li>';
                        } else {
                            while ($row_user = mysqli_fetch_assoc($query_latest_users)) {
                        ?>
                            <li class="item">
                                <div class="product-img">
                                    <img src="../<?php echo htmlspecialchars($row_user['avatar']); ?>" alt="Avatar" class="img-size-50 img-circle">
                                </div>
                                <div class="product-info">
                                    <a href="users.php?edit-id=<?php echo $row_user['id']; ?>" class="product-title">
                                        <?php echo htmlspecialchars($row_user['username']); ?>
                                    </a>
                                    <span class="product-description">
                                        <a href="users.php?edit-id=<?php echo $row_user['id']; ?>" class="btn btn-secondary btn-xs float-right">
                                            <i class="fa fa-edit"></i> Gérer
                                        </a>
                                    </span>
                                </div>
                            </li>
                        <?php
                            }
                        }
                        ?>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <a href="users.php">Voir tous les utilisateurs</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div></section>
<script>
document.addEventListener('DOMContentLoaded', (event) => {
    
    // --- GRAPHIQUE BARRES (Top 5) ---
    const ctxBar = document.getElementById('popularPostsChart');
    if (ctxBar) {
        const postLabels = <?php echo $chart_top_posts_labels_json; ?>;
        const postData = <?php echo $chart_top_posts_data_json; ?>;

        new Chart(ctxBar.getContext('2d'), {
            type: 'bar',
            data: {
                labels: postLabels,
                datasets: [{
                    label: 'Vues',
                    data: postData,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)', // success
                        'rgba(0, 123, 255, 0.7)', // primary
                        'rgba(23, 162, 184, 0.7)', // info
                        'rgba(255, 193, 7, 0.7)',  // warning
                        'rgba(220, 53, 69, 0.7)'   // danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- GRAPHIQUE LIGNES (Publications par mois) ---
    const ctxLine = document.getElementById('postsPerMonthChart');
    if (ctxLine) {
        const monthLabels = <?php echo $chart_months_labels_json; ?>;
        const monthData = <?php echo $chart_months_data_json; ?>;
        
        new Chart(ctxLine.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Publications',
                    data: monthData,
                    fill: true,
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- GRAPHIQUE PIE (Catégories) ---
    const ctxPie = document.getElementById('postsPerCategoryChart');
    if (ctxPie) {
        const catLabels = <?php echo $chart_cat_labels_json; ?>;
        const catData = <?php echo $chart_cat_data_json; ?>;
        
        new Chart(ctxPie.getContext('2d'), {
            type: 'pie',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Articles',
                    data: catData,
                    backgroundColor: [ 
                        'rgba(0, 123, 255, 0.7)', // primary
                        'rgba(40, 167, 69, 0.7)',  // success
                        'rgba(255, 193, 7, 0.7)',  // warning
                        'rgba(220, 53, 69, 0.7)',  // danger
                        'rgba(23, 162, 184, 0.7)', // info
                        'rgba(108, 117, 125, 0.7)' // secondary
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom', 
                    }
                }
            }
        });
    }

});
</script>

<?php
include "footer.php";
?>