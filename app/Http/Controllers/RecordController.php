<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    public function index()
    {
        $id = 0;
        $record_dates = collect([]);
        $formattedRecords = auth()->user()->diseases->map(function ($disease) use (&$id, &$record_dates) {
            $id++;

            $record_dates->push($disease->record->created_at);

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
                'raw_date' => $disease->record->created_at,
                'date' => $disease->record->created_at->diffForHumans()
            ];
        });


        $result = [
            'patient_name' => auth()->user()->name,
            'diseases' => $formattedRecords->sortByDesc('raw_date')->values()->all()
        ];

        return response([
            'records' => $result
        ]);
    }


    public function store(User $patient)
    {
        if (auth()->user()->role->slug === 'doctor' || auth()->user()->role->slug === 'admin') {
            request()->validate([
                'disease_id' => 'required|exists:diseases,id',
                //validate if all ids in the array are valid
                'symptom_Ids' => [
                    'required',
                    'array',
                    Rule::exists('symptoms', 'id')
                ],
                'drug_Ids' => ['required', 'array', Rule::exists('drugs', 'id')],
            ]);

            $patient->diseases()->attach(request('disease_id'));


            // Attach the symptoms to the disease
            $disease = Disease::find(request('disease_id'));
            $disease->symptoms()->sync(request('symptom_Ids'));

            // Attach the drugs to the disease
            $disease->drugs()->sync(request('drug_Ids'));

            $record = $patient->diseases()->where('disease_id', request('disease_id'))->first()->record;


            return response()->json(['message' => 'Record added successfully', 'record' => $record]);
        } else {
            return response()->json([
                'message' => 'You are not authorized to perform this action'

            ]);
        }
    }

    public function diseasesData()
    {

        $diseasesCount = User::find(10)->diseases()
            ->select('disease_id', DB::raw('count(*) as total'))
            ->groupBy('disease_id')
            ->get();

        return response()->json(['diseases' => $diseasesCount]);
    }

    public function yearsVsDiseaseData($start, $end)
    {
        //make sure the start year is less than the end year
        if ($start > $end) {
            return response()->json(['message' => 'The start year must be less than the end year']);
        }
        //convert the start and end years to integers
        $start = (int)$start;
        $end = (int)$end;
        $years = collect([]);
        $data = collect([]);

        $options = [
            "responsive" => true,
            "maintainAspectRatio" => false,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => 'Number of Disease Contraction between Years ' . $start . ' and ' . $end,
                    "font" => [
                        "size" => 18
                    ]
                ]
            ]
        ];

        // Dynamically populate years with $start and $end years passed to the function
        for ($i = $start; $i <= $end; $i++) {
            $years->push($i);
        }

        //Group the diseases by year
        $diseasesGroupedByYear = DB::table('disease_user')
            ->join('diseases', 'disease_user.disease_id', '=', 'diseases.id')
            ->select('diseases.name', 'disease_user.created_at')
            ->get()
            ->groupBy(function ($record) {
                return Carbon::parse($record->created_at)->year;
            });

        // Count the number of diseases in each year
        foreach ($years as $year) {
            $yearDiseaseCount = $diseasesGroupedByYear->has($year) ? $diseasesGroupedByYear[$year]->count() : 0;
            $data->push($yearDiseaseCount);
        }


        return response([
            "data" => [
                'labels' => $years,
                'datasets' => [
                    [
                        'label' => 'Disease Count',
                        'borderColor' => "#413d3d81",
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

    public function monthsVsDiseasesPerYearSelected($year)
    {
        //steps
        /**
         * Get the current year which will be passed on the url
         * Get disease of that year
         * Group by months
         * Count the number of diseases in each month
         * return a json response containing data in vuechatjs format
         */


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
                    "text" => 'Number of Disease Contraction Each Month in ' . $year,
                    "font" => [
                        "size" => 18
                    ]
                ]
            ]
        ];

        //get all records from the disease_user pivot table
        $diseasesGroupedByMonthsOFChosenYear = DB::table('disease_user')
            ->join('diseases', 'disease_user.disease_id', '=', 'diseases.id')
            ->select('diseases.name', 'disease_user.created_at')
            ->whereYear('disease_user.created_at', $year)
            ->get()
            ->groupBy(function ($record) {
                return Carbon::parse($record->created_at)->format('F');
            });

        foreach ($months as $month) {
            $monthDiseaseCount = $diseasesGroupedByMonthsOFChosenYear->has($month) ? $diseasesGroupedByMonthsOFChosenYear[$month]->count() : 0;
            $data->push($monthDiseaseCount);
        }
        return response([
            "data" => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'Disease Count',
                        'borderColor' => "#413d3d81",
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
                        'borderColor' => "#413d3d81",
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
                        'borderColor' => "#413d3d81",
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
                        'borderColor' => "#413d3d81",
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
