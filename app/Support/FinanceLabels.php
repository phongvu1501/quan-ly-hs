<?php

namespace App\Support;

final class FinanceLabels
{
    public static function feeStatus(?string $status): string
    {
        return [
            'pending' => 'Chưa đóng',
            'partially_paid' => 'Đóng một phần',
            'paid' => 'Đã tất toán',
            'overdue' => 'Quá hạn',
            'cancelled' => 'Đã hủy',
        ][$status] ?? (string) $status;
    }

    public static function payrollStatus(?string $status): string
    {
        return [
            'draft' => 'Bản nháp',
            'calculated' => 'Đã tính',
            'approved' => 'Đã duyệt',
            'locked' => 'Đã chốt',
            'paid' => 'Đã thanh toán',
            'cancelled' => 'Đã hủy',
        ][$status] ?? (string) $status;
    }

    public static function inputStatus(?string $status): string
    {
        return ['draft' => 'Bản nháp', 'approved' => 'Đã duyệt', 'cancelled' => 'Đã hủy'][$status] ?? (string) $status;
    }

    public static function reductionType(?string $type): string
    {
        return [
            'percentage' => 'Theo phần trăm',
            'fixed_amount' => 'Số tiền cố định',
            'full_exemption' => 'Miễn toàn bộ',
        ][$type] ?? (string) $type;
    }

    public static function applicationMode(?string $mode): string
    {
        return ['exclusive' => 'Chính sách ưu tiên', 'cumulative' => 'Cho phép cộng dồn'][$mode] ?? (string) $mode;
    }

    public static function periodType(?string $type): string
    {
        return [
            'monthly' => 'Theo tháng', 'semester' => 'Theo học kỳ',
            'annual' => 'Theo năm học', 'one_time' => 'Một lần',
        ][$type] ?? (string) $type;
    }

    public static function paymentMethod(?string $method): string
    {
        return [
            'cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản',
            'online' => 'Trực tuyến', 'other' => 'Khác',
        ][$method] ?? (string) $method;
    }

    public static function componentType(?string $type): string
    {
        return ['earning' => 'Thu nhập', 'deduction' => 'Khấu trừ'][$type] ?? (string) $type;
    }

    public static function calculationType(?string $type): string
    {
        return [
            'fixed' => 'Số tiền cố định', 'percentage' => 'Theo phần trăm',
            'per_unit' => 'Theo đơn vị', 'manual' => 'Nhập thủ công',
        ][$type] ?? (string) $type;
    }

    public static function category(?string $category): string
    {
        return [
            'base_salary' => 'Lương cứng', 'allowance' => 'Phụ cấp',
            'teaching_extra' => 'Dạy thêm', 'bonus' => 'Thưởng',
            'insurance' => 'Bảo hiểm', 'tax' => 'Thuế thu nhập cá nhân',
            'advance' => 'Tạm ứng', 'unpaid_leave' => 'Nghỉ không lương',
            'other_deduction' => 'Khấu trừ khác',
        ][$category] ?? (string) $category;
    }

    public static function inputType(?string $type): string
    {
        return [
            'working_days' => 'Ngày công', 'unpaid_leave' => 'Nghỉ không lương',
            'overtime' => 'Làm thêm giờ', 'bonus' => 'Thưởng',
            'manual_adjustment' => 'Điều chỉnh thủ công', 'other_input' => 'Khoản nhập khác',
        ][$type] ?? (string) $type;
    }
}
