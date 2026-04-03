<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EkayitController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function form()
    {
        return view('welcome');
    }

    public function store(Request $request)
    {
        return redirect()->route('ekayit.tesekkur');
    }

    public function tesekkur()
    {
        return view('welcome');
    }
}
