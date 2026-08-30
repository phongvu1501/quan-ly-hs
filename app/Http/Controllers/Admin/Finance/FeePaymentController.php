<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CancelFinanceRequest;
use App\Http\Requests\Finance\FeePaymentRequest;
use App\Models\FeeAssessment;
use App\Models\FeePayment;
use App\Services\Finance\FeePaymentService;

class FeePaymentController extends Controller
{
    public function store(FeePaymentRequest $request, FeeAssessment $feeAssessment, FeePaymentService $service)
    {
        $service->record($feeAssessment, $request->validated(), $request->user());

        return back()->with('success', 'Đã ghi nhận thanh toán học phí.');
    }

    public function cancel(CancelFinanceRequest $request, FeePayment $feePayment, FeePaymentService $service)
    {
        $service->cancel($feePayment, $request->validated('reason'), $request->user());

        return back()->with('success', 'Đã hủy giao dịch; lịch sử vẫn được lưu.');
    }
}
