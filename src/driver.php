<?php

/*
 * sq-wikiquote
 * github.com/01mu
 */

include_once 'sq-wikiquote.php';

$wikiquote = new wikiquote();

$pages = $wikiquote->get_pages();
$c_names = $wikiquote->get_authors($pages[2]); // List_of_people_by_name,_C

for($i = 0; $i < count($c_names); $i++)
{
    print($c_names[$i] . "\n");
}
