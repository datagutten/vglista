<?php


namespace datagutten\vglista;


use Requests;

class Song
{
    public $rank;
    public $num_weeks;
    public $highest_rank;
    /**
     * @var string Artist string
     */
    public $artist;
    /**
     * @var string Artists separated into an array
     */
    public $artists;

    public $title;
    /**
     * @var string Song id to be used in URL
     */
    public $id;

    public function create_array()
    {
        $song['Plassering'] = $this->rank;
        $song['AntallUker'] = $this->num_weeks;
        $song['HighestRank'] = $this->highest_rank;
        $song['Tittel'] = $this->title;
        $song['Artist'] = $this->artist;
        $song['Artists'] = $this->artists;
        return $song;
    }

    /**
     * @return string URL to song page
     */
    function page_url()
    {
        return sprintf('https://www.vglista.no/sanger/%s/', $this->id);
    }

    /**
     * @return array Song rank history
     */
    public function history()
    {
        $url = sprintf('https://www.vglista.no/sanger/%s/', $this->id);
        $response = Requests::get($url);
        preg_match('/create_chart\((.+)\)/', $response->body, $history);
        return json_decode($history[1], true);
    }

    /**
     * @param int $year Year
     * @param int $week Week
     * @return int|bool Return place if found, else return false if not on the list
     */
    public function historic_place($year, $week)
    {
        $history = $this->history();
        if(isset($history[$year]) && isset($history[$year][$week]))
            return $history[$year][$week];
        else
            return false;
    }

    public function __toString()
    {
        return sprintf('%-2s %-4s %-4s %s - %s',$this->rank,$this->highest_rank,'['.$this->num_weeks.']',$this->artist,$this->title);
    }

}