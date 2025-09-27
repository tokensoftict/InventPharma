<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command("queue:work --stop-when-empty")->everyFiveSeconds()->withoutOverlapping(); //to run emails
Schedule::command('open:stock')->dailyAt('01:00');
Schedule::command('orders:refresh')->everyMinute()->withoutOverlapping();

//Schedule::command('queue:work --sansdaemon --tries=3 --timeout=0')->everyMinute()->withoutOverlapping()->appendOutputTo('storage/app/queuework.txt');

//Schedule::command('backup:run --only-db')->dailyAt('00:00');

Schedule::command('nearos:compute')->dailyAt('02:00');

Schedule::command('retailnearos:compute')->dailyAt('03:00');

Schedule::command('open:supplierdbstock')->dailyAt('07:00');

Schedule::command('run:movingstocks')->dailyAt('04:00');

Schedule::command('sync:stock')->everyTwoHours();

//Schedule::command('download:product-image')->withoutOverlapping()->everyMinute()->appendOutputTo('storage/app/imageDownload.txt');

Schedule::command('uploadproduct:image')->withoutOverlapping()->everyMinute()->appendOutputTo('storage/app/imageUpload.txt');