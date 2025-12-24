<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use App\Services\Customer\CustomerService;

class CustomerController extends Controller
{
    protected $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    public function getFormProspekEntry($task_id)
    {
        $data = $this->service->getFormProspekEntry($task_id);
        return response()->json($data);
    }

    public function customerEntriDataProspek(Request $request)
    {
        $auth = $request->user(); // misal auth middleware
        $data = $this->service->customerEntriDataProspek($request->all(), $auth);
      $nearestDistance = null; // atau assign dari DB query di helper
        \Log::info('Nearest ODP Distance', ['distance_m' => $nearestDistance]);

        return response()->json($data, 201);
    }
    // public function customerEntriDataProspek(Request $request)
    // {
    //     $data = $this->service->customerEntriDataProspek($request->all());
    //     return response()->json($data, 201);
    // }
    public function idplayEntriDataProspek(Request $request)
    {
        $result = $this->service->idplayEntriDataProspek(
            $request->all(),
            $request->query(),
            $request->user() // auth user
        );

        return response()->json($result, 201);
    }

    public function referralEntriDataProspek(Request $request)
    {
        $data = $this->service->referralEntriDataProspek($request->all(), $request->query());
        return response()->json($data, 201);
    }

    // public function referralEntryWeb()
    // {
    //     return response()->file(resource_path('assets/html/entry-prospek-web/entry-prospek.html'));
    // }

    // public function referralAfterSubmit()
    // {
    //     return response()->file(resource_path('assets/html/entry-prospek-web/after-submit.html'));
    // }

    public function getLeadCustomer(Request $request)
    {
        $data = $this->service->getLeadCustomer($request);
        return response()->json($data);
    }

    public function pushLeadCustomer(Request $request)
    {
        $data = $this->service->pushLeadCustomer($request->all());
        return response()->json($data);
    }

    public function previewFAB(Request $request)
    {
        // Mengambil data session
        $session = Helper::getAuthorizationTokenData($request);

        // Mengirim kedua parameter ke service: body (request data) dan session
        // Menggunakan $request->all() untuk body (data) dan session untuk sesi
        $pdf = $this->service->previewFAB($request->all(), $session);

        // Mengembalikan response PDF
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function generateFAB(Request $request, $task_id)
    {
        try {
            $pdf = $this->service->generateFAB($task_id, $request->query());

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header(
                    'Content-Disposition',
                    'inline; filename="' . $task_id . '.pdf"'
                );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateFKB(Request $request, $task_id)
    {
        $pdf = $this->service->generateFKB($request->all(), $task_id);

        return response($pdf, 201)
            ->header('Content-Type', 'application/pdf');
    }


    public function submitFAB(Request $request, $task_id)
    {
        $data = $this->service->submitFAB($request, $task_id);
        return response()->json($data);
    }

    public function uploadKTP(Request $request)
    {
        $data = $this->service->uploadKTP($request);
        return response()->json($data, 201);
    }

    public function uploadSignature(Request $request, string $task_id)
    {
        $data = $this->service->uploadSignature(
            $request,
            $task_id
        );

        return response()->json($data, 201);
    }

    public function uploadFABDocument(Request $request)
    {
        $data = $this->service->uploadFABDocument($request);
        return response()->json($data, 200);
    }

    public function termsAndCondition()
    {
        return $this->service->termsAndCondition();
    }
}
