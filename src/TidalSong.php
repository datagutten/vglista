<?php


namespace datagutten\vglista;


use datagutten\vglista\exceptions;
use datagutten\Tidal;

class TidalSong
{
    public $tidal;
    public $tidal_track;
    /**
     * @var Song
     */
    public $song;

    /**
     * TidalSong constructor.
     * @param Song $song
     * @param string $token Token for TIDAL
     * @throws Tidal\TidalError
     * @throws exceptions\NoSongsFound
     */
    function __construct(&$song, $token=null)
    {
        $this->tidal = new Tidal\Search();
        $this->song = $song;
        if(empty($token))
            $this->tidal->token = $this->tidal->get_token();
        else
            $this->tidal->token = $token;
        if(empty($song->tidal_id)) {
            $this->lookup();
            $this->song->tidal_id = $this->id();
        }
        elseif(!file_exists($this->json_file($song->tidal_id)))
        {
            $this->tidal_track = $this->tidal->track($song->tidal_id);
            $this->save_json();
        }
        else
            $this->load_json($song->tidal_id);
    }

    /**
     * Search for the song on TIDAL
     * @throws Tidal\TidalError
     * @throws exceptions\NoSongsFound Thrown when song is not found on tidal
     */
    function lookup()
    {
        $search_title = Tidal\Search::remove_featured($this->song->title);
        $matches = $this->tidal->search_track($search_title);

        foreach ($matches['items'] as $match) {
            $match = $this->tidal->verify_search($match, $this->song->title, $this->song->artists, $this->song->artist);
            if ($match !== false) {
                $this->tidal_track = $match;
                break;
            }
        }

        if(empty($this->tidal_track))
            throw new exceptions\NoSongsFound('No match for '.$search_title);
    }

    /**
     * Show debug information for the lookup
     * @return string
     */
    function lookup_debug()
    {
        $return = sprintf("VG:\t%s\nTIDAL:\t%s\n", $this->song->title, $this->tidal_track['title']);
        $tidal_artists_string=implode(", ",array_column($this->tidal_track['artists'],'name')); //Create a string from the search result artist array
        $return .= sprintf("VG:\t%s\nTIDAL:\t%s\n\n", $this->song->artist, $tidal_artists_string);
        return $return;
    }

    function url()
    {
        return $this->tidal_track['url'];
    }
    function id()
    {
        return $this->tidal_track['id'];
    }
    function json_file($id=null)
    {
        if(empty($id))
            $id = $this->id();
        return sprintf('tidal_tracks/%d.json', $id);
    }
    function load_json($id=null)
    {
        $this->tidal_track = json_decode(file_get_contents($this->json_file($id)), true);
    }
    function save_json()
    {
        file_put_contents($this->json_file(), json_encode($this->tidal_track));
    }
}