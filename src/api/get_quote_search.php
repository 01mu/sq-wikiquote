<?php
/*
 * sq-wikiquote
 * github.com/01mu
 */

include "pdo.php";

$wikiquote->get_quote_search($_GET['quote'], $_GET['start'], $_GET['limit']);
