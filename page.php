<?php
include "core.php";
head();

if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

$slug = $_GET['name'];
if (empty($slug)) {
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
    exit;
}

$run = mysqli_query($connect, "SELECT * FROM `pages` WHERE slug='$slug' LIMIT 1");
if (mysqli_num_rows($run) == 0) {
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
    exit;
}

$row = mysqli_fetch_assoc($run);
?>

<div class="col-md-8 mb-3">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($row['title']); ?>
        </div>
        <div class="card-body">
           <?php echo html_entity_decode($row['content']); ?>
        </div>
    </div>
</div>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>