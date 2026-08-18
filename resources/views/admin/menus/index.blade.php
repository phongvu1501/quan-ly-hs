@extends('admin.layouts.app')

@section('title', 'Quản lý Menu')

@section('content')

<div class="d-flex justify-content-between mb-4">

    <h2>Quản lý Menu</h2>


    {{-- ==========================================================
         THÊM MENU
         Chỉ hiển thị khi có menus.create
         ========================================================== --}}

    @if(auth()->user()->hasPermission('menus.create'))

        <a
            href="{{ route('admin.menus.create') }}"
            class="btn btn-primary"
            data-modal-title="Thêm Menu">

            + Thêm Menu

        </a>

    @endif

</div>


<div class="card">

    <div class="card-body">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Tên</th>

                    <th>Slug</th>

                    <th>Route</th>

                    <th>Menu cha</th>

                    <th>Thứ tự</th>

                    <th>Trạng thái</th>

                    <th>Thao tác</th>

                </tr>

            </thead>


            <tbody>

            @forelse($menus as $menu)

                <tr>

                    <td>
                        {{ $menu->id }}
                    </td>


                    <td>

                        @if($menu->parent)

                            └─

                        @endif

                        {{ $menu->name }}

                    </td>


                    <td>

                        {{ $menu->slug }}

                    </td>


                    <td>

                        <code>
                            {{ $menu->route ?? '-' }}
                        </code>

                    </td>


                    <td>

                        {{ $menu->parent->name ?? 'Menu gốc' }}

                    </td>


                    <td>

                        {{ $menu->sort_order }}

                    </td>


                    <td>

                        @if($menu->is_active)

                            <span class="badge bg-success">
                                Active
                            </span>

                        @else

                            <span class="badge bg-secondary">
                                Disabled
                            </span>

                        @endif

                    </td>


                    <td>


                        {{-- ==================================================
                             SỬA MENU
                             ================================================== --}}

                        @if(auth()->user()->hasPermission('menus.edit'))

                            <a
                                href="{{ route('admin.menus.edit', $menu) }}"
                                class="btn btn-sm btn-warning"
                                data-modal-title="Cập nhật Menu">

                                Sửa

                            </a>

                        @endif


                        {{-- ==================================================
                             XÓA MENU
                             ================================================== --}}

                        @if(auth()->user()->hasPermission('menus.delete'))

                            <form
                                action="{{ route('admin.menus.destroy', $menu) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Xóa menu này?')">

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-danger">

                                    Xóa

                                </button>

                            </form>

                        @endif


                        {{-- Nếu không có quyền sửa và xóa --}}

                        @if(
                            !auth()->user()->hasPermission('menus.edit')
                            &&
                            !auth()->user()->hasPermission('menus.delete')
                        )

                            <span class="text-muted">
                                Không có thao tác
                            </span>

                        @endif


                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="8"
                        class="text-center text-muted">

                        Chưa có menu

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>


        {{ $menus->links() }}

    </div>

</div>

@endsection