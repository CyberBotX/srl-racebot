<?php
  class srl extends module
  {
    public $title = 'SRL API Test';
    public $author = 'Naram Qashat (CyberBotX) <cyberbotx@cyberbotx.com>';
    public $version = '0.1';

    public function init()
    {
    }

    public function destroy()
    {
    }

    public function priv_searchgames($line, $args)
    {
      if ($args['nargs'] <= 0)
      {
        $this->ircClass->notice($line['fromNick'], 'Syntax: .searchgames <name>');
        $this->ircClass->notice($line['fromNick'], 'Searches the games list for <name>.');
        $this->ircClass->notice($line['fromNick'], 'If <name> consists of more than 1 word, games that match any');
        $this->ircClass->notice($line['fromNick'], 'of those words will be shown. You may use quotes to limit');
        $this->ircClass->notice($line['fromNick'], 'this. For example, Super Mario is treated as Super OR Mario,');
        $this->ircClass->notice($line['fromNick'], 'while "Super Mario" is treated exactly as given.');
        return;
      }
      $to = irc::myStrToLower($line['to']);
      $query = $args['query'];
      $num_quotes = substr_count($query, '"');
      $words = array();
      if ($num_quotes > 0)
      {
        if ($num_quotes % 2)
        {
          $last_quote = strrpos($query, '"');
          $query = substr($query, 0, $last_quote) . substr($query, $last_quote + 1);
          --$num_quotes;
        }
        $quoted_words = $num_quotes / 2;
        for ($x = 0; $x < $quoted_words; ++$x)
        {
          $first_quote = strpos($query, '"');
          $last_quote = strpos($query, '"', $first_quote + 1);
          $words[] = trim(substr($query, $first_quote + 1, $last_quote - $first_quote - 1));
          $query = substr($query, 0, $first_quote) . substr($query, $last_quote + 1);
        }
      }
      $query = trim($query);
      if ($query)
      {
        $parts = explode(' ', $query);
        foreach ($parts as $part)
        {
          $part = trim($part);
          if (!$part)
            continue;
          $words[] = $part;
        }
      }
      $rawgames = json_decode(file_get_contents('http://speedrunslive.com:81/games'));
      $results = array();
      foreach ($rawgames->games as $game)
      {
        foreach ($words as $word)
        {
          $gamenamelc = strtolower($game->name);
          $wordlc = strtolower($word);
          if (substr_count($gamenamelc, $wordlc))
          {
            $results[] = array('name' => $game->name, 'abbrev' => $game->abbrev);
            break;
          }
        }
      }
      if ($results)
      {
        $this->ircClass->notice($line['fromNick'], "Results of search for {$args['query']}:");
        foreach ($results as $result)
          $this->ircClass->notice($line['fromNick'], "{$result['name']} (abbreviation: {$result['abbrev']})");
        $numresults = count($results);
        $this->ircClass->notice($line['fromNick'], "End of search results. ($numresults found)");
      }
      else
        $this->ircClass->notice($line['fromNick'], "No games found for {$args['query']}.");
    }

    public function priv_races($line, $args)
    {
      $rawraces = json_decode(file_get_contents('http://speedrunslive.com:81/races'));
      $results = array();
      foreach ($rawraces->races as $race)
      {
        $state = $race->statetext;
        if ($state == 'Complete' || $state == 'Race Over')
          continue;
        $races[] = array('name' => $race->game->name, 'goal' => $race->goal, 'id' => $race->id, 'entrants' => $race->numentrants, 'state' => $state);
      }
      if ($races)
      {
        $this->ircClass->notice($line['fromNick'], 'Current races:');
        $numraces = count($races);
        for ($x = 0; $x < $numraces; ++$x)
        {
          $y = $x + 1;
          $this->ircClass->notice($line['fromNick'], "$y. {$races[$x]['name']} - {$races[$x]['goal']} | " . chr(3) . "04#srl-{$races[$x]['id']}" . chr(15) . " | {$races[$x]['entrants']} entrant(s) | {$races[$x]['state']}");
        }
        $this->ircClass->notice($line['fromNick'], 'End of current races.');
      }
      else
        $this->ircClass->notice($line['fromNick'], 'No races in progress.');
    }

    public function priv_queue($line, $args)
    {
      $rawraces = json_decode(file_get_contents('http://speedrunslive.com:81/races'));
      $results = array();
      foreach ($rawraces->races as $race)
      {
        $state = $race->statetext;
        if ($state != 'Complete' && $state != 'Race Over')
          continue;
        $races[] = array('name' => $race->game->name, 'goal' => $race->goal, 'id' => $race->id, 'entrants' => $race->numentrants, 'state' => $state);
      }
      if ($races)
      {
        $this->ircClass->notice($line['fromNick'], 'Finished races:');
        $numraces = count($races);
        for ($x = 0; $x < $numraces; ++$x)
        {
          $y = $x + 1;
          $this->ircClass->notice($line['fromNick'], "$y. {$races[$x]['name']} - {$races[$x]['goal']} | " . chr(3) . "04#srl-{$races[$x]['id']}" . chr(15) . " | {$races[$x]['entrants']} entrant(s) | {$races[$x]['state']}");
        }
        $this->ircClass->notice($line['fromNick'], 'End of finished races.');
      }
      else
        $this->ircClass->notice($line['fromNick'], 'No races in queue.');
    }

    public function priv_stream($line, $args)
    {
      if ($args['nargs'] <= 0)
      {
        $this->ircClass->notice($line['fromNick'], 'Syntax: .stream <nick>');
        $this->ircClass->notice($line['fromNick'], 'Gives the stream URL for <nick>, if any.');
        return;
      }
      // TODO
    }
  }
?>