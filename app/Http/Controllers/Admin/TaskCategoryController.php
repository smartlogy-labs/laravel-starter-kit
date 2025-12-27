<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\TaskCategoryUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskCategoryController extends Controller
{
    protected array $page = [
        'route' => 'task_categories',
        'title' => 'Kategori Tugas',
    ];

    protected string $baseRedirect;

    public function __construct(
        protected TaskCategoryUsecase $usecase
    ) {
        $this->baseRedirect = 'admin/'.$this->page['route'];
    }

    public function index(Request $request): View|Response
    {
        $data = $this->usecase->getAll([
            'keywords' => $request->get('keywords'),
        ]);
        $data = $data['data']['list'] ?? [];

        return view('_admin.task_categories.index', [
            'data' => $data,
            'page' => $this->page,
            'keywords' => $request->get('keywords'),
        ]);
    }

    public function add(): View|Response
    {
        return view('_admin.task_categories.add', [
            'page' => $this->page,
        ]);
    }

    public function doCreate(Request $request): RedirectResponse
    {
        $process = $this->usecase->create(
            data: $request,
        );

        if ($process['success']) {
            return redirect()
                ->route('admin.task_categories.index')
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

        return view('_admin.task_categories.update', [
            'data' => (object) $data,
            'id' => $id,
            'page' => $this->page,
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
                ->route('admin.task_categories.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_UPDATED);
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->with('success', ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function delete(int $id): RedirectResponse
    {
        $process = $this->usecase->delete(id: $id);

        if ($process['success']) {
            return redirect()
                ->route('admin.task_categories.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_DELETED);
        } else {
            return redirect()
                ->route('admin.task_categories.index')
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }
}
