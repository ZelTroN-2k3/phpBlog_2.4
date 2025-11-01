<?php
// Inclure le cœur de l'application pour accéder à la base de données et aux fonctions
include "core.php";

// Définir l'en-tête de la réponse comme JSON
header('Content-Type: application/json');

// --- NOUVEL AJOUT : Validation CSRF ---
// Valider le jeton AVANT de traiter toute donnée
validate_csrf_token();
// --- FIN AJOUT ---

// Initialiser le tableau de réponse
$response = [
    'success' => false,
    'message' => 'Une erreur inconnue est survenue.',
    'html' => '',
    'parent_id' => 0
];

// --- Validation des données ---
// 1. Vérifier si les données POST existent
if (!isset($_POST['comment'], $_POST['parent_id'], $_POST['post_id'])) {
    $response['message'] = 'Données de formulaire manquantes.';
    echo json_encode($response);
    exit;
}

// 2. Nettoyer les données
$comment   = $_POST['comment'];
$parent_id = (int)$_POST['parent_id'];
$post_id   = (int)$_POST['post_id'];
$guest     = 'No';

// 3. Vérifier si l'utilisateur peut commenter
$cancomment = 'No';
if ($logged == 'No' && $settings['comments'] == 'guests') {
    $cancomment = 'Yes';
} elseif ($logged == 'Yes') {
    $cancomment = 'Yes';
}

if ($cancomment == 'No') {
    $response['message'] = 'Vous devez être connecté pour commenter.';
    echo json_encode($response);
    exit;
}

// 4. Gérer l'auteur (Invité ou Membre)
$authname_problem = 'No';
if ($logged == 'No') {
    $guest  = 'Yes';
    $author = $_POST['author'] ?? ''; // Nom de l'invité
    
    // 4a. Vérifier le reCAPTCHA pour les invités
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    if (empty($captcha)) {
        $response['message'] = 'Veuillez compléter le reCAPTCHA.';
        echo json_encode($response);
        exit;
    }
    
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($settings['gcaptcha_secretkey']) . '&response=' . urlencode($captcha);
    $recaptcha_response = file_get_contents($url);
    $responseKeys = json_decode($recaptcha_response, true);
    
    if (!$responseKeys["success"]) {
        $response['message'] = 'Échec de la vérification reCAPTCHA.';
        echo json_encode($response);
        exit;
    }
    
    // 4b. Vérifier le nom de l'invité
    if (strlen($author) < 2) {
        $authname_problem = 'Yes';
        $response['message'] = 'Votre nom est trop court.';
    }
} else {
    $author = $rowu['id']; // ID de l'utilisateur connecté
}

// 5. Vérifier la longueur du commentaire
if (strlen($comment) < 2) {
    $response['message'] = 'Votre commentaire est trop court.';
    echo json_encode($response);
    exit;
}

// 6. Vérifier s'il y a eu un problème avec le nom de l'auteur
if ($authname_problem == 'Yes') {
    echo json_encode($response);
    exit;
}

// --- Insertion dans la base de données ---
// Toutes les vérifications sont passées, on insère le commentaire
$stmt = mysqli_prepare($connect, "INSERT INTO `comments` (`post_id`, `parent_id`, `comment`, `user_id`, `guest`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())");
mysqli_stmt_bind_param($stmt, "iisss", $post_id, $parent_id, $comment, $author, $guest);

if (mysqli_stmt_execute($stmt)) {
    $new_comment_id = mysqli_insert_id($connect);
    mysqli_stmt_close($stmt);

    // Calculer la marge pour l'affichage de la réponse
    $margin_left = 0;
    if ($parent_id > 0) {
        // Obtenir le niveau du parent pour déterminer le niveau de l'enfant
        $stmt_level = mysqli_prepare($connect, "SELECT * FROM comments WHERE id = ?");
        mysqli_stmt_bind_param($stmt_level, "i", $parent_id);
        mysqli_stmt_execute($stmt_level);
        $parent_comment = mysqli_stmt_get_result($stmt_level);
        
        $level = 1; // Par défaut, niveau 1 si le parent est 0 (ce qui ne devrait pas arriver ici)
        
        // Boucle pour trouver le niveau racine
        if (mysqli_num_rows($parent_comment) > 0) {
            $parent_data = mysqli_fetch_assoc($parent_comment);
            $current_parent_id = $parent_data['parent_id'];
            $level = 1; // Commence au niveau 1 (réponse directe)
            while ($current_parent_id > 0 && $level < 5) {
                $stmt_parent_check = mysqli_prepare($connect, "SELECT parent_id FROM comments WHERE id = ?");
                mysqli_stmt_bind_param($stmt_parent_check, "i", $current_parent_id);
                mysqli_stmt_execute($stmt_parent_check);
                $parent_result = mysqli_stmt_get_result($stmt_parent_check);
                $parent_row = mysqli_fetch_assoc($parent_result);
                $current_parent_id = $parent_row['parent_id'];
                $level++;
                mysqli_stmt_close($stmt_parent_check);
            }
        }
        mysqli_stmt_close($stmt_level);
        
        $margin_left = ($level > 5) ? (5 * 30) : ($level * 30);
    }

    // Générer le HTML du nouveau commentaire
    $response['success'] = true;
    $response['message'] = 'Commentaire publié !';
    $response['html'] = render_comment_html($new_comment_id, $margin_left);
    $response['parent_id'] = $parent_id;

} else {
    // Erreur lors de l'insertion
    $response['message'] = 'Erreur lors de l\'enregistrement du commentaire.';
}

// Envoyer la réponse JSON finale
echo json_encode($response);
exit;

?>