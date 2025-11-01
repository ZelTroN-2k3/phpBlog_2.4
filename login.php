<?php
include "core.php";
head();

if ($logged == 'Yes') {
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
    exit;
}

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

$error = 0;

// --- NOUVEL AJOUT : Initialisation du Rate Limiting ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['login_lockout_time'])) {
    $_SESSION['login_lockout_time'] = 0;
}
// --- FIN AJOUT ---

?>
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-plus"></i> Membership</div>
                <div class="card-body">

                    <div class="row">
						<div class="col-md-6 mb-4">
							<h5><i class="fas fa-sign-in-alt"></i> Sign In</h5><hr />
<?php
// --- NOUVEL AJOUT : Vérification du blocage ---
$is_locked_out = false;
if ($_SESSION['login_lockout_time'] > time()) {
    $is_locked_out = true;
    $time_remaining = ceil(($_SESSION['login_lockout_time'] - time()) / 60);
    echo '
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> Vous avez échoué trop de fois. Veuillez réessayer dans ' . $time_remaining . ' minute(s).
    </div>';
    $error = 1; // Pour désactiver le formulaire
}
// --- FIN AJOUT ---


if (isset($_POST['signin']) && !$is_locked_out) { // Ne traiter que si non bloqué
    
    // --- Validation CSRF ---
    validate_csrf_token();
    // --- FIN ---
    
    $username = $_POST['username'];
    $password_plain = $_POST['password']; // Mot de passe en clair
    
    // 1. Récupérer le hash du mot de passe pour cet utilisateur
    $stmt = mysqli_prepare($connect, "SELECT username, password FROM `users` WHERE `username`=?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user_row = mysqli_fetch_assoc($result);
        $hashed_password = $user_row['password'];

        // 2. Vérifier le mot de passe en clair contre le hash
        if (password_verify($password_plain, $hashed_password)) {
            // Le mot de passe est correct !
            
            // --- NOUVEL AJOUT : Réinitialiser le compteur ---
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_lockout_time'] = 0;
            // --- FIN AJOUT ---
            
            $_SESSION['sec-username'] = $username;
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
        } else {
            // Mot de passe incorrect
            
            // --- NOUVEL AJOUT : Logique d'échec Rate Limiting ---
            $_SESSION['login_attempts']++;
            $attempts_remaining = 5 - $_SESSION['login_attempts'];
            
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lockout_time'] = time() + 300; // Bloquer pour 5 minutes (300 secondes)
                echo '
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Vous avez échoué 5 fois. Veuillez réessayer dans 5 minutes.
                </div>';
            } else {
                 echo '
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Le <strong>nom d\'utilisateur</strong> ou <strong>mot de passe</strong> est incorrect.<br>
                    Il vous reste ' . $attempts_remaining . ' tentative(s).
                </div>';
            }
            // --- FIN AJOUT ---
            
            $error = 1;
        }
    } else {
        // Utilisateur non trouvé (on le compte aussi comme un échec)
        
        // --- NOUVEL AJOUT : Logique d'échec Rate Limiting ---
        $_SESSION['login_attempts']++;
        $attempts_remaining = 5 - $_SESSION['login_attempts'];

        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_lockout_time'] = time() + 300; // Bloquer pour 5 minutes
             echo '
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Vous avez échoué 5 fois. Veuillez réessayer dans 5 minutes.
            </div>';
        } else {
            echo '
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Le <strong>nom d\'utilisateur</strong> ou <strong>mot de passe</strong> est incorrect.<br>
                Il vous reste ' . $attempts_remaining . ' tentative(s).
            </div>';
        }
        // --- FIN AJOUT ---
        
        $error = 1;
    }
}
?> 
		<form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="input-group mb-3 needs-validation <?php
if ($error == 1) {
    echo 'is-invalid';
}
?>">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="username" name="username" class="form-control" placeholder="Username" <?php
if ($error == 1) {
    echo 'autofocus';
}
?> required <?php if ($is_locked_out) echo 'disabled'; ?>>
            </div>
            <div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" required <?php if ($is_locked_out) echo 'disabled'; ?>>
            </div>

            <button type="submit" name="signin" class="btn btn-primary col-12" <?php if ($is_locked_out) echo 'disabled'; ?>><i class="fas fa-sign-in-alt"></i>
&nbsp;Sign In</button>

        </form> 
					</div>
					
					<div class="col-md-6">
						<h5><i class="fas fa-user-plus"></i> Registration</h5><hr />
                <?php
if (isset($_POST['register'])) {
    
    // --- Validation CSRF ---
    validate_csrf_token();
    // --- FIN ---
    
    $username = $_POST['username'];
    // MODIFICATION : Utiliser password_hash()
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email    = $_POST['email'];
    $captcha  = '';
    if (isset($_POST['g-recaptcha-response'])) {
        $captcha = $_POST['g-recaptcha-response'];
    }
    if ($captcha) {
        $url          = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($settings['gcaptcha_secretkey']) . '&response=' . urlencode($captcha);
        $response     = file_get_contents($url);
        $responseKeys = json_decode($response, true);
        if ($responseKeys["success"]) {
            
            // Use prepared statement for username check
            $stmt = mysqli_prepare($connect, "SELECT username FROM `users` WHERE username=?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);

            if (mysqli_num_rows($result) > 0) {
                echo '<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> The username is taken.</div>';
            } else {
                
                // Use prepared statement for email check
                $stmt = mysqli_prepare($connect, "SELECT email FROM `users` WHERE email=?");
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result2 = mysqli_stmt_get_result($stmt);
                mysqli_stmt_close($stmt);

                if (mysqli_num_rows($result2) > 0) {
                    echo '<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> The E-Mail Address is taken</div>';
                } else {
                    // Use prepared statement for user insert
                    $stmt = mysqli_prepare($connect, "INSERT INTO `users` (`username`, `password`, `email`) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "sss", $username, $password, $email);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Use prepared statement for newsletter insert
                    $stmt = mysqli_prepare($connect, "INSERT INTO `newsletter` (`email`) VALUES (?)");
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    $subject = 'Welcome at ' . $settings['sitename'] . '';
                    $message = '<a href="' . $settings['site_url'] . '" title="Visit ' . $settings['sitename'] . '" target="_blank">
                                    <h4>' . $settings['sitename'] . '</h4>
                                </a><br />

                                <h5>You have successfully registered at ' . $settings['sitename'] . '</h5><br /><br />

                                <b>Registration details:</b><br />
                                Username: <b>' . htmlspecialchars($username) . '</b>';
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                    $headers .= 'To: ' . $email . ' <' . $email . '>' . "\r\n";
                    $headers .= 'From: ' . $settings['email'] . ' <' . $settings['email'] . '>' . "\r\n";
                    @mail($email, $subject, $message, $headers);
                    
                    $_SESSION['sec-username'] = $username;
                    echo '<meta http-equiv="refresh" content="0;url=profile">';
                }
            }
        }
    }
}
?>
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="username" name="username" class="form-control" placeholder="Username" required>
            </div>
			<div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="E-Mail Address" required>
            </div>
            <div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
			<center><div class="g-recaptcha" data-sitekey="<?php
echo $settings['gcaptcha_sitekey'];
?>"></div></center>

            <button type="submit" name="register" class="btn btn-primary col-12 mt-2"><i class="fas fa-sign-in-alt"></i>
&nbsp;Sign Up</button>
        </form> 
		
					</div>
				</div>								
            </div>
        </div>
    </div>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>