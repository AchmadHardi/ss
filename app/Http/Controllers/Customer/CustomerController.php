<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use App\Services\Customer\CustomerService;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;

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

    public function referralEntryWeb()
    {
        return response()->file(resource_path('assets/html/entry-prospek-web/entry-prospek.html'));
    }

    public function referralAfterSubmit()
    {
        return response()->file(resource_path('assets/html/entry-prospek-web/after-submit.html'));
    }

    public function getLeadCustomer(Request $request)
    {
        $data = $this->service->getLeadCustomer($request->all());
        return response()->json($data);
    }

    public function pushLeadCustomer(Request $request)
    {
        $data = $this->service->pushLeadCustomer($request->all());
        return response()->json($data);
    }

    public function previewFAB(Request $request)
    {
        $pdf = $this->service->previewFAB($request->all());
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function generateFAB($task_id)
    {
        $pdf = $this->service->generateFAB($task_id);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function generateFKB(Request $request, $task_id)
    {
        $pdf = $this->service->generateFKB($request->all(), $task_id);
        return response($pdf, 201)->header('Content-Type', 'application/pdf');
    }

    public function submitFAB(Request $request, $task_id)
    {
        $data = $this->service->submitFAB($request->all(), $task_id);
        return response()->json($data);
    }

    public function uploadKTP(Request $request)
    {
        $data = $this->service->uploadKTP($request->all(), $request->file());
        return response()->json($data, 201);
    }

    public function uploadSignature(Request $request, $task_id)
    {
        $data = $this->service->uploadSignature($request->all(), $request->file(), $task_id);
        return response()->json($data, 201);
    }

    public function uploadFABDocument(Request $request)
    {
        $data = $this->service->uploadFABDocument($request->all(), $request->file());
        return response()->json($data);
    }

    public function termsAndCondition()
    {
        $data = $this->service->termsAndCondition();
        return response()->json($data);
    }
}
