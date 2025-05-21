<?php
// Funzione: verifica se l'utente ha acquistato da uno specifico venditore
function utenteHaAcquistatoDaVenditore($db, $user_id, $seller_id) {
    $query = "SELECT 1 FROM transazione WHERE acquirente_id = $1 AND venditore_id = $2 LIMIT 1";
    $result = pg_query_params($db, $query, array($user_id, $seller_id));
    return ($result && pg_num_rows($result) > 0);
}
?>
