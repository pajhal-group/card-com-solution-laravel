<?php

namespace Modules\Payment\Providers;

use Modules\Payment\Facades\Gateway;

use Illuminate\Support\ServiceProvider;

use Modules\Payment\Gateways\CardcomSolution;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!config('app.installed')) {
            return;
        }


        $this->registerCardcomSolution();
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {}





    private function enabled($paymentMethod)
    {
        if (app('inAdminPanel')) {
            return true;
        }

        return setting("{$paymentMethod}_enabled");
    }



    private function registerCardcomSolution()
    {
        if ($this->enabled('card_com_solution')) {
            Gateway::register('card_com_solution', new CardcomSolution());
        }
    }
}
