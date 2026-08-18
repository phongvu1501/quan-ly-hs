             @extends('admin.layouts.app')

             @section('title', 'Sửa Permission')

             @section('content')

                 <h2 class="mb-4">
                     Sửa Permission
                 </h2>

                 <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">

                     @csrf
                     @method('PUT')

                     <div class="card">

                         <div class="card-body">

                             <div class="mb-3">

                                 <label class="form-label">
                                     Tên Permission
                                 </label>

                                 <input type="text" name="name" class="form-control"
                                     value="{{ old('name', $permission->name) }}" required>

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Slug
                                 </label>

                                 <input type="text" name="slug" class="form-control"
                                     value="{{ old('slug', $permission->slug) }}" required>

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Mô tả
                                 </label>

                                 <textarea name="description" class="form-control" rows="3">{{ old('description', $permission->description) }}</textarea>

                             </div>

                             <button class="btn btn-primary">
                                 Cập nhật
                             </button>

                             <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                                 Quay lại
                             </a>

                         </div>

                     </div>

                 </form>

             @endsection
