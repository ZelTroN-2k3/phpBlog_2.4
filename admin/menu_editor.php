<?php
include "header.php";

if (isset($_GET['up-id'])) {
    // Logique de déplacement UP (reste la même)
    $id = (int) $_GET["up-id"];

    // Get previous menu item's ID
    $stmt = mysqli_prepare($connect, "SELECT id FROM `menu` WHERE id < ? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rowpe = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($rowpe) {
        $prev_id = $rowpe['id'];
        $temp_id = 9999999; // Temporary placeholder ID

        // Use a transaction to ensure atomicity
        mysqli_begin_transaction($connect);
        try {
            // Set previous item to a temporary ID
            $stmt1 = mysqli_prepare($connect, "UPDATE menu SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt1, "ii", $temp_id, $prev_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            // Set current item to the previous item's ID
            $stmt2 = mysqli_prepare($connect, "UPDATE menu SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "ii", $prev_id, $id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            // Set temporary item to the current item's original ID
            $stmt3 = mysqli_prepare($connect, "UPDATE menu SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt3, "ii", $id, $temp_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            mysqli_commit($connect);
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($connect);
            // Vous pouvez ajouter une gestion d'erreur ici si nécessaire
        }
    }
    // Rediriger pour nettoyer l'URL après action
    echo '<meta http-equiv="refresh" content="0; url=menu_editor.php">';
    exit;
}

if (isset($_GET['down-id'])) {
    // Logique de déplacement DOWN (reste la même)
    $id = (int) $_GET["down-id"];

    // Get next menu item's ID
    $stmt = mysqli_prepare($connect, "SELECT id FROM `menu` WHERE id > ? ORDER BY id ASC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rowne = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($rowne) {
        $next_id = $rowne['id'];
        $temp_id = 9999998; // Temporary placeholder ID

        // Use a transaction to ensure atomicity
        mysqli_begin_transaction($connect);
        try {
            // Set next item to a temporary ID
            $stmt1 = mysqli_prepare($connect, "UPDATE menu SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt1, "ii", $temp_id, $next_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            // Set current item to the next item's ID
            $stmt2 = mysqli_prepare($connect, "UPDATE menu SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "ii", $next_id, $id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            // Set temporary item to the current item's original ID
            $stmt3 = mysqli_prepare($connect, "UPDATE menu SET id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt3, "ii", $id, $temp_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            mysqli_commit($connect);
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($connect);
            // Vous pouvez ajouter une gestion d'erreur ici si nécessaire
        }
    }
    // Rediriger pour nettoyer l'URL après action
    echo '<meta http-equiv="refresh" content="0; url=menu_editor.php">';
    exit;
}

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    
    // Use prepared statement for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `menu` WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=menu_editor.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-bars"></i> Menu Editor</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Menu Editor</li>
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
    $stmt = mysqli_prepare($connect, "SELECT * FROM `menu` WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sql = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($sql);
    mysqli_stmt_close($stmt);

    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=menu_editor.php">';
        exit;
    }
    
    if (isset($_POST['submit'])) {
        // --- NOUVEL AJOUT : Validation CSRF ---
        validate_csrf_token();
        // --- FIN AJOUT ---
        
        $page    = $_POST['page'];
        $path    = $_POST['path'];
        $fa_icon = $_POST['fa_icon'];
        
        // Use prepared statement for UPDATE
        $stmt = mysqli_prepare($connect, "UPDATE menu SET page=?, path=?, fa_icon=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $page, $path, $fa_icon, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo '<meta http-equiv="refresh" content="0;url=menu_editor.php">';
    }
?>
            <div class="card card-primary card-outline mb-3">
              <div class="card-header">
                <h3 class="card-title">Edit Menu Item</h3>
              </div>         
                <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                  	    <label>Page Title</label>
                  	    <input name="page" class="form-control" type="text" value="<?php
echo htmlspecialchars($row['page']);
?>" required>
                    </div>
                    <div class="form-group">
                  	    <label>Path (Link)</label>
                  	    <input name="path" class="form-control" type="text" value="<?php
echo htmlspecialchars($row['path']);
?>" required>
                    </div>
                    <div class="form-group">
                  	    <label>Font Awesome 5 Icon</label>
                  	    <input name="fa_icon" class="form-control" type="text" value="<?php
echo htmlspecialchars($row['fa_icon']);
?>">
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" class="btn btn-success" name="submit" value="Save" />
                    <a href="menu_editor.php" class="btn btn-secondary">Annuler</a>
                </div>
                </form>
            </div>
<?php
}
?>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                    <a href="add_menu.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Menu Item</a>
                </h3>
              </div>         
                <div class="card-body p-0"> <table class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th style="width: 50px;">Order</th>
                            <th>Page</th>
                            <th>Path</th>
                            <th style="width: 250px;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
<?php
$query = mysqli_query($connect, "SELECT * FROM menu ORDER BY id ASC");

$queryli  = mysqli_query($connect, "SELECT * FROM menu ORDER BY id DESC LIMIT 1");
$rowli    = mysqli_fetch_assoc($queryli);
$last_id  = $rowli ? $rowli['id'] : null;

$first = true;
while ($row = mysqli_fetch_assoc($query)) {
    
    echo '
            <tr>
                <td>' . $row['id'] . '</td>
                <td><i class="fa ' . htmlspecialchars($row['fa_icon']) . '"></i> ' . htmlspecialchars($row['page']) . '</td>
                <td>' . htmlspecialchars($row['path']) . '</td>
                <td>
';
if ($first == false) {
echo '
                    <a href="?up-id=' . $row['id'] . '&token=' . $csrf_token . '" title="Move Up" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-up"></i></a>
';
}
if ($row['id'] != $last_id) {
echo '
                    <a href="?down-id=' . $row['id'] . '&token=' . $csrf_token . '" title="Move Down" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-down"></i></a>
';
}
echo '
                    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
                    <a href="?delete-id=' . $row['id'] . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cet élément de menu ?\');"><i class="fa fa-trash"></i> Delete</a>
                </td>
            </tr>
';
$first = false;
}
?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div></section>
<?php
include "footer.php";
?>