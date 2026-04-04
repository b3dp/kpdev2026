<?php

namespace App\Http\Controllers;

use App\Models\BagisTuru;

class BagisController extends Controller
{
    public function index()
    {
        $bagisturleri = BagisTuru::orderBy('sira')->get();

        return view('pages.bagis.index', compact('bagisturleri'));
    }

    public function show(string $slug)
    {
        return view('welcome');
    }

    public function tesekkur()
    {
        return view('welcome');
    }
}
