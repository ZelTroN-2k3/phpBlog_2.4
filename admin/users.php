<?php
include "header.php";

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statements for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `comments` WHERE user_id=? AND guest='No'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($connect, "DELETE FROM `users` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>

	
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		<h3 class="h3"><i class="fas fa-users"></i> Users</h3>
	</div>
	
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
        $role = $_POST['role'];
        
        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE `users` SET role=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $role, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0;url=users.php">';
    }
?>
            <div class="card mb-3">
              <h6 class="card-header">Edit User</h6>         
                  <div class="card-body">
                    <form action="" method="post">
						<div class="form-group">
							<label class="control-label">Username: </label>
							<input type="text" name="username" class="form-control" value="<?php
    echo htmlspecialchars($row['username']);
?>" readonly disabled>
						</div><br />
						<div class="form-group">
							<label class="control-label">E-Mail Address: </label>
								<input type="email" name="email" class="form-control" value="<?php
    echo htmlspecialchars($row['email']);
?>" readonly disabled>
						</div><br />
						<div class="form-group">
							<label class="control-label">Role: </label><br />
							<select name="role" class="form-select" required>
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
                            </select><br />
						</div>
						<div class="form-actions">
                            <input type="submit" name="edit" class="btn btn-primary col-12" value="Save" />
                        </div>
					</form>
                  </div>
            </div>
<?php
}
?>

			<div class="card">
              <h6 class="card-header">Users</h6>         
                  <div class="card-body">
                    <table id="dt-basic" class="table table-border table-hover bootstrap-datatable" width="100%">
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
        $badge = '<h6><span class="badge bg-danger">Admin</span></h6>';
    }
	if ($row['role'] == 'Editor') {
        $badge = '<h6><span class="badge bg-success">Editor</span></h6>';
    }
	if ($row['role'] == 'User') {
        $badge = '<h6><span class="badge bg-primary">User</span></h6>';
    }
    echo '
                            <tr>
                                <td><img src="../' . htmlspecialchars($row['avatar']) . '" width="40px" height="40px" /> ' . htmlspecialchars($row['username']) . '</td>
								<td>' . htmlspecialchars($row['email']) . '</td>
								<td>' . $badge . '</td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="?edit-id=' . $row['id'] . '">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?delete-id=' . $row['id'] . '">
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
	$('#dt-basic').dataTable( {
		"responsive": true,
		"language": {
			"paginate": {
			  "previous": '<i class="fas fa-angle-left"></i>',
			  "next": '<i class="fas fa-angle-right"></i>'
			}
		}
	} );
} );
</script>
<?php
include "footer.php";
?>