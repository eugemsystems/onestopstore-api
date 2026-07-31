<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\OrderNote;
use App\Helpers\Helpers;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class OrderNoteRepository extends BaseRepository
{
    public function boot()
    {
        try {
            $this->pushCriteria(app(RequestCriteria::class));
        } catch (ExceptionHandler $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        return OrderNote::class;
    }

    public function index($request)
    {
        $q = $this->model->newQuery();
        if ($request->order_id) {
            $q->where('order_id', $request->order_id);
        }
        if ($request->privacy) {
            $q->where('privacy', $request->privacy);
        }
        return $q->latest('created_at')->paginate($request->paginate ?? $q->count());
    }

    public function store($request)
    {
        return $this->model->create([
            'order_id' => $request->order_id,
            'note' => $request->note,
            'privacy' => $request->privacy,
            'created_by_id' => Helpers::getCurrentUserId(),
        ]);
    }

    public function show($id)
    {
        return $this->model->findOrFail($id);
    }

    public function update($request, $id)
    {
        $note = $this->model->findOrFail($id);
        $note->update($request);
        return $note->fresh();
    }

    public function destroy($id)
    {
        return $this->model->destroy($id);
    }
}
