<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
<?php
$slug = $_GET['name'] ?? '';

if (empty($slug)) {
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit;
}

// Use prepared statement for SELECT
$stmt = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE active='Yes' AND slug=?");
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);
$runq = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if (mysqli_num_rows($runq) == 0) {
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit;
}

// Use prepared statement for UPDATE
$stmt_update = mysqli_prepare($connect, "UPDATE `posts` SET views = views + 1 WHERE active='Yes' AND slug=?");
mysqli_stmt_bind_param($stmt_update, "s", $slug);
mysqli_stmt_execute($stmt_update);
mysqli_stmt_close($stmt_update);
$row         = mysqli_fetch_assoc($runq);
$post_id     = $row['id'];
$post_slug   = $row['slug'];
echo '
                    <div class="card shadow-sm bg-light">
                        <div class="col-md-12">
							';
if ($row['image'] != '') {
    echo '
        <img src="' . htmlspecialchars($row['image']) . '" width="100%" height="auto" alt="' . htmlspecialchars($row['title']) . '"/>
';
}
echo '
            <div class="card-body">
                
				<div class="mb-1">
					<i class="fas fa-chevron-right"></i> <a href="category?name=' . post_categoryslug($row['category_id']) . '">' . post_category($row['category_id']) . '</a>
				</div>
				
				<h5 class="card-title fw-bold">' . htmlspecialchars($row['title']) . '</h5>
				
				<div class="d-flex justify-content-between align-items-center">
					<small>
						Posted by <b><i><i class="fas fa-user"></i> ' . post_author($row['author_id']) . '</i></b> 
						on <b><i><i class="far fa-calendar-alt"></i> ' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</i></b>
					</small>
					<small> 	
						<i class="fa fa-eye"></i> ' . $row['views'] . '
					</small>
					<small class="float-end">
						<i class="fa fa-comments"></i> <a href="#comments"><b>' . post_commentscount($row['id']) . '</b></a>
					</small>
				</div>
				<hr />
				
                ' . html_entity_decode($row['content']) . '
				<hr />
				
				';
                if (!empty($row['download_link']) || !empty($row['github_link'])) {
                    echo '<h5><i class="fas fa-download"></i> Téléchargements</h5>';
                    
                    if (!empty($row['download_link'])) {
                        echo '
                        <a href="' . htmlspecialchars($row['download_link']) . '" class="btn btn-primary me-2 mb-2" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-file-archive"></i> Télécharger (.zip/.rar)
                        </a>';
                    }
                    
                    if (!empty($row['github_link'])) {
                        echo '
                        <a href="' . htmlspecialchars($row['github_link']) . '" class="btn btn-dark me-2 mb-2" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-github"></i> Voir sur GitHub
                        </a>';
                    }
                    echo '<hr />';
                }
                
                // --- DÉBUT AFFICHAGE DES TAGS ---
                $stmt_get_tags = mysqli_prepare($connect, "
                    SELECT t.name, t.slug 
                    FROM tags t
                    JOIN post_tags pt ON t.id = pt.tag_id
                    WHERE pt.post_id = ?
                ");
                mysqli_stmt_bind_param($stmt_get_tags, "i", $post_id);
                mysqli_stmt_execute($stmt_get_tags);
                $result_tags = mysqli_stmt_get_result($stmt_get_tags);
                
                if (mysqli_num_rows($result_tags) > 0) {
                    echo '<h5><i class="fas fa-tags"></i> Tags</h5>';
                    echo '<div class="mb-3">';
                    while ($row_tag = mysqli_fetch_assoc($result_tags)) {
                        echo '<a href="tag.php?name=' . htmlspecialchars($row_tag['slug']) . '" class="btn btn-outline-secondary btn-sm me-1 mb-1">
                                <i class="fas fa-tag"></i> ' . htmlspecialchars($row_tag['name']) . '
                              </a>';
                    }
                    echo '</div><hr />';
                }
                mysqli_stmt_close($stmt_get_tags);
                // --- FIN AFFICHAGE DES TAGS ---
                
                echo '
				<h5><i class="fas fa-share-alt-square"></i> Share</h5>
				<div id="share" style="font-size: 14px;"></div>
				<hr />

				<h5 class="mt-2" id="comments">
					<i class="fa fa-comments"></i> Comments (' . post_commentscount($row['id']) . ')
				</h5>
';
?>

<?php
// --- MODIFICATION : Remplacement de la boucle par la nouvelle fonction ---

// 1. Récupérer le nombre total de commentaires principaux (parent_id = 0)
$stmt_count_main = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM comments WHERE post_id=? AND approved='Yes' AND parent_id = 0");
mysqli_stmt_bind_param($stmt_count_main, "i", $post_id);
mysqli_stmt_execute($stmt_count_main);
$q_count = mysqli_stmt_get_result($stmt_count_main);
$count_row = mysqli_fetch_assoc($q_count);
$count_main = $count_row['count'];
mysqli_stmt_close($stmt_count_main);

if ($count_main <= 0) {
    echo '<div class="alert alert-info">There are no comments yet.</div>';
} else {
    // 2. Appeler la fonction récursive pour afficher tous les commentaires (en commençant par les parents)
    display_comments($post_id, 0, 0);
}
// --- FIN MODIFICATION ---
?>                                  
                    
                    <div id="comment-form-container" class="mt-4"> 
                        <h5 class="leave-comment-title">Leave A Comment</h5>
<?php
$guest = 'No';

if ($logged == 'No' AND $settings['comments'] == 'guests') {
    $cancomment = 'Yes';
} else {
    $cancomment = 'No';
}
if ($logged == 'Yes') {
    $cancomment = 'Yes';
}

if ($cancomment == 'Yes') {
?>
                        <form name="comment_form" id="main-comment-form" action="post?name=<?php
    echo $post_slug;
?>" method="post">
                            
                            <input type="hidden" name="parent_id" id="parent_id" value="0">
                            
<?php
    if ($logged == 'No') {
        $guest = 'Yes';
?>
                        <label for="name"><i class="fa fa-user"></i> Name:</label>
                        <input type="text" name="author" value="" class="form-control" required />
                        <br />
<?php
    }
?>
                        <label for="comment"><i class="fa fa-comment"></i> Comment:</label>
                        <textarea name="comment" id="comment" rows="5" class="form-control" maxlength="1000" oninput="countText()" required></textarea>
						<label for="characters"><i>Characters left: </i></label>
						<span id="characters">1000</span><br>
						<br />
<?php
    if ($logged == 'No') {
        $guest = 'Yes';
?>
						<center><div class="g-recaptcha" data-sitekey="<?php
        echo $settings['gcaptcha_sitekey'];
?>"></div></center>
<?php
    }
?>
                        <input type="submit" name="post" class="btn btn-primary col-12" value="Post" />
                        <button type="button" class="btn btn-secondary col-12 mt-2" id="cancel-reply-btn" style="display:none;" onclick="cancelReply()">
                            Annuler la réponse
                        </button>
            </form>
<?php
} else {
    echo '<div class="alert alert-info">Please <strong><a href="login"><i class="fas fa-sign-in-alt"></i> Sign In</a></strong> to be able to post a comment.</div>';
}
?>
                    </div> <?php
if ($cancomment == 'Yes') {
    if (isset($_POST['post'])) {
        
        $authname_problem = 'No';
		$comment          = $_POST['comment'];
		// MODIFICATION : Récupérer le parent_id
		$parent_id        = (int)$_POST['parent_id']; 
		
		$captcha = '';
		
        if ($logged == 'No') {
            $author = $_POST['author'];
            
            $bot = 'Yes';
            if (isset($_POST['g-recaptcha-response'])) {
                $captcha = $_POST['g-recaptcha-response'];
            }
            if ($captcha) {
                $url          = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($settings['gcaptcha_secretkey']) . '&response=' . urlencode($captcha);
                $response     = file_get_contents($url);
                $responseKeys = json_decode($response, true);
                if ($responseKeys["success"]) {
                    $bot = 'No';
                }
            }
            
            if (strlen($author) < 2) {
                $authname_problem = 'Yes';
                echo '<div class="alert alert-warning">Your name is too short.</div>';
            }
        } else {
            $bot    = 'No';
            $author = $rowu['id'];
        }
        
        if (strlen($comment) < 2) {
            echo '<div class="alert alert-danger">Your comment is too short.</div>';
        } else {
            if ($authname_problem == 'No' AND $bot == 'No') {
                // MODIFICATION : Mise à jour de la requête pour inclure parent_id
                $stmt = mysqli_prepare($connect, "INSERT INTO `comments` (`post_id`, `parent_id`, `comment`, `user_id`, `guest`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())");
                // MODIFICATION : Ajustement des paramètres
                mysqli_stmt_bind_param($stmt, "iisss", $row['id'], $parent_id, $comment, $author, $guest);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                echo '<div class="alert alert-success">Your comment has been successfully posted</div>';
                echo '<meta http-equiv="refresh" content="0;url=post?name=' . $row['slug'] . '#comments">';
            }
        }
    }
}
?>
                    </div>
                </div>
            </div>
        </div>
		
<script>
$("#share").jsSocials({
    showCount: false,
    showLabel: true,
    shares: [
        { share: "facebook", logo: "fab fa-facebook-square", label: "Share" },
        { share: "twitter", logo: "fab fa-twitter-square", label: "Tweet" },
        { share: "linkedin", logo: "fab fa-linkedin", label: "Share" },
		{ share: "email", logo: "fas fa-envelope", label: "E-Mail" }
    ]
});

function countText() {
	let text = document.comment_form.comment.value;
	document.getElementById('characters').innerText = 1000 - text.length;
}

// --- NOUVEAU SCRIPT POUR LES RÉPONSES ---
// Références aux éléments du formulaire
const formContainer = document.getElementById('comment-form-container');
const mainForm = document.getElementById('main-comment-form');
const parentIdInput = document.getElementById('parent_id');
const cancelBtn = document.getElementById('cancel-reply-btn');
const formTitle = formContainer.querySelector('h5.leave-comment-title');

// Emplacement d'origine du formulaire
const originalFormParent = formContainer.parentNode;

function replyToComment(commentId) {
    // 1. Trouver le conteneur du commentaire auquel on répond
    const commentElement = document.getElementById('comment-' + commentId);
    if (!commentElement) return;

    // 2. Déplacer le formulaire sous ce commentaire
    commentElement.appendChild(formContainer);

    // 3. Mettre à jour la valeur du parent_id
    parentIdInput.value = commentId;

    // 4. Afficher le bouton "Annuler"
    cancelBtn.style.display = 'block';
    
    // 5. Changer le titre du formulaire
    formTitle.innerText = 'Replying to comment #' + commentId;
    
    // 6. Mettre le focus sur la zone de texte
    document.getElementById('comment').focus();
}

function cancelReply() {
    // 1. Remettre le formulaire à sa place d'origine
    originalFormParent.appendChild(formContainer);

    // 2. Réinitialiser la valeur du parent_id
    parentIdInput.value = '0';

    // 3. Cacher le bouton "Annuler"
    cancelBtn.style.display = 'none';
    
    // 4. Réinitialiser le titre
    formTitle.innerText = 'Leave A Comment';
}
// --- FIN NOUVEAU SCRIPT ---
</script>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>