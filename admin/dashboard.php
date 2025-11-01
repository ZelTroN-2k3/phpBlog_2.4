<?php
include "header.php";

// Variable de version (comme dans core.php)
$phpblog_version = "2.4+"; 

// --- REQUÊTES POUR LES STATISTIQUES EXPLOITABLES ---

// 1. Articles
$query_posts_published = mysqli_query($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Yes'");
$count_posts_published = mysqli_fetch_assoc($query_posts_published)['count'];

$query_posts_drafts = mysqli_query($connect, "SELECT COUNT(id) AS count FROM posts WHERE active='Draft'");
$count_posts_drafts = mysqli_fetch_assoc($query_posts_drafts)['count'];

// 2. Commentaires en attente (Seulement pour l'Admin)
$count_comments_pending = 0;
if ($user['role'] == "Admin") {
    $query_comments_pending = mysqli_query($connect, "SELECT COUNT(id) AS count FROM comments WHERE approved='No'");
    $count_comments_pending = mysqli_fetch_assoc($query_comments_pending)['count'];
}

// 3. Messages non lus (Seulement pour l'Admin)
$count_messages_unread = 0;
if ($user['role'] == "Admin") {
    $query_messages_unread = mysqli_query($connect, "SELECT COUNT(id) AS count FROM messages WHERE viewed = 'No'");
    $count_messages_unread = mysqli_fetch_assoc($query_messages_unread)['count'];
}

// 4. Total des utilisateurs (POUR LE NOUVEAU WIDGET)
$query_total_users = mysqli_query($connect, "SELECT COUNT(id) AS count FROM users");
$count_total_users = mysqli_fetch_assoc($query_total_users)['count'];

// --- REQUÊTE POUR LE GRAPHIQUE ---
$query_top_posts = mysqli_query($connect, "SELECT title, views FROM posts WHERE active='Yes' AND views > 0 ORDER BY views DESC LIMIT 5");

$post_titles = [];
$post_views = [];

while ($row = mysqli_fetch_assoc($query_top_posts)) {
    // Nous utilisons short_text pour éviter que les titres trop longs ne cassent le graphique
    $post_titles[] = short_text($row['title'], 30); 
    $post_views[] = $row['views'];
}

// Convertir les données PHP en JSON pour JavaScript
$chart_labels_json = json_encode($post_titles);
$chart_data_json = json_encode($post_views);
// --- FIN DES REQUÊTES ---

?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h3 class="h3"><i class="fas fa-columns"></i> Dashboard</h3>
    </div>
	  
    <div class="card mb-3">
        <h6 class="card-header">Shortcuts</h6>         
        <div class="card-body">
            <center>
            <a href="add_post.php" class="btn btn-primary"><i class="fa fa-edit"></i><br />Write Post</a>
<?php
if ($user['role'] == "Admin") {
?>
            <a href="settings.php" class="btn btn-primary"><i class="fa fa-cogs"></i><br /> Settings</a>
            <a href="messages.php" class="btn btn-primary"><i class="fa fa-envelope"></i><br /> Messages</a>
            <a href="menu_editor.php" class="btn btn-primary"><i class="fa fa-bars"></i><br /> Menu Editor</a>
            <a href="add_page.php" class="btn btn-primary"><i class="fa fa-file-alt"></i><br /> Add Page</a>
<?php
}
?>
            <a href="add_image.php" class="btn btn-primary"><i class="fa fa-camera-retro"></i><br /> Add Image</a>
<?php
if ($user['role'] == "Admin") {
?>
            <a href="widgets.php" class="btn btn-primary"><i class="fa fa-archive"></i><br /> Widgets</a>
            <a href="add_user.php" class="btn btn-primary"><i class="fa fa-user-plus"></i><br /> Add User</a>
<?php
}
?>
            <a href="upload_file.php" class="btn btn-primary"><i class="fa fa-upload"></i><br /> Upload File</a>
            <a href="<?php echo $settings['site_url']; ?>" class="btn btn-primary"><i class="fa fa-eye"></i><br /> Visit Site</a>
            </center>
        </div>
    </div>
	  
    <div class="row mt-3">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="display-4"><?php echo $count_posts_published; ?></div>
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                    <h5 class="card-title mt-2">Articles Publiés</h5>
                </div>
                <a href="posts.php" class="card-footer text-white text-decoration-none">
                    <span>Voir la liste</span>
                    <i class="fas fa-arrow-circle-right float-end mt-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="display-4"><?php echo $count_posts_drafts; ?></div>
                        <i class="fas fa-pencil-alt fa-3x"></i>
                    </div>
                    <h5 class="card-title mt-2">Ébauches</h5>
                </div>
                <a href="posts.php" class="card-footer text-white text-decoration-none">
                    <span>Voir la liste</span>
                    <i class="fas fa-arrow-circle-right float-end mt-1"></i>
                </a>
            </div>
        </div>

<?php
// N'afficher ces cartes que pour l'Admin
if ($user['role'] == "Admin") :
?>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="display-4"><?php echo $count_comments_pending; ?></div>
                        <i class="fas fa-comments fa-3x"></i>
                    </div>
                    <h5 class="card-title mt-2">Commentaires en attente</h5>
                </div>
                <a href="comments.php" class="card-footer text-white text-decoration-none">
                    <span>Modérer les commentaires</span>
                    <i class="fas fa-arrow-circle-right float-end mt-1"></i>
                </a>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="display-4"><?php echo $count_messages_unread; ?></div>
                        <i class="fas fa-envelope fa-3x"></i>
                    </div>
                    <h5 class="card-title mt-2">Messages non lus</h5>
                </div>
                <a href="messages.php" class="card-footer text-white text-decoration-none">
                    <span>Lire les messages</span>
                    <i class="fas fa-arrow-circle-right float-end mt-1"></i>
                </a>
            </div>
        </div>
<?php
endif;
?>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6 mb-3">
             <div class="card h-100">
                <h6 class="card-header"><i class="fas fa-chart-bar"></i> Top 5 Articles les plus vus</h6>
                <div class="card-body">
                    <?php if (empty($post_titles)): ?>
                        <div class="alert alert-info">Pas encore assez de données pour afficher un graphique. Publiez et partagez vos articles !</div>
                    <?php else: ?>
                        <canvas id="popularPostsChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            
            <div class="card mb-3">
                <h6 class="card-header"><i class="fas fa-tachometer-alt"></i> Aperçu rapide</h6>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Version phpBlog
                            <span class="badge bg-primary rounded-pill"><?php echo $phpblog_version; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Utilisateurs enregistrés
                            <span class="badge bg-primary rounded-pill"><?php echo $count_total_users; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Thème actif
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($settings['theme']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card">
              <h6 class="card-header">Recent Comments</h6>
              <div class="card-container-toggle">
                  <div class="card-body">
                    <div class="row">
<?php
// La requête pour les commentaires reste inchangée
$query = mysqli_query($connect, "
    SELECT 
        c.*,
        p.title AS post_title,
        u.username AS user_username, 
        u.avatar AS user_avatar
    FROM `comments` c
    JOIN `posts` p ON c.post_id = p.id
    LEFT JOIN `users` u ON c.user_id = u.id AND c.guest = 'No'
    ORDER BY 
        CASE WHEN c.approved = 'No' THEN 1 ELSE 2 END,
        c.id DESC
    LIMIT 4
");

$cmnts = mysqli_num_rows($query);
if ($cmnts == "0") {
    echo '<div class="alert alert-info">There are no posted comments.</div>';
} else {
    while ($row = mysqli_fetch_assoc($query)) {
        
        $post_title = $row['post_title'] ? $row['post_title'] : 'N/A';
        $avatar = 'assets/img/avatar.png'; 
        $author_name = 'Guest'; 

        if ($row['guest'] == 'Yes') {
            $author_name = 'Guest';
        } else if ($row['user_username']) {
            $avatar = $row['user_avatar'];
            $author_name = $row['user_username'];
        }
        
        echo '
            <div class="col-md-2">
                <img src="../' . htmlspecialchars($avatar) . '" class="dashboard-member-activity-avatar" />
            </div>
            <div class="col-md-10">
                <a href="comments.php?edit-id=' . $row['id'] . '">
                    <span class="blue">Comment by <strong>' . htmlspecialchars($author_name) . ' </strong> on <strong>' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</strong></span>
                </a><br />
';
        if ($row['approved'] == "Yes") {
            echo '<strong>Status:</strong> <span class="badge bg-success">Approved</span> ';
        } else {
            echo '<strong>Status:</strong> <span class="badge bg-warning">Pending</span> ';
        }
        if ($row['guest'] == "Yes") {
            echo '<span class="badge bg-info"><i class="fas fa-user"></i> Guest</span> ';
        }
        echo '
                <p>' . htmlspecialchars(short_text(html_entity_decode($row['comment']), 100)) . '</p>
            </div>
';
    }
}
?>
                    </div>
                  </div>
              </div>
            </div>
         </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', (event) => {
    // S'assurer que l'élément canvas existe avant de créer le graphique
    const ctx = document.getElementById('popularPostsChart');
    if (ctx) {
        // Récupérer les données JSON intégrées depuis PHP
        const postLabels = <?php echo $chart_labels_json; ?>;
        const postData = <?php echo $chart_data_json; ?>;

        new Chart(ctx, {
            type: 'bar', // Type de graphique
            data: {
                labels: postLabels, // Les titres des articles
                datasets: [{
                    label: 'Vues',
                    data: postData, // Le nombre de vues
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.7)', // Vert (Succès BS)
                        'rgba(13, 110, 253, 0.7)', // Bleu (Primary BS)
                        'rgba(13, 202, 240, 0.7)', // Cyan (Info BS)
                        'rgba(255, 193, 7, 0.7)',  // Jaune (Warning BS)
                        'rgba(220, 53, 69, 0.7)'   // Rouge (Danger BS)
                    ],
                    borderColor: [
                        'rgba(25, 135, 84, 1)',
                        'rgba(13, 110, 253, 1)',
                        'rgba(13, 202, 240, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // S'assurer que les vues sont des nombres entiers
                            precision: 0 
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Cacher la légende (inutile pour un seul jeu de données)
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