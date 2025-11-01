<?php
include "header.php";

if (isset($_POST['add'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $title    = $_POST['title'];
    $content  = htmlspecialchars($_POST['content']);
    $position = $_POST['position'];

    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($connect, "INSERT INTO widgets (title, content, position) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $title, $content, $position);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo '<meta http-equiv="refresh" content="0; url=widgets.php">';
}
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-archive"></i> Widgets</h3>
	</div>

	<div class="card">
        <h6 class="card-header">Add Widget</h6>         
            <div class="card-body">
                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <p>
						<label>Title</label>
						<input class="form-control" name="title" value="" type="text" required>
					</p>
					<p>
						<label>Content</label>
						<textarea class="form-control" id="summernote" name="content" required></textarea>
					</p>
					<div class="form-group">
						<label>Position:</label>
						<select class="form-select" name="position" required>
							<option value="Sidebar" selected>Sidebar</option>
							<option value="Header">Header</option>
							<option value="Footer">Footer</option>
						</select>
					</div><br />
					
					<input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
				</form>                          
			</div>
	</div>
	
<script>
$(document).ready(function() {
	$('#summernote').summernote({height: 350});
	
	var noteBar = $('.note-toolbar');
		noteBar.find('[data-toggle]').each(function() {
		$(this).attr('data-bs-toggle', $(this).attr('data-toggle')).removeAttr('data-toggle');
	});
});
</script>
<?php
include "footer.php";
?>