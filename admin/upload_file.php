<?php
include "header.php";

if (isset($_POST['upload'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $file     = $_FILES['file'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $name     = $_FILES['file']['name'];
    
    $format = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed_formats = ["png", "gif", "jpeg", "jpg", "bmp", "doc", "docx", "pdf", "txt", "rar", "zip", "odt", "rtf", "csv", "ods", "xls", "xlsx", "odp", "ppt", "pptx", "mp3", "flac", "wav", "wma", "aac", "m4a", "mov", "avi", "mkv", "mp4", "wmv", "webm", "ts", "webp"];
    
    $upload_successful = false;
    
    if (!in_array($format, $allowed_formats)) {
        // Utiliser la classe AdminLTE pour les alertes
        echo '<div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Erreur !</h5>
                The uploaded file is with unallowed extension.
              </div>';
    } else {
        $string     = "0123456789wsderfgtyhjuk";
        $new_string = str_shuffle($string);
        // Le chemin est relatif au dossier du projet (un niveau au-dessus de /admin)
        $location   = "uploads/other/file_$new_string.$format"; 
        
        // move_uploaded_file a besoin du chemin absolu ou relatif à l'exécution
        if (move_uploaded_file($tmp_name, '../' . $location)) { 
            $stmt = mysqli_prepare($connect, "INSERT INTO `files` (filename, path, created_at) VALUES (?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "ss", $name, $location);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $upload_successful = true;
        } else {
             echo '<div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Erreur !</h5>
                    Failed to move the uploaded file. Check folder permissions.
                  </div>';
        }
    }
    
    if ($upload_successful) {
        echo '<meta http-equiv="refresh" content="0; url=files.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-upload"></i> Upload File</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="files.php">Files</a></li>
                    <li class="breadcrumb-item active">Upload File</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">
        
        <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">Upload File to Server</h3>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label>File</label>
                        <input type="file" name="file" class="form-control" required />
                        <small class="text-muted">Allowed types: png, gif, jpg, jpeg, bmp, doc, docx, pdf, txt, rar, zip, odt, rtf, csv, ods, xls, xlsx, odp, ppt, pptx, mp3, flac, wav, wma, aac, m4a, mov, avi, mkv, mp4, wmv, webm, ts, webp.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="upload" class="btn btn-primary" value="Upload" />
                </div>
            </form>
        </div>

    </div></section>
<?php
include "footer.php";
?>