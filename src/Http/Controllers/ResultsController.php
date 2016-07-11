<?php

namespace Swis\GotLaravel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Swis\GotLaravel\Models\ResultsRepository;

class ResultsController extends Controller
{

    /**
     * Show tests of all time
     *
     * @return mixed
     */
    public function alltime()
    {
        $repos = new ResultsRepository();
        return view('game-of-tests::results.list', ['results' => $repos->getScorePerUser()]);
    }

    /**
     * Show rankings of specific month
     *
     * @param Request $request
     * @return mixed
     */
    public function scoreForMonth(Request $request)
    {
        $repos = new ResultsRepository();
        $title = Carbon::createFromDate()->subMonths($request->input('monthsBack', 0))->format('F');
        return view(
            'game-of-tests::results.list',
            [
                'title' => $title,
                'months_back' => $request->input('monthsBack', 0),
                'results' => $repos->getScoreForMonth($request->input('monthsBack', 0))
            ]
        );
    }

    /**
     * Show rankings for last X months
     *
     * @param Request $request
     * @return mixed
     */
    public function scoreLastMonths(Request $request)
    {
        $repos = new ResultsRepository();
        return view(
            'game-of-tests::results.list',
            [
                'type' => 'from',
                'months_back' => $request->input('monthsBack', 0),
                'results' => $repos->getScoreLastMonths($request->input('monthsBack', 0))
            ]
        );
    }

    /**
     * Show result for specific user.
     *
     * @param $user
     * @param Request $request
     * @return mixed
     */
    public function resultForUser($user, Request $request)
    {

        $repos = new ResultsRepository();
        $results = $repos->getByUser(
            $user,
            $request->input('monthsBack', false),
            $request->input('fromMonthsBack', false)
        );

        return view('game-of-tests::results.author', ['results' => $results]);
    }
}
