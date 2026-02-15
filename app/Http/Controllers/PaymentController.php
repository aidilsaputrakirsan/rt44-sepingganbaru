<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(\Illuminate\Http\Request $request, \App\Models\Due $due)
    {
        $request->validate([
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $path = null;
        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('payments', 'public');
        }

        // Check if there is already a pending payment?
        // Actually, users might upload multiple proofs if partial? Let's allow multiple for now or just one pending.
        $existing = \App\Models\Payment::where('due_id', $due->id)->where('status', 'pending')->first();
        if ($existing) {
            // Update existing or reject? Usually update proof if re-uploading.
            // Let's create new for history tracking or update. Simple: create new.
        }

        \App\Models\Payment::create([
            'due_id' => $due->id,
            'payer_id' => auth()->id(),
            'amount_paid' => $request->amount ?? $due->amount,
            'method' => 'transfer',
            'status' => 'pending',
            'proof_path' => $path,
            'payment_date' => now()->toDateString(),
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diupload. Menunggu verifikasi Bendahara.');
    }

    public function receipt(\App\Models\Payment $payment)
    {
        if ($payment->status !== 'verified') {
            abort(403, 'Payment not verified.');
        }

        // Ensure user owns this payment or is admin
        if (!in_array(auth()->user()->role, ['admin', 'demo']) && auth()->id() !== $payment->payer_id && auth()->id() !== $payment->due->house->owner_id) {
            abort(403);
        }

        return view('receipt', ['payment' => $payment->load(['due.house.owner', 'recorder'])]);
    }
}
