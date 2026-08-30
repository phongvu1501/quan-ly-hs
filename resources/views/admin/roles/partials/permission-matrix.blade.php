@php
    $selectedPermissionIds = $selectedPermissionIds ?? [];
    $permissionCount = collect($permissionGroups)->sum(fn ($group) => $group['permissions']->count());
@endphp

<div class="permission-section role-permission-matrix" id="role-permission-matrix-create">
    <input type="hidden" name="permissions_present" value="1">
    <p class="text-secondary small mb-3"><code>resource.manage</code> là quyền tổng cho đủ xem, tạo, sửa và xóa của resource. Nếu cần quyền độc lập, bật từng quyền CRUD và để manage tắt.</p>
    <div class="permission-toolbar">
        <div>
            <div class="section-label mb-1">Quyền truy cập</div>
            <div class="permission-total"><strong data-selected-permission-count>0</strong>/{{ $permissionCount }} quyền đã chọn</div>
        </div>
        <label class="permission-select-all" for="permission-select-all-create">
            <input type="checkbox" id="permission-select-all-create">
            <span>Chọn tất cả</span>
        </label>
    </div>

    <div class="permission-group-list">
        @foreach ($permissionGroups as $group)
            @php
                $groupPermissionCount = $group['permissions']->count();
                $groupSelectedCount = $group['permissions']->whereIn('id', $selectedPermissionIds)->count();
            @endphp
            <details class="permission-group" data-permission-group>
                <summary class="permission-group-summary">
                    <label class="permission-group-selector">
                        <input type="checkbox" class="permission-group-toggle" aria-label="Chọn toàn bộ quyền {{ $group['label'] }}" @checked($groupSelectedCount === $groupPermissionCount)>
                        <span class="permission-group-icon"><i data-lucide="{{ $group['icon'] }}"></i></span>
                        <strong class="permission-group-title">{{ $group['label'] }}</strong>
                    </label>
                    <span class="permission-group-meta"><span class="permission-group-count"><strong data-group-selected-count>{{ $groupSelectedCount }}</strong>/{{ $groupPermissionCount }}</span><i class="permission-group-chevron" data-lucide="chevron-down"></i></span>
                </summary>
                <div class="permission-group-body"><div class="row g-2">
                    @foreach ($group['permissions'] as $permission)
                        <div class="col-xl-4 col-md-6"><label class="check-tile">
                            <input type="checkbox" class="permission-item" name="permissions[]" value="{{ $permission->id }}" data-permission-slug="{{ $permission->slug }}" @checked(in_array($permission->id, $selectedPermissionIds, true))>
                            <span><strong>{{ $permission->name }}</strong><small>{{ $permission->slug }}</small></span>
                        </label></div>
                    @endforeach
                </div></div>
            </details>
        @endforeach
    </div>
</div>

@push('styles')
<style>
    .permission-toolbar,.permission-group-summary,.permission-group-selector,.permission-group-meta,.permission-select-all{display:flex;align-items:center}
    .permission-toolbar{justify-content:space-between;gap:18px;margin-bottom:14px}
    .permission-total{color:var(--muted);font-size:13px}.permission-total strong,.permission-group-count strong{color:var(--ink)}
    .permission-select-all{flex:0 0 auto;gap:8px;min-height:38px;margin:0;padding:8px 11px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--ink);font-size:13px;font-weight:700;cursor:pointer}
    .permission-select-all input,.permission-group-toggle{width:17px;height:17px;margin:0;accent-color:var(--accent)}
    .permission-group-list{border-bottom:1px solid var(--line)}.permission-group{border-top:1px solid var(--line)}
    .permission-group-summary{justify-content:space-between;gap:16px;min-height:62px;padding:11px 2px;list-style:none;cursor:pointer}.permission-group-summary::-webkit-details-marker{display:none}
    .permission-group-selector{min-width:0;gap:11px;margin:0;cursor:pointer}.permission-group-icon{display:grid;flex:0 0 auto;width:34px;height:34px;place-items:center;border-radius:6px;background:var(--accent-soft);color:var(--accent)}
    .permission-group-icon svg{width:18px;height:18px}.permission-group-title{min-width:0;color:var(--ink);font-size:14px}.permission-group-meta{flex:0 0 auto;gap:12px;color:var(--muted)}.permission-group-count{min-width:44px;text-align:right;font-size:12px;font-variant-numeric:tabular-nums}
    .permission-group-chevron{width:17px;height:17px;transition:transform .15s ease}.permission-group[open] .permission-group-chevron{transform:rotate(180deg)}.permission-group.has-selection .permission-group-count strong{color:var(--accent)}
    .permission-group-body{padding:2px 0 16px 62px}@media(max-width:640px){.permission-toolbar{align-items:flex-start;flex-direction:column}.permission-group-body{padding-left:0}.permission-group-summary{gap:8px}.permission-group-selector{gap:8px}.permission-group-icon{width:30px;height:30px}}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const matrix = document.getElementById('role-permission-matrix-create');
    if (!matrix) return;
    const groups = [...matrix.querySelectorAll('[data-permission-group]')];
    const allToggle = matrix.querySelector('#permission-select-all-create');
    const selectedCount = matrix.querySelector('[data-selected-permission-count]');
    const groupItems = group => [...group.querySelectorAll('.permission-item')];
    const syncGroup = group => {
        const items = groupItems(group), checkedCount = items.filter(item => item.checked).length;
        const toggle = group.querySelector('.permission-group-toggle');
        toggle.checked = items.length > 0 && checkedCount === items.length;
        toggle.indeterminate = checkedCount > 0 && checkedCount < items.length;
        group.querySelector('[data-group-selected-count]').textContent = String(checkedCount);
        group.classList.toggle('has-selection', checkedCount > 0);
    };
    const syncAll = () => {
        const items = [...matrix.querySelectorAll('.permission-item')], checkedCount = items.filter(item => item.checked).length;
        allToggle.checked = items.length > 0 && checkedCount === items.length;
        allToggle.indeterminate = checkedCount > 0 && checkedCount < items.length;
        selectedCount.textContent = String(checkedCount);
    };
    const normalizeManage = changedItem => {
        const slug = changedItem.dataset.permissionSlug || '', separator = slug.indexOf('.');
        if (separator < 1) return;
        const resource = slug.slice(0, separator), action = slug.slice(separator + 1);
        const items = [...matrix.querySelectorAll('[data-permission-slug]')].filter(item => (item.dataset.permissionSlug || '').startsWith(`${resource}.`));
        const manage = items.find(item => item.dataset.permissionSlug === `${resource}.manage`);
        if (!manage) return;
        if (action === 'manage' && manage.checked) {
            items.forEach(item => { if (['view','create','edit','delete'].includes((item.dataset.permissionSlug || '').split('.').pop())) item.checked = true; });
        } else if (['view','create','edit','delete'].includes(action) && !changedItem.checked) {
            manage.checked = false;
        }
    };
    groups.forEach(group => {
        const toggle = group.querySelector('.permission-group-toggle'), items = groupItems(group);
        group.querySelector('.permission-group-selector').addEventListener('click', event => event.stopPropagation());
        toggle.addEventListener('change', () => { items.forEach(item => { item.checked = toggle.checked; }); syncGroup(group); syncAll(); });
        items.forEach(item => item.addEventListener('change', () => { normalizeManage(item); syncGroup(group); syncAll(); }));
        syncGroup(group);
    });
    allToggle.addEventListener('change', () => { matrix.querySelectorAll('.permission-item').forEach(item => { item.checked = allToggle.checked; }); groups.forEach(syncGroup); syncAll(); });
    syncAll();
})();
</script>
@endpush
