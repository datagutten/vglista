<?php


namespace datagutten\vglista;


use datagutten\vglista\exceptions;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Requests;
use Requests_Exception;

class TopList
{
    /**
     * @var DOMXPath
     */
    public $xpath;
    /**
     * @var array Array with Song objects indexed by rank
     */
    public $songs;
    /**
     * @var int Get songs up to this rank
     */
    public $limit;

    public $year;
    public $week;

    /**
     * TopList constructor.
     * @param int $year Year
     * @param int $week Week
     * @param int $limit Get songs up to this rank
     * @throws Exception
     * @throws exceptions\NoSongsFound
     */
    function __construct($year, $week, $limit = 20)
    {
        $this->week = $week;
        $this->year = $year;
        $this->limit = $limit;
        $this->load($year, $week);
        $this->parse_list();
    }

    /**
     * @param $year
     * @param $week
     * @throws exceptions\NoSongsFound
     * @throws Requests_Exception
     */
    private function load($year, $week)
    {
        $response=Requests::get(sprintf('http://www.vglista.no/topplister/topp-20-single-%s-%02d/',$year,$week));
        $response->throw_for_status();
        $dom=new DOMDocument;
        @$dom->loadHTML($response->body);
        $this->xpath=new DOMXPath($dom);
        $this->parse_list();
    }

    /**
     * @throws exceptions\NoSongsFound
     * @throws Exception
     */
    function parse_list()
    {
        $songs = $this->xpath->query('.//div[@class="music_table"]/div');
        if($songs->length==0)
            throw new exceptions\NoSongsFound('No songs found');
        foreach ($songs as $song) {
            $song =  $this->parse_song($song);
            $this->songs[$song->rank] = $song;
            if($song->rank == $this->limit)
                break;
        }
    }

    /**
     * @param DOMElement $track
     * @return Song Track info
     * @throws exceptions\ParseError
     * @throws Exception
     */
    function parse_song($track)
    {
        $song = new Song();
        $song->rank=$track->getAttribute('data-rank');
        $song->num_weeks=$track->getAttribute('data-numb_weeks');
        $song->highest_rank=$track->getAttribute('data-highest_rank');

        $track_title = $this->xpath->query('.//h2[@class="title"]/span', $track);
        if($track_title->length!=1)
            throw new exceptions\ParseError('Unable to get title');
        else
            $song->title = $track_title->item(0)->textContent;
        list($song->artist, $song->artists) = $this->artists($track);

        return $song;
    }

    /**
     * @param $track
     * @return array
     * @throws Exception
     */
    function artists($track)
    {
        $artists_dom = $this->xpath->query('.//span[@class="artist"]/a', $track);
        if($artists_dom->length===0)
        {
            $artists_dom = $this->xpath->query('.//span[@class="artist"]', $track);
            if($artists_dom->length===0)
                throw new Exception('Unable to find artists');
            $artists_string = $artists_dom->item(0)->textContent;
            if (strpos($artists_string, ', ') !== false)
                $artists = explode(', ', $artists_string);
            else
                $artists[0] = $artists_string;

            $artist = $artists_string;
        }
        else {
            $artists = array();
            foreach ($artists_dom as $artist) {
                $artists[] = $artist->textContent;
            }
            $artist=implode(', ', $artists);
        }
        foreach ($artists as $key=>$artist_string)
        {
            if(preg_match('/(.+)\sfeat\.?\s(.+)/', $artist_string, $matches))
            {
                $artists[$key] = $matches[1];
                $artists[] = $matches[2];
            }
        }
        return [$artist, $artists];
    }

    function title()
    {
        return sprintf('VG-lista Topp %d Single uke %d %d', $this->limit, $this->week, $this->year);
    }

    public function __toString()
    {
        $string = "Plass/Beste plass/Antall uker\n";
        foreach ($this->songs as $song)
        {
            $string.=$song."\n";
        }
        return $string;
    }
}