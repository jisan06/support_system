<?php

namespace App\DataTables;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NotificationsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canDelete = auth()->user()->hasPermissionTo('delete_notification');
        return (new EloquentDataTable($query))
            ->addColumn('multi_check', function ($row) use ($canDelete) {
                return $canDelete ? '<input type="checkbox" class="action-checkbox" value="' . $row->id . '">' : '';
            })
            ->addColumn('title', function ($row) {
                $title = e($row->data['title'] ?? '');
                $url = $row->data['url'] ?? null;
                $bold = is_null($row->read_at) ? '<strong>' . $title . '</strong>' : $title;
                $openUrl = $url ? route('notifications.open', $row->id) : null;
                return $openUrl ? '<a href="' . e($openUrl) . '">' . $bold . '</a>' : $bold;
            })
            ->addColumn('message', function ($row) {
                return e($row->data['message'] ?? '');
            })
            ->addColumn('notification_type', function ($row) {
                $type = $row->data['type'] ?? 'info';
                $colors = ['info' => 'info', 'success' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
                return '<span class="badge bg-' . ($colors[$type] ?? 'secondary') . '">' . ucfirst($type) . '</span>';
            })
            ->addColumn('status', function ($row) {
                return is_null($row->read_at)
                    ? '<span class="badge bg-primary">' . __('notification.unread') . '</span>'
                    : '<span class="badge bg-secondary">' . __('notification.read') . '</span>';
            })
            ->addColumn('created_at', function ($row) {
                return '<span title="' . $row->created_at->format('Y-m-d H:i:s') . '">' . $row->created_at->diffForHumans() . '</span>';
            })
            ->addColumn('action', function ($row) use ($canDelete) {
                $buttons = '<div class="d-flex gap-2 justify-content-center">';
                if (is_null($row->read_at)) {
                    $buttons .= '<button data-id="' . $row->id . '" data-route="' . route('notifications.mark_read', $row->id) . '" class="btn btn-sm btn-outline-success mark-read-btn" title="' . __('notification.mark_as_read') . '"><i class="bx bx-check"></i></button>';
                }
                if ($canDelete) {
                    $buttons .= view('components.action.delete-button', [
                        'id' => $row->id,
                        'route' => route('notifications.destroy', $row->id)
                    ])->render();
                }
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['multi_check', 'title', 'notification_type', 'status', 'created_at', 'action'])
            ->filter(function ($query) {
                if (request()->has('search') && request('search')['value']) {
                    $searchTerm = request('search')['value'];
                    if ($searchTerm) {
                        $query->where('data', 'like', '%' . $searchTerm . '%');
                    }
                }
            })
            ->setRowAttr([
                'data-id' => fn($row) => $row->id,
            ]);
    }

    public function query(DatabaseNotification $model): QueryBuilder
    {
        return $model->newQuery()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', auth()->id())
            ->latest();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('notifications-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(5, 'desc')
            ->selectStyleSingle()
            ->language(['lengthMenu' => '_MENU_'])
            ->setTableAttribute('class', 'dataTable table table-bordered table-striped dt-responsive nowrap w-100 no-footer dtr-inline')
            ->parameters(['select' => ['info' => false]]);
    }

    public function getColumns(): array
    {
        return [
            Column::computed('multi_check')
                ->exportable(false)->printable(false)->width('5%')
                ->title('<input type="checkbox" id="select_all">')
                ->addClass('text-center'),
            Column::computed('title')->title(__('notification.title_label')),
            Column::computed('message')->title(__('notification.message')),
            Column::computed('notification_type')->title(__('notification.type'))->width('8%'),
            Column::computed('status')->title(__('notification.status'))->width('8%'),
            Column::make('created_at')->title(__('notification.date'))->width('12%'),
            Column::computed('action')->exportable(false)->printable(false)->width('10%')->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Notifications_' . date('YmdHis');
    }
}
