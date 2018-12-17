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
Well, Geoff forwarded me a copy of the DEC message, and I eat my words. I sure would have minded it! Nobody should be allowed to send a message with a header that long, no matter what it is about.

GNU, which stands for Gnu's Not Unix, is the name for the complete Unix-compatible software system which I am writing so that I can give it away free to everyone who can use it.

Hundreds of thousands of babies are born every day. While the whole phenomenon is menacing, one of them by itself is not newsworthy. Nor is it a difficult achievement — even some fish can do it. (Now, if you were a seahorse, it would be more interesting, since it would be the male that gave birth.) ...These birth announcements also spread the myth that having a baby is something to be proud of, which fuels natalist pressure, which leads to pollution, extinction of wildlife, poverty, and ultimately mass starvation.

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
* `relations` contains quote authors that appear in a given author's Wikipedia page. (ex: Plato and Hannibal appear as links in Alexander the Great's Wikipedia article, so they are included as `relations`.)
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
