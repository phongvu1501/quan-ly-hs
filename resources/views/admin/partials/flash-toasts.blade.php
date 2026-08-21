@php
    $toastItems = collect();

    if (session('success')) {
        $toastItems->push(['type' => 'success', 'title' => 'Thành công', 'messages' => [session('success')]]);
    }
    if (session('warning')) {
        $toastItems->push(['type' => 'warning', 'title' => 'Lưu ý', 'messages' => [session('warning')]]);
    }
    if (session('error')) {
        $toastItems->push(['type' => 'danger', 'title' => 'Không thể thực hiện', 'messages' => [session('error')]]);
    }
    if ($errors->any()) {
        $toastItems->push(['type' => 'danger', 'title' => 'Dữ liệu chưa hợp lệ', 'messages' => $errors->all()]);
    }
@endphp

@if ($toastItems->isNotEmpty())
    <div class="flash-toasts" aria-live="polite" aria-atomic="true">
        @foreach ($toastItems as $toast)
            <div class="flash-toast flash-toast-{{ $toast['type'] }}" role="status" data-auto-dismiss-toast>
                <div class="flash-toast-icon"><i data-lucide="{{ $toast['type'] === 'success' ? 'check-circle-2' : ($toast['type'] === 'warning' ? 'triangle-alert' : 'circle-alert') }}"></i></div>
                <div class="flash-toast-body">
                    <strong>{{ $toast['title'] }}</strong>
                    @if (count($toast['messages']) === 1)
                        <p>{{ $toast['messages'][0] }}</p>
                    @else
                        <ul>
                            @foreach ($toast['messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <button type="button" class="flash-toast-close" data-dismiss-toast aria-label="Đóng">&times;</button>
            </div>
        @endforeach
    </div>
@endif
