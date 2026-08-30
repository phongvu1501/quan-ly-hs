<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CancelFinanceRequest;
use App\Http\Requests\Finance\PayrollPaymentRequest;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Services\Finance\PayrollPaymentService;

class PayrollPaymentController extends Controller
{
    public function store(PayrollPaymentRequest $request, Payroll $payroll, PayrollPaymentService $service)
    {
        $service->record($payroll, $request->validated(), $request->user());

        return back()->with('success', 'Đã ghi nhận thanh toán lương.');
    }

    public function cancel(CancelFinanceRequest $request, PayrollPayment $payrollPayment, PayrollPaymentService $service)
    {
        $service->cancel($payrollPayment, $request->validated('reason'), $request->user());

        return back()->with('success', 'Đã hủy giao dịch lương; lịch sử vẫn được giữ.');
    }
}
