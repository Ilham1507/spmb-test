@props(['label' => 'Karakter panduan SPMB'])

<svg class="guide-character-svg" viewBox="0 0 160 190" role="img" aria-label="{{ $label }}">
    <ellipse class="character-shadow" cx="80" cy="178" rx="42" ry="8" />
    <g class="character-legs">
        <path class="character-leg character-leg-left" d="M65 123 C58 142 55 156 49 170" />
        <path class="character-leg character-leg-right" d="M96 123 C104 142 108 156 113 170" />
        <path class="character-shoe character-shoe-left" d="M39 170 C47 164 57 166 62 174 C54 179 44 178 37 176 Z" />
        <path class="character-shoe character-shoe-right" d="M103 172 C112 165 123 167 128 176 C119 180 109 180 101 177 Z" />
    </g>
    <g class="character-body">
        <path class="character-shirt" d="M48 78 C50 58 64 48 80 48 C97 48 111 59 113 78 L119 125 C101 137 62 137 42 125 Z" />
        <path class="character-vest" d="M63 54 L80 75 L98 54 C106 60 111 69 113 81 L118 121 C100 132 63 132 43 121 L48 81 C51 68 56 60 63 54 Z" />
        <path class="character-collar" d="M66 54 L80 75 L94 54 L88 51 L80 61 L72 51 Z" />
        <path class="character-badge" d="M93 82 H108 V98 H93 Z" />
    </g>
    <g class="character-arms">
        <path class="character-arm character-arm-left" d="M50 85 C31 92 25 107 29 124" />
        <circle class="character-hand character-hand-left" cx="29" cy="124" r="8" />
        <path class="character-arm character-arm-right" d="M111 84 C129 92 135 106 130 124" />
        <circle class="character-hand character-hand-right" cx="130" cy="124" r="8" />
    </g>
    <g class="character-head">
        <path class="character-neck" d="M70 45 H91 V61 C86 66 75 66 70 61 Z" />
        <path class="character-face-base" d="M48 30 C48 11 62 0 80 0 C99 0 113 12 113 31 C113 54 99 69 80 69 C62 69 48 54 48 30 Z" />
        <path class="character-hair" d="M48 31 C45 15 58 -3 80 -3 C102 -3 116 14 112 35 C101 22 87 21 68 23 C60 24 54 27 48 31 Z" />
        <path class="character-bangs" d="M63 17 C68 28 58 33 48 36 C49 21 55 17 63 17 Z M84 11 C89 24 80 31 70 32 C72 20 76 14 84 11 Z M98 17 C99 28 108 32 114 35 C112 22 106 18 98 17 Z" />
        <g class="character-eyes">
            <ellipse class="character-eye character-eye-left" cx="69" cy="36" rx="4" ry="5" />
            <ellipse class="character-eye character-eye-right" cx="93" cy="36" rx="4" ry="5" />
            <circle class="character-eye-light" cx="70" cy="34" r="1.3" />
            <circle class="character-eye-light" cx="94" cy="34" r="1.3" />
        </g>
        <path class="character-brow character-brow-left" d="M63 28 C67 25 71 25 75 28" />
        <path class="character-brow character-brow-right" d="M87 28 C91 25 96 25 100 28" />
        <path class="character-mouth character-mouth-smile" d="M72 49 C77 55 85 55 90 49" />
        <path class="character-mouth character-mouth-focus" d="M73 50 C78 52 85 52 90 50" />
        <ellipse class="character-mouth character-mouth-open" cx="81" cy="50" rx="7" ry="6" />
        <circle class="character-cheek character-cheek-left" cx="59" cy="45" r="5" />
        <circle class="character-cheek character-cheek-right" cx="103" cy="45" r="5" />
    </g>
</svg>
