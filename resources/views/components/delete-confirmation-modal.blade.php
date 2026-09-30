<template id="delete-confirmation-template">
    <div class="fixed inset-0 z-[2147483647] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-confirmation-title">
        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="h-1.5 bg-rose-500"></div>
            <div class="p-5 sm:p-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-xl font-black text-rose-700" aria-hidden="true">!</div>
                <h2 id="delete-confirmation-title" class="mt-4 text-xl font-black text-slate-950">Hapus data?</h2>
                <p data-delete-confirmation-message class="mt-2 text-sm font-semibold leading-relaxed text-slate-600"></p>
                <p class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold leading-relaxed text-rose-700">Data yang dihapus tidak dapat dikembalikan.</p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-delete-cancel class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Batal</button>
                    <button type="button" data-delete-confirm class="rounded-xl bg-rose-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-rose-200 hover:bg-rose-700">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    (() => {
        const deleteFormSelector = 'form:has(input[name="_method"][value="DELETE" i])';
        let activeForm = null;
        let modal = null;

        const messageFor = (form) => {
            if (form.dataset.deleteMessage) return form.dataset.deleteMessage;

            const inline = form.getAttribute('onsubmit') || '';
            const match = inline.match(/confirm\((['"])(.*?)\1\)/);
            if (match?.[2]) return match[2];

            const label = form.querySelector('button[type="submit"], button:not([type])')?.textContent?.trim();
            return label ? `Yakin ingin ${label.toLowerCase()} data ini?` : 'Yakin ingin menghapus data ini?';
        };

        const close = () => {
            modal?.remove();
            modal = null;
            activeForm = null;
        };

        const open = (form) => {
            const template = document.getElementById('delete-confirmation-template');
            if (!template) return;

            close();
            modal = template.content.firstElementChild.cloneNode(true);
            modal.querySelector('[data-delete-confirmation-message]').textContent = messageFor(form);
            modal.querySelector('[data-delete-cancel]').addEventListener('click', close);
            modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
            modal.querySelector('[data-delete-confirm]').addEventListener('click', () => {
                const submitting = activeForm;
                close();
                if (submitting) HTMLFormElement.prototype.submit.call(submitting);
            });
            document.body.appendChild(modal);
            modal.querySelector('[data-delete-cancel]').focus();
            activeForm = form;
        };

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal) close();
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.matches(deleteFormSelector)) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            open(form);
        }, true);
    })();
</script>
