<span class="wa-contact-avatar" aria-hidden="true">
    <template x-if="{{ $avatarExpression }}.photo"><img :src="{{ $avatarExpression }}.photo" alt="" class="h-full w-full rounded-full object-cover"></template>
    <template x-if="!{{ $avatarExpression }}.photo && {{ $avatarExpression }}.character"><span class="wa-contact-character" :style="{backgroundPosition: {{ $avatarExpression }}.position}"></span></template>
    <template x-if="!{{ $avatarExpression }}.photo && !{{ $avatarExpression }}.character"><span x-text="{{ $avatarExpression }}.initial"></span></template>
</span>
