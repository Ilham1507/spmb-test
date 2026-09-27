<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index() { return view('bank-accounts.index', ['accounts' => BankAccount::latest()->get()]); }
    public function store(Request $request) { BankAccount::create($request->validate(['bank_name'=>'required|max:100','account_number'=>'required|max:50|unique:bank_accounts','account_holder'=>'required|max:150']) + ['is_active' => true]); return back()->with('success', 'Rekening ditambahkan.'); }
    public function toggle(BankAccount $bankAccount) { $bankAccount->update(['is_active' => !$bankAccount->is_active]); return back()->with('success', 'Status rekening diperbarui.'); }
}
