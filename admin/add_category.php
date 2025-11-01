<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $category = $_POST['category'];
    $slug     = generateSeoURL($category, 0);

    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($connect, "SELECT category FROM `categories` WHERE category=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $category);
    mysqli_stmt_execute($stmt);
    $queryvalid = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $validator = mysqli_num_rows($queryvalid);
    if ($validator > 0) {
        echo '<br />
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> Category with this name has already been added.
            </div>';
    } else {
        $stmt = mysqli_prepare($connect, "INSERT INTO categories (category, slug) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $category, $slug);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo '<meta http-equiv="refresh" content="0; url=categories.php">';
    }
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-list-ol"></i> Categories</h3>
	</div>
	
            <div class="card">
              <h6 class="card-header">Add Category</h6>         
                  <div class="card-body">
                      <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <p>
                          <label>Title</label>
                          <input class="form-control" name="category" value="" type="text" required>
                      </p>
					  <div class="form-actions">
                          <input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
                      </div>
                     </form>                           
                  </div>
            </div>

<?php
include "footer.php";
?>