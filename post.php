<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}
?>
    <div class="col-md-8 mb-3">
<?php
$slug = $_GET['name'] ?? '';

if (empty($slug)) {
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit;
}

// Use prepared statement for SELECT
$stmt = mysqli_prepare($connect, "SELECT * FROM `posts` WHERE active='Yes' AND slug=?");
mysqli_stmt_bind_param($stmt, "s", $slug);
mysqli_stmt_execute($stmt);
$runq = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

if (mysqli_num_rows($runq) == 0) {
    echo '<meta http-equiv="refresh" content="0; url=blog">';
    exit;
}

// Use prepared statement for UPDATE
$stmt_update = mysqli_prepare($connect, "UPDATE `posts` SET views = views + 1 WHERE active='Yes' AND slug=?");
mysqli_stmt_bind_param($stmt_update, "s", $slug);
mysqli_stmt_execute($stmt_update);
mysqli_stmt_close($stmt_update);
$row         = mysqli_fetch_assoc($runq);
$post_id     = $row['id'];
$post_slug   = $row['slug'];
echo '
                    <div class="card shadow-sm bg-light">
                        <div class="col-md-12">
							';
if ($row['image'] != '') {
    echo '
        <img src="' . htmlspecialchars($row['image']) . '" width="100%" height="auto" alt="' . htmlspecialchars($row['title']) . '"/>
';
}
echo '
            <div class="card-body">
                
				<div class="mb-1">
					<i class="fas fa-chevron-right"></i> <a href="category?name=' . post_categoryslug($row['category_id']) . '">' . post_category($row['category_id']) . '</a>
				</div>
				
				<h5 class="card-title fw-bold">' . htmlspecialchars($row['title']) . '</h5>
				
				<div class="d-flex justify-content-between align-items-center">
					<small>
						Posted by <b><i><i class="fas fa-user"></i> ' . post_author($row['author_id']) . '</i></b> 
						on <b><i><i class="far fa-calendar-alt"></i> ' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '</i></b>
					</small>
					<small> 	
						<i class="fa fa-eye"></i> ' . $row['views'] . '
					</small>
					<small class="float-end">
						<i class="fa fa-comments"></i> <a href="#comments"><b>' . post_commentscount($row['id']) . '</b></a>
					</small>
				</div>
				<hr />
				
                ' . html_entity_decode($row['content']) . '
				<hr />
				
				<h5><i class="fas fa-share-alt-square"></i> Share</h5>
				<div id="share" style="font-size: 14px;"></div>
				<hr />

				<h5 class="mt-2" id="comments">
					<i class="fa fa-comments"></i> Comments (' . post_commentscount($row['id']) . ')
				</h5>
';
?>

<?php
// Use prepared statement for selecting comments
$stmt_comments = mysqli_prepare($connect, "SELECT * FROM comments WHERE post_id=? AND approved='Yes' ORDER BY id DESC");
mysqli_stmt_bind_param($stmt_comments, "i", $row['id']);
mysqli_stmt_execute($stmt_comments);
$q = mysqli_stmt_get_result($stmt_comments);
$count = mysqli_num_rows($q);
mysqli_stmt_close($stmt_comments);

if ($count <= 0) {
    echo '<div class="alert alert-info">There are no comments yet.</div>';
} else {
    while ($comment = mysqli_fetch_array($q)) {
        $aauthor_id = $comment['user_id'];
        $aauthor_name = 'Guest';
        
        if ($comment['guest'] == 'Yes') {
            $aavatar = 'assets/img/avatar.png';
            $arole   = '<span class="badge bg-secondary">Guest</span>';
        } else {
            // Use prepared statement to get user info
            $stmt_user = mysqli_prepare($connect, "SELECT * FROM `users` WHERE id=? LIMIT 1");
            mysqli_stmt_bind_param($stmt_user, "i", $aauthor_id);
            mysqli_stmt_execute($stmt_user);
            $querych = mysqli_stmt_get_result($stmt_user);
            
            if (mysqli_num_rows($querych) > 0) {
                $rowch = mysqli_fetch_assoc($querych);
                $aavatar = $rowch['avatar'];
                $aauthor_name = $rowch['username'];
                if ($rowch['role'] == 'Admin') {
                    $arole = '<span class="badge bg-danger">Administrator</span>';
                } elseif ($rowch['role'] == 'Editor') {
                    $arole = '<span class="badge bg-warning">Editor</span>';
                } else {
                    $arole = '<span class="badge bg-info">User</span>';
                }
            }
            mysqli_stmt_close($stmt_user);
        }
        
        echo '
		<div class="row d-flex justify-content-center bg-white rounded border mt-3 mb-3 ms-1 me-1">
			<div class="mb-2 d-flex flex-start align-items-center">
				<img class="rounded-circle shadow-1-strong mt-1 me-3"
					src="' . htmlspecialchars($aavatar) . '" alt="' . htmlspecialchars($aauthor_name) . '" 
					width="50" height="50" />
				<div class="mt-1 mb-1">
					<h6 class="fw-bold mt-1 mb-1">
						<i class="fa fa-user"></i> ' . htmlspecialchars($aauthor_name) . ' ' . $arole . '
					</h6>
					<p class="small mb-0">
						<i><i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($comment['date'])) . ', ' . $comment['time'] . '</i>
					</p>
				</div>
			</div>
			<hr class="my-0" />
			<p class="mt-1 mb-1 pb-1">
				' . emoticons(htmlspecialchars($comment['comment'])) . '
			</p>
		</div>
	';
    }
}
?>                                  
                    <h5 class="mt-4">Leave A Comment</h5>


<?php
$guest = 'No';

if ($logged == 'No' AND $settings['comments'] == 'guests') {
    $cancomment = 'Yes';
} else {
    $cancomment = 'No';
}
if ($logged == 'Yes') {
    $cancomment = 'Yes';
}

if ($cancomment == 'Yes') {
?>
                        <form name="comment_form" action="post?name=<?php
    echo $post_slug;
?>" method="post">
<?php
    if ($logged == 'No') {
        $guest = 'Yes';
?>
                        <label for="name"><i class="fa fa-user"></i> Name:</label>
                        <input type="text" name="author" value="" class="form-control" required />
                        <br />
<?php
    }
?>
                        <label for="comment"><i class="fa fa-comment"></i> Comment:</label>
                        <textarea name="comment" id="comment" rows="5" class="form-control" maxlength="1000" oninput="countText()" required></textarea>
						<label for="characters"><i>Characters left: </i></label>
						<span id="characters">1000</span><br>
						<br />
<?php
    if ($logged == 'No') {
        $guest = 'Yes';
?>
						<center><div class="g-recaptcha" data-sitekey="<?php
        echo $settings['gcaptcha_sitekey'];
?>"></div></center>
<?php
    }
?>
                        <input type="submit" name="post" class="btn btn-primary col-12" value="Post" />
            </form>
<?php
} else {
    echo '<div class="alert alert-info">Please <strong><a href="login"><i class="fas fa-sign-in-alt"></i> Sign In</a></strong> to be able to post a comment.</div>';
}

if ($cancomment == 'Yes') {
    if (isset($_POST['post'])) {
        
        $authname_problem = 'No';
        $date             = date($settings['date_format']);
        $time             = date('H:i');
		$comment          = $_POST['comment'];
		
		$captcha = '';
		
        if ($logged == 'No') {
            $author = $_POST['author'];
            
            $bot = 'Yes';
            if (isset($_POST['g-recaptcha-response'])) {
                $captcha = $_POST['g-recaptcha-response'];
            }
            if ($captcha) {
                $url          = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($settings['gcaptcha_secretkey']) . '&response=' . urlencode($captcha);
                $response     = file_get_contents($url);
                $responseKeys = json_decode($response, true);
                if ($responseKeys["success"]) {
                    $bot = 'No';
                }
            }
            
            if (strlen($author) < 2) {
                $authname_problem = 'Yes';
                echo '<div class="alert alert-warning">Your name is too short.</div>';
            }
        } else {
            $bot    = 'No';
            $author = $rowu['id'];
        }
        
        if (strlen($comment) < 2) {
            echo '<div class="alert alert-danger">Your comment is too short.</div>';
        } else {
            if ($authname_problem == 'No' AND $bot == 'No') {
                // Use prepared statement for INSERT
                $stmt = mysqli_prepare($connect, "INSERT INTO `comments` (`post_id`, `comment`, `user_id`, `date`, `time`, `guest`) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isssss", $row['id'], $comment, $author, $date, $time, $guest);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                echo '<div class="alert alert-success">Your comment has been successfully posted</div>';
                echo '<meta http-equiv="refresh" content="0;url=post?name=' . $row['slug'] . '#comments">';
            }
        }
    }
}
?>
                    </div>
                </div>
            </div>
        </div>
		
<script>
$("#share").jsSocials({
    showCount: false,
    showLabel: true,
    shares: [
        { share: "facebook", logo: "fab fa-facebook-square", label: "Share" },
        { share: "twitter", logo: "fab fa-twitter-square", label: "Tweet" },
        { share: "linkedin", logo: "fab fa-linkedin", label: "Share" },
		{ share: "email", logo: "fas fa-envelope", label: "E-Mail" }
    ]
});

function countText() {
	let text = document.comment_form.comment.value;
	
	document.getElementById('characters').innerText = 1000 - text.length;
	//document.getElementById('words').innerText = text.length == 0 ? 0 : text.split(/\s+/).length;
	//document.getElementById('rows').innerText = text.length == 0 ? 0 : text.split(/\n/).length;
}
</script>
<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>