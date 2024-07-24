<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    public function index()
    {
        $id = 0;
        $record_dates = collect([]);
        $formattedRecords = auth()->user()->diseases->map(function ($disease) use (&$id, &$record_dates) {
            $id++;

            $record_dates->push($disease->record->created_at);

            // // Extract the contraction dates
            // $contractionDates = $disease->users->map(function ($user) use ($disease) {
            //     return $user->pivot->created_at;
            // });

            // // Compute most common day of the week
            // $mostCommonDay = $contractionDates->map(function ($date) {
            //     return Carbon::parse($date)->format('l'); // Get the day of the week
            // })->mode();

            // // Compute most common month of the year
            // $mostCommonMonth = $contractionDates->map(function ($date) {
            //     return Carbon::parse($date)->format('F'); // Get the month
            // })->mode();

            // Compute the most common day of the week for the user
            $modalDay = $record_dates->map(function ($record) {
                return Carbon::parse($record)->format('l');
            })->mode();
            // Compute the most common month of the year for the user
            $modalMonth = $record_dates->map(function ($record) {
                return Carbon::parse($record)->format('F');
            })->mode();

            return [
                'id' => $id, // You can use any unique identifier here
                'disease_id' => $disease->id,
                'disease_name' => $disease->name,
                'symptoms' => $disease->symptoms->pluck('description'),
                'modalDay' => $modalDay,
                'modalMonth' => $modalMonth,
                'date' => $disease->record->created_at->diffForHumans()
            ];
        });


        $result = [
            'patient_name' => auth()->user()->name,
            'diseases' => $formattedRecords
        ];

        return response([
            'records' => $result
        ]);
    }

    public function monthDiseaseData()
    {
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];
        $data = collect([]);

        $options = [
            "responsive" => true,
            "maintainAspectRatio" => false,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => 'Number of Disease Contraction Each Month',
                    "font" => [
                        "size" => 18
                    ]
                ]
            ]
        ];

        foreach ($months as $month) {
            $monthDiseaseCount = auth()->user()->diseases->filter(function ($disease) use ($month) {
                return Carbon::parse($disease->record->created_at)->format('F') === $month;
            })->count();
            $data->push($monthDiseaseCount);
        }

        return response([
            "data" => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'Disease Count',
                        'backgroundColor' => [
                            '#FF6384', // January
                            '#36A2EB', // February
                            '#FFCE56', // March
                            '#4BC0C0', // April
                            '#9966FF', // May
                            '#FF9F40', // June
                            '#FF6384', // July
                            '#36A2EB', // August
                            '#FFCE56', // September
                            '#4BC0C0', // October
                            '#9966FF', // November
                            '#FF9F40'  // December
                        ],
                        'data' => $data,
                    ]
                ]
            ],
            "options" => $options
        ]);
    }


    public function dayDiseaseData()
    {
        $days = [
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday",
            "Sunday"
        ];
        $data = collect([]);

        $options = [
            "responsive" => true,
            "maintainAspectRatio" => false,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => 'Number of Disease Contraction by Days of Week',
                    "font" => [
                        "size" => 18
                    ]
                ]
            ]
        ];

        // Dynamically populate years with the past 5 years

        foreach ($days as $day) {
            $dayDiseaseCount = auth()->user()->diseases->filter(function ($disease) use ($day) {
                return Carbon::parse($disease->record->created_at)->format('l') === $day;
            })->count();
            $data->push($dayDiseaseCount);
        }

        return response([
            "data" => [
                'labels' => $days,
                'datasets' => [
                    [
                        'label' => 'Disease Count',
                        'backgroundColor' => [
                            '#FF6384', // 1
                            '#36A2EB', // 2
                            '#FFCE56', // 3
                            '#4BC0C0', // 4
                            '#9966FF', // 5
                            '#FF9F40', // 6
                            '#000000', // 7
                        ],
                        'data' => $data,
                    ]
                ]
            ],
            "options" => $options
        ]);
    }

    public function yearDiseaseData()
    {
        $years = collect([]);
        $data = collect([]);

        $options = [
            "responsive" => true,
            "maintainAspectRatio" => false,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => 'Number of Disease Contraction for The Past 6 Years',
                    "font" => [
                        "size" => 18
                    ]
                ]
            ]
        ];

        // Dynamically populate years with the past 5 years
        $currentYear = Carbon::now()->year;

        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        foreach ($years as $year) {
            $yearDiseaseCount = auth()->user()->diseases->filter(function ($disease) use ($year) {
                return Carbon::parse($disease->record->created_at)->year === $year;
            })->count();
            $data->push($yearDiseaseCount);
        }

        return response([
            "data" => [
                'labels' => $years,
                'datasets' => [
                    [
                        'label' => 'Disease Count',
                        'backgroundColor' => [
                            '#FF6384', // 1
                            '#36A2EB', // 2
                            '#FFCE56', // 3
                            '#4BC0C0', // 4
                            '#9966FF', // 5
                            '#FF9F40', // 6
                        ],
                        'data' => $data,
                    ]
                ]
            ],
            "options" => $options
        ]);
    }
}
