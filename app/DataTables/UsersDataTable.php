<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Route;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
{

    protected array $searchableColumns = ['name', 'email', 'status'];

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $routeName = Route::currentRouteName();
        $user = auth()->user();
        $canEdit = $user->hasPermissionTo('edit_user');
        $canDelete = $user->hasPermissionTo('delete_user');
        return (new EloquentDataTable($query))
            ->addColumn('multi_check', function ($data) use ($canDelete) {
                if ($canDelete && $data->id !== auth()->id() && $data->id !== 1) {
                    return '<input type="checkbox" class="action-checkbox" value="' . $data->id . '">';
                }
                return '';
            })
            ->addColumn('name', function ($data) {
                return $data->name;
            })
            ->addColumn('roles', function ($data) {
                return $data->roles->pluck('name')->implode(', ');
            })
            ->addColumn('status', function ($data) {
                return trans('status.' . $data->status);
            })
            ->addColumn('action', function ($data) use ($routeName, $canEdit, $canDelete) {
                $isProtected = $data->id === auth()->id() || $data->id === 1;
                $canEditThis = $canEdit && ($data->id !== 1 || auth()->id() === 1);

                $buttons = '<div class="d-flex gap-2 justify-content-center">';
                if ($routeName === 'users.trash') {
                    if ($canDelete) {
                        $buttons .= view('components.action.restore-button', [
                            'id' => $data->id,
                            'route' => route('users.restore', $data->id)
                        ])->render();

                        if (!$isProtected) {
                            $buttons .= view('components.action.delete-button', [
                                'id' => $data->id,
                                'route' => route('users.force-delete', $data->id)
                            ])->render();
                        }
                    }

                    $buttons .= '</div>';
                    return $buttons;
                }

                $buttons .= view('components.action.view-button', [
                    'route' => route('users.show', $data->id)
                ])->render();

                if ($canEditThis) {
                    $buttons .= view('components.action.edit-button', [
                        'route' => route('users.edit', $data->id)
                    ])->render();
                }

                if ($canDelete && !$isProtected) {
                    $buttons .= view('components.action.delete-button', [
                        'id' => $data->id,
                        'route' => route('users.destroy', $data->id)
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
                        foreach ($this->searchableColumns as $column) {
                            $query->orWhere($column, 'like', '%' . $searchTerm . '%');
                        }
                    }
                }
            })
            ->setRowAttr([
                'data-id' => function ($data) {
                    return $data->id;
                },
            ]);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        $routeName = Route::currentRouteName();
        $myLevel = auth()->user()->roleLevel();

        // Admin User Management strictly lists internal staff/admin accounts
        $query = (!empty($routeName) && $routeName === 'users.trash')
            ? $model->onlyTrashed()->isAdmin()
            : $model->newQuery()->isAdmin();

        return $query
            ->with('roles:id,name')
            ->whereDoesntHave('roles', fn ($q) => $q->where('level', '<', $myLevel));
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('users-table')
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
                            'info' => false, // ✅ disables "1 row selected" in footer
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

            Column::make('name')
                ->title(trans('user.name')),

            Column::make('email')
                ->title(trans('user.email')),

            Column::computed('roles')
                ->title(trans('user.roles')),

            Column::make('status')
                ->title(trans('status.status')),

            Column::computed('action')
                    ->exportable(false)
                    ->printable(false)
                    ->width('15%')
                    ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Users_' . date('YmdHis');
    }
}
