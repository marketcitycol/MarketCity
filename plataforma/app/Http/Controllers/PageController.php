<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->isPublished(), 404);

        return view('shop.page', ['page' => $page]);
    }
}
