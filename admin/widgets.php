<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statement for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `widgets` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=widgets.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-archive"></i> Widgets</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Widgets</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];

    // Use prepared statement for SELECT
    $stmt = mysqli_prepare($connect, "SELECT * FROM `widgets` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=widgets.php">';
        exit;
    }
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $title    = $_POST['title'];
        $position = $_POST['position'];
        $content  = htmlspecialchars($_POST['content']);
        
        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE widgets SET title=?, content=?, position=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $title, $content, $position, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0; url=widgets.php">';
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                  <h3 class="card-title">Edit Widget</h3>
              </div>         
                  <form action="" method="post">
                      <div class="card-body">
                          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                          <div class="form-group">
                            <label>Title</label>
                            <input name="title" type="text" class="form-control" value="<?php
echo htmlspecialchars($row['title']);
?>" required>
                          </div>
                          <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" id="summernote" required><?php
echo html_entity_decode($row['content']);
?></textarea>
                          </div>
						  <div class="form-group">
                            <label>Position:</label>
                            <select class="form-control" name="position" required>
                                <option value="Sidebar" <?php
    if ($row['position'] == "Sidebar") {
        echo 'selected';
    }
?>>Sidebar</option>
                                <option value="Header" <?php
    if ($row['position'] == "Header") {
        echo 'selected';
    }
?>>Header</option>
                                <option value="Footer" <?php
    if ($row['position'] == "Footer") {
        echo 'selected';
    }
?>>Footer</option>
                            </select>
						  </div>
                      </div>
                      <div class="card-footer">
                          <input type="submit" class="btn btn-primary" name="submit" value="Save" />
                          <a href="widgets.php" class="btn btn-secondary">Annuler</a>
                      </div>
                  </form>
            </div>
<?php
}
?>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                    <a href="add_widget.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Widget</a>
                </h3>
              </div>         
                  <div class="card-body">
                    <table class="table table-bordered table-hover" id="dt-basic" style="width:100%">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM widgets ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
    echo '
                <tr>
	                <td>' . htmlspecialchars($row['title']) . '</td>
					<td>' . htmlspecialchars($row['position']) . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce widget ?\');"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
                        </tbody>
                    </table>

                  </div>
              </div>

    </div></section>
<script>
$(document).ready(function() {
    // Note: DataTables est initialisé dans footer.php. On le surcharge ici pour l'ordre.
	$('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 0, "asc" ]] 
	});
});
</script>
<?php
include "footer.php";
?>