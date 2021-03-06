<?php

namespace App\Http\Controllers;

use App\Bit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $months = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];
        // mysql is our local database
        if (Config::get('database.default') === 'pgsql') {
            $monthRawQuery = DB::raw("DATE_PART('month', created_at) as month");
        }
        // our heroku database is pgsql
        if (Config::get('database.default') === 'mysql') {
            $monthRawQuery = DB::raw("month(created_at) as month");
        }

        //we are checking sqlite for testing
        if (Config::get('database.default') === 'sqlite') {
            $monthRawQuery = DB::raw("strftime('%m', created_at) as month");
        }

        $rawQueries = array_merge([DB::raw("count(id) as total")], [$monthRawQuery]);

        $result = Bit::query()->where('user_id', auth()->id())->select($rawQueries)->groupBy('month')->orderBy('month')->pluck('total', 'month')->all();

        $data = [];

        //we are checking sqlite for testing
        if (Config::get('database.default') === 'sqlite') {
            foreach ($months as $key => $month) {
                $monthNumber = $key + 1;
                $formattedMonthNumber = sprintf("%02d", $monthNumber);

                if (array_key_exists($formattedMonthNumber, $result)) {
                    $data[$month] = $result[$formattedMonthNumber];
                } else {
                    $data[$month] = 0;
                }
            }
        } else {
            foreach ($months as $key => $month) {
                if (array_key_exists($key + 1, $result)) {
                    $data[$month] = $result[$key + 1];
                } else {
                    $data[$month] = 0;
                }
            }
        }

        return response([
            'data' => array_values($data),
            'snippets_count' => auth()->user()->bits()->count(),
            'bookmarks_count' => auth()->user()->bookmarks()->count()
        ]);
    }
}
