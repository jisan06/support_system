<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketImportRequest;
use App\Models\TicketImport;
use App\Repositories\Contracts\TicketImportRepositoryInterface;
use App\Services\TicketImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketImportController extends Controller
{
    protected TicketImportService $importService;
    protected TicketImportRepositoryInterface $repository;

    public function __construct(
        TicketImportService $importService,
        TicketImportRepositoryInterface $repository
    ) {
        $this->importService = $importService;
        $this->repository = $repository;
    }

    /**
     * Display the Ticket Import dashboard with upload form and history.
     */
    public function index(): View
    {
        $imports = $this->repository->getPaginatedImports(10);

        return view('admin.ticket.import.index', compact('imports'));
    }

    /**
     * Handle CSV file upload and queue the import processing.
     */
    public function store(TicketImportRequest $request): RedirectResponse
    {
        $file = $request->file('csv_file');
        $import = $this->importService->initiateImport($file, auth()->user());

        return redirect()->route('tickets.import.index')
            ->with('success', "Ticket import '{$import->file_name}' ({$import->total_rows} rows) has been uploaded and queued for processing.");
    }

    /**
     * Download the sample CSV template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return $this->importService->generateTemplateCsv();
    }

    /**
     * Download error report CSV for a given import.
     */
    public function downloadErrors(TicketImport $ticketImport): StreamedResponse
    {
        return $this->importService->generateErrorReportCsv($ticketImport);
    }

    /**
     * Return live JSON progress for an ongoing import.
     */
    public function status(TicketImport $ticketImport): JsonResponse
    {
        return response()->json([
            'id'              => $ticketImport->id,
            'status'          => $ticketImport->status,
            'processed_rows'  => $ticketImport->processed_rows,
            'total_rows'      => $ticketImport->total_rows,
            'successful_rows' => $ticketImport->successful_rows,
            'failed_rows'     => $ticketImport->failed_rows,
            'duplicate_rows'  => $ticketImport->duplicate_rows,
            'progress'        => $ticketImport->progressPercentage(),
        ]);
    }
}

