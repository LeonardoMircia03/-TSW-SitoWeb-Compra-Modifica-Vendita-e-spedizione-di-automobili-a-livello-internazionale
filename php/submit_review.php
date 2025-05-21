<?php
session_start();
require_once 'config.php';
require_once 'review_model.php';
require_once 'utils_acquisti.php';

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
        // Usa la connessione già creata in config.php
        global $dbconnect;
        if (!$dbconnect) {
            throw new Exception("Connessione al database fallita");
        }

        // Controllo: l'utente ha acquistato da questo venditore?
        if (!utenteHaAcquistatoDaVenditore($dbconnect, $_SESSION['user_id'], $sellerId)) {
            echo "<script>alert('Puoi recensire solo venditori da cui hai acquistato.'); window.location.href='profilo.php';</script>";
            exit;
        }

        $reviewModel = new ReviewModel($dbconnect);
        $result = $reviewModel->submitReview(
            $sellerId, 
            $_SESSION['user_id'], 
            $carId, 
            $rating, 
            $reviewText
        );

        if ($result) {
            header('Location: profilo.php?review=success');
            exit;
        } else {
            echo "<script>alert('Impossibile inviare la recensione. Riprova.'); window.location.href='profilo.php';</script>";
            exit;
        }
    
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
