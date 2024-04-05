<?php

namespace Akbarali\DataObject;

use Illuminate\Support\ServiceProvider;

class DataObjectProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CreateDataObject::class,
            ]);
        }
    }

}
