<?php
namespace Swis\GotLaravel\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResultsRepository
{

    /**
     * @var \Swis\GotLaravel\Models\Results
     */
    private $model;

    public static function normalizeNames()
    {
        $names = config('game-of-tests.normalize-names');

        foreach ($names as $name => $aliases) {
            \DB::update(
                'UPDATE results SET author_normalized = "' . $name . '" WHERE author IN ("' . implode(
                    '", "',
                    $aliases
                ) . '") '
            );
        }
        \DB::update('UPDATE results SET author_normalized = author WHERE author_normalized = ""');

        foreach ($names as $name => $aliases) {
            \DB::update(
                'UPDATE results SET author_slug = "' . Str::slug($name) . '" WHERE author_normalized = "' . $name . '"'
            );
        }
    }

    public function getByUser($slug, $monthsBack = false, $fromMonthsBack = false)
    {
        $query = Results::whereAuthorSlug($slug)->orderBy('created_at', 'DESC');
        
        if ($monthsBack !== false) {
            if ((int)$monthsBack === 0) {
                $query->whereRaw('YEAR(NOW()) = YEAR(created_at) AND MONTH(NOW()) = MONTH(created_at)');
            } else {
                $query->whereRaw(
                    'YEAR(DATE_SUB(NOW(), INTERVAL ' . (int)$monthsBack . ' MONTH)) = YEAR(created_at) 
                    AND MONTH(DATE_SUB(NOW(), INTERVAL ' . (int)$monthsBack . ' MONTH)) = MONTH(created_at)'
                );
            }
        }

        if ($fromMonthsBack !== false) {
            $query->whereRaw('DATE_SUB(NOW(), INTERVAL ' . (int)$fromMonthsBack . ' MONTH) < created_at');
        }
        $this->excludedFiles($query);

        return $query->get();
    }

    public function excludedFiles(&$query)
    {

        $excludedRemotes = config('game-of-tests.excluded-remotes', []);
        foreach($excludedRemotes as $remote){
            $query->whereRaw('remote NOT LIKE "'. $remote .'"');
        }

        $excludedFiles = config('game-of-tests.excluded-filenames');
        foreach($excludedFiles as $file){
            $query->whereRaw('filename NOT LIKE "'. $file .'"');
        }

        $excludedAuthors = config('game-of-tests.excluded-authors');
        if(count($excludedAuthors) > 0){
            $query->whereNotIn('author', $excludedAuthors);
        }

    }

    public function getScorePerUser()
    {
        $query = \DB::query()
            ->from('results')
            ->select(DB::raw('author_normalized as author, COUNT(1) AS score, author_slug'))
            ->groupBy('author_normalized', 'author_slug')
            ->orderBy('score', 'DESC');

        $this->excludedFiles($query);

        return $query->get();
    }

    public function getScorePerWeekPerUser()
    {
        $query = \DB::query()
            ->from('results')
            ->select(DB::raw('author, YEARWEEK(MAX(created_at)) AS week, COUNT(1) AS score, author_slug'))
            ->groupBy('author_normalized', 'author_slug', DB::raw('YEARWEEK(created_at)'))
            ->orderBy('week', 'DESC')
            ->orderBy('score', 'DESC');

        $this->excludedFiles($query);

        return $query->get();
    }

    public function getScorePerMonthPerUser()
    {
        $query = \DB::query()
            ->from('results')
            ->select(
                DB::raw(
                    'author_normalized as author, CONCAT(YEAR(created_at),MONTH(created_at)) AS month, 
                    COUNT(1) AS score, author_slug'
                )
            )
            ->groupBy('author_normalized', 'author_slug', DB::raw('CONCAT(YEAR(created_at),MONTH(created_at))'))
            ->orderBy('month', 'DESC')
            ->orderBy('score', 'DESC');

        $this->excludedFiles($query);

        return $query->get();
    }

    public function getScoreLastMonths($months = 3)
    {
        $query = \DB::query()
            ->from('results')
            ->select(
                DB::raw(
                    'author_normalized as author, CONCAT(YEAR(MAX(created_at)),MONTH(MAX(created_at))) AS month, COUNT(1) AS score, author_slug'
                )
            )
            ->groupBy('author_normalized', 'author_slug')
            ->whereRaw('DATE_SUB(NOW(), INTERVAL ' . (int)$months . ' MONTH) < created_at')
            ->orderBy('score', 'DESC');

        $this->excludedFiles($query);

        return $query->get();
    }

    public function getScoreForMonth($monthsBack = 0)
    {
        $query = \DB::query()
            ->from('results')
            ->select(
                DB::raw(
                    'author_normalized as author, CONCAT(YEAR(MAX(created_at)),MONTH(MAX(created_at))) AS month, COUNT(1) AS score, author_slug'
                )
            )
            ->groupBy('author_normalized', 'author_slug')
            ->whereRaw(
                '(MONTH(DATE_SUB(NOW(), INTERVAL ' . (int)$monthsBack . ' MONTH)) = MONTH(created_at) 
                AND YEAR(DATE_SUB(NOW(), INTERVAL ' . (int)$monthsBack . ' MONTH)) = YEAR(created_at))'
            )
            ->orderBy('score', 'DESC');

        $this->excludedFiles($query);

        return $query->get();
    }
}