<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id     = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE post_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

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
    
    if (isset($_POST['submit'])) {
        $title       = $_POST['title'];
        $slug        = generateSeoURL($title);
        $image       = $row['image'];
        $active      = $_POST['active'];
        $featured    = $_POST['featured'];
        $category_id = $_POST['category_id'];
        $content     = htmlspecialchars($_POST['content']);
        
        // NOUVEAUX CHAMPS
        $download_link = $_POST['download_link'];
        $github_link   = $_POST['github_link'];
        
        $date        = date($settings['date_format']);
        $time        = date('H:i');
        
        if (@$_FILES['image']['name'] != '') {
            $target_dir    = "uploads/posts/";
            $target_file   = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            $uploadOk = 1;
            
            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                echo '<div class="alert alert-danger">The file is not an image.</div>';
                $uploadOk = 0;
            }
            
            // Check file size
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
        
        // Use prepared statement for UPDATE - MISE À JOUR
        $stmt = mysqli_prepare($connect, "UPDATE posts SET title=?, slug=?, image=?, active=?, featured=?, date=?, time=?, category_id=?, content=?, download_link=?, github_link=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssssssssi", $title, $slug, $image, $active, $featured, $date, $time, $category_id, $content, $download_link, $github_link, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0;url=posts.php">';
    }
?>
	<div class="card mb-3">
		<h6 class="card-header">Edit Post</h6>         
		<div class="card-body">
			<form name="post_form" action="" method="post" enctype="multipart/form-data">
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
				<label>Active</label><br />
				<select name="active" class="form-select" required>
					<option value="Yes" <?php
if ($row['active'] == "Yes") {
	echo 'selected';
}
?>>Yes</option>
					<option value="No" <?php
if ($row['active'] == "No") {
	echo 'selected';
}
?>>No</option>
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
if ($row['featured'] == "No") {
	echo 'selected';
}
?>>No</option>
					</select>
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
					<label>Lien de téléchargement (.rar, .zip)</label>
					<div class="input-group">
						<span class="input-group-text"><i class="fas fa-file-archive"></i></span>
						<input class="form-control" name="download_link" value="<?php echo htmlspecialchars($row['download_link']); ?>" type="url" placeholder="https://.../file.zip">
					</div>
				</p>
				<p>
					<label>Lien GitHub</label>
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
						<th>Active</th>
						<th>Category</th>
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
						<td data-sort="' . strtotime($row['date']) . '">' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '</td>
						<td>';
	if($row['active'] == "Yes") {
		echo '<span class="badge bg-success">Yes</span>';
	} else {
		echo '<span class="badge bg-danger">No</span>';
	}
	echo '</td>
						<td>' . $cat['category'] . '</td>
						<td>
							<a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
							<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
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
} );
</script>
<?php
include "footer.php";
?>