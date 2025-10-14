<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MiddlewareController extends Controller
{
    public function index()
    {
        return view('middleware.middleware', ['content' => 'ミドルウェアテストページ']);
    }

    public function post(Request $request)
    {
        $content = $request->input('content', '');
        return view('middleware.middleware', ['content' => $content . ' が送信されました']);
    }
} 