             @extends('admin.layouts.app')

             @section('title', 'Sửa Menu')

             @section('content')

                 <h2 class="mb-4">
                     Sửa Menu
                 </h2>

                 <form action="{{ route('admin.menus.update', $menu) }}" method="POST">

                     @csrf
                     @method('PUT')

                     <div class="card">

                         <div class="card-body">

                             <div class="mb-3">

                                 <label class="form-label">
                                     Tên Menu
                                 </label>

                                 <input type="text" name="name" class="form-control"
                                     value="{{ old('name', $menu->name) }}" required>

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Slug
                                 </label>

                                 <input type="text" name="slug" class="form-control"
                                     value="{{ old('slug', $menu->slug) }}" required>

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Route
                                 </label>

                                 <input type="text" name="route" class="form-control"
                                     value="{{ old('route', $menu->route) }}">

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Icon
                                 </label>

                                 <input type="text" name="icon" class="form-control"
                                     value="{{ old('icon', $menu->icon) }}">

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Menu cha
                                 </label>

                                 <select name="parent_id" class="form-select">

                                     <option value="">
                                         -- Menu gốc --
                                     </option>

                                     @foreach ($parents as $parent)
                                         <option value="{{ $parent->id }}" @selected($menu->parent_id == $parent->id)>

                                             {{ $parent->name }}

                                         </option>
                                     @endforeach

                                 </select>

                             </div>

                             <div class="mb-3">

                                 <label class="form-label">
                                     Thứ tự
                                 </label>

                                 <input type="number" name="sort_order" class="form-control"
                                     value="{{ old('sort_order', $menu->sort_order) }}">

                             </div>

                             <div class="form-check mb-3">

                                 <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                     id="is_active" @checked($menu->is_active)>

                                 <label class="form-check-label" for="is_active">

                                     Hiển thị menu

                                 </label>

                             </div>

                             <button class="btn btn-primary">
                                 Cập nhật
                             </button>

                             <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
                                 Quay lại
                             </a>

                         </div>

                     </div>

                 </form>

             @endsection
