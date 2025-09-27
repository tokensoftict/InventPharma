<?php

namespace App\Console\Commands;

use App\Models\Stock;
use CURLFile;
use Illuminate\Console\Command;

class UploadImageToServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploadproduct:image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload Product Image to Server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $stockImage = Stock::where("image_uploaded", 0)->whereNotNull('image_path')->orderBy('id', 'desc')->first();

        $image = public_path($stockImage->image_path);

        $url = 'https://admin.generaldrugcentre.com/api/data/uploadImage';

        $path = explode("/",$stockImage->image_path);

        $label = $path[count($path) -1];

        $ch = curl_init($url);

        $image =$this->makeCurlFile($image);

        $data = array('image' => $image, 'local_id' => $stockImage->id);

        curl_setopt($ch, CURLOPT_POST,1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->info('Error output :'.$stockImage->id);
            $stockImage->image_uploaded = 2;
        }else{
            $this->info('Uploaded'.$stockImage->id);
            $stockImage->image_uploaded = 1;
        }

        curl_close ($ch);

        $stockImage->update();

        return Command::SUCCESS;
    }


    private function makeCurlFile($file){
        $mime = mime_content_type($file);
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);
        return $output;
    }
}
