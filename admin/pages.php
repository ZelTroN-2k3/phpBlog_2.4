<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];

    // Use prepared statements to get the slug
    $stmt = mysqli_prepare($connect, "SELECT slug FROM `pages` WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $slug = $row['slug'];
        $path = 'page?name=' . $slug;

        // Delete from menu table
        $stmt_menu = mysqli_prepare($connect, "DELETE FROM `menu` WHERE path=?");
        mysqli_stmt_bind_param($stmt_menu, "s", $path);
        mysqli_stmt_execute($stmt_menu);
        mysqli_stmt_close($stmt_menu);

        // Delete from pages table
        $stmt_page = mysqli_prepare($connect, "DELETE FROM `pages` WHERE id=?");
        mysqli_stmt_bind_param($stmt_page, "i", $id);
        mysqli_stmt_execute($stmt_page);
        mysqli_stmt_close($stmt_page);
    }
    mysqli_stmt_close($stmt);
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-file-alt"></i> Pages</h3>
	</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `pages` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=pages.php">';
        exit;
    }
    $slug_old = $row['slug'];
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $title   = $_POST['title'];
        $slug    = generateSeoURL($title, 0);
        $content = htmlspecialchars($_POST['content']);
        
        // Use prepared statement for validation
        $stmt = mysqli_prepare($connect, "SELECT id FROM `pages` WHERE title = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $title, $id);
        mysqli_stmt_execute($stmt);
        $queryvalid = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if (mysqli_num_rows($queryvalid) > 0) {
            echo '
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> Page with this name has already been added.
                </div>';
        } else {
            // Use prepared statement for UPDATE pages
            $stmt = mysqli_prepare($connect, "UPDATE pages SET title=?, slug=?, content=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssi", $title, $slug, $content, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Use prepared statement for UPDATE menu
            $new_path = 'page?name=' . $slug;
            $old_path = 'page?name=' . $slug_old;
            $stmt = mysqli_prepare($connect, "UPDATE menu SET page=?, path=? WHERE path=?");
            mysqli_stmt_bind_param($stmt, "sss", $title, $new_path, $old_path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            echo '<meta http-equiv="refresh" content="0; url=pages.php">';
        }
    }
?>
            <div class="card mb-3">
              <h6 class="card-header">Edit Page</h6>         
                  <div class="card-body">
					  <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <p>
						  	<label>Title</label>
						  	<input name="title" type="text" class="form-control" value="<?php
						      echo htmlspecialchars($row['title']); // Prevent XSS
?>" required>
						  </p>
						  <p>
						  	<label>Content</label>
						  	<textarea name="content" id="summernote" required><?php
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
              <h6 class="card-header">Pages</h6>
                  <div class="card-body">
				  <a href="add_page.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Page</a><br /><br />

            <table id="dt-basic" class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Title</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$sql = mysqli_query($connect, "SELECT * FROM pages ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
  echo '
                <tr>
	                <td>' . $row['title'] . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
			</table>
                  </div>
              
            </div>

<script>
$(document).ready(function() {

	$('#dt-basic').dataTable( {
		"responsive": true,
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