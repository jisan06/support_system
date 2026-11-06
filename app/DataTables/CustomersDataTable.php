<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Route;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CustomersDataTable extends DataTable
{
    protected array $searchableColumns = ['name', 'email', 'status'];

    /**
     * Build the DataTable class.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $routeName = Route::currentRouteName();
        $user = auth()->user();
        $canEdit = $user->hasPermissionTo('edit_customer');
        $canDelete = $user->hasPermissionTo('delete_customer');

        return (new EloquentDataTable($query))
            ->addColumn('multi_check', function ($data) use ($canDelete) {
                if ($canDelete) {
                    return '<input type="checkbox" class="action-checkbox" value="' . $data->id . '">';
                }
                return '';
            })
            ->addColumn('name', function ($data) {
                return $data->name;
            })
            ->addColumn('email', function ($data) {
                return $data->email;
            })
            ->addColumn('status', function ($data) {
                return trans('status.' . $data->status);
            })
            ->addColumn('created_at', function ($data) {
                return $data->created_at?->format('d M, Y H:i');
            })
            ->addColumn('action', function ($data) use ($routeName, $canEdit, $canDelete) {
                $buttons = '<div class="d-flex gap-2 justify-content-center">';

                if ($routeName === 'customers.trash') {
                    if ($canDelete) {
                        $buttons .= view('components.action.restore-button', [
                            'id' => $data->id,
                            'route' => route('customers.restore', $data->id),
                        ])->render();

                        $buttons .= view('components.action.delete-button', [
                            'id' => $data->id,
                            'route' => route('customers.force-delete', $data->id),
                        ])->render();
                    }

                    $buttons .= '</div>';
                    return $buttons;
                }

                $buttons .= view('components.action.view-button', [
                    'route' => route('customers.show', $data->id),
                ])->render();

                if ($canEdit) {
                    $buttons .= view('components.action.edit-button', [
                        'route' => route('customers.edit', $data->id),
                    ])->render();
                }

                if ($canDelete) {
                    $buttons .= view('components.action.delete-button', [
                        'id' => $data->id,
                        'route' => route('customers.destroy', $data->id),
                    ])->render();
                }

                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['multi_check', 'action'])
            ->filter(function ($query) {
                if (request()->has('search') && request('search')['value']) {
                    $searchTerm = request()->get('search')['value'];
                    if ($searchTerm) {
                        $query->where(function ($q) use ($searchTerm) {
                            foreach ($this->searchableColumns as $column) {
                                $q->orWhere($column, 'like', '%' . $searchTerm . '%');
                            }
                        });
                    }
                }
            })
            ->setRowAttr([
                'data-id' => fn ($data) => $data->id,
            ]);
    }

    /**
     * Get the query source of dataTable.
     * Enforces User::isCustomer() scope.
     */
    public function query(User $model): QueryBuilder
    {
        $routeName = Route::currentRouteName();

        $query = (!empty($routeName) && $routeName === 'customers.trash')
            ? $model->onlyTrashed()->isCustomer()
            : $model->newQuery()->isCustomer();

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('customers-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy(1)
                    ->selectStyleSingle()->language([
                        'lengthMenu' => '_MENU_',
                    ])
                    ->setTableAttribute(
                        'class',
                        'dataTable table table-bordered table-striped dt-responsive nowrap w-100 no-footer dtr-inline'
                    )
                    ->parameters([
                        'select' => [
                            'info' => false,
                        ],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('multi_check')
                ->exportable(false)
                ->printable(false)
                ->width('5%')
                ->title('<input type="checkbox" id="select_all">')
                ->addClass('text-center'),
            Column::make('name')->title(__('user.name')),
            Column::make('email')->title(__('user.email')),
            Column::make('status')->title(__('common.status')),
            Column::make('created_at')->title(__('common.created_at')),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('12%')
                ->title(__('common.action'))
                ->addClass('text-center'),
        ];
    }
}

