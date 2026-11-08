@extends('admin.layouts.master')
@section('title', 'Import Tickets')

@section('content')
<div class="row item-list-wrap">
    <div class="col-12">
        <div class="card">
            <x-table.header
                :title="trans_choice('menu.ticket', 2)"
                icon="bx bx-support"
                :tabs="[
                    ['label' => 'All Tickets', 'route' => 'tickets.index', 'icon' => 'bx bx-list-ul'],
                    ['label' => 'Import Tickets', 'route' => 'tickets.import.index', 'icon' => 'bx bx-upload', 'permission' => 'create_ticket'],
                ]"
                :add-button="['label'=> __('permission.create_ticket'), 'route'=>'tickets.create', 'icon'=>'fas fa-plus-circle', 'permission' => 'create_ticket']"
            />

            {{-- Import Upload Section --}}
            <div class="card-body">
                <div class="row">
                    {{-- Upload Form --}}
                    <div class="col-lg-6 mb-4 mb-lg-0 border-end-lg">
                        <div class="pe-lg-3">
                            <h5 class="font-size-15 fw-bold mb-2 text-dark">
                                <i class="bx bx-upload text-primary me-1"></i> Upload Tickets CSV
                            </h5>
                            <p class="text-muted font-size-13 mb-3">
                                Upload a CSV file containing support tickets. Records are validated independently, processed in chunks via background queues, and duplicates are automatically prevented.
                            </p>

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show font-size-13" role="alert">
                                    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger font-size-13 p-3 mb-3" role="alert">
                                    <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i> Upload failed:</div>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('tickets.import.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-3">
                                    <label for="csv_file" class="form-label fw-semibold font-size-13">
                                        Select CSV File <span class="text-danger">*</span>
                                    </label>
                                    <input type="file"
                                        name="csv_file"
                                        id="csv_file"
                                        class="form-control @error('csv_file') is-invalid @enderror"
                                        accept=".csv,text/csv,text/plain"
                                        required
                                    >
                                    @error('csv_file')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <div class="form-text text-muted font-size-12 mt-1">
                                        Supported format: <code>.csv</code> up to 20MB.
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 align-items-center pt-2">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bx bx-cloud-upload me-1 align-middle"></i> Start Import
                                    </button>

                                    <a href="{{ route('tickets.import.template') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-download me-1 align-middle"></i> Download Sample CSV
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- CSV Structure Instructions --}}
                    <div class="col-lg-6">
                        <div class="ps-lg-3">
                            <h5 class="font-size-15 fw-bold mb-2 text-dark">
                                <i class="bx bx-info-circle text-info me-1"></i> Expected CSV Format
                            </h5>
                            <p class="text-muted font-size-13 mb-3">
                                Ensure your CSV file includes the required header row with the exact column names below:
                            </p>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm font-size-12 mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Column</th>
                                            <th>Required?</th>
                                            <th>Description / Accepted Values</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>customer_email</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Email address of an active customer account in the system.</td>
                                        </tr>
                                        <tr>
                                            <td><code>subject</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Ticket subject summary (max 255 characters).</td>
                                        </tr>
                                        <tr>
                                            <td><code>description</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Initial inquiry or issue description (min 2 characters).</td>
                                        </tr>
                                        <tr>
                                            <td><code>priority</code></td>
                                            <td><span class="badge bg-secondary">Optional</span></td>
                                            <td><code>low</code>, <code>medium</code>, <code>high</code>, <code>urgent</code> (default: <code>medium</code>).</td>
                                        </tr>
                                        <tr>
                                            <td><code>category</code></td>
                                            <td><span class="badge bg-secondary">Optional</span></td>
                                            <td>Configured category, e.g. <code>Technical Support</code>, <code>Billing & Payments</code>, etc.</td>
                                        </tr>
                                        <tr>
                                            <td><code>status</code></td>
                                            <td><span class="badge bg-secondary">Optional</span></td>
                                            <td><code>open</code>, <code>in_progress</code>, <code>waiting_for_customer</code>, <code>resolved</code>, <code>closed</code> (default: <code>open</code>).</td>
                                        </tr>
                                        <tr>
                                            <td><code>assigned_agent</code></td>
                                            <td><span class="badge bg-secondary">Optional</span></td>
                                            <td>Email address of an active staff member to assign the ticket to.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-light border font-size-12 mb-0">
                                <strong class="text-dark"><i class="bx bx-shield-quarter text-warning me-1"></i> Duplicate Prevention Strategy:</strong>
                                A row is flagged as duplicate if an active ticket (<code>open</code> or <code>in_progress</code>) with the identical subject already exists for the customer in the database, or appeared earlier in the same CSV file. Duplicates are skipped safely and noted in the error report.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Import History & Tracking --}}
            <div class="card-header bg-white border-top py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title font-size-15 mb-0">
                        <i class="bx bx-history text-muted me-1"></i> Import History & Progress
                    </h5>
                    <a href="{{ route('tickets.import.index') }}" class="btn btn-sm btn-light border text-muted">
                        <i class="bx bx-refresh me-1"></i> Refresh
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 font-size-13">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>File Name</th>
                                <th>Uploaded By</th>
                                <th>Upload Date</th>
                                <th style="min-width: 130px;">Progress</th>
                                <th>Total Rows</th>
                                <th>Success</th>
                                <th>Failed</th>
                                <th>Duplicates</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $hasRunningImport = false;
                            @endphp
                            @forelse($imports as $import)
                                @php
                                    if ($import->isProcessing() || $import->isPending()) {
                                        $hasRunningImport = true;
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-3 fw-bold text-muted">#{{ $import->id }}</td>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $import->file_name }}</span>
                                    </td>
                                    <td>{{ $import->uploader?->name ?? 'System' }}</td>
                                    <td class="text-muted font-size-12">
                                        {{ format_datetime($import->created_at, 'M d, Y H:i') }}
                                    </td>
                                    <td>
                                        @php
                                            $pct = $import->progressPercentage();
                                        @endphp
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar {{ $import->isCompleted() ? 'bg-success' : ($import->isFailed() ? 'bg-danger' : 'bg-primary progress-bar-striped progress-bar-animated') }}"
                                                     role="progressbar"
                                                     style="width: {{ $pct }}%;"
                                                     aria-valuenow="{{ $pct }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="font-size-11 text-muted">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">{{ number_format($import->total_rows) }}</td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold font-size-12 px-2 py-1">
                                            {{ number_format($import->successful_rows) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($import->failed_rows > 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold font-size-12 px-2 py-1">
                                                {{ number_format($import->failed_rows) }}
                                            </span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($import->duplicate_rows > 0)
                                            <span class="badge bg-warning bg-opacity-10 text-warning fw-bold font-size-12 px-2 py-1">
                                                {{ number_format($import->duplicate_rows) }}
                                            </span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $import->statusBadgeClass() }} text-uppercase font-size-11">
                                            {{ $import->status }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if($import->failed_rows > 0 || $import->duplicate_rows > 0 || (!empty($import->errors) && count($import->errors) > 0))
                                            <a href="{{ route('tickets.import.errors', $import->id) }}"
                                               class="btn btn-sm btn-outline-danger"
                                               title="Download Error Report CSV">
                                                <i class="bx bx-download me-1"></i> Error Report
                                            </a>
                                        @else
                                            <span class="text-muted font-size-12">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <i class="bx bx-import display-4 d-block mb-2 text-muted"></i>
                                        No ticket imports found. Upload your first CSV file above.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($imports->hasPages())
                <div class="card-footer bg-white border-top-0 d-flex justify-content-end py-3">
                    {{ $imports->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@if(!empty($hasRunningImport))
    {{-- Auto-refresh if an import is currently in pending or processing state --}}
    @push('scripts')
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    </script>
    @endpush
@endif
@endsection

