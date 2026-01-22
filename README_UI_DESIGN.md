# UI Design System — Oleku (Navy + Maroon)

## Overview
Visual rules for Oleku’s EdTech platform with a navy blue foundation, white typography, and maroon call-to-action buttons, inspired by institutional styles like `miva.edu.ng`. This system targets readability, trust, and exam focus.

## Core Palette
- Primary Navy: `#0B2C4D` (headers, footers, hero, section bars)
- CTA Maroon: `#8B1E3F` (primary buttons and key actions)
- Accent Gold: `#F5B301` (secondary highlights and indicators)
- Background White: `#FFFFFF` (main content areas)
- Section Grey: `#F4F6F8` (subsections and cards)
- Text Primary: `#FFFFFF` on navy; `#1A1A1A` on white

## Design Principles
- High contrast (navy/white) for long study sessions
- Clear hierarchy with bold headings and generous spacing
- Mobile-first, low-motion, fast-loading components
- Exam realism for JAMB CBT while remaining friendly

## Tokens (CSS variables)
Use these in `assets/css/style.css` to standardize styles:
```
:root {
  --color-navy: #0B2C4D;
  --color-maroon: #8B1E3F;
  --color-gold: #F5B301;
  --color-white: #FFFFFF;
  --color-grey: #F4F6F8;
  --text-dark: #1A1A1A;
  --text-muted: #4A4A4A;
}
.btn { border-radius: 10px; font-weight: 600; }
.btn-primary { background: var(--color-maroon); color: var(--color-white); }
.btn-primary:hover { filter: brightness(0.9); }
.btn-outline { border: 2px solid var(--color-white); color: var(--color-white); }
.navbar { background: var(--color-navy); color: var(--color-white); }
.card { background: var(--color-white); border-radius: 12px; box-shadow: 0 8px 24px rgba(11,44,77,0.08); }
```

## Tailwind (CDN) Mapping
When using Tailwind CDN already in the project:
- Navy backgrounds: `bg-[#0B2C4D]`
- Maroon buttons: `bg-[#8B1E3F] text-white hover:brightness-90`
- Gold accents: `text-[#F5B301]` or `bg-[#F5B301]`
- Cards: `bg-white rounded-xl shadow-lg`
- Typography on navy: `text-white`; on white: `text-[#1A1A1A]`

## Components
- Navigation: navy background, white links, active link underlined or bold
- Hero: navy or navy gradient, large white heading, maroon primary CTA
- Buttons: 8–12px radius, minimal hover, clear focus outline
- Cards: white background, rounded 12px, soft shadow, breathable spacing
- Footer: navy background, white text, subtle separators

## Quiz UI (JAMB CBT)
- Header: navy with white text
- Timer: gold accent for visibility
- Canvas: white background for questions and inputs
- Feedback: show correctness after submission; avoid distractions during timing

## Accessibility & Mobile
- Minimum touch target 44px; ensure clear focus states
- Maintain AA contrast ratios (navy/white, maroon/white)
- Prefer reduced motion; keep transitions subtle
- Optimize for low bandwidth; avoid heavy images on critical paths

## Notes
- “Maton” in the request is interpreted as “maroon” for button colour.
- Keep style usage consistent across `index.php`, `jamb-subjects.php`, and auth pages for brand coherence.

## License
Proprietary — controlled educational deployment.
