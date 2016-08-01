<?php
namespace Swis\GotLaravel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Swis\GoT\Inspector;
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $client = new \Github\Client(
            new \Github\HttpClient\CachedHttpClient(array('cache_dir' => storage_path('github-cache')))
        );


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


        $settings = SettingsFactory::create();
        $inspector = new Inspector($settings);

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
