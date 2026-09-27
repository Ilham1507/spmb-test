@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Approve Pembayaran')
@section('page_title', 'Approve Pembayaran')

@section('content')
    <div class="payment-review mx-auto max-w-[1360px]">
        <x-payment-transaction-table :transactions="$transactions" :route-prefix="request()->routeIs('admin.*') ? 'admin.' : 'panitia.'" accent="violet" show-pagination />
    </div>
@endsection
