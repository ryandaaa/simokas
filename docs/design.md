# ClassiAds Design System & Documentation

## 1. Vision & Identity

**ClassiAds** is a modern, high-trust classifieds and local community marketplace platform. The design balances visual polish with high conversion efficiency, providing users with an intuitive, clutter-free environment to browse, search, and trade local goods and services.

### Core Brand Attributes
- **Trustworthy & Verified:** Clean typography, status indicators, and seller verification badges.
- **Vibrant & Engaging:** Energetic electric blue and sunlight yellow accent colors that capture attention without causing visual fatigue.
- **Symmetrical & Balanced:** Centered grid structures, consistent padding tokens, and smooth, responsive container alignment.

---

## 2. Color Palette & Surface System

### Primary & Accent Colors
| Role | Color Name | Hex Code | Tailwind Utility | Application |
| :--- | :--- | :--- | :--- | :--- |
| **Primary Brand** | Electric Blue | `#2563EB` | `bg-blue-600`, `text-blue-600` | Logos, primary CTA buttons, links, active state indicators |
| **Secondary Brand** | Deep Indigo | `#4F46E5` | `from-blue-600 to-indigo-600` | Gradient backgrounds, mobile mockups, section highlights |
| **Vibrant Accent** | Sunlight Yellow | `#FACC15` | `bg-yellow-400`, `hover:bg-yellow-500` | Search action buttons, promotional badges, highlight CTAs |
| **Success / Verified** | Emerald Green | `#10B981` | `bg-emerald-500` | "Verified" badges, status dots, app store accents |

### Neutral Palette & Surfaces
| Surface / Element | Color / Hex Code | Tailwind Utility | Purpose |
| :--- | :--- | :--- | :--- |
| **Main Page Canvas** | `#F8FAFC` (Slate 50) | `bg-slate-50` | Soft, glare-free overall background |
| **Card / Header Fill** | `#FFFFFF` (White) | `bg-white` | Elevated surface for cards, menus, and forms |
| **Primary Text** | `#0F172A` (Slate 900) | `text-slate-900` | Headlines, titles, and high-emphasis labels |
| **Muted Text** | `#64748B` (Slate 500) | `text-slate-500` | Body copy, meta information, dates, view counts |
| **Borders & Dividers** | `#E2E8F0` (Slate 200) | `border-slate-200` | Soft containment lines and card outlines |

---

## 3. Typography & Hierarchy

The interface utilizes a dual font stack provided via Google Fonts:
- **Heading & Display Font:** `Plus Jakarta Sans` (Geometric, clean, highly readable display)
- **Body & UI Font:** `Inter` (Optimized for interface text, metrics, and smaller legibility)

### Type Scale
- **Display Hero Headline:** `text-4xl` to `text-6xl` (`font-extrabold`, leading `1.15`)
- **Section Headings (H2):** `text-3xl` (`font-extrabold`, `tracking-tight`)
- **Card Titles (H3):** `text-sm` to `text-base` (`font-bold` or `font-extrabold`)
- **Body Text:** `text-xs` to `text-sm` (`font-normal` or `font-medium`, leading `relaxed`)
- **Micro Labels & Badges:** `text-[10px]` to `text-[11px]` (`font-bold` / `font-extrabold`, uppercase tracking)

---

## 4. Layout Architecture & Grid System

The platform adopts a centered **12-column responsive grid** bounded within a maximum content container (`max-w-7xl` or ~1280px).

```
+-----------------------------------------------------------------------+
|                       Sticky Header (Container)                       |
+-----------------------------------------------------------------------+
|                   Hero Section (Centered 2-Column)                    |
|       Left: Search & Copy (6 col)    |    Right: 3D Graphic (6 col)  |
+-----------------------------------------------------------------------+
|                    Horizontal Scroll Category Nav                     |
+-----------------------------------------------------------------------+
|        Sidebar Nav (3 col)    |      Main Ad Cards Grid (9 col)       |
+-----------------------------------------------------------------------+
|                        How It Works (3-Column)                        |
+-----------------------------------------------------------------------+
|                     Popular Cities (4-Column Grid)                    |
+-----------------------------------------------------------------------+
|                 Top Verified Sellers (4-Column Grid)                  |
+-----------------------------------------------------------------------+
|                 Marketplace Guides (3-Column Grid)                    |
+-----------------------------------------------------------------------+
|                     FAQ Accordion (Centered Max-3xl)                  |
+-----------------------------------------------------------------------+
|                   App Download & Newsletter Banner                    |
+-----------------------------------------------------------------------+
|                                Footer                                 |
+-----------------------------------------------------------------------+
```

### Spacing Principles
- **Section Padding:** Vertical padding is standardized to `py-12` or `py-16` across major content blocks to maintain vertical rhythmic pacing.
- **Card Spacing:** Grid gaps are fixed at `gap-6` (24px) for product cards and `gap-8` (32px) for larger featured cards and blog columns.
- **Symmetry & Centering:** Section titles, section headers, and call-to-action blocks are explicitly centered with `text-center mx-auto` to provide balanced eye flow.

---

## 5. Component Anatomy & Design Guidelines

### 1. Cards (Product Ads, Sellers, Articles)
- **Container:** Rounded corners (`rounded-2xl`), subtle border (`border border-slate-200/80`), white background (`bg-white`), and micro-shadow (`shadow-xs` hovering to `shadow-xl`).
- **Media Aspect Ratios:** 
  - Product Listings: `aspect-4/3`
  - City Showcases: `aspect-4/3`
  - Blog / News Articles: `aspect-16/10`
- **Badges:** Absolute-positioned top-left pills (`rounded-full px-2.5 py-1 text-[10px] font-bold`) for "Verified", "Featured", or "New".
- **Action Triggers:** Top-right circular bookmark heart toggle (`w-8 h-8 rounded-full bg-white/80 backdrop-blur-md`).

### 2. Form Inputs & Interactive Controls
- **Search Bar:** Pill-shaped compound input with integrated icon prefix and energetic yellow primary submission button.
- **Dropdown Filters:** Clean white selects with soft slate borders and explicit hover focus rings (`focus:ring-2 focus:ring-blue-500/20`).
- **Interactive Accordions (FAQ):** Expanding/collapsible card containers with dynamic icon rotation (`fa-plus` to `fa-minus rotate-180`).

### 3. Floating Phone Showcase Animation
- Keyframe animation `floating-phone` gently shifts the graphic vertically by `-12px` over a `6s` infinite cubic-bezier cycle.
- Frame features a 3D dark slate smartphone body with speaker notch cutout, interactive top offer banner, quick category badges, and mini ad cards.

---

## 6. Micro-Interactions & Interactivity Map

- **Category Filter:** Clicking category icons triggers client-side JS (`filterCategory('name')`), displaying matching `.card-item` elements while hiding non-matching entries.
- **Real-Time Search:** Typing in the hero search box filters cards instantly based on product title matching.
- **Price Sorting:** Dropping down the sorting menu re-orders card grid DOM nodes instantly (`price-low` or `price-high`).
- **Favorite Bookmark Toggle:** Clicking the heart icon on any card toggles solid red/empty heart states without page reloads.
- **FAQ Accordion Toggle:** Smoothly reveals or hides extended text content with animated indicator rotation.