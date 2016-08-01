<?php
namespace Swis\GotLaravel\Providers;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Swis\GotLaravel\Console\Commands\InspectDirectory;
use Swis\GotLaravel\Console\Commands\InspectGithub;
use Swis\GotLaravel\Console\Commands\InspectUrl;
use Swis\GotLaravel\Console\Commands\Normalizenames;

class GameOfTestsProvider extends ServiceProvider {

    protected $commands = [
        InspectUrl::class,
        InspectDirectory::class,
        InspectGithub::class,
        Normalizenames::class,
    ];

    public function boot(Router $router)
    {

        $router->group(['prefix' => config('game-of-tests.route-prefix')], function() use ($router){
            $router->get('/', [
                'as' => 'index',
                'uses' => 'Swis\GotLaravel\Http\Controllers\ResultsController@alltime'
            ]);

            $router->get('score-for-month', [
                'as' => 'score-for-month',
                'uses' => 'Swis\GotLaravel\Http\Controllers\ResultsController@scoreForMonth'
            ]);

            $router->get('score-for-months-back', [
                'as' => 'score-for-months-back',
                'uses' => 'Swis\GotLaravel\Http\Controllers\ResultsController@scoreLastMonths'
            ]);

            $router->get('{user}', [
                'as' => 'user',
                'uses' => 'Swis\GotLaravel\Http\Controllers\ResultsController@resultForUser'
            ]);

        });

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'game-of-tests');

        $this->publishes([
            __DIR__.'/../../resources/views' => base_path('resources/views/vendor/game-of-tests'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../../config/' => config_path(),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations')
        ], 'migrations');
    }

    /**
    * Load config.
    *
    * @return void
    */
    public function register()
    {

        $this->app->instance('Swis\Got\Settings', function(){
            return \Swis\Got\SettingsFactory::create();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/game-of-tests.php',
            'game-of-tests'
        );

        $this->commands($this->commands);

    }
}

