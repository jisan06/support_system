<?php

namespace App\DataTables;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ActivityLogsDataTable extends DataTable
{
    protected array $searchableColumns = ['description', 'event', 'log_name', 'subject_type'];

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canDelete = auth()->user()->hasPermissionTo('delete_activity_log');
        return (new EloquentDataTable($query))
            ->addColumn('multi_check', function ($data) use ($canDelete) {
                return $canDelete ? '<input type="checkbox" class="action-checkbox" value="' . $data->id . '">' : '';
            })
            ->addColumn('causer', function ($data) {
                return $data->causer_name;
            })
            ->addColumn('description', function ($data) {
                return $data->description;
            })
            ->addColumn('event', function ($data) {
                $colors = [
                    'created'            => 'success',
                    'updated'            => 'info',
                    'deleted'            => 'warning',
                    'restored'           => 'primary',
                    'force_deleted'      => 'danger',
                    'permission_updated' => 'secondary',
                    'settings_updated'   => 'dark',
                ];
                $color = $colors[$data->event] ?? 'secondary';
                $label = __('activity_log.' . $data->event, [], 'en');
                return '<span class="badge bg-' . $color . '">' . $label . '</span>';
            })
            ->addColumn('subject', function ($data) {
                if ($data->subject_type) {
                    return $data->subject_label . ' #' . $data->subject_id;
                }
                return '-';
            })
            ->addColumn('created_at', function ($data) {
                return $data->created_at->format('Y-m-d H:i:s');
            })
            ->addColumn('action', function ($data) use ($canDelete) {
                $buttons = '<div class="d-flex gap-2 justify-content-center">';
                $buttons .= '<a href="' . route('activity-logs.show', $data->id) . '" class="btn btn-sm btn-outline-info"><i class="bx bx-show"></i></a>';
                if ($canDelete) {
                    $buttons .= view('components.action.delete-button', [
                        'id' => $data->id,
                        'route' => route('activity-logs.destroy', $data->id)
                    ])->render();
                }
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['multi_check', 'event', 'action'])
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
                'data-id' => function ($data) {
                    return $data->id;
                },
            ]);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ActivityLog $model): QueryBuilder
    {
        return $model->newQuery()->with(['causer', 'subject'])->latest();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('activity-logs-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy(5, 'desc')
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

            Column::computed('causer')
                ->title(__('activity_log.causer')),

            Column::make('description')
                ->title(__('activity_log.description')),

            Column::make('event')
                ->title(__('activity_log.event')),

            Column::computed('subject')
                ->title(__('activity_log.subject')),

            Column::make('created_at')
                ->title(__('activity_log.date')),

            Column::computed('action')
                    ->exportable(false)
                    ->printable(false)
                    ->width('12%')
                    ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ActivityLogs_' . date('YmdHis');
    }
}