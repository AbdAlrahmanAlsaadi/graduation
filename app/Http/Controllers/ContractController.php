<?php

namespace App\Http\Controllers;

use App\Http\Requests\Misc\StoreContractRequest;
use App\Http\Responses\Response;
use App\Services\ContractService;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use Throwable;

class ContractController extends Controller
{
    protected ContractService $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function store(StoreContractRequest $request)
    {
        try {
            $data = $this->contractService->store($request);

            $pdf = Pdf::loadView('contracts.template', [
                'contract' => $data['contract'],
            ])
                ->setOption('encoding', 'utf-8')
                ->setOption('enable-local-file-access', true)
                ->setOption('page-size', 'A4')
                ->setOption('orientation', 'Portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $fileName = 'contract-' . $data['contract']->contract_no . '.pdf';

            return $pdf->download($fileName);
        } catch (Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'code' => $throwable->getCode(),
            ], 500);
        }
    }

    public function exportPdf($id)
    {
        try {
            $result = $this->contractService->getContractPdfData($id);

            $data = $result['data'];

            $pdf = Pdf::loadView('contracts.template', $data)
                ->setOption('encoding', 'utf-8')
                ->setOption('enable-local-file-access', true)
                ->setOption('load-error-handling', 'ignore')
                ->setOption('load-media-error-handling', 'ignore')
                ->setOption('page-size', 'A4')
                ->setOption('orientation', 'Portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $fileName = 'contract-' . $data['contract']->contract_no . '.pdf';

            return $pdf->download($fileName);
        } catch (Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'code' => $throwable->getCode(),
            ], 500);
        }
    }

    public function testSnappy()
    {
        try {
            return Pdf::loadHTML('<html><body><h1>Hello Snappy</h1></body></html>')
                ->setOption('encoding', 'utf-8')
                ->download('test.pdf');
        } catch (Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'code' => $throwable->getCode(),
            ], 500);
        }
    }
}
