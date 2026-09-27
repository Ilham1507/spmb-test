@if(session('success'))
    <div role="status" class="portal-flash border-emerald-200 bg-emerald-50 text-emerald-800"><span class="portal-flash-dot bg-emerald-500"></span>{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div role="alert" class="portal-flash border-amber-200 bg-amber-50 text-amber-900"><span class="portal-flash-dot bg-amber-500"></span>{{ session('warning') }}</div>
@endif
@if(session('error'))
    <div role="alert" class="portal-flash border-rose-200 bg-rose-50 text-rose-800"><span class="portal-flash-dot bg-rose-500"></span>{{ session('error') }}</div>
@endif
