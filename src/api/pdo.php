<?php
/*
 * sq-wikiquote
 * github.com/01mu
 */

include_once '../sq-wikiquote.php';

$server = '';
$username = '';
$pw = '';
$db = '';

$wikiquote = new wikiquote();
$wikiquote->conn($server, $username, $pw, $db);
