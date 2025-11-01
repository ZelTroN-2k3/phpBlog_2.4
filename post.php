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
$stmt = mysqli_prepare($connect, "SELECT * FROM posts WHERE active='Yes' AND publish_at <= NOW() AND slug=?");
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

// --- NOUVELLE LOGIQUE PHP (FAVORIS) ---
// Vérifier si l'utilisateur a mis cet article en favori
$user_has_favorited = false;
if ($logged == 'Yes') {
    $stmt_fav_check = mysqli_prepare($connect, "SELECT id FROM user_favorites WHERE user_id = ? AND post_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_fav_check, "ii", $rowu['id'], $post_id);
    mysqli_stmt_execute($stmt_fav_check);
    $result_fav_check = mysqli_stmt_get_result($stmt_fav_check);
    if (mysqli_num_rows($result_fav_check) > 0) {
        $user_has_favorited = true;
    }
    mysqli_stmt_close($stmt_fav_check);
}
// --- FIN NOUVELLE LOGIQUE PHP ---

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
                        
                        <span class="ms-3">
                           <b><i>' . get_reading_time($row['content']) . '</i></b>
                        </span>
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
                    echo '<h5><i class="fas fa-download"></i> Downloads</h5>';
                    
                    if (!empty($row['download_link'])) {
                        echo '
                        <a href="' . htmlspecialchars($row['download_link']) . '" class="btn btn-primary me-2 mb-2" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-file-archive"></i> Download (.zip/.rar)
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
				
				';

				// Récupérer l'état du "like" et le compteur
				$total_likes = get_post_like_count($post_id);
				$user_has_liked = check_user_has_liked($post_id);
				
				$like_class = $user_has_liked ? 'btn-primary' : 'btn-outline-primary';
				$like_text = $user_has_liked ? 'Love' : 'I like';
				?>
				
				<button class="btn <?php echo $like_class; ?> mt-2" id="like-button" data-post-id="<?php echo $post_id; ?>">
					<i class="fas fa-thumbs-up"></i>
					<span id="like-text"><?php echo $like_text; ?></span>
					(<span id="like-count"><?php echo $total_likes; ?></span>)
				</button>
				
				<?php
                if ($logged == 'Yes'):
                    // Déterminer l'apparence initiale du bouton
                    $fav_class = $user_has_favorited ? 'btn-warning' : 'btn-outline-warning';
                    $fav_icon = $user_has_favorited ? 'fas fa-bookmark' : 'far fa-bookmark'; // fas = plein, far = vide
                    $fav_text = $user_has_favorited ? 'Enregistré' : 'Save Favorite';
                ?>
                    
                    <button class="btn <?php echo $fav_class; ?> mt-2 ms-2" id="favorite-button" data-post-id="<?php echo $post_id; ?>">
                        <i class="<?php echo $fav_icon; ?>"></i>
                        <span id="favorite-text"><?php echo $fav_text; ?></span>
                    </button>
                
                <?php 
                endif; 
                ?>
                <hr />

				<?php
				
				echo '
				
				';
                
                // 1. Trouver les tags de l'article actuel
                $stmt_find_tags = mysqli_prepare($connect, "SELECT tag_id FROM post_tags WHERE post_id = ?");
                mysqli_stmt_bind_param($stmt_find_tags, "i", $post_id);
                mysqli_stmt_execute($stmt_find_tags);
                $result_find_tags = mysqli_stmt_get_result($stmt_find_tags);
                
                $tag_ids = [];
                while ($tag_row = mysqli_fetch_assoc($result_find_tags)) {
                    $tag_ids[] = $tag_row['tag_id'];
                }
                mysqli_stmt_close($stmt_find_tags);

                if (!empty($tag_ids)) {
                    // 2. Trouver des articles similaires basés sur ces tags
                    $tag_placeholders = implode(',', array_fill(0, count($tag_ids), '?')); // Crée ?,?,?
                    $types = str_repeat('i', count($tag_ids)); // Crée 'iii'
                    $params = $tag_ids;
                    
                    // Ajouter post_id et la limite aux paramètres
                    $types .= 'ii';
                    $params[] = $post_id;
                    $params[] = 4; // Limite de 4 articles similaires
                    
                    $sql_related = "
                        SELECT p.*, COUNT(pt.tag_id) AS common_tags
                        FROM post_tags pt
                        JOIN posts p ON pt.post_id = p.id
                        WHERE pt.tag_id IN ($tag_placeholders)
                          AND p.id != ?
                          AND p.active = 'Yes' AND p.publish_at <= NOW()
                        GROUP BY p.id
                        ORDER BY common_tags DESC, p.created_at DESC
                        LIMIT ?
                    ";
                    
                    $stmt_related = mysqli_prepare($connect, $sql_related);
                    mysqli_stmt_bind_param($stmt_related, $types, ...$params);
                    mysqli_stmt_execute($stmt_related);
                    $result_related = mysqli_stmt_get_result($stmt_related);

                    if (mysqli_num_rows($result_related) > 0) {
                        echo '<h5><i class="fas fa-stream"></i> Articles Similaires</h5>';
                        echo '<div class="row">';
                        
                        while ($related_post = mysqli_fetch_assoc($result_related)) {
                            // Style d'affichage similaire à index.php
                            $image = "";
                            if($related_post['image'] != "") {
                                $image = '<img src="' . htmlspecialchars($related_post['image']) . '" alt="' . htmlspecialchars($related_post['title']) . '" class="card-img-top" width="100%" height="150em" />';
                            } else {
                                $image = '<svg class="bd-placeholder-img card-img-top" width="100%" height="150em" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                                <title>No Image</title><rect width="100%" height="100%" fill="#55595c"/>
                                <text x="40%" y="50%" fill="#eceeef" dy=".3em">No Image</text></svg>';
                            }
                            
                            echo '
                                <div class="col-md-6 mb-3"> 
                                    <div class="card shadow-sm h-100">
                                        <a href="post?name=' . htmlspecialchars($related_post['slug']) . '">
                                            '. $image .'
                                        </a>
                                        <div class="card-body d-flex flex-column">
                                            <a href="post?name=' . htmlspecialchars($related_post['slug']) . '"><h6 class="card-title">' . htmlspecialchars($related_post['title']) . '</h6></a>
                                            <small class="text-muted mb-2">
                                                <i class="far fa-calendar-alt"></i> ' . date($settings['date_format'], strtotime($related_post['created_at'])) . '
                                            </small>
                                            <p class="card-text mt-2">' . short_text(strip_tags(html_entity_decode($related_post['content'])), 80) . '</p>
                                            <a href="post?name=' . htmlspecialchars($related_post['slug']) . '" class="btn btn-sm btn-primary col-12 mt-auto">
                                                Read more
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            ';
                        }
                        
                        echo '</div><hr />';
                    }
                    mysqli_stmt_close($stmt_related);
                }
                echo '
                <h5 class="mt-2" id="comments">
					<i class="fa fa-comments"></i> Comments (' . post_commentscount($row['id']) . ')
				</h5>
';
?>

<?php
// --- MODIFICATION : Ajout d'un conteneur pour la liste des commentaires ---
echo '<div id="comment-list-container">';

// 1. Récupérer le nombre total de commentaires principaux (parent_id = 0)
$stmt_count_main = mysqli_prepare($connect, "SELECT COUNT(id) AS count FROM comments WHERE post_id=? AND approved='Yes' AND parent_id = 0");
mysqli_stmt_bind_param($stmt_count_main, "i", $post_id);
mysqli_stmt_execute($stmt_count_main);
$q_count = mysqli_stmt_get_result($stmt_count_main);
$count_row = mysqli_fetch_assoc($q_count);
$count_main = $count_row['count'];
mysqli_stmt_close($stmt_count_main);

if ($count_main <= 0) {
    echo '<div class="alert alert-info" id="no-comments-alert">There are no comments yet.</div>';
} else {
    // 2. Appeler la fonction récursive pour afficher tous les commentaires (en commençant par les parents)
    display_comments($post_id, 0, 0);
}
echo '</div>'; // Fin de #comment-list-container
// --- FIN MODIFICATION ---
?>                                  
                    
                    <div id="comment-form-container" class="mt-4"> 
                        <h5 class="leave-comment-title">Leave A Comment</h5>
                        
                        <div id="comment-form-messages" class="mb-3"></div>
                        
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
                        <form name="comment_form" id="main-comment-form" method="post" action="ajax_submit_comment.php">
                            
                            <input type="hidden" name="parent_id" id="parent_id" value="0">
                            <input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>">
                            
<?php
    if ($logged == 'No') {
        $guest = 'Yes';
?>
                        <label for="name"><i class="fa fa-user"></i> Name:</label>
                        <input type="text" name="author" id="comment-author" value="" class="form-control" required />
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
?>" id="recaptcha-widget"></div></center>
<?php
    }
?>
                        <input type="submit" name="post" id="submit-comment-btn" class="btn btn-primary col-12" value="Post" />
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
// MODIFICATION : Le bloc PHP de traitement a été supprimé
// Il est maintenant dans ajax_submit_comment.php
?>
                    </div>
                </div>
            </div>
        </div>

<style>
#like-button {
    transition: all 0.3s ease;
    min-width: 100px;
}
#like-button .fa-thumbs-up {
    margin-right: 8px;
}
/* Style pour le nouveau bouton favori */
#favorite-button {
    transition: all 0.3s ease;
    min-width: 130px; /* Un peu plus large pour "Enregistrer" */
}
#favorite-button .fa-bookmark {
    margin-right: 8px;
}
</style>

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

// --- SCRIPT POUR LES RÉPONSES ET L'AJAX ---

// Références aux éléments du formulaire
const formContainer = document.getElementById('comment-form-container');
const mainForm = document.getElementById('main-comment-form');
const parentIdInput = document.getElementById('parent_id');
const cancelBtn = document.getElementById('cancel-reply-btn');
const formTitle = formContainer.querySelector('h5.leave-comment-title');
const formMessages = document.getElementById('comment-form-messages');
const submitBtn = document.getElementById('submit-comment-btn');
const commentListContainer = document.getElementById('comment-list-container');

// Emplacement d'origine du formulaire
const originalFormParent = formContainer.parentNode;

function replyToComment(commentId) {
    const commentElement = document.getElementById('comment-' + commentId);
    if (!commentElement) return;
    commentElement.appendChild(formContainer);
    parentIdInput.value = commentId;
    cancelBtn.style.display = 'block';
    formTitle.innerText = 'Replying to comment #' + commentId;
    document.getElementById('comment').focus();
}

function cancelReply() {
    originalFormParent.appendChild(formContainer);
    parentIdInput.value = '0';
    cancelBtn.style.display = 'none';
    formTitle.innerText = 'Leave A Comment';
    formMessages.innerHTML = ''; // Nettoyer les messages
}

// --- DÉBUT GESTION AJAX ---
if (mainForm) {
    mainForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Empêcher la soumission classique

        // Désactiver le bouton et afficher un loader
        submitBtn.value = 'Envoi...';
        submitBtn.disabled = true;
        formMessages.innerHTML = '';

        const formData = new FormData(mainForm);
        
        fetch('ajax_submit_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Succès !
                formMessages.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                
                // Vider le formulaire
                mainForm.reset();
                document.getElementById('characters').innerText = '1000';
                
                // Réinitialiser reCAPTCHA si c'est un invité
                <?php if ($guest == 'Yes'): ?>
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
                <?php endif; ?>

                // --- MODIFICATION : Logique d'insertion corrigée ---
                // Créer un élément temporaire pour insérer le HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;
                const newCommentElement = tempDiv.firstElementChild;
                
                if (data.parent_id == 0) {
                    // C'est un commentaire principal, ajouter à la fin de la liste principale
                    commentListContainer.appendChild(newCommentElement);
                    // Cacher le message "pas de commentaires" s'il existe
                    const noCommentsAlert = document.getElementById('no-comments-alert');
                    if(noCommentsAlert) noCommentsAlert.style.display = 'none';
                } else {
                    // C'est une réponse, l'ajouter sous le parent
                    const parentElement = document.getElementById('comment-' + data.parent_id);
                    if (parentElement) {
                        parentElement.appendChild(newCommentElement);
                    }
                }
                
                // Animer l'apparition
                setTimeout(() => {
                    newCommentElement.style.opacity = 1;
                }, 10);
                // --- FIN MODIFICATION ---
                
                // Réinitialiser et déplacer le formulaire
                cancelReply();

            } else {
                // Erreur
                formMessages.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                // Réinitialiser reCAPTCHA pour que l'utilisateur puisse réessayer
                <?php if ($guest == 'Yes'): ?>
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
                <?php endif; ?>
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            formMessages.innerHTML = '<div class="alert alert-danger">Une erreur réseau est survenue.</div>';
        })
        .finally(() => {
            // Réactiver le bouton
            submitBtn.value = 'Post';
            submitBtn.disabled = false;
        });
    });
}
// --- FIN GESTION AJAX ---
</script>
<script>
// --- GESTION DU "LIKE" ---
document.getElementById('like-button').addEventListener('click', function() {

    const likeButton = this;
    const postId = likeButton.dataset.postId;
    const likeText = document.getElementById('like-text');
    const likeCount = document.getElementById('like-count');

    // Empêcher les double-clics
    likeButton.disabled = true;

    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('ajax_like_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le compteur
            likeCount.innerText = data.new_count;

            // Mettre à jour l'apparence du bouton
            if (data.liked) {
                likeButton.classList.remove('btn-outline-primary');
                likeButton.classList.add('btn-primary');
                likeText.innerText = 'Love';
            } else {
                likeButton.classList.remove('btn-primary');
                likeButton.classList.add('btn-outline-primary');
                likeText.innerText = 'I like';
            }
        } else {
            console.error(data.message);
        }
    })
    .catch(error => console.error('Erreur:', error))
    .finally(() => {
        // Réactiver le bouton
        likeButton.disabled = false;
    });
});

// --- NOUVEAU SCRIPT (FAVORIS) ---
// --- GESTION DU "FAVORI" ---
$(document).on('click', '#favorite-button', function() {

    const favButton = $(this);
    const postId = favButton.data('post-id');
    const favText = $('#favorite-text');
    const favIcon = favButton.find('i'); // Cible l'icône

    // Empêcher les double-clics
    favButton.prop('disabled', true);

    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('ajax_favorite_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            
            // Mettre à jour l'apparence du bouton
            if (data.favorited) {
                favButton.removeClass('btn-outline-warning').addClass('btn-warning');
                favIcon.removeClass('far fa-bookmark').addClass('fas fa-bookmark');
                favText.text('Enregistré');
            } else {
                favButton.removeClass('btn-warning').addClass('btn-outline-warning');
                favIcon.removeClass('fas fa-bookmark').addClass('far fa-bookmark');
                favText.text('Enregistrer');
            }
        } else {
            // Gérer les erreurs (ex: afficher data.message dans une alerte)
            console.error(data.message); 
            alert(data.message); // Alerter l'utilisateur s'il n'est pas connecté
        }
    })
    .catch(error => console.error('Erreur:', error))
    .finally(() => {
        // Réactiver le bouton
        favButton.prop('disabled', false);
    });
});
// --- FIN NOUVEAU SCRIPT ---

</script>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>