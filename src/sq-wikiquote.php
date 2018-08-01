<?php
/*
    sq-wikiquote.php

    Get authors and quotes from wikiquote.org.

    github.com/01mu
*/

class wikiquote
{
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

        $quotes_array = $this->get_quotes($quotes_str, $author);
        $n_quotes_array = count($quotes_array);

        return $quotes_array;
    }

    private function get_authors($page)
    {
        $name_str = $this->get_authors_string($page);
        $name_str_len = strlen($name_str);
        $name_str = $this->clear_author_links($name_str, $name_str_len);
        $authors = $this->get_author_names($name_str);

        return $authors;
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

    private function get_bottom_delimiter($quotes) {
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
            if($i == 0)
            {
                $case = "[[w:";
            } else if ($i == 1)
            {
                $case = "[[";
            } else if ($i == 2)
            {
                $case = "{{";
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
                //print("empty found\n\n");
                $range = str_replace($quote, "", $range);
                continue;
            }

            $quotes[] = $to_put;

            $range = str_replace($quote, "", $range);
        }

        return $quotes;
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
        ini_set('user_agent', 'github.com/01mu/sq-wikiquote');

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
