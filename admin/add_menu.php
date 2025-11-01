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
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-bars"></i> Add Menu Item</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="menu_editor.php">Menu Editor</a></li>
                    <li class="breadcrumb-item active">Add Menu Item</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">New Menu Item Details</h3>
            </div>         
            <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="page" value="" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Path (Link)</label>
                        <input class="form-control" name="path" value="" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Font Awesome 5 Icon</label>
                        <input class="form-control" name="fa_icon" value="" type="text">
                        <small class="form-text text-muted">Ex: fa-home, fa-images, fa-envelope. La classe "fa" ou "fas" est ajoutée automatiquement.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="add" class="btn btn-primary" value="Add" />
                </div>
            </form>                       
        </div>

    </div>
</section>
<?php
include "footer.php";
?>