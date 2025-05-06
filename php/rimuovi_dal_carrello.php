<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_auto'])) {
    $id_da_rimuovere = intval($_POST['id_auto']);

    if (isset($_SESSION['carrello']) && is_array($_SESSION['carrello'])) {
        // Trova la posizione dell'id nell'array
        $key = array_search($id_da_rimuovere, $_SESSION['carrello']);

        // Se trovato, lo rimuove
        if ($key !== false) {
            unset($_SESSION['carrello'][$key]);

            // Riordina gli indici dell'array
            $_SESSION['carrello'] = array_values($_SESSION['carrello']);
        }
    }
}

// Reindirizza al carrello
header("Location: carrello.php");
exit;
?>