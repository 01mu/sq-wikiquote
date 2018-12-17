<?php
/*
 * sq-wikiquote
 * github.com/01mu
 */

include "pdo.php";

$wikiquote->get_author_single($_GET['author']);
