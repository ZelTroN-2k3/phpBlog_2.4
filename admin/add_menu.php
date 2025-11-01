<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $page    = $_POST['page'];
    $path    = $_POST['path'];
    $fa_icon = $_POST['fa_icon'];
    
    // Use prepared statement for INSERT
    $stmt = mysqli_prepare($connect, "INSERT INTO menu (page, path, fa_icon) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $page, $path, $fa_icon);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo '<meta http-equiv="refresh" content="0;url=menu_editor.php">';
}
?>
		<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
			<h3 class="h3"><i class="fas fa-bars"></i> Menu Editor</h3>
		</div>

            <div class="card">
              <h6 class="card-header">Add Menu</h6>         
                  <div class="card-body">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <p>
								<label>Title</label>
								<input class="form-control" name="page" value="" type="text" required>
							</p>
							<p>
								<label>Path (Link)</label>
								<input class="form-control" name="path" value="" type="text" required>
							</p>
                            <p>
								<label>Font Awesome 5 Icon</label>
								<input class="form-control" name="fa_icon" value="" type="text">
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