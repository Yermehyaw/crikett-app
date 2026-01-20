<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use L0n3ly\LaravelDynamicHelpers\Helper;

class QueryableHelper extends Helper
{
    /**
     * Get pagination details from a paginated model.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $model
     * @return array
     */
    public function getPagination($model)
    {
        return [
            'from' => $model->firstItem(),
            'to' => $model->lastItem(),
            'total' => $model->total(),
            'per_page' => $model->perPage(),
            'first_page' => 1,
            'previous_page' => $model->currentPage() > 1
                ? $model->currentPage() - 1
                : null,
            'current_page' => $model->currentPage(),
            'next_page' => $model->currentPage() < $model->lastPage()
                ? $model->currentPage() + 1
                : null,
            'last_page' => $model->lastPage(),
        ];
    }

    /**
     * Apply common filters like search, status, sorting, and pagination to a queryable model.
     *
     * @param  callable|null  $extraQuery  Optional additional query logic.
     */
    public function fetchWithFilters(Model|EloquentBuilder|QueryBuilder $model, ?callable $extraQuery = null): LengthAwarePaginator
    {
        $this->validate();
        if ($model instanceof Model) {
            $model = $model->newQuery();
        }
        if (request()->filled('status') && $this->hasColumn($model, 'status')) {
            $model->where('status', request('status'));
        }
        if ($extraQuery) {
            $extraQuery($model);
        }
        $sortBy = request('sort_by', 'created_at');
        $sortOrder = request('sort_order', 'desc');
        $perPage = request('per_page', 10);
        $model->orderBy($sortBy, $sortOrder);

        return $model->paginate($perPage);
    }

    /**
     * Check if the query’s table has a given column.
     */
    protected function hasColumn($query, string $column): bool
    {
        $table = $query->getModel()->getTable();

        return Schema::hasColumn($table, $column);
    }

    public function validate()
    {
        request()->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'sort_by' => 'nullable|string|in:first_name,last_name,created_at',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
    }
}
