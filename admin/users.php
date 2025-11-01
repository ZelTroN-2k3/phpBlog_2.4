<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE (comments)
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE user_id=? AND guest='No'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Use prepared statements for DELETE (user)
    $stmt = mysqli_prepare($connect, "DELETE FROM `users` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=users.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-users"></i> Users</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Users</li>
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
    $stmt = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=users.php">';
        exit;
    }
    
    if (isset($_POST['edit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $role = $_POST['role'];
        
        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE `users` SET role=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $role, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0;url=users.php">';
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                  <h3 class="card-title">Edit User Role</h3>
              </div>         
                <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label class="control-label">Username: </label>
                        <input type="text" name="username" class="form-control" value="<?php
    echo htmlspecialchars($row['username']);
?>" readonly disabled>
                    </div>
                    <div class="form-group">
                        <label class="control-label">E-Mail Address: </label>
                        <input type="email" name="email" class="form-control" value="<?php
    echo htmlspecialchars($row['email']);
?>" readonly disabled>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Role: </label>
                        <select name="role" class="form-control" required>
                            <option value="User" <?php
    if ($row['role'] == "User") {
        echo 'selected';
    }
?>>User</option>
                            <option value="Editor" <?php
    if ($row['role'] == "Editor") {
        echo 'selected';
    }
?>>Editor</option>
                            <option value="Admin" <?php
    if ($row['role'] == "Admin") {
        echo 'selected';
    }
?>>Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="edit" class="btn btn-primary" value="Save" />
                    <a href="users.php" class="btn btn-secondary">Annuler</a>
                </div>
                </form>
            </div>
<?php
}
?>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                    <a href="add_user.php" class="btn btn-primary"><i class="fa fa-plus"></i> Ajouter un utilisateur</a>
                </h3>
              </div>         
                  <div class="card-body">
                    <table id="dt-basic" class="table table-bordered table-hover" width="100%">
                          <thead>
                              <tr>
								  <th>Username</th>
								  <th>E-Mail</th>
								  <th>Role</th>
								  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>
<?php
$query = mysqli_query($connect, "SELECT * FROM users ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($query)) {
    $badge = '';
    if ($row['role'] == 'Admin') {
        $badge = '<span class="badge bg-danger">Admin</span>';
    }
	if ($row['role'] == 'Editor') {
        $badge = '<span class="badge bg-success">Editor</span>';
    }
	if ($row['role'] == 'User') {
        $badge = '<span class="badge bg-primary">User</span>';
    }
    echo '
                            <tr>
                                <td><img src="../' . htmlspecialchars($row['avatar']) . '" width="40px" height="40px" class="img-circle elevation-2" /> ' . htmlspecialchars($row['username']) . '</td>
								<td>' . htmlspecialchars($row['email']) . '</td>
								<td>' . $badge . '</td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="?edit-id=' . $row['id'] . '">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cet utilisateur ? Tous ses commentaires seront également supprimés.\');">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
';
}
?>
                          </tbody>
                     </table>
                  </div>
            </div>
			
<script>
$(document).ready(function() {
	// Activation de DataTables avec ordre par défaut ascendant (colonne 0)
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