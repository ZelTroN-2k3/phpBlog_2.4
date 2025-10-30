<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statement for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `gallery` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-images"></i> Gallery</h3>
	</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `gallery` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=gallery.php">';
        exit;
    }
    
    if (isset($_POST['edit'])) {
        $title       = $_POST['title'];
        $image       = $row['image'];
        $active      = $_POST['active'];
        $album_id    = $_POST['album_id'];
        $description = htmlspecialchars($_POST['description']);
        
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
        
        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE gallery SET album_id=?, title=?, image=?, active=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "issssi", $album_id, $title, $image, $active, $description, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0; url=gallery.php">';
    }
?>

	  <div class="card mb-3">
		  <h6 class="card-header">Edit Image</h6>         
              <div class="card-body">
				  <form action="" method="post" enctype="multipart/form-data">
					  <p>
						  <label>Title</label>
						  <input class="form-control" name="title" type="text" value="<?php
    echo htmlspecialchars($row['title']);
?>" required>
					  </p>
					  <p>
						  <label>Image</label><br />
						  <img src="../<?php
    echo htmlspecialchars($row['image']);
?>" width="50px" height="50px" /><br />
						  <input type="file" name="avafile" class="form-control" />
					  </p>
					  <p>
						  <label>Active</label><br />
						  <select name="active" class="form-select">
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
						  <label>Album</label><br />
						  <select name="album_id" class="form-select" required>
<?php
    $crun = mysqli_query($connect, "SELECT * FROM `albums`");
    while ($rw = mysqli_fetch_assoc($crun)) {
		$selected = "";
		if ($row['album_id'] == $rw['id']) {
			$selected = "selected";
		}
        echo '<option value="' . $rw['id'] . '" ' . $selected . '>' . htmlspecialchars($rw['title']) . '</option>';
    }
?>
						  </select>
						</p>
					  <p>
						  <label>Description</label>
						  <textarea class="form-control" id="summernote" name="description"><?php
    echo htmlspecialchars($row['description']);
?></textarea>
					  </p>

					  <input type="submit" class="btn btn-primary col-12" name="edit" value="Save" /><br />

				  </form>
			  </div>
	  </div>
<?php
}
?>

            <div class="card">
              <h6 class="card-header">Gallery</h6>         
                  <div class="card-body">
				  <a href="add_image.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Image</a><br /><br />

            <table class="table table-border table-hover" id="dt-basic" width="100%">
                <thead>
				<tr>
                    <th>Image</th>
                    <th>Title</th>
					<th>Active</th>
					<th>Album</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$sql = mysqli_query($connect, "SELECT * FROM gallery ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
	$album_id = $row['album_id'];
    
    // Use prepared statement to get album title
    $stmt = mysqli_prepare($connect, "SELECT title FROM `albums` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $album_id);
    mysqli_stmt_execute($stmt);
    $runq2 = mysqli_stmt_get_result($stmt);
    $cat = mysqli_fetch_assoc($runq2);
    mysqli_stmt_close($stmt);
    
    $album_title = $cat ? $cat['title'] : 'N/A';

    echo '
                <tr>
	                <td><center><img src="../' . htmlspecialchars($row['image']) . '" width="100px" height="75px" /></center></td>
	                <td>' . htmlspecialchars($row['title']) . '</td>
					<td>' . htmlspecialchars($row['active']) . '</td>
					<td>' . htmlspecialchars($album_title) . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
echo '</table>';

?>
                  </div>
            </div>

<script>
$(document).ready(function() {
	
	$('#dt-basic').dataTable( {
		"responsive": true,
		"order": [[ 1, "asc" ]],
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