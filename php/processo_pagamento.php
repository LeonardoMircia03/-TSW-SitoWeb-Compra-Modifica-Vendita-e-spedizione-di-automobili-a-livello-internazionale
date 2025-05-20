<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica i dati del pagamento
if (
    empty($_POST['carta']) ||
    empty($_POST['scadenza']) ||
    empty($_POST['cvv'])
) {
    header("Location: carrello.php");
    exit;
}

$utente_id = $_SESSION['user_id'];

// Verifica se ci sono auto nel carrello
$query_carrello = "
    SELECT c.auto_id, a.utente_id, a.prezzo 
    FROM carrello c 
    JOIN auto a ON c.auto_id = a.id 
    WHERE c.utente_id = $1
";
$result_carrello = pg_query_params($dbconnect, $query_carrello, array($utente_id));

if (!$result_carrello || pg_num_rows($result_carrello) == 0) {
    die("Nessuna auto trovata nel carrello. Riprova.");
}

// Reindirizza a una pagina di conferma
header("Location: pagamento_successo.php?success=1");
exit;
?>
