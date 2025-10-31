<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

$user_id = $rowu['id'];

if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

if (isset($_GET['delete-comment'])) {
    $id = (int)$_GET["delete-comment"];
    
    // Utiliser une requête préparée
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE user_id=? AND id=?");
    // Notez "si" car $user_id vient de la session (peut être string), et "i" pour $id
    mysqli_stmt_bind_param($stmt, "si", $user_id, $id); 
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Il faudrait aussi supprimer les enfants de ce commentaire, mais pour l'instant, 
    // ils resteront orphelins (ce qui est géré par la fonction d'affichage).
}
?>
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><i class="fa fa-comments"></i> My Comments</div>
            <div class="card-body">

<?php
$query = mysqli_query($connect, "SELECT * FROM comments WHERE user_id='$user_id' ORDER BY created_at DESC");
$count = mysqli_num_rows($query);
if ($count <= 0) {
    echo '<div class="alert alert-info">You have not written any comments yet.</div>';
} else {
    while ($comment = mysqli_fetch_array($query)) {
        
        // --- MODIFICATION : Vérifier si c'est une réponse ---
        $reply_info = '';
        if ($comment['parent_id'] > 0) {
            
            // Chercher le nom de l'auteur parent
            $stmt_parent = mysqli_prepare($connect, "SELECT user_id, guest FROM `comments` WHERE id = ?");
            mysqli_stmt_bind_param($stmt_parent, "i", $comment['parent_id']);
            mysqli_stmt_execute($stmt_parent);
            $result_parent = mysqli_stmt_get_result($stmt_parent);
            
            if($parent = mysqli_fetch_assoc($result_parent)) {
                $parent_author_name = 'Guest';
                if ($parent['guest'] == 'Yes') {
                    $parent_author_name = $parent['user_id'];
                } else {
                    // On pourrait faire une autre requête pour le nom d'utilisateur,
                    // mais pour rester simple, on utilise l'ID ou "Utilisateur"
                    $parent_author_name = 'Utilisateur #' . $parent['user_id'];
                }
                $reply_info = '<small class="text-muted d-block ms-1 mb-2">
                                  <i class="fas fa-reply"></i> 
                                  En réponse à ' . htmlspecialchars($parent_author_name) . '
                               </small>';
            }
            mysqli_stmt_close($stmt_parent);
        }
        // --- FIN MODIFICATION ---
        
		echo '
			<div class="card mb-3">
			  <div class="row">
				<div class="col-md-12">
				  <div class="card-body">
					<h6 class="card-title">
						<div class="row">
							<div class="col-md-10">
								<i class="fas fa-newspaper"></i> On post: <a href="post?name=' . post_slug($comment['post_id']) . '#comment-' . $comment['id'] . '">' . post_title($comment['post_id'])  . '</a>
							</div>
							<div class="col-md-2 d-flex justify-content-end">
								<a href="?delete-comment=' . $comment['id']  . '" class="btn btn-danger btn-sm" title="Delete">
									<i class="fa fa-trash"></i>
								</a>
							</div>
						</div>
					</h6>
                    ' . $reply_info . '
					<p class="card-text">' . format_comment_with_code($comment['comment'])  . '</p>
					<p class="card-text">
						<div class="row">
							<div class="col-md-10">
								<small class="text-muted">
									' . date($settings['date_format'] . ' H:i', strtotime($comment['created_at'])) . '
								</small>
							</div>
							<div class="col-md-2 d-flex justify-content-end">
								'; 
								if ($comment['approved'] == 'Yes') {
									echo '<span class="badge bg-success"><i class="fas fa-check"></i> Approved</span>';
								} else {
									echo '<span class="badge bg-secondary"><i class="fas fa-clock"></i> Pending</span>';
								}
								echo '
							</div>
						</div>
					</p>
				  </div>
				</div>
			  </div>
			</div>			
	';
	}
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