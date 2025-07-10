<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CheckInExport implements FromView
{
    protected $filteredCheckIns;

    public function __construct($filteredCheckIns)
    {
        $this->filteredCheckIns = $filteredCheckIns;
    }

    public function view(): View
    {
        return view('exports.checkins', [
            'checkIns' => $this->filteredCheckIns
        ]);
    }
}
