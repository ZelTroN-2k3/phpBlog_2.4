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
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-archive"></i> Add Widget</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="widgets.php">Widgets</a></li>
                    <li class="breadcrumb-item active">Add Widget</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">New Widget Details</h3>
            </div>
            <form action="" method="post">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input class="form-control" name="title" value="" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea class="form-control" id="summernote" name="content" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Position:</label>
                        <select class="form-control" name="position" required>
                            <option value="Sidebar" selected>Sidebar</option>
                            <option value="Header">Header</option>
                            <option value="Footer">Footer</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="add" class="btn btn-primary" value="Add" />
                </div>
            </form>                          
        </div>

    </div></section>
<?php
include "footer.php";
?>