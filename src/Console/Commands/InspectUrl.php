<?php
namespace Swis\GotLaravel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Swis\GoT\Inspector;
use Swis\GoT\Settings\SettingsFactory;

class InspectUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'got:inspect {repositoryUrl} {--dry-run : Only inspect, do not insert into the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect a repository by URL';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Gitonomy\Git\Exception\RuntimeException
     * @throws \Gitonomy\Git\Exception\InvalidArgumentException
     */
    public function handle()
    {

        $settings = SettingsFactory::create();
        $inspector = new Inspector($settings);

        $repository = $inspector->getRepositoryByUrl($this->argument('repositoryUrl'));

        $inspectedRepository = $inspector->inspectRepository($repository);

        $header = array_keys((array)$inspectedRepository[key($inspectedRepository)]);

        if(!$this->option('dry-run')){
            $remote = $inspectedRepository['remote'];

            foreach($inspectedRepository['results'] as $result){
                $insert = $result->toArray();
                $insert['remote'] = $remote;
                $insert['author_slug'] = Str::slug($result->getAuthor());
                $insert['created_at'] = Carbon::createFromTimestamp($insert['date']);
                try {
                    \Swis\GotLaravel\Models\Results::updateOrCreate(array_only($insert, ['remote', 'filename', 'line']), $insert);
                } catch(\Exception $e){
                    $this->error('Couldnt insert: ' . $e->getMessage() . PHP_EOL . print_r($insert,1));
                }
            }
        }

        reset($inspectedRepository['results']);
        array_walk(
            $inspectedRepository['results'],
            function (&$row) {
                $row = $row->toArray();
            }
        );

        $this->info($inspectedRepository['remote']);
        $this->table($header, $inspectedRepository['results']);


        Artisan::call('got:normalize-names');
    }
}
