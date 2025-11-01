<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $title = $_POST['title'];
    
    $stmt = mysqli_prepare($connect, "INSERT INTO albums (title) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $title);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    echo '<meta http-equiv="refresh" content="0; url=albums.php">';
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-list-ol"></i> Albums</h3>
	</div>
	
            <div class="card">
              <h6 class="card-header">Add Album</h6>         
                  <div class="card-body">
                      <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <p>
                          <label>Title</label>
                          <input class="form-control" name="title" value="" type="text" required>
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