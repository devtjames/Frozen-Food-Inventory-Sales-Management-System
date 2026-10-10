<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __invoke(): View
    {
        return view('cashier.pos');
    }
}
