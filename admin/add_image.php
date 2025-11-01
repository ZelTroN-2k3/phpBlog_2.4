<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $title       = $_POST['title'];
    $active      = $_POST['active'];
	$album_id = $_POST['album_id'];
    $description = htmlspecialchars($_POST['description']);
    
    $image = '';
    
    if (@$_FILES['avafile']['name'] != '') {
        $target_dir    = "uploads/gallery/";
        $target_file   = $target_dir . basename($_FILES["avafile"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["avafile"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '<div class="alert alert-danger">The file is not an image.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["avafile"]["size"] > 10000000) {
            echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            $location   = "../uploads/gallery/image_$new_string.$imageFileType";
            move_uploaded_file($_FILES["avafile"]["tmp_name"], $location);
            $image = 'uploads/gallery/image_' . $new_string . '.' . $imageFileType . '';
        }
    }
    
    // Use prepared statement for INSERT
    $stmt = mysqli_prepare($connect, "INSERT INTO `gallery` (album_id, title, image, description, active) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $album_id, $title, $image, $description, $active);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo '<meta http-equiv="refresh" content="0; url=gallery.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-camera-retro"></i> Add Image</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="gallery.php">Gallery</a></li>
                    <li class="breadcrumb-item active">Add Image</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Add New Image to Gallery</h3>
            </div>         
            <form action="" method="post" enctype="multipart/form-data">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
						<label>Title</label>
						<input class="form-control" name="title" value="" type="text" required>
					</div>
					<div class="form-group">
						<label>Image</label>
						<input type="file" name="avafile" class="form-control" required />
					</div>
					
					<div class="form-group">
						<label>Active</label>
						<select name="active" class="form-control" required>
							<option value="Yes" selected>Yes</option>
							<option value="No">No</option>
                        </select>
					</div>
					
					<div class="form-group">
						<label>Album</label>
						<select name="album_id" class="form-control" required>
                            <?php
                            $crun = mysqli_query($connect, "SELECT * FROM `albums`");
                            while ($rw = mysqli_fetch_assoc($crun)) {
                                echo '
                                            <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['title']) . '</option>
                                    ';
                            }
                            ?>
						</select>
					</div>
					
					<div class="form-group">
						<label>Description</label>
						<textarea class="form-control" id="summernote" name="description"></textarea>
					</div>
                </div>
                <div class="card-footer">
					<input type="submit" name="add" class="btn btn-primary" value="Add" />
                </div>
			</form>
        </div>

    </div></section>
<script>
$(document).ready(function() {
    // Note: Summernote est initialisé dans footer.php via une vérification
    // $(function () { if ($('#summernote').length) { ... } });
});
</script>
<?php
include "footer.php";
?>