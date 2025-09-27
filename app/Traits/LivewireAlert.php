<?php
namespace App\Traits;

use Jantinnerezo\LivewireAlert\Facades\LivewireAlert as Alert;

trait LivewireAlert
{
    /**
     * @param string $type
     * @param string $message
     * @param array $data
     * @return void
     */
    public static function alert(string $type, string $message, array $data = []): void
    {
        $alert = Alert::title($message);
        if ($type == 'success') {
            $alert->success();
        } elseif ($type == 'error') {
            $alert->error();
        } elseif ($type == 'warning') {
            $alert->warning();
        } elseif ($type == 'info') {
            $alert->info();
        }

        if(isset($data['onConfirm'])) {
            $alert->onConfirm($data['onConfirm']['method'], $data['onConfirm']['params']);
        }

        $alert->withOptions($data);
        $alert->show();
    }

}
