<?php

namespace App\Http\Controllers;

class EtkinlikController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function show(string $slug)
    {
        return view('welcome');
    }
}
