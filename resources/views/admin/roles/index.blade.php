@extends('admin.layouts.app')

@section('title', 'Quản lý Role')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2>Quản lý Role</h2>


    {{-- ==========================================================
         THÊM ROLE
         Chỉ hiện khi có roles.create
         ========================================================== --}}

    @if(auth()->user()->hasPermission('roles.create'))

        <a
            href="{{ route('admin.roles.create') }}"
            class="btn btn-primary"
            data-modal-title="Thêm Role">

            + Thêm Role

        </a>

    @endif

</div>


<div class="card">

    <div class="card-body">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>#</th>

                    <th>Tên</th>

                    <th>Slug</th>

                    <th>Users</th>

                    <th>Permissions</th>

                    <th>Menus</th>

                    <th width="220">
                        Thao tác
                    </th>

                </tr>

            </thead>


            <tbody>

            @forelse($roles as $role)

                <tr>


                    {{-- ID --}}

                    <td>
                        {{ $role->id }}
                    </td>


                    {{-- NAME --}}

                    <td>

                        <strong>
                            {{ $role->name }}
                        </strong>

                    </td>


                    {{-- SLUG --}}

                    <td>

                        <span class="badge bg-secondary">

                            {{ $role->slug }}

                        </span>

                    </td>


                    {{-- USERS --}}

                    <td>

                        {{ $role->users_count }}

                    </td>


                    {{-- PERMISSIONS --}}

                    <td>

                        {{ $role->permissions_count }}

                    </td>


                    {{-- MENUS --}}

                    <td>

                        {{ $role->menus_count }}

                    </td>


                    {{-- ==================================================
                         ACTION
                         ================================================== --}}

                    <td>


                        {{-- ==================================================
                             SỬA ROLE
                             ================================================== --}}

                        @if(auth()->user()->hasPermission('roles.edit'))

                            <a
                                href="{{ route('admin.roles.edit', $role) }}"
                                class="btn btn-sm btn-warning"
                                data-modal-title="Cập nhật Role">

                                Sửa

                            </a>

                        @endif


                        {{-- ==================================================
                             XÓA ROLE
                             ================================================== --}}

                        @if(auth()->user()->hasPermission('roles.delete'))

                            <form
                                action="{{ route('admin.roles.destroy', $role) }}"
                                method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Bạn có chắc muốn xóa?')">

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
                             KHÔNG CÓ QUYỀN
                             ================================================== --}}

                        @if(
                            !auth()->user()->hasPermission('roles.edit')
                            &&
                            !auth()->user()->hasPermission('roles.delete')
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
                        colspan="7"
                        class="text-center text-muted">

                        Chưa có role

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>


        {{ $roles->links() }}

    </div>

</div>

@endsection