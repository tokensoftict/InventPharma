<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadProductImageFromOnlineStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:product-image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command download image from online store to local server one after the other';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $stock = Stock::where('image_download_status', 'PENDING')->first();

        if($stock){
            $baseURL = 'https://admin.generaldrugcentre.com/api/data/downloadImage/'.$stock->id;

            $this->info('Downloading image from '.$baseURL);

            $request = @file_get_contents($baseURL);
            if(!isset($http_response_header[0])) return Command::SUCCESS;
            preg_match('/([0-9])\d+/',$http_response_header[0],$matches);

            $responsecode = intval($matches[0]);

            if($responsecode == 200)
            {
                $ext = explode(".",$http_response_header[12]);

                if(isset($ext[1]))
                    $ext = ".".$ext[1];
                else
                    $ext = "";

                Storage::disk('product_images')->put('images/'.$stock->id.$ext, $request);

                $stock->image_download_status = "COMPLETE";
                $stock->image_uploaded = "1";
                $stock->image_path = "products/images/".$stock->id.$ext;


            }else if($responsecode == 404){
                $stock->image_download_status = "NOT-FOUND";
                $stock->image_path = NULL;
                $stock->image_uploaded = "0";
            }else{
                $stock->image_download_status = "UNKNOWN-ERROR";
                $stock->image_path = NULL;
                $stock->image_uploaded = "0";
            }

            $stock->update();
        }

        return Command::SUCCESS;
    }
}
