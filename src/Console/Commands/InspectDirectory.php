<?php
namespace Swis\GotLaravel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Swis\GoT\Inspector;
use Swis\GoT\Settings\SettingsFactory;
use Symfony\Component\Console\Logger\ConsoleLogger;

class InspectDirectory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'got:inspect-directory {directory} {--skippast= : Skip all before (and including) this} {--modified= : Repository modified since (uses strtotime)} {--only= : Skip every directory except this one} {--dry-run : Only inspect, do not insert into the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect a directory with bare repositories';



    protected function logVerbose($message){
        if($this->getOutput()->isVerbose()){
            $this->info($message);
        }
    }

    /**
     * @var \Swis\Got\Settings
     */
    protected $settings;

    /**
     * InspectDirectory constructor.
     * @param \Swis\Got\Settings $settings
     */
    public function __construct(\Swis\Got\Settings $settings)
    {
        parent::__construct();
        $this->settings = $settings;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        $this->info('Getting directory list');

        // Bare respoitories
        $directory = rtrim(trim($this->argument('directory')), '/') . '/';
        $scanned_directory = array_diff(scandir($directory), array('..', '.'));
        $this->info('Found ' . count($scanned_directory) . ' directories');

        $progresbar = $this->output->createProgressBar(count($scanned_directory));
        $progresbar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message% ');
        $progresbar->setMessage('Searching...');

        \Swis\GotLaravel\Models\Results::unguard();

        $inspector = new Inspector($this->settings);

        foreach($scanned_directory as $path){
            $this->logVerbose('Inpecting: ' . $path);


            if(null !== $this->option('only') && strcmp($path, $this->option('only')) !== 0){
                $progresbar->advance();
                continue;
            }

            if(null !== $this->option('skippast') && strcmp($path, $this->option('skippast')) <= 0){
                $progresbar->advance();
                continue;
            }

            $repository = $inspector->getRepositoryByPath($directory . $path);


            // Check modified date
            if(null !== $this->option('modified')){
                $modifiedTimestamp = strtotime($this->option('modified'));

                /**
                 * @var $date \DateTime
                 */
                try {
                    $this->logVerbose('Getting commit date');
                    $commitDate = $repository->getHeadCommit()->getCommitterDate();
                } catch(\Gitonomy\Git\Exception\ReferenceNotFoundException $e){
                    $this->error($e->getMessage());
                    $this->error('Error finding reference for ' . $path);
                }

                if($modifiedTimestamp === false || $commitDate->getTimestamp() < $modifiedTimestamp){
                    $progresbar->advance();
                    continue;
                }
                $this->logVerbose('Don\'t skip');
            }


            $repository->setLogger(new ConsoleLogger($this->getOutput()));

            $this->logVerbose('Searching git');

            $resultSet = $inspector->inspectRepository($repository);
            $remote = $resultSet['remote'];

            $this->logVerbose('Found remote ' . $remote);

            if(count($resultSet['results']) > 0){
                $progresbar->setMessage('Found ' . count($resultSet['results']) . ' tests for ' . $remote);
            }
            if(!$this->option('dry-run')) {
                foreach ($resultSet['results'] as $result) {
                    $insert = $result->toArray();
                    $insert['remote'] = $remote;
                    $insert['author_slug'] = Str::slug($result->getAuthor());
                    $insert['created_at'] = Carbon::createFromTimestamp($result->getDate());

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
