<?php
include "core.php";
head();

// 1. Vérifier si l'utilisateur est connecté
if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

$user_id = $rowu['id'];
$comment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Vérifier si l'ID du commentaire est valide
if ($comment_id == 0) {
    echo '<meta http-equiv="refresh" content="0;url=my-comments.php">';
    exit;
}

// 3. Récupérer le commentaire ET VÉRIFIER QUE L'UTILISATEUR EN EST L'AUTEUR
$stmt = mysqli_prepare($connect, "SELECT * FROM comments WHERE id = ? AND user_id = ? AND guest = 'No' LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$comment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 4. Si le commentaire n'existe pas ou n'appartient pas à l'utilisateur, le renvoyer
if (!$comment) {
    echo '<meta http-equiv="refresh" content="0;url=my-comments.php">';
    exit;
}

// 5. Gérer la soumission du formulaire
if (isset($_POST['save_comment'])) {
    $new_comment_content = $_POST['comment_content'];
    
    if (strlen($new_comment_content) < 2) {
        echo '<div class="alert alert-danger">Votre commentaire est trop court.</div>';
    } else {
        // Mettre à jour le commentaire
        $stmt_update = mysqli_prepare($connect, "UPDATE comments SET comment = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt_update, "sii", $new_comment_content, $comment_id, $user_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        // Rediriger vers "Mes Commentaires"
        echo '<div class="alert alert-success">Commentaire mis à jour !</div>';
        echo '<meta http-equiv="refresh" content="2;url=my-comments.php">';
    }
}


if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>

<div class="col-md-8 mb-3">
    <div class="card">
        <div class="card-header"><i class="fa fa-edit"></i> Modifier mon commentaire</div>
        <div class="card-body">
            
            <form method="post" action="">
                <p>
                    <label for="comment_content">Votre commentaire :</label>
                    <textarea name="comment_content" id="comment_content" rows="6" class="form-control" required><?php echo htmlspecialchars($comment['comment']); ?></textarea>
                    <small>Article original : <a href="post.php?name=<?php echo post_slug($comment['post_id']); ?>#comment-<?php echo $comment['id']; ?>" target="_blank"><?php echo post_title($comment['post_id']); ?></a></small>
                </p>
                <br>
                <input type="submit" name="save_comment" class="btn btn-primary col-12" value="Enregistrer les modifications" />
            </form>

        </div>
    </div>
</div>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>