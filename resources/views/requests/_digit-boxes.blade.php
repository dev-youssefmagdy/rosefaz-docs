{{-- 10 boxed digit cells. Params: $name (nullable in view mode), $value (string), $edit (bool), $yellow (bool), $placeholder (optional caption) --}}
@php
    $chars = mb_str_split(str_pad(mb_substr((string) $value, 0, 10), 10, ' '));
    $boxBg = ($yellow ?? false) ? 'bg-[#ffff00]' : 'bg-white';
@endphp
<div class="relative inline-flex flex-col items-center gap-0.5 py-1" @if ($edit && $name) data-digits @endif>
    <div class="flex rounded-sm focus-within:ring-2 focus-within:ring-rose-700 focus-within:ring-offset-1" dir="ltr">
        @foreach ($chars as $i => $char)
            <span data-box class="flex h-8 w-7 items-center justify-center border border-black {{ $boxBg }} text-base font-bold tabular-nums {{ $i > 0 ? 'border-s-0' : '' }}">{{ trim($char) }}</span>
        @endforeach
    </div>
    @if ($edit && $name)
        <input type="text" name="{{ $name }}" value="{{ $value }}" inputmode="numeric" autocomplete="off" maxlength="10"
               aria-label="{{ $label ?? $name }}" class="absolute inset-0 h-full w-full cursor-text opacity-0">
    @endif
    @if (! empty($placeholder))
        <span class="text-[10px] text-gray-500">{{ $placeholder }}</span>
    @endif
</div>
