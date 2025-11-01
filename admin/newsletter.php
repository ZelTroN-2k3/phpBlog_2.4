<?php
include "header.php";

if (isset($_GET['unsubscribe'])) {
	$unsubscribe_email = $_GET['unsubscribe'];

    // Use prepared statement for DELETE
    $stmt = mysqli_prepare($connect, "DELETE FROM `newsletter` WHERE email=?");
    mysqli_stmt_bind_param($stmt, "s", $unsubscribe_email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Rediriger pour nettoyer l'URL
    echo '<meta http-equiv="refresh" content="0; url=newsletter.php">';
    exit;
}
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="far fa-envelope"></i> Newsletter</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active">Newsletter</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<section class="content">
    <div class="container-fluid">

        <div class="card card-primary card-outline">
			<div class="card-header">
                <h3 class="card-title">Send mass message</h3>
            </div>         
			<form action="" method="post">
			<div class="card-body">
<?php
if (isset($_POST['send_mass_message'])) {
    // --- NOUVEL AJOUT : Validation CSRF ---
    validate_csrf_token();
    // --- FIN AJOUT ---
    
    $title    = addslashes($_POST['title']);
    $content  = htmlspecialchars($_POST['content']);

    $from     = $settings['email'];
    $sitename = $settings['sitename'];
	
    $run2 = mysqli_query($connect, "SELECT * FROM `newsletter`");
    while ($row = mysqli_fetch_assoc($run2)) {
		
        $to = $row['email'];
		
        $subject = $title;
        
        $message = '
<html>
<body>
  <b><h1><a href="' . $settings['site_url'] . '/" title="Visit the website">' . $settings['sitename'] . '</a></h1><b/>
  <br />

  ' . html_entity_decode($content) . '
  
  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . $settings['site_url'] . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';
        
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

        $headers .= 'From: ' . $from . '';
        
        @mail($to, $subject, $message, $headers);
    }
    
    echo '
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            Your global message has been sent successfully.
        </div>';
}
?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
					<label>Title</label>
					<input class="form-control" name="title" value="" type="text" required>
				</div>
				<div class="form-group">
					<label>Content</label>
					<textarea class="form-control" id="summernote" name="content" required></textarea>
				</div>
            </div>
            <div class="card-footer">
				<input type="submit" name="send_mass_message" class="btn btn-primary" value="Send" />
			</div>
			</form>
        </div>
			
        <div class="card">
			<div class="card-header">
                <h3 class="card-title">Subscribers List</h3>
            </div>         
            <div class="card-body">
                <table class="table table-bordered table-hover" id="dt-basic" width="100%">
                    <thead>
                        <tr>
                            <th>E-Mail</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
$query = mysqli_query($connect, "SELECT * FROM newsletter ORDER BY email ASC");
while ($row = mysqli_fetch_assoc($query)) {
    echo '
                        <tr>
                            <td>' . htmlspecialchars($row['email']) . '</td>
                            <td>
                                <a href="?unsubscribe=' . urlencode($row['email']) . '&token=' . $csrf_token . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Êtes-vous sûr de vouloir désabonner cet e-mail ?\');"><i class="fas fa-bell-slash"></i> Unsubscribe</a>
                            </td>
                        </tr>
';
}
?>
                    </tbody>
                </table>
            </div>
        </div>

    </div></section>
<script>
$(document).ready(function() {
    // Activation de DataTables avec ordre par défaut ascendant (colonne 0: Email)
	$('#dt-basic').DataTable({
        "responsive": true,
        "lengthChange": false, 
        "autoWidth": false,
		"order": [[ 0, "asc" ]] 
	});
    // Note : Summernote est initialisé dans footer.php via une vérification
});
</script>
<?php
include "footer.php";
?>