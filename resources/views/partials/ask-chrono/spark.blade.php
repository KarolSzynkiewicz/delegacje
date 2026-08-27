{{-- spark / Iskra: czysty zegar (loading mark) — bez atrybutu / bez twarzy --}}
<circle class="ac-bot__aura" cx="32" cy="32" r="31" fill="url(#{{ $glow }})"/>

<circle class="ac-bot__head" cx="32" cy="32" r="24" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="2.4"/>

<g class="ac-bot__ticks" stroke="rgba(148,163,184,.45)" stroke-width="2" stroke-linecap="round">
    <path d="M32 11.5 L32 15"/>
    <path d="M52.5 32 L49 32"/>
    <path d="M32 52.5 L32 49"/>
    <path d="M11.5 32 L15 32"/>
</g>

<circle class="ac-bot__scan" cx="32" cy="32" r="24" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="2.6" stroke-linecap="round"
        stroke-dasharray="23 128"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M32 32 L41 37.2"
      stroke="#f59e0b" stroke-width="3.2" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M32 32 L32 17.5"
      stroke="url(#{{ $grad }})" stroke-width="2.2" stroke-linecap="round"/>
<circle class="ac-bot__core" cx="32" cy="32" r="2.8" fill="url(#{{ $grad }})"/>

<circle class="ac-bot__spark" cx="49.5" cy="14.5" r="2.6" fill="url(#{{ $grad }})"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="54" cy="9" r="2"/>
    <circle cx="59" cy="5" r="1.4"/>
    <circle cx="62.5" cy="2" r="1"/>
</g>

<g class="ac-bot__check">
    <circle cx="51" cy="50" r="8" fill="#10b981"/>
    <path d="M47.2 50.2 L49.8 52.8 L54.8 47.6" fill="none"
          stroke="#052e1b" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
</g>
