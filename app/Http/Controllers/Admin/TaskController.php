<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ResponseConst;
use App\Constants\TaskStatusConst;
use App\Http\Controllers\Controller;
use App\Usecase\TaskCategoryUsecase;
use App\Usecase\TaskUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    protected array $page = [
        'route' => 'tasks',
        'title' => 'Manajemen Tugas',
    ];

    protected string $baseRedirect;

    public function __construct(
        protected TaskUsecase $usecase,
        protected TaskCategoryUsecase $categoryUsecase
    ) {
        $this->baseRedirect = 'admin/'.$this->page['route'];
    }

    public function index(Request $request): View|Response
    {
        $data = $this->usecase->getAll([
            'keywords' => $request->get('keywords'),
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
        ]);
        $data = $data['data']['list'] ?? [];

        $categories = $this->categoryUsecase->getAll(['no_pagination' => true]);
        $categories = $categories['data']['list'] ?? [];

        return view('_admin.tasks.index', [
            'data' => $data,
            'page' => $this->page,
            'keywords' => $request->get('keywords'),
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
            'categories' => $categories,
            'statuses' => TaskStatusConst::getList(),
        ]);
    }

    public function add(): View|Response
    {
        $categories = $this->categoryUsecase->getAll(['no_pagination' => true]);
        $categories = $categories['data']['list'] ?? [];

        return view('_admin.tasks.add', [
            'page' => $this->page,
            'categories' => $categories,
            'statuses' => TaskStatusConst::getList(),
        ]);
    }

    public function doCreate(Request $request): RedirectResponse
    {
        $process = $this->usecase->create(
            data: $request,
        );

        if ($process['success']) {
            return redirect()
                ->route('admin.tasks.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_CREATED);
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function update(int $id): View|RedirectResponse|Response
    {
        $data = $this->usecase->getByID($id);

        if (empty($data['data'])) {
            return redirect()
                ->intended($this->baseRedirect)
                ->with('error', ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
        $data = $data['data'] ?? [];

        $categories = $this->categoryUsecase->getAll(['no_pagination' => true]);
        $categories = $categories['data']['list'] ?? [];

        return view('_admin.tasks.update', [
            'data' => (object) $data,
            'id' => $id,
            'page' => $this->page,
            'categories' => $categories,
            'statuses' => TaskStatusConst::getList(),
        ]);
    }

    public function doUpdate(int $id, Request $request): RedirectResponse
    {
        $process = $this->usecase->update(
            data: $request,
            id: $id,
        );

        if ($process['success']) {
            return redirect()
                ->route('admin.tasks.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_UPDATED);
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function delete(int $id): RedirectResponse
    {
        $process = $this->usecase->delete(id: $id);

        if ($process['success']) {
            return redirect()
                ->route('admin.tasks.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_DELETED);
        } else {
            return redirect()
                ->route('admin.tasks.index')
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }
}
