<?php
/*
 * sq-wikiquote
 * github.com/01mu
 */

class wikiquote
{
    private $context;
    private $conn;

    private function print_json($json, $relations)
    {
        if(count($json) == 0)
        {
           echo json_encode([['Response' => 'Empty']]);
        }
        else
        {
            $end = array();

            $end[0] = ['Response' => 'Good'];
            $end[1] = $json;
            $end[2] = $relations;

            echo json_encode($end);
        }
    }

    public function get_quote_search($quote, $start, $limit)
    {
        $sql = 'SELECT quote, author FROM quotes WHERE quote ' .
            'LIKE :quote ORDER BY quote_string_length ASC LIMIT :start, :limit';

        $json = array();
        $limits = [50, 100, 250, 500];

        if(!isset($quote) || !isset($start) || !isset($limit))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        if(!in_array($limit, $limits))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        $quote = '%' . $quote . '%';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':quote', $quote);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $json[] = ['author' => $result['author'],
                'quote' => $result['quote']];
        }

        $this->print_json($json, '');
    }

    public function get_quote_random($start, $limit)
    {
        $sql = 'SELECT quote, author FROM quotes ' .
            'ORDER BY RAND() LIMIT :start, :limit';

        $json = array();
        $limits = [50, 100, 250, 500];

        if(!in_array($limit, $limits))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $json[] = ['author' => $result['author'],
                'quote' => $result['quote']];
        }

        $this->print_json($json, '');
    }

    public function get_author_search($author, $start, $limit)
    {
        $sql = 'SELECT author FROM authors WHERE author ' .
            'LIKE :author AND quotes_total > 0 ORDER BY quotes_length ' .
            'DESC LIMIT :start, :limit';

        $json = array();
        $limits = [50, 100, 250, 500];

        if(!isset($author) || !isset($start) || !isset($limit))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        if(!in_array($limit, $limits))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        $author = '%' . $author . '%';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $json[] = $result['author'];
        }

        $this->print_json($json, '');
    }

    public function get_author_single($author)
    {
        $sql_q = 'SELECT quote, author FROM quotes ' .
            'WHERE author = :author ORDER BY quote_word_count ASC';

        $sql_r = 'SELECT relation FROM relations WHERE ' .
            'author = :author ORDER BY RAND()';

        $json = array();
        $relations = array();

        $stmt = $this->conn->prepare($sql_q);
        $stmt->bindParam(':author', $author);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if(!$results)
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        foreach($results as $result)
        {
            $json[] = ['quote' => $result['quote'],
                'author' => $result['author']];
        }

        $stmt = $this->conn->prepare($sql_r);
        $stmt->bindParam(':author', $author);
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $relations[] = $result['relation'];
        }

        $this->print_json($json, $relations);
    }

    public function get_author_list($start, $limit)
    {
        $sql = 'SELECT author FROM authors ' .
            'WHERE quotes_total > 0 ORDER BY RAND() LIMIT :start, :limit';

        $json = array();
        $limits = [50, 100, 250, 500];

        if(!isset($limit) || !isset($start))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        if(!in_array($limit, $limits))
        {
            echo json_encode([['Response' => 'Error']]);
            return;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $json[] = $result['author'];
        }

        $this->print_json($json, '');
    }

    public function write_relations()
    {
        $authors = array();

        $sql = 'SELECT author FROM authors WHERE quotes_total > 0';
        $sql_r = 'INSERT INTO relations (author, relation) VALUES (?, ?)';

        $stmt = $this->conn->query($sql);
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $authors[] = $result['author'];
        }

        foreach($authors as $author)
        {
            $relations = array();

            $links = $this->get_relations($author);

            printf("Finding relations for '" . $author . "'... ");

            foreach($links as $link)
            {
                if(in_array($link, $authors))
                {
                    $relations[] = $link;
                }
            }

            foreach($relations as $relation)
            {
                $stmt = $this->conn->prepare($sql_r);
                $stmt->execute([$author, $relation]);
            }

            printf("Count: " . count($relations) . ".\n");
        }
    }

    private function write_quotes($page, $author, $quotes)
    {
        $sql_q = 'INSERT INTO quotes (author, quote, quote_word_count, ' .
            'quote_string_length) VALUES (?, ?, ?, ?)';

        $sql_a = 'INSERT INTO authors (author, quotes_total, quotes_length, ' .
            'quotes_word_count, source_page) VALUES (?, ?, ?, ?, ?)';

        $q_count = count($quotes);
        $q_len = 0;
        $q_wc = 0;

        printf("Inserting '" . $author . "' quotes... ");

        foreach($quotes as $quote)
        {
            $wc = substr_count($quote, ' ') + 1;
            $len = strlen($quote);

            $stmt= $this->conn->prepare($sql_q);
            $stmt->execute([$author, utf8_encode($quote), $wc, $len]);

            $q_wc += $wc;
            $q_len += $len;
        }

        $stmt = $this->conn->prepare($sql_a);
        $stmt->execute([$author, $q_count, $q_len, $q_wc, $page]);

        printf("Quotes inserted: " . $q_count . ".\n");
    }

    public function write_db()
    {
        $pages = $this->get_pages();

        foreach($pages as $page)
        {
            $authors = $this->get_authors($page);

            foreach($authors as $author)
            {
                $quotes = $this->get_author_quotes($author);

                $this->write_quotes($page, $author, $quotes);
            }
        }
    }

    public function create_tables()
    {
        $tables = ['"quotes"', '"authors"', '"relations"'];

        foreach($tables as $table)
        {
            $query = 'SHOW TABLES LIKE ' . $table;
            $result = $this->conn->query($query)->fetchAll();
            $exists = count($result);

            if(!$exists)
            {
                switch($table)
                {
                    case '"quotes"':
                        $sql = "CREATE TABLE quotes (
                            id INT(8) AUTO_INCREMENT PRIMARY KEY,
                            author VARCHAR(255) NOT NULL,
                            quote LONGTEXT NOT NULL,
                            quote_word_count VARCHAR(255) NOT NULL,
                            quote_string_length VARCHAR(255) NOT NULL
                            )";
                        printf("Table 'quotes' created.\n");
                        break;
                    case '"authors"':
                        $sql = "CREATE TABLE authors (
                            id INT(8) AUTO_INCREMENT PRIMARY KEY,
                            author VARCHAR(255) NOT NULL,
                            quotes_total INT(8) NOT NULL,
                            quotes_length INT(8) NOT NULL,
                            quotes_word_count INT(8) NOT NULL,
                            source_page VARCHAR(255) NOT NULL
                            )";
                        printf("Table 'authors' created.\n");
                        break;
                    case '"relations"':
                        $sql = "CREATE TABLE relations (
                            id INT(8) AUTO_INCREMENT PRIMARY KEY,
                            author VARCHAR(255) NOT NULL,
                            relation VARCHAR(255) NOT NULL
                            )";
                        printf("Table 'relations' created.\n");
                        break;
                    default: break;
                }

                $this->conn->exec($sql);
            }
        }
    }

    public function conn($server, $user, $pw, $db)
    {
        try
        {
            $conn = new PDO("mysql:host=$server;dbname=$db", $user, $pw);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e)
        {
            echo "Error: " . $e->getMessage();
        }

        $this->conn = $conn;
    }

    public function get_pages()
    {
        $pages = ['List_of_people_by_name,_A', 'List_of_people_by_name,_B',
            'List_of_people_by_name,_C', 'List_of_people_by_name,_D',
            'List_of_people_by_name,_E–F', 'List_of_people_by_name,_G',
            'List_of_people_by_name,_H', 'List_of_people_by_name,_I–J',
            'List_of_people_by_name,_K', 'List_of_people_by_name,_L',
            'List_of_people_by_name,_M', 'List_of_people_by_name,_N-O',
            'List_of_people_by_name,_P', 'List_of_people_by_name,_Q–R',
            'List_of_people_by_name,_S', 'List_of_people_by_name,_T–V',
            'List_of_people_by_name,_W–Z'];

        return $pages;
    }

    public function get_author_quotes($author)
    {
        $quotes_str = $this->get_quotes_string($author);
        $quotes_str_len = strlen($quotes_str) - 1;

        $quotes_begin = strpos($quotes_str, "==");
        $quotes_end =  $this->get_bottom_delimiter($quotes_str);

        $quotes_str = substr($quotes_str, $quotes_begin, $quotes_end);
        $quotes_str = $quotes_str . "\n";
        $quotes_str_len = strlen($quotes_str);

        $quotes_str = $this->clear_quote_links($quotes_str, $quotes_str_len);
        $quotes_str = $this->prettify_text($quotes_str);

        return $this->get_quotes($quotes_str, $author);;
    }

    public function get_authors($page)
    {
        $name_str = $this->get_authors_string($page);
        $name_str_len = strlen($name_str);
        $name_str = $this->clear_author_links($name_str, $name_str_len);
        $authors = $this->get_author_names($name_str);

        return $authors;
    }

    public function wikiquote()
    {
        $this->agent = 'github.com/01mu/sq-wikiquote';
        $this->context = stream_context_create(
            array("http" => array("header" => $this->agent)));
    }

    public function get_relations($author)
    {
        $page = $this->get_wikipedia_string($author);

        return $this->get_wikipedia_links($page);
    }

    private function get_wikipedia_string($redirect)
    {
        $quotes;

        $redirect = str_replace(" ", "_", $redirect);

        $url = 'https://en.wikipedia.org/w/api.php?action=query' .
            '&titles=' . $redirect . '&prop=revisions&rvprop=content' .
            '&format=json&formatversion=2';

        $data = file_get_contents($url, false, $this->context);
        $wiki = json_decode($data, true);

        if(isset($wiki['query']['pages'][0]['missing']))
        {
            $quotes = 'err_page_missing';
        }
        else
        {
            $quotes = $wiki['query']['pages'][0]['revisions'][0]['content'];
        }

        return $quotes;
    }

    private function get_wikipedia_links($article_str)
    {
        $links = array();

        $link_start_idx;
        $link_end_idx;
        $link_size;

        $link_set = false;

        $size = strlen($article_str) - 1;
        $index = 0;

        while($index != $size)
        {
            $fidx = $article_str[$index];
            $sidx = $article_str[$index + 1];

            if($fidx == '[' && $sidx == '[')
            {
                $link_start_idx = $index + 2;
                $link_set = true;
            }

            if($fidx == ']' && $sidx == ']' && $link_set == true)
            {
                $link_end_idx = $index;
                $link_size = $link_end_idx - $link_start_idx;
                $found_link = substr($article_str, $link_start_idx, $link_size);

                if(!in_array($found_link, $links))
                {
                    $links[] = $found_link;
                }

                $link_set = false;
            }

            $index++;
        }

        return $links;
    }

    private function get_quotes_string($author)
    {
        ini_set('user_agent', 'github.com/01mu/sq-wikiquote');

        $author = str_replace(" ", "_", $author);

        $url = 'https://en.wikiquote.org/w/api.php?action=query&titles=' .
            $author . '&prop=revisions&rvprop=content&format=json';

        $data = file_get_contents($url, false);
        $wiki = json_decode($data, true);

        $quote = sizeof($wiki['query']['pages']);

        foreach($wiki['query']['pages'] as $key => $item)
        {
            $id = $key;
        }

        return $wiki['query']['pages'][$id]['revisions'][0]['*'];
    }

    private function get_quotes_about($quotes)
    {
        $pos = ["=Quotes a", "= Quotes a", "=Quotes A", "= Quotes A",
            "=About", "= About"];

        foreach($pos as $q)
        {
            $quo_pos = strpos($quotes, $q);

            if($quo_pos === FALSE)
            {
                $quo_pos = strpos($quotes, $q);
            }
            else
            {
                break;
            }
        }

        return $quo_pos;
    }

    private function get_misattributed($quotes)
    {
        $pos = ["{{Misattributed begin}}", "{{Misattributed Begin}}",
            "{{misattributed begin}}", "{{misattributed Begin}}"];

        foreach($pos as $q)
        {
            $mis_pos = strpos($quotes, $q);

            if($mis_pos === FALSE)
            {
                $mis_pos = strpos($quotes, $q);
            }
            else
            {
                break;
            }
        }

        return $mis_pos;
    }

    private function determine_end($quo_pos, $mis_pos, $len)
    {
        if(empty($mis_pos))
        {
            $mis_pos = 0;
        }

        if(empty($quo_pos))
        {
            $quo_pos = 0;
        }

        if($quo_pos === 0 || $mis_pos === 0)
        {
            if($mis_pos < $quo_pos)
            {
                $end = $quo_pos;

                if($quo_pos == 0)
                {
                    $end = $len;
                }
            }
            else
            {
                $end = $mis_pos;

                if($mis_pos == 0)
                {
                    $end = $len;
                }
            }
        }
        else
        {
            if($mis_pos > $quo_pos)
            {
                $end = $quo_pos;

                if($quo_pos == 0)
                {
                    $end = $len;
                }
            }
            else
            {
                $end = $mis_pos;

                if($mis_pos == 0)
                {
                    $end = $len;
                }
            }
        }

        return $end;
    }

    private function get_bottom_delimiter($quotes)
    {
        $len = strlen($quotes) - 1;

        $quo_pos = $this->get_quotes_about($quotes);
        $mis_pos = $this->get_misattributed($quotes);
        $end = $this->determine_end($quo_pos, $mis_pos, $len);

        return $end;
    }

    private function prettify_text($text)
    {
        $text = str_replace("'''", "", $text);
        $text = str_replace("''", "", $text);
        $text = str_replace("&nbsp;", " ", $text);

        return $text;
    }

    private function clear_quote_links($text, $len)
    {
        for($i = 0; $i < 3; $i ++)
        {
            switch($i)
            {
                case 0: $case = "[[w:"; break;
                case 1: $case = "[["; break;
                case 2: $case = "{{"; break;
                default: break;
            }

            while(strpos($text, $case) !== FALSE)
            {
                $first = strpos($text, $case);

                $remainder = substr($text, $first, $len - 1);

                if($i == 0 || $i == 1)
                {
                    $last = strpos($remainder, "]]");
                }
                else
                {
                    $last = strpos($remainder, "}}");
                }

                $print = substr($text, $first, $last + 2);

                $print_dup = $print;
                $print_dup = str_replace($case, "", $print_dup);

                if($i == 0 || $i == 1)
                {
                    if(strpos($remainder, "]]") === FALSE)
                    {
                        break;
                    }

                    $print_dup = str_replace("]]", "", $print_dup);
                }
                else
                {
                    if(strpos($remainder, "}}") === FALSE)
                    {
                        break;
                    }

                    $print_dup = str_replace("}}", "", $print_dup);
                }

                $res = $print_dup;

                if(strpos($print_dup, "|"))
                {
                    $pos = strpos($print_dup, "|") + 1;
                    $print_len = strlen($print_dup) - 2;
                    $res = substr($print_dup, $pos, $print_len);
                }

                $text = str_replace($print, $res, $text);
            }
        }

        return $text;
    }

    private function get_quotes($text, $name_check)
    {
        $range = $text;

        $n_line_count = substr_count($range, "\n") + 1;
        $n_iter = 0;

        $quotes = array();

        while(strpos($range, "\n\n*") !== FALSE)
        {
            if($n_iter > $n_line_count)
            {
                break;
            }

            $len = strlen($range);

            $start = strpos($range, "\n\n*");
            $str = substr($range, $start + 2, $len - 1);
            $end = strpos($str, "\n\n");

            $quote = substr($range, $start, $end);
            $quote = trim("\n\n" . $quote);

            $to_put = substr($range, $start, $end + 2);
            $to_put = trim($to_put);

            if(strpos($quote, "="))
            {
                $range = str_replace($quote, "", $range);
                continue;
            }

            $start = strpos($to_put, "*");
            $len = strlen($to_put);
            $str = substr($to_put, $start, $len - 1);
            $end = strpos($str, "\n");

            $to_put = substr($to_put, $start + 1, $end);
            $to_put = trim($to_put);

            $n_iter ++;

            if(empty($to_put))
            {
                $range = str_replace($quote, "", $range);
                continue;
            }

            $quotes[] = $this->trim_quote($to_put);

            $range = str_replace($quote, "", $range);
        }

        return $quotes;
    }

    private function trim_quote($quote)
    {
        $trim = ["\"", "\n", "”", "“", "<p>", "</p>"];

        foreach($trim as $t)
        {
            $quote = trim($quote, $t);
        }

        return $quote;
    }

    private function clear_author_links($text, $len)
    {
        for($i = 0; $i < 2; $i++)
        {
            if($i == 0)
            {
                $case = "[[w:";
            } else
            {
                $case = "[[";
            }

            while(strpos($text, $case) !== FALSE)
            {
                $first = strpos($text, $case);

                $remainder = substr($text, $first, $len - 1);
                $last = strpos($remainder, "]]");

                $print = substr($text, $first, $last + 2);

                $print_dup = $print;
                $print_dup = str_replace($case, "", $print_dup);
                $print_dup = str_replace("]]", "", $print_dup);

                $res = $print_dup;

                if(strpos($print_dup, "|"))
                {
                    $pos = strpos($print_dup, "*");
                    $end = strpos($print_dup, "|");
                    $res = substr($print_dup, $pos, $end);
                }

                $text = str_replace($print, $res, $text);
            }
        }

        return $text;
    }

    private function get_author_names($text)
    {
        $names = array();

        while(strpos($text, "\n*") !== FALSE)
        {
            $len = strlen($text);

            $start = strpos($text, "\n*");
            $str = substr($text, $start + 2, $len - 1);
            $end = strpos($str, "\n");

            $quote = substr($text, $start, $end);
            $quote = trim("\n" . $quote);

            $to_put = substr($text, $start, $end + 2);
            $to_put = str_replace("\n", "", $to_put);
            $to_put = str_replace("*", "", $to_put);
            $to_put = trim($to_put);

            if(strpos($to_put, ", see"))
            {
                $to_put = substr($to_put, 0, strpos($to_put, ", see"));
                $to_put = trim($to_put);
            }

            $names[] = $to_put;

            $text = str_replace($quote, "", $text);
        }

        return $names;
    }

    private function get_authors_string($current_page)
    {
        ini_set('user_agent', $this->agent);

        $url = 'https://en.wikiquote.org/w/api.php?action=query&titles=' .
            $current_page . '&prop=revisions&rvprop=content&format=json';

        $data = file_get_contents($url, false);
        $wiki = json_decode($data, true);

        foreach($wiki['query']['pages'] as $key => $item)
        {
            $id = $key;
        }

        return $wiki['query']['pages'][$id]['revisions'][0]['*'];
    }
}
