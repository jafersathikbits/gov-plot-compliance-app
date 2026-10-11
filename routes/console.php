<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Government Plot Compliance App ready.');
})->purpose('Display an application status message');
