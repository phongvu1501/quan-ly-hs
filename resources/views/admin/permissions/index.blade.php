@extends('admin.layouts.app')

@section('title', 'Permissions')

@section('content')

<div class="d-flex justify-content-between mb-4">

    <h2>Permissions</h2>


    {{-- ==========================================================
         THÊM PERMISSION
         Chỉ hiện khi có permissions.create
         ========================================================== --}}

    @if(auth()->user()->hasPermission('permissions.create'))

        <a
            href="{{ route('admin.permissions.create') }}"
            class="btn btn-primary"
            data-modal-title="Thêm Permission">

            + Thêm Permission

        </a>

    @endif

</div>


<div class="card">

    <div class="card-body">

        <table class="table table-hover">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Tên</th>

                    <th>Slug</th>

                    <th>Số Role</th>

                    <th>Thao tác</th>

                </tr>

            </thead>


            <tbody>

            @forelse($permissions as $permission)

                <tr>

                    <td>
                        {{ $permission->id }}
                    </td>


                    <td>
                        {{ $permission->name }}
                    </td>


                    <td>

                        <code>
                            {{ $permission->slug }}
                        </code>

                    </td>


                    <td>
                        {{ $permission->roles_count }}
                    </td>


                    <td>


                        {{-- ==================================================
                             SỬA PERMISSION
                             ================================================== --}}

                        @if(auth()->user()->hasPermission('permissions.edit'))

                            <a
                                href="{{ route('admin.permissions.edit', $permission) }}"
                                class="btn btn-sm btn-warning"
                                data-modal-title="Cập nhật Permission">

                                Sửa

                            </a>

                        @endif


                        {{-- ==================================================
                             XÓA PERMISSION
                             ================================================== --}}

                        @if(auth()->user()->hasPermission('permissions.delete'))

                            <form
                                action="{{ route('admin.permissions.destroy', $permission) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Xóa permission này?')">

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-danger">

                                    Xóa

                                </button>

                            </form>

                        @endif


                        {{-- ==================================================
                             KHÔNG CÓ QUYỀN THAO TÁC
                             ================================================== --}}

                        @if(
                            !auth()->user()->hasPermission('permissions.edit')
                            &&
                            !auth()->user()->hasPermission('permissions.delete')
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
                        colspan="5"
                        class="text-center text-muted">

                        Chưa có permission

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>


        {{ $permissions->links() }}

    </div>

</div>

@endsection