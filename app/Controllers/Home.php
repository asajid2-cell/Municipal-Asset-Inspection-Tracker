<?php

namespace App\Controllers;

/**
 * Basic project landing page for the current foundation build.
 */
class Home extends BaseController
{
    public function index(): string
    {
        return view('home', [
            'projectName' => 'Municipal Asset & Inspection Tracker',
            'currentBuild' => 'Current build: foundation, schema, and demo data',
        ]);
    }
}
