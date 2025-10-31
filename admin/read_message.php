<?php
include "header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($id)) {
    echo '<meta http-equiv="refresh" content="0; url=messages.php">';
    exit;
}

// Use prepared statement for UPDATE
$stmt_update = mysqli_prepare($connect, "UPDATE `messages` SET viewed='Yes' WHERE id=?");
mysqli_stmt_bind_param($stmt_update, "i", $id);
mysqli_stmt_execute($stmt_update);
mysqli_stmt_close($stmt_update);

// Use prepared statement for SELECT
$stmt_select = mysqli_prepare($connect, "SELECT * FROM `messages` WHERE id=?");
mysqli_stmt_bind_param($stmt_select, "i", $id);
mysqli_stmt_execute($stmt_select);
$runq = mysqli_stmt_get_result($stmt_select);
$row = mysqli_fetch_assoc($runq);
mysqli_stmt_close($stmt_select);


if (!$row) {
    echo '<meta http-equiv="refresh" content="0; url=messages.php">';
    exit;
}
?>
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
		  <h3 class="h3"><i class="fas fa-envelope"></i> Messages</h3>
	  </div>

	  <div class="card">
		  <h6 class="card-header">Message</h6>
		  <div class="card-body">
				  
<?php
echo '
			<a href="messages.php" class="btn btn-secondary col-12 btn-sm">
				<i class="fa fa-arrow-left"></i> Back to Messages
			</a><br />
			
			<i class="fa fa-user"></i> Sender: <b>' . htmlspecialchars($row['name']) . '</b><br>
			<i class="fa fa-envelope"></i> E-Mail Address: <b>' . htmlspecialchars($row['email']) . '</b><br>
			<i class="fa fa-calendar-alt"></i> Date: <b>' . date($settings['date_format'] . ' H:i', strtotime($row['created_at'])) . '</b><br><br />
			<i class="fa fa-file"></i> Message:<br><b>' . nl2br(htmlspecialchars($row['content'])) . '</b><br><hr>
			  
			<div class="row">
				<div class="col-md-6">
					<a href="mailto:' . htmlspecialchars($row['email']) . '" class="btn btn-primary btn-sm col-12" target="_blank">
						<i class="fa fa-reply"></i> Reply
					</a>
				</div>
				<div class="col-md-6">
					<a href="messages.php?id=' . $row['id'] . '" class="btn btn-danger col-12 btn-sm">
						<i class="fa fa-trash"></i> Delete
					</a>
				</div>
			</div>
';
?>
		  </div>
	  </div>
<?php
include "footer.php";
?>