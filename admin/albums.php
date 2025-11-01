<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];

    // Corrected table name from galery to gallery and using prepared statements
    $stmt = mysqli_prepare($connect, "DELETE FROM `gallery` WHERE album_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($connect, "DELETE FROM `albums` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-list-ol"></i> Albums</h3>
	</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `albums` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
        exit;
    }
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $title = $_POST['title'];

        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE albums SET title=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $title, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
    }
?>
            <div class="card mb-3">
              <h6 class="card-header">Edit Album</h6>         
                  <div class="card-body">
                      <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <p>
                          <label>Title</label>
                          <input class="form-control" name="title" type="text" value="<?php
    echo htmlspecialchars($row['title']); // Prevent XSS
?>" required>
						</p>
                        <input type="submit" class="btn btn-primary col-12" name="submit" value="Save" /><br />
                      </form>
                  </div>
            </div>
<?php
}
?>

            <div class="card">
              <h6 class="card-header">Albums</h6>         
                  <div class="card-body">
				  <a href="add_album.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Album</a><br /><br />

            <table class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Title</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$sql    = "SELECT * FROM albums ORDER BY title ASC";
$result = mysqli_query($connect, $sql);
while ($row = mysqli_fetch_assoc($result)) {
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
<?php
include "footer.php";
?>