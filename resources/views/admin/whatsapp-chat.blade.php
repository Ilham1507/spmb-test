@extends('layouts.admin')
@section('title', 'Chat WhatsApp')
@section('page_title', 'Chat WhatsApp')
@section('content')
<div x-data="whatsappInbox(@js(['messages' => $messages, 'open' => $open, 'expires' => $expires, 'phone' => $phone, 'indexUrl' => route('admin.whatsapp-chat.index'), 'sendUrl' => route('admin.whatsapp-chat.send')]))" class="space-y-4">
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        <strong>{{ config('services.whatsapp.sender_number') ?: '+62 812-4707-5160' }}</strong>
    </div>
    <div class="grid gap-4 lg:grid-cols-[280px_1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-4">
            <h2 class="font-bold">Percakapan</h2>
            <input x-model="search" placeholder="Cari nomor WhatsApp" aria-label="Cari nomor WhatsApp" class="my-3 w-full rounded-xl border-slate-200 text-sm">
            <div class="max-h-[520px] space-y-2 overflow-y-auto">
                <template x-for="contact in contacts" :key="contact.sender_phone">
                    <a x-show="contact.sender_phone.includes(search)" :href="indexUrl + '?phone=' + contact.sender_phone" class="block rounded-xl border p-3 text-sm" :class="contact.sender_phone === phone ? 'border-emerald-500 bg-emerald-50' : 'border-slate-100'">
                        <strong x-text="'+' + contact.sender_phone"></strong><p class="text-xs text-slate-500" x-text="time(contact.last_at)"></p>
                    </a>
                </template>
                <p x-show="contacts.length === 0" class="text-sm text-slate-500">Belum ada pesan masuk.</p>
            </div>
        </aside>
        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 border-b pb-3">
                <h2 class="font-bold">{{ $phone ? '+'.$phone : 'Pilih percakapan' }}</h2>
                <button type="button" @click="refresh()" class="rounded-xl bg-slate-100 px-3 py-2 text-sm">Perbarui</button>
            </div>
            <p class="mt-2 text-xs text-slate-500">Riwayat pesan otomatis mulai tercatat sejak pembaruan ini. Pesan lama yang belum tersimpan tidak ditampilkan. Dokumen ditampilkan sebagai nama file.</p>
            <p x-show="phone" class="my-3 rounded-xl p-3 text-sm" :class="open ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900'" x-text="open ? 'Balasan aktif sampai ' + expires + ' (batas aman sistem).' : 'Sesi berakhir. Penerima perlu mengirim pesan baru agar bisa dibalas.'"></p>
            <div x-ref="history" class="flex h-[380px] flex-col gap-3 overflow-y-auto rounded-xl bg-slate-50 p-3">
                <template x-for="message in messages" :key="message.id">
                    <div class="max-w-[90%] rounded-2xl border p-3 text-sm" :class="message.direction === 'out' ? 'self-end border-emerald-100 bg-emerald-50' : 'self-start border-slate-200 bg-white'">
                        <p class="whitespace-pre-wrap break-words" x-text="message.body"></p>
                        <p class="mt-2 text-[11px] text-slate-500" x-text="time(message.at) + ' · ' + message.status"></p>
                    </div>
                </template>
                <p x-show="!phone" class="m-auto text-center text-sm text-slate-500">Pilih nomor di daftar untuk membaca dan membalas.</p>
            </div>
            <form @submit.prevent="send()" class="mt-3 space-y-2" x-show="phone">
                <textarea x-model="draft" :disabled="!open || sending" maxlength="4000" rows="3" placeholder="Tulis balasan…" aria-label="Isi balasan WhatsApp" class="w-full rounded-xl border-slate-200 disabled:bg-slate-100"></textarea>
                <div class="flex items-center justify-between gap-3"><span class="text-xs text-slate-500" x-text="draft.length + '/4000'"></span><button :disabled="!open || sending || !draft.trim()" class="rounded-xl bg-emerald-700 px-5 py-3 font-bold text-white disabled:opacity-40" x-text="sending ? 'Mengirim…' : 'Kirim balasan'"></button></div>
            </form>
            <p x-cloak x-show="notice" role="status" class="mt-3 text-sm" :class="error ? 'text-red-700' : 'text-emerald-700'" x-text="notice"></p>
        </section>
    </div>
</div>
<script>
function whatsappInbox(initial) {
    return { ...initial, contacts: @js($conversations), search: '', draft: '', sending: false, notice: '', error: false, timer: null,
        init() { this.$nextTick(() => this.bottom()); this.timer = setInterval(() => { if (!document.hidden && !this.sending) this.refresh(); }, 15000); },
        destroy() { clearInterval(this.timer); },
        time(value) { return new Date(value.replace(' ', 'T') + (value.includes('Z') || /[+-]\d\d:\d\d$/.test(value) ? '' : '+07:00')).toLocaleString('id-ID', {timeZone: 'Asia/Jakarta', day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}); },
        bottom() { if(this.$refs.history) this.$refs.history.scrollTop = this.$refs.history.scrollHeight; },
        async refresh() {
            try { const response = await fetch(this.indexUrl + (this.phone ? '?phone=' + this.phone : ''), {headers:{Accept:'application/json'}, cache:'no-store'});
                if (!response.ok) throw new Error(); const data = await response.json();
                const atBottom = this.$refs.history.scrollHeight - this.$refs.history.scrollTop - this.$refs.history.clientHeight < 70;
                this.messages = data.messages; this.contacts = data.conversations; this.open = data.open; this.expires = data.expires;
                if (atBottom) this.$nextTick(() => this.bottom());
            } catch { this.error = true; this.notice = 'Chat belum bisa diperbarui. Periksa koneksi atau masuk kembali jika sesi berakhir.'; }
        },
        async send() {
            if (this.sending || !this.open || !this.draft.trim()) return;
            this.sending = true; this.notice = ''; this.error = false;
            try { const response = await fetch(this.sendUrl, {method:'POST', headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({phone:this.phone, body:this.draft})});
                const data = await response.json(); if(!response.ok) throw new Error(data.message || 'Pengiriman gagal.');
                this.notice = data.message; this.draft = ''; await this.refresh(); this.$nextTick(() => this.bottom());
            } catch(e) { this.error = true; this.notice = e.message || 'Pengiriman belum terkonfirmasi. Periksa sebelum mencoba lagi.'; }
            finally { this.sending = false; }
        }
    };
}
</script>
@endsection
