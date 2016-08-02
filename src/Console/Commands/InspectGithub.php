<?php
namespace Swis\GotLaravel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Cache\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Swis\GoT\Inspector;
use Swis\Got\Settings;
use Swis\GoT\Settings\SettingsFactory;
use Symfony\Component\Console\Logger\ConsoleLogger;

class InspectGithub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'got:inspect-github {organisation} {--modified= : Repository modified since (uses strtotime)} {--dry-run : Only inspect, do not insert into the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect a github organisation';

    /**
     * @var \Swis\Got\Settings
     */
    protected $settings;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * InspectDirectory constructor.
     * @param \Swis\Got\Settings $settings
     * @param \Illuminate\Cache\Repository $cache
     */
    public function __construct(\Swis\Got\Settings $settings, Repository $cache)
    {
        parent::__construct();
        $this->settings = $settings;
        $this->cache = $cache;
    }


    /**
     * Execute the console command.
     *
     * @param Settings $settings
     * @return mixed
     */
    public function handle()
    {

        $client = new \Github\Client();

        if ($this->cache && config('game-of-tests.cache') && class_exists(CacheItemPool::class)) {
            $client->addCache(new CacheItemPool($this->cache));
        }

        $this->info('Getting repository list');


        if($this->argument('organisation') !== false){
            $repositories = $client->organization()->repositories($this->argument('organisation'), 'owner');
        }

        $repositoryUrls = array_pluck($repositories, 'clone_url');

        $this->info('Found ' . count($repositoryUrls) . ' repositories on Github');

        $progresbar = $this->output->createProgressBar(count($repositoryUrls));
        $progresbar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message% ');
        $progresbar->setMessage('Searching...');

        \Swis\GotLaravel\Models\Results::unguard();

        $inspector = new Inspector($this->settings);

        foreach($repositoryUrls as $gitUrl){

            $repository = $inspector->getRepositoryByUrl($gitUrl);

            // Check modified date
            if(null !== $this->option('modified')){
                $modifiedTimestamp = strtotime($this->option('modified'));
                /**
                 * @var $date \DateTime
                 */
                try {
                    $commitDate = $repository->getHeadCommit()->getCommitterDate();
                } catch(\Gitonomy\Git\Exception\ReferenceNotFoundException $e){
                    $this->error($e->getMessage());
                    $this->error('Error finding reference for ' . $gitUrl);
                }

                if($modifiedTimestamp === false || $commitDate->getTimestamp() < $modifiedTimestamp){
                    $progresbar->advance();
                    continue;
                }
            }


            $repository->setLogger(new ConsoleLogger($this->getOutput()));

            $resultSet = $inspector->inspectRepository($repository);
            $remote = $resultSet['remote'];
            if(count($resultSet['results']) > 0){
                $progresbar->setMessage('Found ' . count($resultSet['results']) . ' tests for ' . $remote);
            }
            if(!$this->option('dry-run')) {
                foreach ($resultSet['results'] as $result) {
                    $insert = $result->toArray();
                    $insert['remote'] = $remote;
                    $insert['author_slug'] = Str::slug($result->getAuthor());
                    $insert['created_at'] = Carbon::createFromTimestamp($insert['date']);
                    try {
                        \Swis\GotLaravel\Models\Results::updateOrCreate(
                            array_only($insert, ['remote', 'filename', 'line']),
                            $insert
                        );
                    } catch (\Exception $e) {
                        $this->error('Couldnt insert: ' . $e->getMessage() . PHP_EOL . print_r($insert, 1));
                    }
                }
            }
            $progresbar->advance();


        }

        Artisan::call('got:normalize-names');
    }
}
