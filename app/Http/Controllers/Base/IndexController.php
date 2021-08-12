<?php

namespace Kriegerhost\Http\Controllers\Base;

use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;

class IndexController extends Controller
{
    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * IndexController constructor.
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns listing of user's servers.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('templates/base.core');
    }
}
