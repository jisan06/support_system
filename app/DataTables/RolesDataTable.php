<?php

namespace App\DataTables;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Route;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RolesDataTable extends DataTable
{

    protected array $searchableColumns = ['name', 'status'];

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $routeName = Route::currentRouteName();
        $user = auth()->user();
        $userRoleIds = $user->roles->pluck('id')->toArray();
        $canEdit = $user->hasPermissionTo('edit_role');
        $canDelete = $user->hasPermissionTo('delete_role');
        return (new EloquentDataTable($query))
            ->addColumn('multi_check', function ($role) use ($userRoleIds, $canDelete) {
                if (!$canDelete || in_array($role->id, $userRoleIds)) {
                    return '';
                }
                return '<input type="checkbox" class="action-checkbox" value="' . $role->id . '">';
            })
            ->addColumn('name', function ($role) {
                return $role->name;
            })
            ->addColumn('status', function ($role) {
                return trans('status.' . $role->status);
            })
            ->addColumn('action', function ($role) use ($routeName, $userRoleIds, $canEdit, $canDelete) {
                $isOwnRole = in_array($role->id, $userRoleIds);

                if ($routeName === 'roles.trash') {
                    $html = '<div class="d-flex gap-2 justify-content-center">';
                    if ($canDelete) {
                        $html .= view('components.action.restore-button', [
                                'id' => $role->id,
                                'route' => route('roles.restore', $role->id)
                            ])->render();
                        if (!$isOwnRole) {
                            $html .= view('components.action.delete-button', [
                                    'id' => $role->id,
                                    'route' => route('roles.force-delete', $role->id)
                                ])->render();
                        }
                    }
                    return $html . '</div>';
                }

                $html = '<div class="d-flex gap-2 justify-content-center">';
                if ($canEdit) {
                    $html .= view('components.action.edit-button', [
                            'route' => route('roles.edit', $role->id)
                        ])->render() .
                        view('components.action.permission-button', [
                            'id' => $role->id,
                            'route' => route('roles.permission', $role->id)
                        ])->render();
                }
                if ($canDelete && !$isOwnRole) {
                    $html .= view('components.action.delete-button', [
                            'id' => $role->id,
                            'route' => route('roles.destroy', $role->id)
                        ])->render();
                }
                return $html . '</div>';
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
                'data-id' => function ($role) {
                    return $role->id;
                },
            ]);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Role $model): QueryBuilder
    {
        $routeName = Route::currentRouteName();
        $myLevel = auth()->user()->roleLevel();

        $query = (!empty($routeName) && $routeName === 'roles.trash')
            ? $model->onlyTrashed()
            : $model->newQuery();

        return $query->where('level', '>=', $myLevel);
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('roles-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy(2, 'asc')
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
                ->title(trans('role.name')),

            Column::make('level')
                ->title(trans('role.level'))
                ->width('10%')
                ->addClass('text-center'),

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
        return 'Roles_' . date('YmdHis');
    }
}
