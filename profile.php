<?php
include "core.php";
head();

if ($logged == 'No') {
    echo '<meta http-equiv="refresh" content="0;url=login">';
    exit;
}

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><i class="fas fa-cog"></i> Account Settings</div>
                    <div class="card-body">
<?php
$uname   = $_SESSION['sec-username'];
$user_id = $rowu['id'];
$message = '';

if (isset($_POST['save'])) {
    
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

    $email    = $_POST['email'];
    $username = $_POST['username'];
    $avatar   = $rowu['avatar'];
    $password = $_POST['password']; // C'est le mot de passe en clair
    
    $bio      = htmlspecialchars($_POST['bio']); 
    
    $emused = 'No';
    
    // Use prepared statement for email check
    $stmt = mysqli_prepare($connect, "SELECT id FROM `users` WHERE email=? AND id != ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (mysqli_num_rows($result) > 0) {
        $emused = 'Yes';
    }
    
    if (@$_FILES['avafile']['name'] != '') {
        $target_dir    = "uploads/avatars/";
        $target_file   = $target_dir . basename($_FILES["avafile"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $filename      = $uname . '.' . $imageFileType;
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["avafile"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $message .= '<div class="alert alert-danger">File is not an image.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["avafile"]["size"] > 1000000) {
            $message .= '<div class="alert alert-warning">Sorry, your file is too large. Max 1MB.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            // Supprimer l'ancienne image si elle n'est pas celle par défaut
            if ($rowu['avatar'] != 'assets/img/avatar.png' && file_exists($rowu['avatar'])) {
                unlink($rowu['avatar']);
            }
            
            move_uploaded_file($_FILES["avafile"]["tmp_name"], "uploads/avatars/" . $filename);
            $avatar = "uploads/avatars/" . $filename;
        }
    }
    
    if ($emused == 'Yes') {
        $message .= '<div class="alert alert-danger">The E-Mail Address is already in use.</div>';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) && $emused == 'No') {
        
        if ($password != null) {
            // MODIFICATION : Utiliser password_hash()
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // MODIFICATION : Ajout de 'bio' à la requête
            $stmt = mysqli_prepare($connect, "UPDATE `users` SET email=?, username=?, avatar=?, password=?, bio=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssssi", $email, $username, $avatar, $password_hashed, $bio, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            
            // MODIFICATION : Ajout de 'bio' à la requête
            $stmt = mysqli_prepare($connect, "UPDATE `users` SET email=?, username=?, avatar=?, bio=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssi", $email, $username, $avatar, $bio, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        // Afficher un message de succès (sauf si une erreur d'upload est apparue)
        if (empty($message)) {
            $message = '<div class="alert alert-success">Account settings updated successfully!</div>';
        }

        // Recharger la page (après un court délai pour afficher le succès)
        echo $message;
        echo '<meta http-equiv="refresh" content="1;url=profile">';
        exit;
    }
    
    // Si l'e-mail est invalide ou une erreur d'upload a eu lieu sans rediriger
    if ($message) {
         echo $message;
    }
}
?>
<form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group mb-3">
                            <label for="username"><i class="fa fa-user"></i> Username:</label>
                            <input type="text" name="username" id="username" value="<?php
    echo htmlspecialchars($rowu['username']);
    ?>" class="form-control" required />
                        </div>
									
						<div class="form-group mb-3">
                            <label for="email"><i class="fa fa-envelope"></i> E-Mail Address:</label>
                            <input type="email" name="email" id="email" value="<?php
    echo htmlspecialchars($rowu['email']);
    ?>" class="form-control" required />
                        </div>
									
						<div class="form-group mb-3">
                            <label for="avatar"><i class="fa fa-image"></i> Avatar:</label>
                            <div class="text-center mb-3">
                                <img src="<?php
    echo htmlspecialchars($rowu['avatar']);
    ?>" width="100px" height="100px" style="object-fit: cover; border-radius: 50%; border: 3px solid #ddd;">
                            </div>
                            <div class="custom-file">
                                <input type="file" class="form-control" name="avafile" accept="image/*" id="avatarfile">
                                <small class="form-text text-muted">Max file size: 1 MB. Upload a new image to replace the current one.</small>
                            </div>
						</div>
                        
                        <div class="form-group mb-3">
                            <label for="bio"><i class="fa fa-info-circle"></i> Biographie :</label>
                            <textarea name="bio" id="bio" rows="4" class="form-control"><?php
    // htmlspecialchars_decode est nécessaire si vous avez utilisé htmlspecialchars à l'enregistrement
    // Si $bio est stocké en HTML brut, utilisez htmlspecialchars() pour l'affichage
    echo htmlspecialchars($rowu['bio'] ?? ''); // ?? '' pour gérer les valeurs NULL
    ?></textarea>
                            <small class="form-text text-muted">Une courte biographie qui apparaîtra sur votre profil public.</small>
						</div>
                        
                        <div class="form-group mb-3">
                            <label for="name"><i class="fa fa-key"></i> Password:</label>
                            <input type="password" name="password" id="name" value="" class="form-control" />
                            <small class="form-text text-muted">Fill this field only if you want to change your password.</small>
						</div>

                        <div class="form-actions mt-4">
                            <input type="submit" name="save" class="btn btn-primary col-12" value="Update" />
                        </div>
                    </form>
                    </div>
			    </div>
			</div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>