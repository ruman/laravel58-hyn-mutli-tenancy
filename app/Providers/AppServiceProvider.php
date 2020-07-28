<?php

namespace App\Providers;

use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Console\ClientCommand;
use Laravel\Passport\Console\InstallCommand;
use Laravel\Passport\Console\KeysCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
	    Schema::defaultStringLength(191); //Solved by increasing StringLength


	    $env = app(Environment::class);


	    if ($fqdn = optional($env->hostname())->fqdn) {
		    config(['database.default' => 'tenant']);

		    //Config(['audit.drivers.database.connection' => $env->website()->uuid]);

	    }else{
		    config(['database.default' => 'system']);
	    }

	    $this->commands([
		    InstallCommand::class,
		    ClientCommand::class,
		    KeysCommand::class,
        ]);
        

        Response::macro('streamed', function($type, $size, $name, $callback) {
            $start = 0;
            $length = $size;
            $status = 200;
            $headers = [
                'Content-Type' => $type,
                'Content-Length' => $size,
                'Accept-Ranges' => 'bytes'
            ];
            if (false !== $range = Request::server('HTTP_RANGE', false)) {
                list($param, $range) = explode('=', $range);
                if (strtolower(trim($param)) !== 'bytes') {
                    header('HTTP/1.1 400 Invalid Request');
                    exit;
                }
                list($from, $to) = explode('-', $range);
                if ($from === '') {
                    $end = $size - 1;
                    $start = $end - intval($from);
                } elseif ($to === '') {
                    $start = intval($from);
                    $end = $size - 1;
                } else {
                    $start = intval($from);
                    $end = intval($to);
                }
                if ($end >= $length) {
                    $end = $length - 1;
                }
                $length = $end - $start + 1;
                $status = 206;
                $headers['Content-Range'] = sprintf('bytes %d-%d/%d', $start, $end, $size);
                $headers['Content-Length'] = $length;
            }
            return response()->stream(function() use ($start, $length, $callback) {
                call_user_func($callback, $start, $length);
            }, $status, $headers);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
