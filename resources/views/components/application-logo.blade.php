<svg xmlns="http://www.w3.org/2000/svg"
     viewBox="0 0 300 300"
     width="64"
     height="64"
     aria-label="MK Technic logo">

  <defs>
    <linearGradient id="accent" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="#7ddcff"/>
      <stop offset="100%" stop-color="#2a8cff"/>
    </linearGradient>
  </defs>

  <!-- Gear -->
  <g fill="#0e141b">
    <g transform="translate(150 150)">
      <g id="tooth">
        <rect x="-6" y="-150" width="12" height="18"/>
      </g>
      <use href="#tooth" transform="rotate(45)"/>
      <use href="#tooth" transform="rotate(90)"/>
      <use href="#tooth" transform="rotate(135)"/>
      <use href="#tooth" transform="rotate(180)"/>
      <use href="#tooth" transform="rotate(225)"/>
      <use href="#tooth" transform="rotate(270)"/>
      <use href="#tooth" transform="rotate(315)"/>
    </g>

    <circle cx="150" cy="150" r="128"/>
  </g>

  <!-- Inner ring -->
  <circle cx="150" cy="150" r="112" fill="#0b1118"/>
  <circle cx="150" cy="150" r="112" fill="none" stroke="#7ddcff" stroke-width="4"/>

  <!-- MK -->
  <g fill="url(#accent)">
    <path d="
      M 72 182
      V 88
      H 96
      L 120 128
      L 144 88
      H 168
      V 182
      H 148
      V 122
      L 120 160
      L 92 122
      V 182
      Z"/>

    <path d="
      M 182 88
      V 182
      H 204
      V 140
      L 232 182
      H 252
      L 220 136
      L 252 88
      H 232
      L 204 130
      V 88
      Z"/>
  </g>

  <!-- TECHNIC -->
  <text
    x="150"
    y="218"
    text-anchor="middle"
    font-family="Arial, Helvetica, sans-serif"
    font-size="20"
    letter-spacing="1.5"
    fill="#7ddcff">
    TECHNIC
  </text>
</svg>
