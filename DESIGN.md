---
name: Contadores SENABED
description: Registro institucional del consumo de copias e impresiones de las multifuncionales de SENABED, con voz de oficio en el acceso y tablero sobrio para operar.
colors:
  marino: "#0d2149"
  marino-700: "#123a7a"
  marino-600: "#1d4ed8"
  marino-50: "#eef2fb"
  cian: "#29a9e0"
  saldo: "#15803d"
  saldo-50: "#ecfdf3"
  papel: "#f5f6f8"
  bond: "#fbfbf8"
  blanco: "#ffffff"
  tinta: "#0f172a"
  tinta-2: "#334155"
  tinta-3: "#64748b"
  tinta-4: "#94a3b8"
  linea: "rgba(15, 23, 42, 0.08)"
typography:
  display-oficio:
    fontFamily: "\"Source Serif 4\", Georgia, serif"
    fontSize: "clamp(30px, 5vw, 40px)"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.015em"
  reference-oficio:
    fontFamily: "\"Source Serif 4\", Georgia, serif"
    fontSize: "14px"
    fontWeight: 600
    fontFeature: "\"tnum\""
  title:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.43
  body:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.43
  body-oficio:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 400
  label:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 500
  label-renglon:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 600
    letterSpacing: "0.14em"
  table-head:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 500
    letterSpacing: "0.025em"
  figure:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontWeight: 600
    fontFeature: "\"tnum\""
rounded:
  hoja: "3px"
  sm: "4px"
  md: "6px"
  lg: "8px"
  xl: "12px"
  full: "9999px"
spacing:
  control-h: "36px"
  renglon-h: "48px"
  panel: "20px"
  page-y: "32px"
  page-x-sm: "16px"
  page-x-md: "24px"
  page-x-lg: "32px"
  oficio-x: "56px"
  oficio-y: "48px"
components:
  button-primary:
    backgroundColor: "{colors.marino}"
    textColor: "{colors.blanco}"
    typography: "{typography.title}"
    rounded: "{rounded.lg}"
    padding: "0 14px"
    height: "36px"
  button-primary-hover:
    backgroundColor: "{colors.marino-700}"
  button-secondary:
    backgroundColor: "{colors.blanco}"
    textColor: "{colors.tinta-2}"
    rounded: "{rounded.lg}"
    padding: "0 14px"
    height: "36px"
  button-secondary-hover:
    backgroundColor: "{colors.papel}"
  button-oficio:
    backgroundColor: "{colors.marino}"
    textColor: "{colors.blanco}"
    rounded: "{rounded.sm}"
    padding: "0 24px"
    height: "48px"
  button-oficio-hover:
    backgroundColor: "{colors.marino-700}"
  input-control:
    backgroundColor: "{colors.papel}"
    textColor: "{colors.tinta}"
    typography: "{typography.body}"
    rounded: "{rounded.lg}"
    padding: "8px 12px"
  input-renglon:
    textColor: "{colors.tinta}"
    padding: "0"
    height: "48px"
  panel:
    backgroundColor: "{colors.blanco}"
    rounded: "{rounded.xl}"
    padding: "20px"
  oficio-sheet:
    backgroundColor: "{colors.bond}"
    rounded: "{rounded.hoja}"
    padding: "48px 56px"
    width: "640px"
  chip-nivel:
    backgroundColor: "{colors.marino}"
    textColor: "{colors.blanco}"
    rounded: "{rounded.sm}"
    height: "20px"
    padding: "0 6px"
  chip-contrato:
    backgroundColor: "{colors.marino-50}"
    textColor: "{colors.marino}"
    rounded: "{rounded.md}"
    padding: "2px 8px"
---

# Design System: Contadores SENABED

## Overview

**Creative North Star: "El Oficio y el Libro de Registro"**

The system speaks in two registers of one institution. At the door it is a **document**: a warm bond sheet with the official DIE letterhead and a cyan rule under the logo, laid on a navy desk ruled like a register book. The title and reference line are set in a serif, the fields are lines you write on, the button says "Firmar y entrar", and a blue rubber stamp «RECIBIDO» comes down when the request is sent. Inside it is an **operating register**: a navy sidebar, a cool paper (papel) canvas, white sheets (hojas) lifted by a hairline ring, sans-serif labels, and tabular figures that have to match the provider's counts.

Both registers use the same ink. Navy (marino) is the institution's ink: the desk, the sidebar, the primary button, the floor chip, and the title of the oficio. A darker slate scale (tinta) carries text. Green (saldo) means remaining balance and nothing else. Density in the app is medium-high: 14px body, 36px controls, 20px panel padding. The login is more relaxed: 48px lines and a sheet up to 640px wide.

The register is institutional and sober, and it is SENABED's own (PRODUCT.md: nothing that "looks like a template"). The official DIE logo is a binding identity asset and appears whole in the letterhead.

**Key Characteristics:**
- One ink family (marino) across the oficio and the app shell; marino-600 is the action and focus blue.
- The document voice (Source Serif 4, bond, cian rule, ruled lines, stamp) lives on the access surfaces. The app's operating voice is Figtree on papel and white.
- Sheets (hojas) are white with a hairline ring shadow and no border.
- Every counter figure uses tabular numerals.
- Every printer is identified by its floor chip (N1 … N9.2).

## Colors

The palette is cool and institutional: navy ink on cool paper, a single cyan taken from the SENABED logo, and green reserved for balance.

### Primary
- **Tinta Marino / Navy Ink** (marino): the institution's color. Used for the guest desk background, the app sidebar, primary and "Firmar y entrar" buttons, floor chips, the user avatar, and the oficio title.
- **Marino Hondo / Deep Navy** (marino-700): hover state of every navy button.
- **Azul Acción / Action Blue** (marino-600): focus rings and borders in the app, the focused renglón line, link text, the consumed bar of the bolsón gauge, and the rubber stamp ink.
- **Velo Marino / Navy Veil** (marino-50): background of the contract chip in the topbar.

### Secondary
- **Cian Institucional / Institutional Cyan** (cian): the accent from the SENABED logo. Used as the 3px rule under the letterhead, the focus ring on the oficio, the underline decoration on links (60% alpha), and text selection (28% alpha behind marino text).

### Tertiary
- **Verde Saldo / Balance Green** (saldo, saldo-50): available balance (disponibles), the empty track of the bolsón gauge, the "on pace" message, and the Excel export action.

### Neutral
- **Papel / Cool Paper** (papel): the app canvas, table headers, input fills, and secondary-button hover.
- **Bond / Warm Bond** (bond): the oficio sheet only, plus the ring-offset color on that sheet.
- **Blanco** (#ffffff): panels and tables (hojas) in the app.
- **Tinta / Ink** (tinta): primary text and the time-elapsed marker on the gauge. Also the caret color base (the caret itself is marino).
- **Tinta 2** (tinta-2): secondary text, field labels, and table cells.
- **Tinta 3** (tinta-3): descriptions, help text, table headings, and the uppercase renglón labels.
- **Tinta 4** (tinta-4): placeholders only.
- **Línea** (linea): hairline dividers, panel-header rules, table row dividers, and the inset ring on inputs.

Status colors are Tailwind's red (red-600 / red-700) for errors and gauge overage (90% and above), and amber-700 for "consumption ahead of pace". None of these is a theme token yet (see Do's and Don'ts).

### Named Rules
**The One Ink Rule.** Navy is the only brand fill. Primary actions, the sidebar, and identity chips are marino. Interaction (focus, links, progress) is marino-600. Don't introduce a second brand hue.

**The Saldo Rule.** Green means balance remaining. Don't use it for generic success decoration.

**The Filete Rule.** Cian is a line, never a field. It is used as a rule, a ring, an underline, or a selection tint. At 2.6:1 on bond it is not legible as text and is not used as a surface fill.

## Typography

**Display Font:** Source Serif 4 (with Georgia, serif). Self-hosted from `public/fonts`, weights 600 and 700 only.
**Body Font:** Figtree (with the Tailwind sans stack).

**Character:** The serif is the voice of the oficio: the institution signing a document. Figtree is the voice of the register: plain, compact, and numeric. The serif never sets running text or figures inside the app.

### Hierarchy
- **Display oficio** (Source Serif 4, 700, 30px rising to 40px from sm, line-height 1.1, -0.015em, marino): the title on the access sheet.
- **Reference oficio** (Source Serif 4, 600, 14px, tabular, tinta-2): the letterhead line "Ref.: DIE · …" with the date on the right.
- **Title** (Figtree, 600, 14px, tinta): topbar section name, panel and table-section titles.
- **Body** (Figtree, 400, 14px): app content. Descriptions are 12px tinta-3.
- **Body oficio** (Figtree, 400, 15px, tinta-2): the lead line under the oficio title. Renglón input text is 18px.
- **Label** (Figtree, 500, 12px, tinta-2): app field labels (x-campo). Help text is 11px tinta-3.
- **Label renglón** (Figtree, 600, 11px, uppercase, 0.14em, tinta-3): the caption above each writing line on the oficio, like the printed caption on a form field.
- **Table head** (Figtree, 500, 11px, 0.025em, tinta-3 on papel).

### Named Rules
**The Cifra Rule.** Tables and every `.cifra` element use tabular numerals. Counters must not shift when they update.

**The Two Voices Rule.** Source Serif 4 is for the document (letterhead, reference, oficio title) at 600 or 700. Don't use it for body copy, tables, or app chrome.

## Layout

**Guest (oficio):** a full-height marino desk ruled with a 1px line every 30px (white at 4.5% alpha). The sheet is centered, `max-width` 640px, padded 24px/32px on mobile and 56px/48px from sm. The letterhead logo is up to 400px wide. The 3px cian rule sits 16px below it, then the reference row, then content 40px down. The form stacks with 28px between lines. The signature row wraps: the button goes full width on mobile. A one-line network note sits under the sheet.

**App (operate):** a fixed 256px marino sidebar from lg, which becomes an off-canvas drawer over a 50% gray scrim below lg. The sticky 56px topbar is white at 90% with backdrop blur and a linea bottom border. The page container is `py-8`, padding 16/24/32px by breakpoint, with max-width chosen per view: 5xl/6xl for forms, 7xl for lists, 1600px for the general dashboard. Panels stack with 20–24px gaps.

Rhythm comes from the Tailwind 4px scale. Common steps are 8, 12, 16, 20, and 24px.

## Elevation & Depth

This is a hybrid system of tonal layers with paper-like lift. In the app, depth is white on papel plus one hairline-ring shadow, so panels read as sheets on a desk without a drawn border. On the guest desk, the oficio sheet casts a deep shadow on navy, with an inset top highlight, because it is the one physical object on that surface.

### Shadow Vocabulary
- **Hoja** (`box-shadow: 0 0 0 1px rgba(15,23,42,.06), 0 1px 2px -1px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04)`): every app panel, table section, and secondary button.
- **Oficio en mesa** (`box-shadow: 0 1px 0 rgba(255,255,255,.6) inset, 0 24px 60px -12px rgba(0,0,0,.55), 0 8px 18px -8px rgba(0,0,0,.35)`): the access sheet on the marino desk only.
- **Tinta del botón** (`box-shadow: 0 6px 14px -6px rgba(13,33,73,.6)`): the "Firmar y entrar" button on bond.

### Named Rules
**The Hairline Sheet Rule.** App surfaces separate by the hoja shadow, not by borders or heavier drop shadows.

## Shapes

Corners are low and controlled. The oficio sheet uses 3px, like cut paper. The oficio button, floor chips, and the checkbox use 4px. App buttons and inputs use 8px, export actions and the contract chip use 6px, and panels use 12px. Pills (fully rounded) are used only for gauges, the avatar, and the sidebar's active bar. The renglón field has no shape at all: just a 2px bottom rule. The stamp is the one circular device, with concentric rings (4px, 1.5px, 1.5px strokes) and text on a ring path.

## Components

### Buttons
Compact and navy-inked. Press feedback is a 0.97 scale.
- **Shape:** gently rounded (8px) in the app; nearly square (4px) on the oficio.
- **Primary:** marino fill, white 14px/500 text, 36px tall, 14px horizontal padding, icon gap 8px.
- **Hover / Focus:** marino-700 on hover. A 2px focus-visible ring with 2px offset: marino-600 in the app, cian on the oficio. Background and transform transitions take 150ms.
- **Secondary:** white with the hoja shadow, tinta-2 text, papel on hover.
- **Oficio:** 48px tall, 24px padding, 15px/600, carries the ink shadow and a nib icon. Its loading state reads "Verificando…" with a spinner.
- **Export actions:** 32px, 12px/500, 6px radius, colored text with a 25% inset ring of the same hue (saldo for Excel, red-700 for PDF).

### Chips
- **Nivel (floor chip):** marino, white 10px/600 tabular text "N{nivel}", 20px tall, min-width 36px, 4px radius. It identifies each printer everywhere in the system.
- **Contrato:** marino-50 with marino 11px/500 text and a contract icon, in the topbar (inside the session only).

### Cards / Containers
- **Corner Style:** 12px.
- **Background:** white on papel.
- **Shadow Strategy:** hoja (see Elevation).
- **Border:** none. A linea rule separates the header from the body when the body is flush (tables).
- **Internal Padding:** 20px; the header is 20px horizontal, 16px top. Table sections put a papel table head over linea row dividers, with horizontal scroll.

### Inputs / Fields
- **Control (app):** papel fill, no border, 1px inset linea ring, 8px radius, 8px/12px padding, 14px tinta text, tinta-4 placeholder.
- **Focus:** a 2px marino-600 ring.
- **Renglón (oficio):** transparent and 48px tall, with no box and no horizontal padding. A 2px bottom rule in tinta at 25% turns marino-600 on focus (150ms color transition, no ring). Text is 18px and the caret is marino. A password field gets a trailing show/hide toggle.
- **Error:** the line or ring turns red-600/70. The message is 12–14px red-700 below the field, or as a top alert with an icon on the oficio. On a failed login the whole sheet shakes (sacudir, 380ms).

### Navigation
- **Sidebar:** marino, 256px. A brand block of 64px with a white/10 bottom rule. Items are 14px/500 slate-300 with an 8px radius and a 16px icon column. Hover is white/5 with white text. Active is white/10 with white text, a sky-300 icon, and a 4px full-round sky-400 bar on the inline start. The footer is an 11px slate-500 credit.
- **Topbar:** the white/90 blurred bar holds the section title (title style), the contract chip, and the user menu with a marino initial avatar.
- **Mobile:** the drawer slides in over 200ms ease-out and closes on scrim tap or navigation.

### Sello «RECIBIDO» (signature)
A blue rubber stamp drawn as inline SVG in marino-600 with multiply blend. It has an outer ring, two inner rings, "DIRECCIÓN DE INFORMÁTICA Y ESTADÍSTICA · SENABED ·" on a ring path, "RECIBIDO" at 800 weight, and the date in capitals. While the login request is verifying, it comes down over the signature row: `sello-baja`, 420ms `cubic-bezier(0.23,1,0.32,1)`, from scale 1.35 at -14° with blur to scale 1 at -11° and 0.9 opacity. With reduced motion it appears static.

### Medidor del bolsón (signature)
A 12px pill track in saldo-50 with a saldo/20 inset ring, filled in marino-600 (red-600 at 90% and above). A 2px tinta marker shows how much of the contract period has elapsed. Below it are tabular captions (% consumido, disponibles in saldo) and a pace message (saldo when on pace, amber-700 when ahead).

## Do's and Don'ts

### Do:
- **Do** use marino for primary actions, the sidebar, floor chips, and identity marks. Use marino-700 for hover and marino-600 for focus, links, and progress.
- **Do** set every counter, date reference, and table figure with tabular numerals (`.cifra`).
- **Do** build app surfaces as white hojas (12px radius, hoja shadow, 20px padding) on papel, with no borders.
- **Do** identify each printer with the N{nivel} chip wherever it appears.
- **Do** keep the oficio voice together on access surfaces: bond sheet on the ruled marino desk, the official DIE logo whole in the letterhead, the 3px cian rule, Source Serif 4 reference and title, renglón fields, navy signature button.
- **Do** give every animation a `prefers-reduced-motion` fallback, as sello-baja and sacudir have.

### Don't:
- **Don't** set Source Serif 4 at weights other than 600/700, or use it for body copy, tables, or app chrome.
- **Don't** use cian as a surface fill or as text color. It is a rule, ring, underline, or selection tint.
- **Don't** use green (saldo) for anything other than available balance and the export-to-Excel action.
- **Don't** put the deep "oficio en mesa" shadow on app panels. App depth is the hoja hairline.
- **Don't** substitute Tailwind gray, indigo, or blue for tinta, linea, papel, and marino-600 in new controls.

Open and undecided items: status red and amber have no theme tokens (the config comment names "alerta (ámbar)", but none is defined). Figtree and Font Awesome load from public CDNs, while the serif is self-hosted for the internal network. Chart.js colors are hard-coded in the app layout. The sidebar accent uses Tailwind sky-400/300 rather than the cian token.
