<?php

namespace App\Http\Controllers;

use App\Models\Scrape;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class ScrapController extends Controller
{
    /**
     * Fecth the scrape data with pagination
     */
   public function fetchCrawledData()
   {
    try {
        $scrape_data = Scrape::paginate(5);

        //Check if paginated collection is empty
        if ($scrape_data->isNotEmpty()) {
            return response()->json([
                'message' => "List of data.",
                'data' => $scrape_data
            ], Response::HTTP_CREATED);
        } else {
            return response()->json([
                'message' => "List is empty.",
            ], Response::HTTP_CREATED);
        }
        
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
   }
}
