<?php

namespace App\Console\Commands;

use App\Models\Scrape;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrap data from a website';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = 'https://jameelcast.pinecast.co/';

        //Flush existing data from table before scrapping.
        Scrape::truncate();
        
        $client = new Client();
        $response = $client->request('GET', $url);
        $htmlContent = $response->getBody()->getContents();

        $crawler = new Crawler($htmlContent);

        $styleContent = $crawler->filter('style._styletron_hydrate_')->first()->text();

        $_this = $this;
        $data = $crawler->filter('section.av.aw.al')->each(function(Crawler $node) use($_this, $styleContent){
            return $_this->getNodeContent($node, $styleContent);
        });

        // Insert scrape data to databse table.
        try {
            for ($i=0; $i < count($data[0]['titles']); $i++) {
                $tilte_val = $data[0]['titles'][$i];
                $note_val = $data[0]['notes'][$i];
                $image_val = $data[0]['images'][$i];
                $audio_val = $data[0]['audios'][$i];

                Scrape::create([
                    'title' => $tilte_val,
                    'episode_notes' => $note_val,
                    'image_url' => $image_val,
                    'audio_url' => $audio_val
                ]);
            }
        } catch (Exception $e) {
            echo 'Error: '. $e->getMessage();
        }

        return Command::SUCCESS;
    }

    /**
     * Check is content available
     */
    private function hasContent($node)
    {
        return $node->count() > 0 ? true : false;
    }

    /**
     * Get node values
     */
    private function getNodeContent($node, $styleContent)
    {
        //Scrape title
        $titles = $this->titleScrape($node);

        //Scrape episode notes
        $notes = $this->episodeNoteScrape($node);

        //Scrape image url
        $image_urls = $this->imageUrlScrape($node, $styleContent);

        //Scrape audio urls
        $audio_urls = $this->audioUrlScrape($node);

        $array = [
            'titles' => $titles,
            'notes' => $notes,
            'images' => $image_urls,
            'audios' => $audio_urls
        ];

        return $array;
    }

    /**
     * Function to scrape title
     * @param $node
     */
    public function titleScrape($node) {
        $titles = [];
        if($this->hasContent($node->filter('h1.c4')) != false) {
            $node->filter('h1.c4')->each(function (Crawler $title) use(&$titles) {
                $titles[] = $title->text();
            });
        }

        return $titles;
    }

    /**
     * Function to scrape episode notes
     * @param $node
     */
    public function episodeNoteScrape($node) {
        $notes = [];
        if($this->hasContent($node->filter('div.c4 p')) != false) {
            $node->filter('div.c4')->each(function (Crawler $note) use(&$notes) {
                $firstPTag = $note->filterXPath('//p')->first();
                $notes[] = $firstPTag->innerText();
            });
        }

        return $notes;
    }

    /**
     * Function to fetch image urls.
     * @param $node
     * @param $styleContent
     */
    public function imageUrlScrape($node, $styleContent) {
        $image_urls = [];
        if($this->hasContent($node->filter('article')) != false) {
            $node->filter('article')->each(function (Crawler $image) use(&$image_urls, $styleContent) {
                $firstDivTag = $image->filter('div')->first();
                $classAttribute = $firstDivTag->attr('class');
                $classNames = explode(' ', $classAttribute);
    
                //Image url is added as backround-image:url() on third class in the div. 
                $className = isset($classNames[3]) ? $classNames[3] : '';

                //Match the classname from the inline style.
                preg_match('/\.' . preg_quote($className) . '\s*{([^}]+)}/', $styleContent, $matches);
                if (isset($matches[1])) {
                    $properties = explode(';', $matches[1]);

                    //Fetch background-image:url() value.
                    foreach($properties as $property) {
                        preg_match('/url\((.*?)\)/', $property, $bgmatches);
                        // Check if a background image URL is found
                        if (isset($bgmatches[1])) {
                            $image_urls[] = $bgmatches[1];
                        } 
                    }
                }
            });
        }

        return $image_urls;
    }

    /**
     * Function to fetch audio urls
     * @param $node
     */
    public function audioUrlScrape($node) {
        $audio_urls = [];
        $iframeElement = $node->filter('iframe.bb');
        if($this->hasContent($iframeElement) != false) {
            $iframeElement->each(function (Crawler $audio) use (&$audio_urls){
                $iframeSrc = $audio->attr('src');
                if($iframeSrc) {
                    // Fetch the HTML content of the iframe src URL
                    $iframeContent = file_get_contents($iframeSrc);
    
                    // Check if the iframe content is fetched successfully
                    if ($iframeContent !== false) {
                        // Initialize DomCrawler for the iframe content
                        $iframeCrawler = new Crawler($iframeContent);
                        
                         // Select the <a> tag within the iframe content
                        $aTags = $iframeCrawler->filter('a.download-button');
    
                        // Check if any <a> tags are found
                        if ($aTags->count() > 0) {
                            // Get the href attribute value of the first <a> tag
                            $hrefValue = $aTags->eq(0)->attr('href');
                            
                            // Output or process the href value
                            $audio_urls[] = $hrefValue;
                        }
                    }
                }
            });
        }

        return $audio_urls;
    }
}
