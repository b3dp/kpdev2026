<?php

namespace App\Http\Controllers;

class KurumsalController extends Controller
{
    public function show(?string $slug = null)
    {
        return view('welcome');
    }
}
