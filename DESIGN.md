# AR Homes Posadas Farm Resort - Design System Specification

## 1. Brand Identity & Design Direction

The AR Homes Posadas Farm Resort design system follows a luxury, modern resort hospitality aesthetic inspired by the authentication experience. It incorporates deep midnight backdrops, multi-layered glassmorphism, gold accents, and fluid transitions.

### Core Philosophy
- **Cinematic Elegance:** High-resolution resort imagery with dynamic background animation.
- **Glassmorphic Depth:** Frosted glass panels (`backdrop-filter: blur(20px)`) with crisp borders.
- **Golden Luxury:** Radiant warm gold gradients for primary actions and accents.
- **Clean Contrast:** Pure white headings and high-contrast labels against dark backdrops.
- **Strict Iconography:** Font Awesome 6 icons exclusively. No unicode emojis are permitted in the user interface or code base.

---

## 2. Color Palette & Design Tokens

### Primary Canvas Tokens
| Token | Value | Description |
| :--- | :--- | :--- |
| `--dark-canvas` | `#0a0f1d` | Base background color of the viewport |
| `--dark-canvas-alt` | `#0f172a` | Secondary dark background container |
| `--glass-bg` | `rgba(15, 23, 42, 0.78)` | Standard frosted glass surface |
| `--glass-bg-card` | `rgba(15, 23, 42, 0.88)` | Primary card and form container surface |
| `--glass-bg-heavy` | `rgba(15, 23, 42, 0.95)` | Modals, popups, and dropdown panels |
| `--glass-border` | `rgba(255, 255, 255, 0.14)` | Subtle highlight border on dark surfaces |
| `--glass-border-gold` | `rgba(250, 204, 21, 0.35)` | Accent border for active or featured items |

### Gold Accent Tokens
| Token | Value | Description |
| :--- | :--- | :--- |
| `--primary-gold` | `#facc15` | Accent gold for highlights, icons, active borders |
| `--primary-gold-light` | `#fef08a` | Highlight edge for gradients and badges |
| `--primary-gold-dark` | `#ca8a04` | Shadow gradient stop |
| `--primary-gradient` | `linear-gradient(135deg, #fef08a 0%, #facc15 50%, #eab308 100%)` | CTA button and active pill gradient |

### Status & Feedback Tokens
| Status | Background Token | Border Token | Text Token |
| :--- | :--- | :--- | :--- |
| **Confirmed / Approved** | `rgba(34, 197, 94, 0.18)` | `rgba(74, 222, 128, 0.40)` | `#4ade80` |
| **Pending / Warning** | `rgba(245, 158, 11, 0.18)` | `rgba(251, 191, 36, 0.40)` | `#fbbf24` |
| **Cancelled / Error** | `rgba(239, 68, 68, 0.18)` | `rgba(248, 113, 113, 0.40)` | `#f87171` |
| **Rebooked / Special** | `rgba(168, 85, 247, 0.18)` | `rgba(192, 132, 252, 0.40)` | `#c084fc` |
| **Info / Cyan Accent** | `rgba(56, 189, 248, 0.18)` | `rgba(56, 189, 248, 0.40)` | `#38bdf8` |

### Typography Tokens
| Token | Font Family | Weights |
| :--- | :--- | :--- |
| Primary Font | `"Poppins", sans-serif` | 300, 400, 500, 600, 700, 800 |
| Heading Sizes | H1: `1.75rem` / H2: `1.55rem` / H3: `1.25rem` | 700, 800 |
| Body Text | `0.95rem`, line-height `1.6` | 400, 500 |
| Captions & Badges | `0.75rem` - `0.85rem` | 600, 700 |

---

## 3. Strict Iconography Standards

Unicode emojis are strictly forbidden throughout the entire system. All visual indicators, status badges, buttons, form controls, and feedback elements use Font Awesome 6 icons (`fas` / `far`).

### Icon Catalog by Domain

#### Navigation & Header
- Dashboard: `<i class="fas fa-chart-line"></i>`
- Make Reservation: `<i class="fas fa-calendar-plus"></i>`
- Booking & Payment: `<i class="fas fa-credit-card"></i>`
- Profile: `<i class="fas fa-user-cog"></i>`
- My Reservations: `<i class="fas fa-clipboard-list"></i>`
- Reservation History: `<i class="fas fa-history"></i>`
- Rebooking Requests: `<i class="fas fa-calendar-alt"></i>`
- Notifications: `<i class="fas fa-bell"></i>`
- Reviews: `<i class="fas fa-star"></i>`
- Logout: `<i class="fas fa-sign-out-alt"></i>`

#### Dashboard Statistics
- Total Reservations: `<i class="fas fa-calendar-alt"></i>`
- Pending Reservations: `<i class="fas fa-clock"></i>`
- Approved Reservations: `<i class="fas fa-check-circle"></i>`
- Completed Stays: `<i class="fas fa-star"></i>`
- Empty State: `<i class="fas fa-inbox"></i>`

#### Reservation Policies
- Single Booking Exclusivity: `<i class="fas fa-calendar-check"></i>`
- Non-Refundable Deposit: `<i class="fas fa-exclamation-triangle"></i>`
- Rebooking Allowance: `<i class="fas fa-history"></i>`
- Downpayment Requirement: `<i class="fas fa-wallet"></i>`
- Security Bond: `<i class="fas fa-shield-alt"></i>`
- Guest Capacity: `<i class="fas fa-users"></i>`

#### Booking Steps & Packages
- Package Inclusions: `<i class="fas fa-list-check"></i>`
- External Lightbox Link: `<i class="fas fa-external-link-alt"></i>`
- View Image: `<i class="fas fa-search-plus"></i>`
- Step Checkmark: `<i class="fas fa-clipboard-check"></i>`
- Check-in: `<i class="fas fa-sign-in-alt"></i>`
- Check-out: `<i class="fas fa-sign-out-alt"></i>`
- Special Requests: `<i class="fas fa-comment-dots"></i>`
- Secure Checkout: `<i class="fas fa-lock"></i>`
- GCash: `<i class="fas fa-mobile-alt"></i>`
- Credit / Debit Card: `<i class="fas fa-credit-card"></i>`
- Bank Transfer: `<i class="fas fa-university"></i>`

#### Modals & Feedback
- Success State: `<i class="fas fa-check-circle"></i>`
- Error State: `<i class="fas fa-exclamation-circle"></i>`
- Warning / Logout State: `<i class="fas fa-sign-out-alt"></i>`
- Close Modal: `<i class="fas fa-times"></i>`
- Back Action: `<i class="fas fa-arrow-left"></i>`
- Forward Action: `<i class="fas fa-arrow-right"></i>`
- Refresh: `<i class="fas fa-sync-alt"></i>`

---

## 4. Component Standards

### 4.1 Background Slideshow & Overlay
- Background element `body::before` cycles through 11 high-resolution resort assets on a 48s infinite keyframe loop (`backgroundSlideshow`).
- Radial gradient dark overlay on `body::after` (`radial-gradient(ellipse at center, rgba(15, 23, 42, 0.82) 0%, rgba(10, 15, 29, 0.96) 100%)`) with `backdrop-filter: blur(5px)`.

### 4.2 Dashboard Header
- Fixed positioning at top with `z-index: 1000`.
- Dark glass surface (`rgba(15, 23, 42, 0.88)` with `backdrop-filter: blur(20px)`).
- Circular resort logo with gold outer ring and subtle box shadow.
- Brand typography: "AR Homes Posadas" H1 with gold uppercase subtitle "FARM RESORT • GUEST PORTAL".
- Guest profile badge containing avatar and gold role pill.
- Logout CTA button in gold gradient.

### 4.3 Resort Showcase Gallery
- Dimensions: `100%` width by `480px` height with `24px` border-radius.
- Pure unobstructed imagery: all overlay badges, scroll indicators, and caption pills removed from the transition images area to maximize visual presentation.
- Horizontal flex slider with smooth cubic-bezier transitions on slide index.
- Circular controls (`.gallery-btn`) with gold arrow icons and hover scale animation.

### 4.4 Horizontal Navigation Bar
- Frosted glass container (`rgba(15, 23, 42, 0.80)` with blur).
- Navigation pills (`.nav-btn`):
  - Inactive: dark translucent background, subtle border, gold icon.
  - Hover: slight vertical lift (`translateY(-2px)`), gold border glow.
  - Active: linear gold gradient background (`#fef08a` to `#eab308`), dark text (`#0f172a`), font-weight 700.
  - Notification badge: crimson pill with box shadow.

### 4.5 Make Reservation Flow

#### Step 1: Package Selection
- Policy card with gold shield icon and 6 structured highlights.
- Responsive 4-column card grid (`.room-packages-grid`).
- `.room-package-card`:
  - Frosted glass body with hover lift.
  - Top header with package title and status badge (Budget Friendly, Most Popular, Full Experience).
  - Featured card: 2px solid gold border with centered luxury top badge `RECOMMENDED` (no overlapping diagonal ribbon).
  - Bold price in gold typography (`#fde047`) with glowing text shadow.
  - Duration and time slot chips with left accent borders.
  - Inclusions trigger button with icon and slide-out inclusions modal.
  - Gold gradient action button (`.select-package-btn`).

#### Step 2: Complete Your Booking (Booking & Payment)
- Two-column responsive layout (`1fr 380px`) styled in dark luxury midnight & gold glass aesthetic (`rgba(15, 23, 42, 0.88)`).
- Left Column:
  - Booking selection summary card with obsidian backdrop, gold header, and time slot pill.
  - Date Selection Card with Flatpickr calendar input, gold focus outline, and animated point hint pill.
  - Group Details Card with emerald guest notice box (`rgba(6, 78, 59, 0.35)`), group type select, and special requests textarea.
  - Terms acknowledgment card with custom luxury gold checkmark.
  - Action buttons: `.btn-back-v2` in dark glass and `.btn-submit-v2` in vibrant gold gradient (`#fef08a` to `#eab308`).
- Right Column:
  - Sticky price summary card (`.price-summary-card`) with dark glass panel and glowing gold header.
  - Breakdown rows: Package price, Required Downpayment (amber accent), Balance on arrival, Refundable Security Bond (teal accent).
  - Highlighted footer with gold Total Due Now amount (`#facc15` with text glow).
  - Payment method card displaying verified PayMongo integration with GCash, Card, and Bank channels.
  - Dynamic PayMongo return URLs resolving against active host/port to eliminate redirection 404s.

### 4.6 Modal Windows
- Overlay: `rgba(10, 15, 29, 0.82)` with `backdrop-filter: blur(12px)`.
- Dialog Container: `rgba(15, 23, 42, 0.94)`, border `1px solid rgba(255, 255, 255, 0.16)`, border-radius `24px`.
- Modal Header: sticky header with gold icon, title, and circular close button.
- Modal Body: structured glass items with gold form labels.
- Modal Actions: primary CTA in gold gradient, secondary in dark glass with gold text.

### 4.7 Animated Section Scroll Indicators (Top & Bottom Navigation)
- **Top Section Indicator:**
  - Container: `.section-scroll-indicator-wrap.top-indicator` positioned at the head of every view.
  - Interactive pill: `.section-scroll-indicator.scroll-down-btn` with frosted glass backdrop, 1px gold border, and `scrollGlowPulse` animation.
  - Left: Animated CSS mouse icon with sliding wheel dot (`mouseWheelSlide`).
  - Center: Contextual uppercase prompt text (`Scroll Down for Policies & Packages`, `Scroll Down for Stats & Activity`, etc.).
  - Right: Bouncing gold chevron `<i class="fas fa-chevron-down"></i>` with `chevronBounce` keyframe animation.
- **Bottom Section Indicator (Back to Top):**
  - Container: `.section-scroll-indicator-wrap.bottom-indicator` centered at the foot of every section.
  - Interactive pill: `.scroll-up-btn` featuring `<i class="fas fa-arrow-up scroll-indicator-chevron-up"></i>` with `arrowUpBounce` animation and "Back to Top" label. Smoothly returns viewport to top.
  - No duplicate middle scroll buttons or redundant bottom down-scroll controls.
- **Floating Back to Top Control:**
  - Circular frosted glass button (`.floating-back-to-top`) fixed at bottom-right viewport with gold border and icon `<i class="fas fa-arrow-up"></i>`.
- **Coverage across all portal sections:**
  - Resort Gallery (`#resort-gallery-section`)
  - Dashboard Overview (`#dashboard-section`)
  - Make a Reservation Step 1 (`#my-reservations-section` / `#step1`)
  - Make a Reservation Step 2 (`#step2`)
  - Booking Details & Payment (`#booking-details-section`)
  - Profile Settings (`#profile-section`)
  - My Active Reservations (`#my-bookings-section`)
  - Reservation History (`#bookings-history-section`)
  - Rebooking Requests (`#rebooking-requests-section`)
  - User Notifications (`#notifications-section`)
  - Reviews & Feedback (`#reviews-section`)

---

## 5. Interaction States & Transitions

### Micro-Interactions
- **Buttons (`.btn-primary`, `.select-package-btn`, `.btn-submit-v2`):**
  - Hover: `transform: translateY(-2px); box-shadow: 0 10px 30px rgba(250, 204, 21, 0.55);`
  - Active: `transform: translateY(0);`
- **Cards (`.room-package-card`, `.booking-list-item`, `.stats-card`):**
  - Hover: `transform: translateY(-5px); border-color: rgba(250, 204, 21, 0.5);`
  - Transition: `all 0.3s cubic-bezier(0.16, 1, 0.3, 1)`
- **Form Controls (`input`, `select`, `textarea`):**
  - Default: `background: rgba(10, 15, 29, 0.80); border: 1px solid rgba(255, 255, 255, 0.18);`
  - Focus: `border-color: #facc15; box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.25); background: rgba(10, 15, 29, 0.95);`

---

## 6. Responsive Breakpoints

| Breakpoint | Target Devices | Layout Behavior |
| :--- | :--- | :--- |
| `< 600px` | Mobile Phones | 1-column packages, single-column stats, stacked header |
| `601px - 1024px` | Tablets | 2-column packages, single-column Step 2 booking layout |
| `> 1024px` | Desktop | 4-column packages, two-column Step 2 layout (`1fr 380px`), max-width `1400px` |
