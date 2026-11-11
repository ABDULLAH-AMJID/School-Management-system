<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', ['metrics' => [
            ['label' => 'Total students', 'value' => '1,248', 'delta' => '+32 this month', 'tone' => 'violet', 'icon' => 'users'],
            ['label' => 'Present today', 'value' => '94.8%', 'delta' => '1,183 of 1,248', 'tone' => 'green', 'icon' => 'check'],
            ['label' => 'Fees collected', 'value' => '₨ 2.48M', 'delta' => '82% of November', 'tone' => 'blue', 'icon' => 'wallet'],
            ['label' => 'Pending approvals', 'value' => '12', 'delta' => '4 require attention', 'tone' => 'amber', 'icon' => 'clock'],
        ]]);
    }
}
