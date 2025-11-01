<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id     = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // MODIFICATION : Supprimer aussi les liaisons de tags
    $stmt_tags = mysqli_prepare($connect, "DELETE FROM `post_tags` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt_tags, "i", $id);
    mysqli_stmt_execute($stmt_tags);
    mysqli_stmt_close($stmt_tags);
    // FIN MODIFICATION

    $stmt = mysqli_prepare($connect, "DELETE FROM `posts` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-list"></i> Posts</h3>
	</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=posts.php">';
        exit;
    }
    
    // --- DÉBUT CHARGEMENT DES TAGS EXISTANTS ---
    $tags_value = '';
    $stmt_get_tags = mysqli_prepare($connect, "
        SELECT t.name 
        FROM tags t
        JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = ?
    ");
    mysqli_stmt_bind_param($stmt_get_tags, "i", $id);
    mysqli_stmt_execute($stmt_get_tags);
    $result_tags = mysqli_stmt_get_result($stmt_get_tags);
    $existing_tags = [];
    while ($row_tag = mysqli_fetch_assoc($result_tags)) {
        $existing_tags[] = $row_tag['name'];
    }
    mysqli_stmt_close($stmt_get_tags);
    // Convertit le tableau PHP en une chaîne de tags séparés par des virgules pour Tagify
    $tags_value = implode(',', $existing_tags);
    // --- FIN CHARGEMENT DES TAGS EXISTANTS ---
    
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $title       = $_POST['title'];
        $slug        = generateSeoURL($title);
        $image       = $row['image'];
        $active      = $_POST['active']; // Sera "Draft", "Yes", ou "No"
        $featured    = $_POST['featured'];
        $category_id = $_POST['category_id'];
        $content     = htmlspecialchars($_POST['content']);
        
        $download_link = $_POST['download_link'];
        $github_link   = $_POST['github_link'];
        $publish_at  = $_POST['publish_at'];

        if (@$_FILES['image']['name'] != '') {
            $target_dir    = "uploads/posts/";
            $target_file   = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            $uploadOk = 1;
            
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                echo '<div class="alert alert-danger">The file is not an image.</div>';
                $uploadOk = 0;
            }
            
            if ($_FILES["image"]["size"] > 10000000) {
                echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
                $uploadOk = 0;
            }
            
            if ($uploadOk == 1) {
                $string     = "0123456789wsderfgtyhjuk";
                $new_string = str_shuffle($string);
                $location   = "../uploads/posts/image_$new_string.$imageFileType";
                move_uploaded_file($_FILES["image"]["tmp_name"], $location);
                $image = 'uploads/posts/image_' . $new_string . '.' . $imageFileType . '';
            }
        }
        
        // Mise à jour de l'article
        $stmt = mysqli_prepare($connect, "UPDATE posts SET title=?, slug=?, image=?, active=?, featured=?, category_id=?, content=?, download_link=?, github_link=?, publish_at=?, created_at=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssissssi", $title, $slug, $image, $active, $featured, $category_id, $content, $download_link, $github_link, $publish_at, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // --- DÉBUT GESTION DES TAGS (MISE À JOUR) ---
        $post_id = $id; // L'ID de l'article ne change pas
        $new_tag_slugs = []; // Stocker les slugs des nouveaux tags
        
        if (!empty($_POST['tags'])) {
            $tags_json = $_POST['tags'];
            $tags_array = json_decode($tags_json);
            
            if (is_array($tags_array) && !empty($tags_array)) {
                
                $stmt_tag_find = mysqli_prepare($connect, "SELECT id, slug FROM tags WHERE slug = ? LIMIT 1");
                $stmt_tag_insert = mysqli_prepare($connect, "INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmt_post_tag_insert = mysqli_prepare($connect, "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                
                foreach ($tags_array as $tag_obj) {
                    $tag_name = $tag_obj->value;
                    $tag_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tag_name), '-'));
                    
                    if (empty($tag_slug)) continue;
                    
                    $new_tag_slugs[] = $tag_slug; // Ajouter au tableau pour la vérification de suppression

                    // 1. Vérifier si le tag existe
                    mysqli_stmt_bind_param($stmt_tag_find, "s", $tag_slug);
                    mysqli_stmt_execute($stmt_tag_find);
                    $result_tag = mysqli_stmt_get_result($stmt_tag_find);
                    
                    if ($row_tag = mysqli_fetch_assoc($result_tag)) {
                        $tag_id = $row_tag['id'];
                    } else {
                        // 2. S'il n'existe pas, le créer
                        mysqli_stmt_bind_param($stmt_tag_insert, "ss", $tag_name, $tag_slug);
                        mysqli_stmt_execute($stmt_tag_insert);
                        $tag_id = mysqli_insert_id($connect);
                    }
                    
                    // 3. Lier le tag à l'article (ignorer si la liaison existe déjà)
                    mysqli_stmt_bind_param($stmt_post_tag_insert, "ii", $post_id, $tag_id);
                    @mysqli_stmt_execute($stmt_post_tag_insert); // Utiliser @ pour ignorer les erreurs de doublons
                }
                
                mysqli_stmt_close($stmt_tag_find);
                mysqli_stmt_close($stmt_tag_insert);
                mysqli_stmt_close($stmt_post_tag_insert);
            }
        }
        
        // 4. Supprimer les anciens tags qui ne sont plus dans la liste
        if (!empty($existing_tags)) {
            $stmt_get_tag_id_slug = mysqli_prepare($connect, "SELECT id, slug FROM tags WHERE name = ?");
            $stmt_delete_link = mysqli_prepare($connect, "DELETE FROM post_tags WHERE post_id = ? AND tag_id = ?");

            foreach ($existing_tags as $old_tag_name) {
                mysqli_stmt_bind_param($stmt_get_tag_id_slug, "s", $old_tag_name);
                mysqli_stmt_execute($stmt_get_tag_id_slug);
                $result_old_tag = mysqli_stmt_get_result($stmt_get_tag_id_slug);
                
                if ($row_old_tag = mysqli_fetch_assoc($result_old_tag)) {
                    $old_tag_slug = $row_old_tag['slug'];
                    $old_tag_id = $row_old_tag['id'];

                    // Si l'ancien slug n'est PAS dans le nouveau tableau, le supprimer
                    if (!in_array($old_tag_slug, $new_tag_slugs)) {
                        mysqli_stmt_bind_param($stmt_delete_link, "ii", $post_id, $old_tag_id);
                        mysqli_stmt_execute($stmt_delete_link);
                    }
                }
            }
            mysqli_stmt_close($stmt_get_tag_id_slug);
            mysqli_stmt_close($stmt_delete_link);
        }
        // --- FIN GESTION DES TAGS (MISE À JOUR) ---

        echo '<meta http-equiv="refresh" content="0;url=posts.php">';
    }
?>
	<div class="card mb-3">
		<h6 class="card-header">Edit Post</h6>         
		<div class="card-body">
			<form name="post_form" action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <p>
					<label>Title</label>
					<input class="form-control" name="title" id="title" type="text" value="<?php
echo htmlspecialchars($row['title']); // Prevent XSS
?>" oninput="countText()" required>
					<i>For best SEO keep title under 50 characters.</i>
					<label for="characters">Characters: </label>
					<span id="characters"><?php
echo strlen($row['title']);
?></span><br>
				</p>
				<p>
					<label>Image</label><br />
<?php
if ($row['image'] != '') {
?>
					<img src="../<?php
	echo $row['image'];
?>" width="50px" height="50px" /><br />
<?php
}
?>
					<input type="file" name="image" class="form-control" />
				</p>
				
                <p>
				<label>Statut</label><br />
				<select name="active" class="form-select" required>
					<option value="Draft" <?php if ($row['active'] == "Draft") { echo 'selected'; } ?>>Draft (draft)</option>
                    <option value="Yes" <?php if ($row['active'] == "Yes") { echo 'selected'; } ?>>Published (public)</option>
					<option value="No" <?php if ($row['active'] == "No") { echo 'selected'; } ?>>Inactive (hidden)</option>
				</select>
				</p>
                <p>
					<label>Featured</label><br />
					<select name="featured" class="form-select" required>
						<option value="Yes" <?php
if ($row['featured'] == "Yes") {
	echo 'selected';
}
?>>Yes</option>
						<option value="No" <?php
if ($row['featured'] == "Yes") {
	echo 'selected';
}
?>>No</option>
					</select>
				</p>
                <p>
                    <label>Publication Date</label>
                    <input type="datetime-local" class="form-control" name="publish_at" value="<?php echo date('Y-m-d\TH:i', strtotime($row['publish_at'])); ?>" required>
                </p>                
				<p>
					<label>Category</label><br />
					<select name="category_id" class="form-select" required>
<?php
$crun = mysqli_query($connect, "SELECT * FROM `categories`");
while ($rw = mysqli_fetch_assoc($crun)) {
	$selected = "";
	if ($row['category_id'] == $rw['id']) {
		$selected = "selected";
	}
	echo '<option value="' . $rw['id'] . '" ' . $selected . '>' . $rw['category'] . '</option>';
}
?>
					</select>
				</p>
				
				<p>
					<label>Tags</label>
					<input name="tags" class="form-control" value="<?php echo htmlspecialchars($tags_value); ?>" placeholder="php, javascript, css">
					<i>Separate tags with a comma or Enter.</i>
				</p>
				<p>
					<label>Download link (.rar, .zip)</label>
					<div class="input-group">
						<span class="input-group-text"><i class="fas fa-file-archive"></i></span>
						<input class="form-control" name="download_link" value="<?php echo htmlspecialchars($row['download_link']); ?>" type="url" placeholder="https://.../file.zip">
					</div>
				</p>
				<p>
					<label>GitHub link</label>
					<div class="input-group">
						<span class="input-group-text"><i class="fab fa-github"></i></span>
						<input class="form-control" name="github_link" value="<?php echo htmlspecialchars($row['github_link']); ?>" type="url" placeholder="https://github.com/user/repo">
					</div>
				</p>
				<p>
					<label>Content</label>
					<textarea name="content" id="summernote" rows="8" required><?php
echo html_entity_decode($row['content']);
?></textarea>
				</p>

				<input type="submit" class="btn btn-primary col-12" name="submit" value="Save" /><br />
			</form>
		</div>
	</div>
<?php
}
?>

	<div class="card">
		<h6 class="card-header">Posts</h6>         
		<div class="card-body">
			<a href="add_post.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Post</a><br /><br />

			<table class="table table-border table-hover" id="dt-basic" width="100%">
				<thead>
					<tr>
						<th>Image</th>
						<th>Title</th>
						<th>Author</th>
						<th>Date</th>
						<th>Statut</th> <th>Category</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM posts ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
    $category_id = $row['category_id'];
    $runq2       = mysqli_query($connect, "SELECT * FROM `categories` WHERE id='$category_id'");
    $cat         = mysqli_fetch_assoc($runq2);
	
	$featured = "";
	if($row['featured'] == "Yes") {
		$featured = '<span class="badge bg-primary">Featured</span>';
	}
	
    echo '
					<tr>
						<td>';
    if ($row['image'] != '') {
        echo '
	                <center><img src="../' . $row['image'] . '" width="45px" height="45px" /></center>
					';
    }
    echo '</td>
						<td>' . $row['title'] . ' ' . $featured . '</td>
						<td>' . post_author($row['author_id']) . '</td>
						<td data-sort="' . strtotime($row['created_at']) . '">' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</td>
						
                        <td>';
    if($row['active'] == "Yes") {
        echo '<span class="badge bg-success">Published</span>';
    } elseif($row['active'] == "Draft") {
        echo '<span class="badge bg-warning text-dark">Draft</span>';
    } else {
        echo '<span class="badge bg-danger">Inactive</span>';
    }
    echo '</td>
                        <td>' . $cat['category'] . '</td>
						<td>
							<a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
							<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
						</td>
					</tr>
	';
}
?>
				</tbody>
			</table>
		</div>
	</div>

<script>
$(document).ready(function() {
	
	$('#dt-basic').dataTable( {
		"responsive": true,
		"order": [[ 3, "desc" ]],
		"language": {
			"paginate": {
			  "previous": '<i class="fa fa-angle-left"></i>',
			  "next": '<i class="fa fa-angle-right"></i>'
			}
		}
	} );
	
	$('#summernote').summernote({height: 350});
	
	var noteBar = $('.note-toolbar');
		noteBar.find('[data-toggle]').each(function() {
		$(this).attr('data-bs-toggle', $(this).attr('data-toggle')).removeAttr('data-toggle');
	});

	// --- DÉBUT INITIALISATION TAGIFY ---
	// Récupère l'élément input
	var input = document.querySelector('input[name=tags]');
	
	// Initialise Tagify
	if(input) { // S'assurer que l'input existe (il n'existe que sur la vue "edit")
		new Tagify(input, {
			duplicate: false, 
			delimiters: ",", 
			addTagOnBlur: true 
		});
	}
	// --- FIN INITIALISATION TAGIFY ---
} );
</script>
<?php
include "footer.php";
?>