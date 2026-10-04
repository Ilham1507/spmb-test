@extends('layouts.admin')
@section('title', 'Chat WhatsApp')
@section('page_title', 'Chat WhatsApp')
@section('content')
<div x-data="whatsappInbox(@js(['messages' => $messages, 'open' => $open, 'expires' => $expires, 'phone' => $phone, 'contactName' => $contactName, 'contactAvatar' => $contactAvatar, 'unread' => $unread, 'lastIncomingId' => $lastIncomingId, 'readUrl' => route('admin.whatsapp-chat.read'), 'indexUrl' => route('admin.whatsapp-chat.index'), 'sendUrl' => route('admin.whatsapp-chat.send')]))" @resize.window="mobile = window.innerWidth < 1024" class="wa-inbox min-w-0 space-y-4">
    <div x-show="!mobile || !showConversation" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        <strong>{{ $siteSettings['school_name'] ?? 'SMK Muhammadiyah 4 Cileungsi' }}</strong>
        <p>{{ config('services.whatsapp.sender_number') ?: '+62 812-4707-5160' }}</p>
    </div>
    <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside x-show="!mobile || !showConversation" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between"><h2 class="font-bold">Percakapan</h2><span x-show="unread > 0" class="rounded-full bg-emerald-700 px-3 py-1 text-xs font-bold text-white" x-text="unread + ' belum dibaca'"></span></div>
            <input x-model="search" placeholder="Cari nama atau nomor" aria-label="Cari nama atau nomor WhatsApp" class="my-3 w-full rounded-xl border-slate-200 text-sm">
            <div class="max-h-[520px] space-y-2 overflow-y-auto">
                <template x-for="contact in contacts" :key="contact.sender_phone">
                    <a @click.prevent="select(contact)" x-show="contact.sender_phone.includes(search) || contact.name.toLowerCase().includes(search.toLowerCase())" :href="indexUrl + '?phone=' + contact.sender_phone" class="wa-contact block min-w-0 overflow-hidden rounded-xl border p-3 text-sm" :class="contact.sender_phone === phone ? 'border-emerald-500 bg-emerald-50' : 'border-slate-100'">
                        <div class="flex min-w-0 items-center gap-3">
                        @include('admin.partials.whatsapp-avatar', ['avatarExpression' => 'contact.avatar'])
                        <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2"><strong class="truncate" x-text="contact.name"></strong><span class="shrink-0 text-[11px] text-slate-500" x-text="time(contact.last_at)"></span></div>
                        <p class="text-xs text-slate-500" x-text="'+' + contact.sender_phone"></p>
                        <div class="mt-1 flex items-center gap-2"><p class="min-w-0 flex-1 truncate text-sm text-slate-600" x-text="(contact.last_direction === 'out' ? 'Anda: ' : '') + contact.preview"></p><span x-show="contact.unread > 0" class="shrink-0 rounded-full bg-emerald-600 px-2 py-1 text-xs font-bold text-white" x-text="contact.unread" aria-label="Pesan belum dibaca"></span></div>
                        </div></div>
                    </a>
                </template>
                <p x-show="contacts.length === 0" class="text-sm text-slate-500">Belum ada pesan masuk.</p>
            </div>
        </aside>
        <section x-cloak x-show="!mobile || showConversation" class="wa-chat-panel min-w-0 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="wa-chat-header flex shrink-0 items-center justify-between gap-3 border-b pb-3">
                <button type="button" x-show="mobile" @click="showConversation = false" class="wa-chat-back rounded-xl px-3 py-2 font-bold" aria-label="Kembali ke daftar chat">← Kembali</button>
                <div x-show="phone" class="shrink-0">@include('admin.partials.whatsapp-avatar', ['avatarExpression' => 'contactAvatar'])</div>
                <div class="min-w-0 flex-1"><h2 class="truncate font-bold" x-text="phone ? contactName : 'Pilih percakapan'"></h2><p x-show="phone" class="text-sm text-slate-500" x-text="'+' + phone"></p></div>
            </div>
            <p x-show="phone && !open" class="wa-chat-session my-2 shrink-0 rounded-xl bg-amber-50 p-2 text-xs text-amber-900">Sesi berakhir. Penerima perlu mengirim pesan baru agar bisa dibalas.</p>
            <div x-ref="history" @scroll.debounce.200ms="markRead()" class="wa-chat-history flex h-[clamp(200px,42dvh,480px)] flex-col gap-3 overflow-y-auto rounded-xl bg-slate-50 p-3">
                <template x-for="message in messages" :key="message.id">
                    <div class="max-w-[90%] rounded-2xl border p-3 text-sm" :class="message.direction === 'out' ? 'self-end border-emerald-100 bg-emerald-50' : 'self-start border-slate-200 bg-white'">
                        <p class="whitespace-pre-wrap break-words" x-text="message.body"></p>
                        <p class="mt-2 text-[11px] text-slate-500" x-text="time(message.at) + ' · ' + message.status"></p>
                    </div>
                </template>
                <p x-show="!phone" class="m-auto text-center text-sm text-slate-500">Pilih nomor di daftar untuk membaca dan membalas.</p>
            </div>
            <form @submit.prevent="send()" class="wa-chat-composer mt-2 shrink-0" x-show="phone">
                <div class="flex items-end gap-2"><textarea x-model="draft" :disabled="!open || sending" maxlength="4000" rows="1" placeholder="Tulis balasan…" aria-label="Isi balasan WhatsApp" class="wa-chat-input min-w-0 flex-1 rounded-xl border-slate-200 disabled:bg-slate-100"></textarea><button :disabled="!open || sending || !draft.trim()" class="shrink-0 rounded-xl bg-emerald-700 px-4 py-3 font-bold text-white disabled:opacity-40" aria-label="Kirim balasan" x-text="sending ? '…' : 'Kirim'"></button></div>
                <p x-show="draft.length" class="mt-1 text-right text-[11px] text-slate-500" x-text="draft.length + '/4000'"></p>
            </form>
            <p x-cloak x-show="notice" role="status" class="mt-3 text-sm" :class="error ? 'text-red-700' : 'text-emerald-700'" x-text="notice"></p>
        </section>
    </div>
</div>
<script>
function whatsappInbox(initial) {
    return { ...initial, contacts: @js($conversations), search: '', draft: '', sending: false, notice: '', error: false, timer: null,
        mobile: window.innerWidth < 1024, showConversation: window.innerWidth >= 1024, refreshing: false, readIds: {}, marking: false,
        init() { this.publishCount(); this.$nextTick(() => { this.bottom(); this.markRead(); }); this.timer = setInterval(() => { if (!document.hidden && !this.sending) this.refresh(); }, 3000); },
        destroy() { clearInterval(this.timer); },
        time(value) { return new Date(value.replace(' ', 'T') + (value.includes('Z') || /[+-]\d\d:\d\d$/.test(value) ? '' : '+07:00')).toLocaleString('id-ID', {timeZone: 'Asia/Jakarta', day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}); },
        bottom() { if(this.$refs.history) this.$refs.history.scrollTop = this.$refs.history.scrollHeight; },
        publishCount() { window.dispatchEvent(new CustomEvent('whatsapp-unread', {detail: {unread:this.unread}})); },
        async select(contact) {
            if (this.draft.trim() && this.phone !== contact.sender_phone) { this.showConversation = true; this.notice = 'Kirim atau kosongkan draf sebelum berpindah chat.'; this.error = true; return; }
            this.phone = contact.sender_phone; this.contactName = contact.name; this.contactAvatar = contact.avatar; this.messages = []; this.lastIncomingId = null; this.open = false; this.showConversation = true; this.notice = '';
            await this.refresh(true); this.$nextTick(() => {this.bottom(); this.markRead();});
        },
        async markRead() {
            if (!this.phone || !this.lastIncomingId || document.hidden || this.marking || (this.mobile && !this.showConversation)) return;
            const history = this.$refs.history;
            if (!history || history.scrollHeight - history.scrollTop - history.clientHeight > 70 || this.readIds[this.phone] >= this.lastIncomingId) return;
            const phone = this.phone, lastId = this.lastIncomingId; this.marking = true;
            try { const response = await fetch(this.readUrl, {method:'POST', headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({phone, last_id:lastId})});
                if (!response.ok) return; const data = await response.json(); this.readIds[phone] = lastId; this.unread = data.unread;
                const contact = this.contacts.find(c => c.sender_phone === phone); if(contact) contact.unread = data.contactUnread; this.publishCount();
            } catch {} finally { this.marking = false; }
        },
        async refresh(force = false) {
            if (this.refreshing && !force) return;
            const requestedPhone = this.phone; this.refreshing = true;
            try { const response = await fetch(this.indexUrl + (this.phone ? '?phone=' + this.phone : ''), {headers:{Accept:'application/json'}, cache:'no-store'});
                if (!response.ok) throw new Error(); const data = await response.json();
                if (requestedPhone !== this.phone) return;
                const atBottom = this.$refs.history.scrollHeight - this.$refs.history.scrollTop - this.$refs.history.clientHeight < 70;
                this.messages = data.messages; this.contacts = data.conversations; this.open = data.open; this.expires = data.expires; this.contactName = data.contactName; this.contactAvatar = data.contactAvatar;
                this.unread = data.unread; this.lastIncomingId = data.lastIncomingId; this.publishCount();
                if (atBottom || force) this.$nextTick(() => {this.bottom(); this.markRead();});
            } catch { this.error = true; this.notice = 'Chat belum bisa diperbarui. Periksa koneksi atau masuk kembali jika sesi berakhir.'; }
            finally {this.refreshing = false;}
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
