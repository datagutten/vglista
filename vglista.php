<?php


class vglista
{

    /**
     * @var DOMXPath
     */
    public $xpath;

    /**
     * @param int $week Week to fetch
     */
    function load($week)
    {
        $data=file_get_contents(sprintf('http://www.vglista.no/topplister/topp-20-single-%s-%02d/',date('Y'),$week));

        $dom=new DOMDocument;
        @$dom->loadHTML($data);
        $this->xpath=new DOMXPath($dom);
    }


    /**
     * @param DOMElement $track
     * @return array Track info
     * @throws Exception
     */
    function track_info($track)
    {
        $song = array();
        $song['Plassering']=$track->getAttribute('data-rank');
        $song['AntallUker']=$track->getAttribute('data-numb_weeks');
        $song['HighestRank']=$track->getAttribute('data-highest_rank');

        //$info=$track->childNodes->item(0)->childNodes->item(1)->childNodes;
        //*[@id="foo"]/div[1]/div[2]/div/a/h2/span
        //$song['Tittel']=$xpath->query('div[1]/div[2]/div/a/h2/span',$track)->item(0)->textContent;

        $track_title = $this->xpath->query('.//h2[@class="title"]/span', $track);
        if($track_title->length!=1)
            throw new Exception('Unable to get title');
        else
            $song['Tittel'] = $track_title->item(0)->textContent;
        list($song['Artist'], $song['Artists']) = $this->artists($track);
        return $song;
    }

    /**
     * @param $track
     * @return array
     * @throws Exception
     */
    function artists($track)
    {
        $song['Artist'] = '';
        $artists = $this->xpath->query('.//span[@class="artist"]/a', $track);
        if($artists->length===0)
        {
            $artists = $this->xpath->query('.//span[@class="artist"]', $track);
            if($artists->length===0)
                throw new Exception('Unable to find artists');
            $artists_string = $artists->item(0)->textContent;
            if (strpos($artists_string, ', ') !== false)
                $song['Artists'] = explode(', ', $artists_string);
            else
                $song['Artists'][0] = $artists_string;

            $song['Artist'] = $artists_string;
        }
        else {
            foreach ($artists as $artist) {
                $song['Artists'][] = $artist->textContent;
            }
            $song['Artist']=implode(', ', $song['Artists']);
        }
        foreach ($song['Artists'] as $key=>$artist_string)
        {
            if(preg_match('/(.+)\sfeat\.?\s(.+)/', $artist_string, $matches))
            {
                $song['Artists'][$key] = $matches[1];
                $song['Artists'][] = $matches[2];
            }
        }
        return [$song['Artist'], $song['Artists']];
    }

}