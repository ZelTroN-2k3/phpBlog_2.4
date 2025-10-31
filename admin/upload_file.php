<?php
include "header.php";
?>
	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h3 class="h3"><i class="fas fa-folder-open"></i> Files</h3>
    </div>
    
            <div class="card">
              <h6 class="card-header">Upload File</h6>
                  <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
						<p>
							<label><b>File</b></label>
							<input type="file" name="file" class="form-control" required />
						</p>
						<div class="form-actions">
                            <input type="submit" name="upload" class="btn btn-primary col-12" value="Upload" />
                        </div>
                    </form>
<?php
if (isset($_POST['upload'])) {
    $file     = $_FILES['file'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $name     = $_FILES['file']['name'];
    
    // MODIFICATION : Suppression de date & time
    
    $format = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed_formats = ["png", "gif", "jpeg", "jpg", "bmp", "doc", "docx", "pdf", "txt", "rar", "zip", "odt", "rtf", "csv", "ods", "xls", "xlsx", "odp", "ppt", "pptx", "mp3", "flac", "wav", "wma", "aac", "m4a", "mov", "avi", "mkv", "mp4", "wmv", "webm", "ts", "webp"];
    if (!in_array($format, $allowed_formats)) {
        echo '<br /><div class="alert alert-info">The uploaded file is with unallowed extension.<br />';
    } else {
        $string     = "0123456789wsderfgtyhjuk";
        $new_string = str_shuffle($string);
        $location   = "../uploads/other/file_$new_string.$format";
        move_uploaded_file($tmp_name, $location);
        
        // MODIFICATION : Mise à jour de la requête
        $stmt = mysqli_prepare($connect, "INSERT INTO `files` (filename, path, created_at) VALUES (?, ?, NOW())");
        // MODIFICATION : Ajustement des paramètres
        mysqli_stmt_bind_param($stmt, "ss", $name, $location);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

		echo '<meta http-equiv="refresh" content="0; url=files.php">';
    }
}
?>                          
                  </div>
              </div>
            </div>
<?php
include "footer.php";
?>