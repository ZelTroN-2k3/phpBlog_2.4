<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `posts` WHERE category_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($connect, "DELETE FROM `categories` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-list-ol"></i> Categories</h3>
	</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `categories` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=categories.php">';
        exit;
    }
    
    if (isset($_POST['submit'])) {
        $category = $_POST['category'];
        $slug     = generateSeoURL($category, 0);
        
        // Use prepared statement for validation
        $stmt = mysqli_prepare($connect, "SELECT id FROM `categories` WHERE category = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $category, $id);
        mysqli_stmt_execute($stmt);
        $queryvalid = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if (mysqli_num_rows($queryvalid) > 0) {
            echo '
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> Category with this name has already been added.
                </div>';
        } else {
            // Use prepared statement for UPDATE
            $stmt = mysqli_prepare($connect, "UPDATE categories SET category=?, slug=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssi", $category, $slug, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            echo '<meta http-equiv="refresh" content="0; url=categories.php">';
        }
    }
?>
            <div class="card mb-3">
              <h6 class="card-header">Edit Category</h6>         
                  <div class="card-body">
                      <form action="" method="post">
						<p>
                          <label>Category</label>
                          <input class="form-control" name="category" type="text" value="<?php
    echo htmlspecialchars($row['category']); // Prevent XSS
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
              <h6 class="card-header">Categories</h6>         
                  <div class="card-body">
				  <a href="add_category.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Category</a><br /><br />

            <table class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Category</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$sql    = "SELECT * FROM categories ORDER BY category ASC";
$result = mysqli_query($connect, $sql);
while ($row = mysqli_fetch_assoc($result)) {
        echo '
                <tr>
	                <td>' . $row['category'] . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
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