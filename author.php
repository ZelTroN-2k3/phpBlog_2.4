<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

// 1. Récupérer le nom de l'auteur depuis l'URL
$username = $_GET['username'] ?? '';
if (empty($username)) {
    echo '<meta http-equiv="refresh" content="0; url=blog.php">';
    exit();
}

// 2. Récupérer les informations de l'auteur
$stmt_author = mysqli_prepare($connect, "SELECT * FROM `users` WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_author, "s", $username);
mysqli_stmt_execute($stmt_author);
$result_author = mysqli_stmt_get_result($stmt_author);

if (mysqli_num_rows($result_author) == 0) {
    // Auteur non trouvé
    echo '<meta http-equiv="refresh" content="0; url=blog.php">';
    exit();
}

$author = mysqli_fetch_assoc($result_author);
$author_id = $author['id'];
$author_name = htmlspecialchars($author['username']);
$author_avatar = htmlspecialchars($author['avatar']);
// nl2br() convertit les sauts de ligne de la biographie en <br>
$author_bio = nl2br(htmlspecialchars($author['bio'] ?? '')); 
mysqli_stmt_close($stmt_author);
?>

<div class="col-md-8 mb-3">

    <div class="card mb-3">
        <div class="row g-0">
            <div class="col-md-3 d-flex justify-content-center align-items-center p-3">
                <img src="<?php echo $author_avatar; ?>" class="img-fluid rounded-circle" alt="<?php echo $author_name; ?>" style="width: 150px; height: 150px; object-fit: cover;">
            </div>
            <div class="col-md-9">
                <div class="card-body">
                    <h4 class="card-title"><?php echo $author_name; ?></h4>
                    <p class="card-text text-muted"><?php echo $author_bio; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="far fa-file-alt"></i> Items from <?php echo $author_name; ?></div>
        <div class="card-body">

<?php
// 5. Logique de pagination (identique à blog.php mais filtrée par author_id)
$postsperpage = 8;
$pageNum = 1;
if (isset($_GET['page'])) {
    $pageNum = (int)$_GET['page'];
}
if (!is_numeric($pageNum) || $pageNum < 1) {
    echo '<meta http-equiv="refresh" content="0; url=blog.php">';
    exit();
}
$rows = ($pageNum - 1) * $postsperpage;

// 6. Compter le total des articles pour la pagination
$stmt_count = mysqli_prepare($connect, "SELECT COUNT(id) AS numrows FROM posts WHERE author_id=? AND active='Yes' AND publish_at <= NOW()");
mysqli_stmt_bind_param($stmt_count, "i", $author_id);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$row_count = mysqli_fetch_assoc($result_count);
$numrows = $row_count['numrows'];
mysqli_stmt_close($stmt_count);

if ($numrows == 0) {
    echo '<div class="alert alert-info">This author has not yet published any articles.</div>';
} else {
    // 7. Récupérer les articles paginés
    $stmt_posts = mysqli_prepare($connect, "SELECT * FROM posts WHERE author_id=? AND active='Yes' AND publish_at <= NOW() ORDER BY id DESC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt_posts, "iii", $author_id, $rows, $postsperpage);
    mysqli_stmt_execute($stmt_posts);
    $run = mysqli_stmt_get_result($stmt_posts);

    while ($row = mysqli_fetch_assoc($run)) {
        
        $image = "";
        if($row['image'] != "") {
            $image = '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="rounded-start" width="100%" height="100%">';
        } else {
            $image = '<svg class="bd-placeholder-img rounded-start" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
            <title>No Image</title><rect width="100%" height="100%" fill="#55595c"/>
            <text x="37%" y="50%" fill="#eceeef" dy=".3em">No Image</text></svg>';
        }
        
        echo '
                        <div class="card shadow-sm mb-3">
                            <div class="row g-0">
								<div class="col-md-4">
									<a href="post.php?name=' . htmlspecialchars($row['slug']) . '">
										'. $image .'
									</a>
								</div>
								<div class="col-md-8">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-center row">
											<div class="col-md-9">
												<a href="post.php?name=' . htmlspecialchars($row['slug']) . '">
													<h5 class="card-title">' . htmlspecialchars($row['title']) . '</h5>
												</a>
											</div>
											<div class="col-md-3">
												<a href="category.php?name=' . post_categoryslug($row['category_id']) . '">
													<span class="badge bg-primary float-end">' . post_category($row['category_id']) . '</span>
												</a>
											</div>
										</div>
										
										<div class="d-flex justify-content-between align-items-center mb-3">
											<small>
												Posté le <b><i><i class="far fa-calendar-alt"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</i></b>
											</small>
                                            <small class="float-end"><i class="fas fa-comments"></i>
												<a href="post.php?name=' . htmlspecialchars($row['slug']) . '#comments" class="blog-comments"><b>' . post_commentscount($row['id']) . '</b></a>
											</small>
										</div>
										
										<p class="card-text">' . short_text(strip_tags(html_entity_decode($row['content'])), 200) . '</p>
									</div>
								</div>
							</div>
						</div>
';
    }
    mysqli_stmt_close($stmt_posts);
    
    // 8. Afficher la pagination
    $maxPage = ceil($numrows / $postsperpage);
    $pagenums = '';
    $safe_username = urlencode($username); // Encoder le nom pour l'URL
    
    echo '<center>';
    
    for ($page = 1; $page <= $maxPage; $page++) {
        if ($page == $pageNum) {
            $pagenums .= "<a href='?username=$safe_username&page=$page' class='btn btn-primary'>$page</a> ";
        } else {
            $pagenums .= "<a href=\"?username=$safe_username&page=$page\" class='btn btn-default'>$page</a> ";
        }
    }
    
    if ($pageNum > 1) {
        $page     = $pageNum - 1;
        $previous = "<a href=\"?username=$safe_username&page=$page\" class='btn btn-default'><i class='fa fa-arrow-left'></i> Previous</a> ";
        $first = "<a href=\"?username=$safe_username&page=1\" class='btn btn-default'>First</a> ";
    } else {
        $previous = ' ';
        $first    = ' ';
    }
    
    if ($pageNum < $maxPage) {
        $page = $pageNum + 1;
        $next = "<a href=\"?username=$safe_username&page=$page\" class='btn btn-default'><i class='fa fa-arrow-right'></i> Next</a> ";
        $last = "<a href=\"?username=$safe_username&page=$maxPage\" class='btn btn-default'>Last</a> ";
    } else {
        $next = ' ';
        $last = ' ';
    }
    
    echo $first . $previous . $pagenums . $next . $last;
    echo '</center>';
}
?>

        </div>
    </div>
</div>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>