<?php

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\UserUsecase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected UserUsecase $usecase
    ) {}
}
