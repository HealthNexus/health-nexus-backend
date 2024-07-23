<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function index()
    {
        $id = 0;
        $record_dates = collect([]);
        $formattedRecords = auth()->user()->diseases->map(function ($disease) use (&$id, &$record_dates) {
            $id++;

            $record_dates->push($disease->records->created_at);

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
}
