# Frontend Speed & Core Web Vitals Audit

**Current Performance Score:** 51/100
**First Contentful Paint (FCP):** 19.8s (Critical)
**Largest Contentful Paint (LCP):** 32.8s (Critical)

---

## 🔍 Identified Problems

### 1. Server Response Time (TTFB)
*   **Bottleneck:** The initial HTML takes too long to generate. 
*   **Cause:** The `HandleInertiaRequests` middleware fetches Categories, Settings, and Pages from the database on **every single page load** without caching.
*   **Impact:** Directly increases FCP and LCP by delaying the first byte.

### 2. Rendering Blocking & Bundle Bloat
*   **Bottleneck:** Large libraries like `ApexCharts` and `GSAP` are currently being handled in a way that blocks initial rendering.
*   **Cause:** `ApexCharts` is registered globally in `app.js`, even if not used on the homepage.
*   **Impact:** Increases "Total Blocking Time" and delays FCP.

### 3. Image Delivery & LCP Optimization
*   **Bottleneck:** Hero images (Sliders) are treated as regular images by the browser.
*   **Cause:** Missing `fetchpriority="high"` and `loading="eager"` for the first slider image.
*   **Impact:** The browser discovers the most important image (LCP) too late in the network waterfall.

### 4. Metadata Fetching Redundancy
*   **Bottleneck:** The frontend performs a separate `fetch('/api/meta/pixel-config')` on every reload.
*   **Cause:** This should be part of the initial shared props to avoid an extra network roundtrip.

---

## 🛠️ Required Improvements (Step-by-Step)

### Phase 1: Server-Side Speed (TTFB)
1.  **Cache Shared Data:** Implement caching for `getAllCategoriesForApi` and `getAllPages` in their respective services.
2.  **Optimize Permissions:** Cache the user role and permissions retrieval to avoid repeated DB queries in `HandleInertiaRequests`.

### Phase 2: Frontend Bundle Optimization
1.  **Code Splitting:** Move `VueApexCharts` from global registration in `app.js` to component-level imports.
2.  **Async Components:** Use `defineAsyncComponent` for heavy sections like `InstagramFeed` or `ProductsGroup` that are below the fold.

### Phase 3: Vital Assets Optimization
1.  **High Priority Loading:** Add `fetchpriority="high"` to the first image of the `GsapSlider`.
2.  **Preconnect:** Optimize fonts and external domains in `app.blade.php`.

### Phase 4: Modern Delivery
1.  **WebP Conversion:** Ensure all images (Sliders, Products) are served in `.webp` or `.avif` format.
2.  **Static Asset Caching:** Configure `.htaccess` or Nginx to set `Cache-Control` headers for 1 year for static JS/CSS.

---

## 📊 Expected Results
*   **Score Improvement:** 51 → 85+
*   **FCP Reduction:** 19.8s → < 1.5s
*   **LCP Reduction:** 32.8s → < 2.5s
