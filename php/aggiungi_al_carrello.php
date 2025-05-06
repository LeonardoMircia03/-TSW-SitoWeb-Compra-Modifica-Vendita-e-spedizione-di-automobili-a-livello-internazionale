<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_auto'])) {
    $id_auto = intval($_POST['id_auto']);

    // Inizializza l'array del carrello se non esiste
    if (!isset($_SESSION['carrello'])) {
        $_SESSION['carrello'] = [];
    }

    // Aggiungi l'auto al carrello (se non è già presente)
    if (!in_array($id_auto, $_SESSION['carrello'])) {
        $_SESSION['carrello'][] = $id_auto;
        echo "<script>alert('Auto aggiunta al carrello!');</script>";
    } else {
        echo "<script>alert('Questa auto è già nel carrello.');</script>";
    }
}

// Torna alla pagina di ricerca
header("Location: auto.php");
exit;
?>