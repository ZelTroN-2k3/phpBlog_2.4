<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

// 1. Vérifier si l'utilisateur est connecté
if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

$user_id = $rowu['id'];

// Gérer la suppression d'un favori (optionnel, mais pratique)
if (isset($_GET['remove-favorite'])) {
    $post_id_to_remove = (int)$_GET["remove-favorite"];
    
    $stmt_delete = mysqli_prepare($connect, "DELETE FROM `user_favorites` WHERE user_id=? AND post_id=?");
    mysqli_stmt_bind_param($stmt_delete, "ii", $user_id, $post_id_to_remove); 
    mysqli_stmt_execute($stmt_delete);
    mysqli_stmt_close($stmt_delete);
    
    // Recharger la page sans le paramètre GET
    echo '<meta http-equiv="refresh" content="0;url=my-favorites.php">';
}
?>
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-bookmark"></i> My favorites</div>
            <div class="card-body">

<?php
// 2. Récupérer les articles favoris
$stmt_favs = mysqli_prepare($connect, "
    SELECT p.* FROM `posts` p
    JOIN `user_favorites` uf ON p.id = uf.post_id
    WHERE uf.user_id = ?
    ORDER BY uf.created_at DESC
");
mysqli_stmt_bind_param($stmt_favs, "i", $user_id);
mysqli_stmt_execute($stmt_favs);
$query = mysqli_stmt_get_result($stmt_favs);

$count = mysqli_num_rows($query);
if ($count <= 0) {
    echo '<div class="alert alert-info">You don\'t have any favorite items yet.</div>';
} else {
    // 3. Afficher chaque article (similaire à blog.php)
    while ($row = mysqli_fetch_array($query)) {
        echo '
			<div class="card mb-3">
			  <div class="row g-0">
				<div class="col-md-4">
                  <a href="post?name=' . htmlspecialchars($row['slug']) . '">
                    <img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="rounded-start" width="100%" height="150px" style="object-fit: cover;">
                  </a>
                </div>
				<div class="col-md-8">
				  <div class="card-body">
					<h6 class="card-title">
						<div class="row">
							<div class="col-md-10">
								<a href="post?name=' . htmlspecialchars($row['slug']) . '">' . htmlspecialchars($row['title']) . '</a>
							</div>
							<div class="col-md-2 d-flex justify-content-end">
								<a href="?remove-favorite=' . $row['id'] . '" class="btn btn-danger btn-sm" title="Retirer des favoris">
									<i class="fa fa-trash"></i>
								</a>
							</div>
						</div>
					</h6>
					<p class="card-text">' . short_text(strip_tags(html_entity_decode($row['content'])), 150) . '</p>
					<p class="card-text">
                        <small class="text-muted">
                            <i class="far fa-calendar-alt"></i> ' . date($settings['date_format'], strtotime($row['created_at'])) . '
                        </small>
                    </p>
				  </div>
				</div>
			  </div>
			</div>			
	    ';
	}
}
mysqli_stmt_close($stmt_favs);
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