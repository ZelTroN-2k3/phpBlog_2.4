<?php
include "header.php";

if (isset($_GET['delete_bgrimg'])) {
	unlink('../' . $settings['background_image']);
	
    $settings['background_image'] = '';
	
	file_put_contents('../config_settings.php', '<?php $settings = ' . var_export($settings, true) . '; ?>');
	echo '<meta http-equiv="refresh" content="0;url=settings.php">';
    exit;
}


if (isset($_POST['save'])) {

    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---

	if (@$_FILES['background_image']['name'] != '') {
        $target_dir    = "uploads/other/";
        $target_file   = $target_dir . basename($_FILES["background_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["background_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Erreur !</h5>
                The file is not an image.
            </div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["background_image"]["size"] > 2000000) {
            echo '
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-exclamation-triangle"></i> Attention !</h5>
                Sorry, the image file size is too large. Limit: 2 MB.
            </div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            $location   = "../uploads/other/bgr_$new_string.$imageFileType";
            move_uploaded_file($_FILES["background_image"]["tmp_name"], $location);
            $image = 'uploads/other/bgr_' . $new_string . '.' . $imageFileType . '';
        }
    } else {
		$image = $settings['background_image'];	
	}
    
    // Si la validation du fichier image a échoué, ne pas sauver les autres paramètres.
    // Vous pouvez commenter cette ligne si vous voulez sauver le texte même en cas d'échec de l'image.
    if (!isset($uploadOk) || $uploadOk == 1) {
        // Sanitize all inputs before saving
        $settings['sitename'] 			= htmlspecialchars($_POST['sitename']);
        $settings['description']        = htmlspecialchars($_POST['description']);
        $settings['email']              = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $settings['gcaptcha_sitekey']   = htmlspecialchars($_POST['gcaptcha-sitekey']);
        $settings['gcaptcha_secretkey'] = htmlspecialchars($_POST['gcaptcha-secretkey']);
        $settings['head_customcode'] 	= base64_encode($_POST['head-customcode']); // Already base64 encoded, which is safe
        $settings['facebook']           = filter_var($_POST['facebook'], FILTER_VALIDATE_URL) ? $_POST['facebook'] : '';
        $settings['instagram']          = filter_var($_POST['instagram'], FILTER_VALIDATE_URL) ? $_POST['instagram'] : '';
        $settings['twitter']            = filter_var($_POST['twitter'], FILTER_VALIDATE_URL) ? $_POST['twitter'] : '';
        $settings['youtube']            = filter_var($_POST['youtube'], FILTER_VALIDATE_URL) ? $_POST['youtube'] : '';
        $settings['linkedin']           = filter_var($_POST['linkedin'], FILTER_VALIDATE_URL) ? $_POST['linkedin'] : '';
        
        // Whitelist validation for select inputs
        $allowed_comments = ['guests', 'registered'];
        $settings['comments'] = in_array($_POST['comments'], $allowed_comments) ? $_POST['comments'] : 'guests';

        $allowed_rtl = ['Yes', 'No'];
        $settings['rtl'] = in_array($_POST['rtl'], $allowed_rtl) ? $_POST['rtl'] : 'No';

        $allowed_date_formats = ['d.m.Y', 'm.d.Y', 'Y.m.d', 'd F Y', 'F j, Y', 'Y F j', 'd-m-Y', 'm-d-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
        $settings['date_format'] = in_array($_POST['date_format'], $allowed_date_formats) ? $_POST['date_format'] : 'd.m.Y';

        $allowed_layouts = ['Wide', 'Boxed'];
        $settings['layout'] = in_array($_POST['layout'], $allowed_layouts) ? $_POST['layout'] : 'Boxed';

        $allowed_latestposts = ['Enabled', 'Disabled'];
        $settings['latestposts_bar'] = in_array($_POST['latestposts_bar'], $allowed_latestposts) ? $_POST['latestposts_bar'] : 'Enabled';

        $allowed_sidebar = ['Left', 'Right'];
        $settings['sidebar_position'] = in_array($_POST['sidebar_position'], $allowed_sidebar) ? $_POST['sidebar_position'] : 'Right';

        $allowed_posts_per_row = ['2', '3'];
        $settings['posts_per_row'] = in_array($_POST['posts_per_row'], $allowed_posts_per_row) ? $_POST['posts_per_row'] : '3';

        $allowed_themes = ["Bootstrap 5", "Cerulean", "Cosmo", "Darkly", "Flatly", "Journal", "Litera", "Lumen", "Lux", "Materia", "Minty", "Morph", "Pulse", "Sandstone", "Simplex", "Sketchy", "Slate", "Solar", "Spacelab", "Superhero", "United", "Vapor", "Yeti", "Zephyr"];
        $settings['theme'] = in_array($_POST['theme'], $allowed_themes) ? $_POST['theme'] : 'Bootstrap 5';

        $settings['background_image']   = $image;
        
        file_put_contents('../config_settings.php', '<?php $settings = ' . var_export($settings, true) . '; ?>');
        echo '<meta http-equiv="refresh" content="0;url=settings.php">';
        exit;
    }
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-cogs"></i> Settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

		<div class="card card-primary card-outline">
			<div class="card-header">
                <h3 class="card-title">General Settings</h3>
            </div>
			<form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="card-body">
                    <div class="form-group">
						<label>Site Name</label>
						<input class="form-control" name="sitename" value="<?php
echo htmlspecialchars($settings['sitename']);
?>" type="text" required>
					</div>
					<div class="form-group">
						<label>Description</label>
						<textarea class="form-control" name="description" required><?php
echo htmlspecialchars($settings['description']);
?></textarea>
					</div>
					<div class="form-group">
						<label>Website's E-Mail Address</label>
						<input class="form-control" name="email" type="email" value="<?php
echo htmlspecialchars($settings['email']);
?>" required>
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>reCAPTCHA v2 Site Key:</label>
								<input class="form-control" name="gcaptcha-sitekey" value="<?php
echo htmlspecialchars($settings['gcaptcha_sitekey']);
?>" type="text" required>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>reCAPTCHA v2 Secret Key:</label>
								<input class="form-control" name="gcaptcha-secretkey" value="<?php
echo htmlspecialchars($settings['gcaptcha_secretkey']);
?>" type="text" required>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label>Custom code for < head > tag</label>
						<textarea name="head-customcode" class="form-control" rows="4" placeholder="For example: Google Analytics tracking code can be placed here"><?php
echo htmlspecialchars(base64_decode($settings['head_customcode']));
?></textarea>
					</div>
					<div class="form-group">
						<label>Facebook Profile</label>
						<input class="form-control" name="facebook" type="url" value="<?php
echo htmlspecialchars($settings['facebook']);
?>" type="text">
					</div>
					<div class="form-group">
						<label>Instagram Profile</label>
						<input class="form-control" name="instagram" type="url" value="<?php
echo htmlspecialchars($settings['instagram']);
?>" type="text">
					</div>
					<div class="form-group">
						<label>Twitter Profile</label>
						<input class="form-control" name="twitter" type="url" value="<?php
echo htmlspecialchars($settings['twitter']);
?>" type="text">
					</div>
					<div class="form-group">
						<label>Youtube Profile</label>
						<input class="form-control" name="youtube" type="url" value="<?php
echo htmlspecialchars($settings['youtube']);
?>" type="text">
					</div>
					<div class="form-group">
						<label>LinkedIn Profile</label>
						<input class="form-control" name="linkedin" type="url" value="<?php
echo htmlspecialchars($settings['linkedin']);
?>" type="text">
					</div>
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>RTL Content (Right-To-Left)</label>
								<select name="rtl" class="form-control" required>
									<option value="No" <?php
if ($settings['rtl'] == "No") {
	echo 'selected';
}
?>>No</option>
									<option value="Yes" <?php
if ($settings['rtl'] == "Yes") {
	echo 'selected';
}
?>>Yes</option>
								</select>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Comments Section</label>
								<select name="comments" class="form-control" required>
									<option value="guests" <?php
if ($settings['comments'] == "guests") {
	echo 'selected';
}
		?>>Registration not required</option>
									<option value="registered" <?php
if ($settings['comments'] == "registered") {
	echo 'selected';
}
?>>Registration required</option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Date Format</label>
								<select name="date_format" class="form-control" required>
									<option value="d.m.Y" <?php
if ($settings['date_format'] == "d.m.Y") {
	echo 'selected';
}
?>><?php echo date("d.m.Y"); ?></option>
									<option value="m.d.Y" <?php
if ($settings['date_format'] == "m.d.Y") {
	echo 'selected';
}
?>><?php echo date("m.d.Y"); ?></option>
									<option value="Y.m.d" <?php
if ($settings['date_format'] == "Y.m.d") {
	echo 'selected';
}
?>><?php echo date("Y.m.d"); ?></option>
									<option disabled>───────────</option>
									<option value="d F Y" <?php
if ($settings['date_format'] == "d F Y") {
	echo 'selected';
}
?>><?php echo date("d F Y"); ?></option>
									<option value="F j, Y" <?php
if ($settings['date_format'] == "F j, Y") {
	echo 'selected';
}
?>><?php echo date("F j, Y"); ?></option>
									<option value="Y F j" <?php
if ($settings['date_format'] == "Y F j") {
	echo 'selected';
}
?>><?php echo date("Y F j"); ?></option>
									<option disabled>───────────</option>
									<option value="d-m-Y" <?php
if ($settings['date_format'] == "d-m-Y") {
	echo 'selected';
}
?>><?php echo date("d-m-Y"); ?></option>
									<option value="m-d-Y" <?php
if ($settings['date_format'] == "m-d-Y") {
	echo 'selected';
}
?>><?php echo date("m-d-Y"); ?></option>
									<option value="Y-m-d" <?php
if ($settings['date_format'] == "Y-m-d") {
	echo 'selected';
}
?>><?php echo date("Y-m-d"); ?></option>
									<option disabled>───────────</option>
									<option value="d/m/Y" <?php
if ($settings['date_format'] == "d/m/Y") {
	echo 'selected';
}
?>><?php echo date("d/m/Y"); ?></option>
									<option value="m/d/Y" <?php
if ($settings['date_format'] == "m/d/Y") {
	echo 'selected';
}
?>><?php echo date("m/d/Y"); ?></option>
									<option value="Y/m/d" <?php
if ($settings['date_format'] == "Y/m/d") {
	echo 'selected';
}
?>><?php echo date("Y/m/d"); ?></option>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Layout</label>
								<select name="layout" class="form-control" required>
									<option value="Wide" <?php
if ($settings['layout'] == "Wide") {
	echo 'selected';
}
?>>Wide (Full-Sized)</option>
									<option value="Boxed" <?php
if ($settings['layout'] == "Boxed") {
	echo 'selected';
}
?>>Boxed</option>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Latest Posts bar</label>
								<select name="latestposts_bar" class="form-control" required>
									<option value="Enabled" <?php
if ($settings['latestposts_bar'] == "Enabled") {
	echo 'selected';
}
?>>Enabled</option>
									<option value="Disabled" <?php
if ($settings['latestposts_bar'] == "Disabled") {
	echo 'selected';
}
?>>Disabled</option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<div class="form-group">
								<label>Sidebar Position</label>
								<select name="sidebar_position" class="form-control" required>
									<option value="Left" <?php
if ($settings['sidebar_position'] == "Left") {
	echo 'selected';
}
?>>Left</option>
									<option value="Right" <?php
if ($settings['sidebar_position'] == "Right") {
	echo 'selected';
}
?>>Right</option>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
                                <label>Homepage posts per row</label>
								<select name="posts_per_row" class="form-control" required>
									<option value="2" <?php
if ($settings['posts_per_row'] == "2") {
	echo 'selected';
}
?>>2</option>
									<option value="3" <?php
if ($settings['posts_per_row'] == "3") {
	echo 'selected';
}
?>>3</option>
								</select>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								<label>Theme</label>
								<select class="form-control" name="theme" required>
<?php
$themes = array("Bootstrap 5", "Cerulean", "Cosmo", "Darkly", "Flatly", "Journal", "Litera", "Lumen", "Lux", "Materia", "Minty", "Morph", "Pulse", "Sandstone", "Simplex", "Sketchy", "Slate", "Solar", "Spacelab", "Superhero", "United", "Vapor", "Yeti", "Zephyr");
foreach ($themes as $design) {
	if ($settings['theme'] == $design) {
		$selected = 'selected';
	} else {
		$selected = '';
	}
	echo '<option value="'.$design.'" '.$selected.'>'.$design.'</option>';
}
?>
								</select>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label>Background Image</label>
                        <?php
						if ($settings['background_image'] != "") {
                            echo '<div class="row align-items-center mb-2">
                                <div class="col-md-4">
                                    <img src="../' . $settings['background_image'] . '" class="img-fluid" style="max-height: 120px;" />
                                </div>
                                <div class="col-md-8">
                                    <a href="?delete_bgrimg" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete Image
                                    </a>
                                </div>
                            </div>';
                        }
                        ?>
						<div class="input-group">
                            <div class="custom-file">
						        <input name="background_image" class="custom-file-input" type="file" id="formFile">
                                <label class="custom-file-label" for="formFile">Choose file</label>
                            </div>
                        </div>
					</div>
                </div>
                <div class="card-footer">
                    <input type="submit" name="save" class="btn btn-primary" value="Save" />
                </div>
			</form>                           
		</div>   
    </div></section>
<?php
include "footer.php";
?>