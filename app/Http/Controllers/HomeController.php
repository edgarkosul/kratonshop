<?php


namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Support\ViewModels\HomePageViewModel;

class HomeController extends Controller
{
    public function index(): View
    {
        $vm = new HomePageViewModel();

        return view('pages.home', $vm->toArray());
    }
}
