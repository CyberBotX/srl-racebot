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
      $to = irc::myStrToLower($line['to']);
      if ($to == '#speedrunslive')
        $command = 'notice';
      elseif ($to == irc::myStrToLower($this->ircClass->getNick()))
        $command = 'privMsg';
      else
        return;
      if ($args['nargs'] <= 0)
      {
        $this->ircClass->$command($line['fromNick'], 'Syntax: .searchgames <name>');
        $this->ircClass->$command($line['fromNick'], 'Searches the games list for <name>.');
        $this->ircClass->$command($line['fromNick'], 'If <name> consists of more than 1 word, games that match any');
        $this->ircClass->$command($line['fromNick'], 'of those words will be shown. You may use quotes to limit');
        $this->ircClass->$command($line['fromNick'], 'this. For example, Super Mario is treated as Super OR Mario,');
        $this->ircClass->$command($line['fromNick'], 'while "Super Mario" is treated exactly as given.');
        return;
      }
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
        $this->ircClass->$command($line['fromNick'], "Results of search for {$args['query']}:");
        foreach ($results as $result)
          $this->ircClass->$command($line['fromNick'], "{$result['name']} (abbreviation: {$result['abbrev']})");
        $numresults = count($results);
        $this->ircClass->$command($line['fromNick'], "End of search results. ($numresults found)");
      }
      else
        $this->ircClass->$command($line['fromNick'], "No games found for {$args['query']}.");
    }

    public function priv_races($line, $args)
    {
      $to = irc::myStrToLower($line['to']);
      if ($to == '#speedrunslive')
        $command = 'notice';
      elseif ($to == irc::myStrToLower($this->ircClass->getNick()))
        $command = 'privMsg';
      else
        return;
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
        $this->ircClass->$command($line['fromNick'], 'Current races:');
        $numraces = count($races);
        for ($x = 0; $x < $numraces; ++$x)
        {
          $y = $x + 1;
          $this->ircClass->$command($line['fromNick'], "$y. {$races[$x]['name']} - {$races[$x]['goal']} | " . chr(3) . "04#srl-{$races[$x]['id']}" . chr(15) . " | {$races[$x]['entrants']} entrant(s) | {$races[$x]['state']}");
        }
        $this->ircClass->$command($line['fromNick'], 'End of current races.');
      }
      else
        $this->ircClass->$command($line['fromNick'], 'No races in progress.');
    }

    public function priv_queue($line, $args)
    {
      if (!$this->ircClass->hasModeSet('#speedrunslive', $line['fromNick'], 'qaohv'))
        return;
      $to = irc::myStrToLower($line['to']);
      if ($to == '#speedrunslive')
        $command = 'notice';
      elseif ($to == irc::myStrToLower($this->ircClass->getNick()))
        $command = 'privMsg';
      else
        return;
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
        $this->ircClass->$command($line['fromNick'], 'Finished races:');
        $numraces = count($races);
        for ($x = 0; $x < $numraces; ++$x)
        {
          $y = $x + 1;
          $this->ircClass->$command($line['fromNick'], "$y. {$races[$x]['name']} - {$races[$x]['goal']} | " . chr(3) . "04#srl-{$races[$x]['id']}" . chr(15) . " | {$races[$x]['entrants']} entrant(s) | {$races[$x]['state']}");
        }
        $this->ircClass->$command($line['fromNick'], 'End of finished races.');
      }
      else
        $this->ircClass->$command($line['fromNick'], 'No races in queue.');
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

    public function priv_entrants($line, $args)
    {
      $to = irc::myStrToLower($line['to']);
      if (strlen($to) < 5 || substr($to, 0, 5) != '#srl-')
        return;
      $id = substr($line['to'], 5);
      $limit_to = '';
      if ($args['nargs'] > 0)
        switch ($args['arg1'])
        {
          case 'done':
            $limit_to = 'done';
            break;
          case 'ready':
            $limit_to = 'ready';
            break;
          case 'forfeit':
            $limit_to = 'forfeit';
            break;
          case 'dq':
            $limit_to = 'dq';
            break;
          default:
            $this->ircClass->notice($line['fromNick'], 'Syntax: .entrants [<limitto>]');
            $this->ircClass->notice($line['fromNick'], 'Get the entrants of the race in this channel.');
            $this->ircClass->notice($line['fromNick'], '<limitto> is optional, if given, it must be one of: done,');
            $this->ircClass->notice($line['fromNick'], 'ready, forfeit, or dq.');
            return;
        }

      $rawraces = json_decode(file_get_contents('http://speedrunslive.com:81/races'));
      $results = array();
      foreach ($rawraces->races as $race)
      {
        if ($race->id != $id)
          continue;
        foreach ($race->entrants as $entrant)
        {
          $state = $entrant->statetext;
          if ($limit_to)
          {
            if ($limit_to == 'done' && $state != 'Finished')
              continue;
            elseif ($limit_to == 'ready' && $state != 'Ready')
              continue;
            elseif ($limit_to == 'forfeit' && $state != 'Forfeit')
              continue;
            elseif ($limit_to == 'dq' && $state != 'DQ')
              continue;
          }
          $place = $entrant->place == 9994 ? 0 : ($entrant->place == 9998 ? - 1 : $entrant->place);
          $time = '';
          if ($place > 0)
          {
            $secs = $entrant->time % 60;
            $mins = $entrant->time / 60;
            $hours = $mins / 60;
            $time = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
          }
          $results[] = array('name' => $entrant->displayname, 'place' => $place, 'time' => $time, 'message' => $entrant->message, 'state' => $state);
        }
      }
      if ($results)
      {
        $finalline = '';
        foreach ($results as $result)
        {
          $entrant = '';
          if ($result['place'] != 0)
          {
            $entrant .= ($result['place'] == -1 ? '-' : $result['place']);
            if ($result['place'] != -1)
              $entrant .= '.';
            $entrant .= ' ';
          }
          $entrant .= "{$result['name']}";
          if ($result['place'] > 0)
            $entrant .= " ({$result['time']})";
          if ($result['state'] != 'Finished' && $result['state'] != 'Entered')
            $entrant .= " ({$result['state']})";
          if ($result['message'])
            $entrant .= " ({$result['message']})";
          $entrant .= ' | ';
          if (strlen($finalline) + strlen($entrant) > 450)
          {
            $finalline = trim($finalline);
            $this->ircClass->privMsg($line['to'], $finalline);
            $finalline = '';
          }
          $finalline .= $entrant;
        }
        if ($finalline)
        {
          $finalline = trim($finalline);
          if ($finalline[strlen($finalline) - 1] == '|')
            $finalline = substr($finalline, 0, -2);
          $this->ircClass->privMsg($line['to'], $finalline);
        }
      }
      else
        $this->ircClass->notice($line['fromNick'], 'There are currently no entrants in this race.');
    }
  }
?>