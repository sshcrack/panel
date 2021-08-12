<?php

namespace Kriegerhost\Http\Controllers\Admin;

use Illuminate\View\View;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    /**
     * @var \Kriegerhost\Services\Helpers\SoftwareVersionService
     */
    private $version;

    /**
     * BaseController constructor.
     */
    public function __construct(SoftwareVersionService $version)
    {
        $this->version = $version;
    }

    /**
     * Return the admin index view.
     */
    public function index(): View
    {
        return view('admin.index', ['version' => $this->version]);
    }
}
