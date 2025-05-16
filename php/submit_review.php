<?php
session_start();
require_once 'config.php';
require_once 'review_model.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Devi effettuare il login']));
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sellerId = filter_input(INPUT_POST, 'seller_id', FILTER_VALIDATE_INT);
    $carId = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $reviewText = filter_input(INPUT_POST, 'review_text', FILTER_SANITIZE_STRING);

    // Validate inputs
    if (!$sellerId || !$carId || !$rating || $rating < 1 || $rating > 5) {
        die(json_encode(['success' => false, 'message' => 'Dati di recensione non validi']));
    }

    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($db->connect_error) {
            throw new Exception("Connessione al database fallita: " . $db->connect_error);
        }

        $reviewModel = new ReviewModel($db);
        $result = $reviewModel->submitReview(
            $sellerId, 
            $_SESSION['user_id'], 
            $carId, 
            $rating, 
            $reviewText
        );

        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Recensione inviata con successo!'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Impossibile inviare la recensione'
            ]);
        }

        $db->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
}
?>
