# sq-wikiquote
Get authors and quotes from wikiquote.org. Since user-edited Wikiquote pages are not formatted consistently some quotes are not included. There are over 100,000 quotes from over 7,000 authors.
## Usage
### Get quotes from an individual author:
```php
<?php
include_once 'sq-wikiquote.php';

$wikiquote = new wikiquote();

$quotes = $wikiquote->get_author_quotes('Richard Stallman');

for($i = 0; $i < count($quotes); $i++)
{
    print($quotes[$i] . "\n\n");
}
```
```
Well, Geoff forwarded me a copy of the DEC message, and I eat my words. (...)

GNU, which stands for Gnu's Not Unix, is the name for the complete  (...)

Hundreds of thousands of babies are born every day. While the whole (...)

...
```
### Get names by page "List_of_people_by_name,_C", etc.:
```php
<?php
include_once 'sq-wikiquote.php';

$wikiquote = new wikiquote();

$pages = $wikiquote->get_pages();
$c_names = $wikiquote->get_authors($pages[2]); // List_of_people_by_name,_C

for($i = 0; $i < count($c_names); $i++)
{
    print($c_names[$i] . "\n\n");
}
```
```
Melanie C

Vico C

Louis C.K.

Vince Cable

Nicola Cabibbo

...
```
### Write all quotes to a MySQL database:
Creates three tables (`quotes`, `authors`, and `relations`) and makes inserts.
* `quotes` contains quote length information, the quote's author, and the quote itself.
* `authors` contains the names of authors and their quote count.
* `relations` contains quote authors that appear in a given author's Wikipedia page. (ex: Plato and Hannibal appear as links in Alexander the Great's Wikipedia article, so they are included as `relations` for Alexander the Great.)
```php
<?php
include_once 'sq-wikiquote.php';

$wikiquote = new wikiquote();

$server = '';
$username = '';
$pw = '';
$db = '';

$wikiquote->conn($server, $username, $pw, $db);
$wikiquote->create_tables();
$wikiquote->write_db();
$wikiquote->write_relations();
```
```
Inserting 'Brooks Adams' quotes... Quotes inserted: 3.
Inserting 'Bryan Adams' quotes... Quotes inserted: 19.
Inserting 'Charles Francis Adams, Sr.' quotes... Quotes inserted: 3.
Inserting 'Charles Follen Adams' quotes... Quotes inserted: 1.
Inserting 'Douglas Adams' quotes... Quotes inserted: 37.
...
```
### API
Return quotes from the MySQL database.
* get_author_list(`$start`, `$limit`): Return random authors.
* get_author_search(`$author`, `$start`, `$limit`): Return authors matching query
* get_author_single(`$author`): Return specific author, quotes, and relations
* get_quote_random(`$start`, `$limit`): Return random quotes
* get_quote_search(`$quote`, `$start`, `$limit`): Return quotes matching query
