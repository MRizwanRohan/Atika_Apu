# ওয়েবসাইট স্পিড সংশোধন

তারিখ: ১৩ অক্টোবর ২০২৬। লোকাল কোড ও build আপডেট করা হয়েছে; live hosting-এ deploy করা হয়নি। মূল পর্যবেক্ষণ: [আগের অডিট](WEBSITE_SPEED_ISSUES_2026-10-13.md)।

## পরিবর্তন

| অডিট ইস্যু | বর্তমান পরিবর্তন |
|---|---|
| একসঙ্গে ৩০টি autoplay video | Preview-এর video element কেবল viewport-এ দৃশ্যমান হলে তৈরি হয়। বাইরে গেলে, tab hidden হলে অথবা modal খুললে preview বন্ধ হয়। Save Data/reduced motion-এ poster থাকে। Modal-এর পাশের preview এখন image। |
| একই cart বারবার DB থেকে আনা | Request-এর মধ্যে একবার cart load; resource, count ও total একই object reuse করে। Empty cart-ও memoized। Repository-তে থাকা relations আবার reload হয় না। |
| অব্যবহৃত campaign payload | Homepage controller থেকে campaign fetch/prop এবং অব্যবহৃত import সরানো হয়েছে। |
| সব group/products initial response-এ | Videos, category products, product groups ও Instagram props এখন optional; section কাছে এলে Inertia partial request করে। Group প্রতি page-এ ৬টি, প্রতি group-এ সর্বোচ্চ ১০টি product; বাকি collection “View More Collections” দিয়ে পাওয়া যায়। Group data cached এবং group/product changes-এ invalidated। Card query-তে detail-only বড় columns বাদ। |
| Cold category cache-এ recursive SQL | Active category tree একবার query করে memory-তে descendants বের হয়; প্রতিটি descendant node-এর আলাদা SQL নেই। Per-root product query এখনও আছে, তবে initial response-এ চলে না এবং cached। |
| Wishlist/compare অতিরিক্ত query | Wishlist IDs সরাসরি product_id query থেকে আসে, পুরো product relations নয়। Compare list request-এর মধ্যে একবার আনা হয়। |
| Hero priority/mobile preload | Actual `Sliders.vue`-তে প্রথম image high priority/eager; পরের slide lazy। Blade head-এর preload media/srcset অনুযায়ী browser-এর `<picture>` selection-এর সঙ্গে মেলে। |
| নিচের image eager/full-size | Category, group banner ও Instagram image lazy/async। Product/category/group/Instagram/slider-এ responsive srcset ও dimensions যোগ হয়েছে। Existing ১৩৪টি image-এর metadata এবং প্রয়োজনীয় ছোট WebP sizes তৈরি; original অক্ষত। New `ImageHelper` uploads-এও variants তৈরি হয়। |
| Font import chain/অতিরিক্ত family | CSS Montserrat `@import` ও অব্যবহৃত Jost বাদ। First document-এর area অনুযায়ী একটি family আসে; Inertia navigation-এ area বদলালে font stylesheet বদলায়। |
| SSR enabled কিন্তু bundle নেই | Missing SSR-কে enabled ধরে রাখা বন্ধ হয়েছে; SSR opt-in। Homepage-এর navigation/search/first hero সরাসরি Blade HTML-এ render হয়, সঙ্গে early responsive preload। Vue শুরু হলে সেই bootstrap content replace করে। Full Vue SSR service যোগ করা হয়নি; critical first screen-এর জন্য Node SSR service প্রয়োজন হয় না। |
| Initial component/bundle খরচ | Below-fold sections, cart sidebar ও quick-view async। Quick-view instance কেবল খোলার সময় তৈরি হয়। Hidden cart আর mount-এ recommendation request করে না। সব page-এর speculative Vite prefetch বন্ধ। |
| Static cache/compression যাচাই | Apache `.htaccess`-এ text compression, hashed JS/CSS-এ এক বছরের immutable cache এবং images/fonts-এ এক দিনের cache rules যোগ। Hosting-এ modules/overrides অনুযায়ী বাস্তবে প্রয়োগ হবে। |

`images:optimize` generated derivatives Git-এ ignored; originals থেকেই deployment-এ পুনরায় তৈরি করা যায়। External/CDN image URL-এর জন্য local variants ধরে নেওয়া হয় না।

## যাচাই

- Production `npm run build` পাস।
- পরিবর্তিত PHP files syntax check এবং `git diff --check` পাস।
- নতুন ৫টি PHP test, ৪৬টি assertions পাস: cart/request isolation, optional props/partial requests, group cache invalidation, actual repository pagination/per-group limit, serialization-এ hidden query না হওয়া, responsive image dimensions/transparency/original preservation।
- Existing দুইটি JavaScript test file পাস।
- `node tests/browser/storefront-speed.mjs`: mobile 390×844 এবং desktop 1280×800-এ real built bundles + isolated fixture দিয়ে responsive hero, wrong preload না হওয়া, deferred data, visible-only video, quick-view scroll lock/close এবং group pagination পাস। Server HTML-এ usable navigation ও hero থাকার assertion-ও পাস। Live site/database/Meta-তে test request যায়নি।
- Broader Unit run-এ তখন ৩৬টি পাস, পুরনো ৩টি PreOrderLead test ব্যর্থ: SQLite-এ `ALTER TABLE ... MODIFY ... ENUM` migration সমর্থিত নয়। পরে যোগ করা repository test-সহ targeted পাঁচটি পাস। Migration পরিবর্তন করা হয়নি।

একই installed dependencies দিয়ে clean HEAD baseline এবং modified build-এর app+Home static import graph মাপা হয়েছে:

| মাপ | আগে | পরে |
|---|---:|---:|
| JS/CSS files | ২৭ | ১৮ |
| Raw bytes | ৮৯০,০৫০ | ৮৩৯,৪৩৮ |
| পৃথক file gzip estimate মোট | ২৪৩,৫৪৫ | ২২৬,৭৮০ |

Gzip estimate প্রায় **৬.৯% কম**। এটি browser request count, actual compressed transfer, Lighthouse score বা LCP নয়; baseline build-এ private `.env` copy করা হয়নি। Initial JSON/HTML payload-এর সাশ্রয় এবং video transfer reduction live data দিয়ে আলাদাভাবে মাপতে হবে।

## Deployment-এ করণীয়

Updated source deploy করার পর target server-এ:

```bash
npm ci
npm run build
php artisan images:optimize
php artisan cache:clear
php artisan config:cache
php artisan view:cache
```

Cache clear পুরনো serialized models সরাবে, যাতে নতুন responsive attributes সঙ্গে সঙ্গে আসে। `images:optimize` GD/WebP support ও source image directories-এ write access চায়; original replace করে না। Generated variants ও `.responsive.json` sidecar files source-এর পাশে রাখুন। Asset host/CDN ব্যবহার হলে variants-ও সেখানে sync করতে হবে।

Full Vue SSR service/bundle ছাড়া `INERTIA_SSR_ENABLED=true` সেট করবেন না। Default false-এ server-rendered first-screen fallback কাজ করে।

Apache hosting-এ `mod_headers`/`mod_deflate` এবং `.htaccess` override কার্যকর কিনা response headers দিয়ে যাচাই করুন। Nginx/CDN হলে সংশ্লিষ্ট configuration-এ সমতুল্য cache/compression rules দিতে হবে; `.htaccess` সেখানে কাজ করবে না।

এখনও live environment প্রয়োজন: mobile TTFB/LCP/INP/CLS, DB cold/warm timings, enabled marketing scripts, cache/session driver, CDN hit/miss ও actual Cache-Control/Content-Encoding। এগুলোর বর্তমান ফল লোকাল পরীক্ষায় নিশ্চিত করা যায় না।
