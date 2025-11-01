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
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=pages.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-alt"></i> Pages</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Pages</li>
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
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Erreur !</h5>
                    Une page avec ce nom existe déjà.
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
            exit;
        }
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                  <h3 class="card-title">Edit Page</h3>
              </div>         
                  <form action="" method="post">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="form-group">
                            <label>Title</label>
                            <input name="title" type="text" class="form-control" value="<?php
                                  echo htmlspecialchars($row['title']); // Prevent XSS
?>" required>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" id="summernote" required><?php
                                  echo html_entity_decode($row['content']);
?></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <input type="submit" class="btn btn-primary" name="submit" value="Save" />
                        <a href="pages.php" class="btn btn-secondary">Annuler</a>
                    </div>
                  </form>
            </div>
<?php
}
?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <a href="add_page.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Page</a>
                    </h3>
                </div>
                <div class="card-body">
                    <table id="dt-basic" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$sql = mysqli_query($connect, "SELECT * FROM pages ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($sql)) {
  echo '
                <tr>
	                <td>' . htmlspecialchars($row['title']) . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette page ? L\'élément de menu sera également supprimé.\');"><i class="fa fa-trash"></i> Delete</a>
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
    // Note: DataTables est initialisé dans footer.php. On le surcharge ici pour l'ordre si nécessaire.
    // L'ordre par défaut (colonne 0 descendante) convient ici.
});
</script>
<?php
include "footer.php";
?>