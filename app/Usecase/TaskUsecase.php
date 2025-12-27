<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskUsecase extends Usecase
{
    public function getAll(array $filterData = []): array
    {
        try {
            $query = DB::table(DatabaseConst::TASK.' as t')
                ->leftJoin(DatabaseConst::TASK_CATEGORY.' as tc', 't.task_category_id', '=', 'tc.id')
                ->select('t.*', 'tc.name as category_name')
                ->whereNull('t.deleted_at')
                ->when($filterData['keywords'] ?? false, function ($query, $keywords) {
                    return $query->where('t.title', 'like', '%'.$keywords.'%');
                })
                ->when($filterData['status'] ?? false, function ($query, $status) {
                    return $query->where('t.status', $status);
                })
                ->when($filterData['category_id'] ?? false, function ($query, $categoryId) {
                    return $query->where('t.task_category_id', $categoryId);
                })
                ->orderBy('t.created_at', 'desc');

            if (! empty($filterData['no_pagination'])) {
                $data = $query->get();
            } else {
                $data = $query->paginate(20);

                if (! empty($filterData)) {
                    $data->appends($filterData);
                }
            }

            // Manually cast dates for the collection
            $data->getCollection()->transform(function ($item) {
                $item->task_date = \Carbon\Carbon::parse($item->task_date);

                return $item;
            });

            return Response::buildSuccess(
                [
                    'list' => $data,
                ],
                ResponseConst::HTTP_SUCCESS
            );
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function getByID(int $id): array
    {
        try {
            $data = DB::table(DatabaseConst::TASK)
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (! $data) {
                return Response::buildErrorService(ResponseConst::ERROR_MESSAGE_NOT_FOUND);
            }

            // Parse types manually since we are not using Eloquent Casts
            $data->task_date = \Carbon\Carbon::parse($data->task_date);

            return Response::buildSuccess(
                data: collect($data)->toArray()
            );
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function create(Request $data): array
    {
        $validator = Validator::make($data->all(), [
            'title' => 'required|string|max:255',
            'task_category_id' => 'required|exists:task_categories,id',
            'task_date' => 'required|date',
            'status' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $validator->validate();

        DB::beginTransaction();
        try {
            $payload = $data->only(['title', 'task_category_id', 'task_date', 'status', 'description']);
            $payload['created_by'] = Auth::user()?->id;
            $payload['created_at'] = now();
            $payload['updated_at'] = now();

            DB::table(DatabaseConst::TASK)->insert($payload);

            DB::commit();

            return Response::buildSuccessCreated();
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function update(Request $data, int $id): array
    {
        $validator = Validator::make($data->all(), [
            'title' => 'required|string|max:255',
            'task_category_id' => 'required|exists:task_categories,id',
            'task_date' => 'required|date',
            'status' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $validator->validate();

        DB::beginTransaction();

        try {
            $payload = $data->only(['title', 'task_category_id', 'task_date', 'status', 'description']);
            $payload['updated_by'] = Auth::user()?->id;
            $payload['updated_at'] = now();

            DB::table(DatabaseConst::TASK)
                ->where('id', $id)
                ->update($payload);

            DB::commit();

            return Response::buildSuccess(
                message: ResponseConst::SUCCESS_MESSAGE_UPDATED
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        DB::beginTransaction();

        try {
            $delete = DB::table(DatabaseConst::TASK)
                ->where('id', $id)
                ->update([
                    'deleted_by' => Auth::user()?->id,
                    'deleted_at' => now(),
                ]);

            if (! $delete) {
                DB::rollback();
                throw new Exception('FAILED DELETE DATA');
            }

            DB::commit();

            return Response::buildSuccess(
                message: ResponseConst::SUCCESS_MESSAGE_DELETED
            );
        } catch (\Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }
}
