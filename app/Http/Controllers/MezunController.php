<?php

namespace App\Http\Controllers;

class MezunController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function show(string $id)
    {
        return view('welcome');
    }
}
