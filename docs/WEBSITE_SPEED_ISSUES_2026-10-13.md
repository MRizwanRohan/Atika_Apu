# ওয়েবসাইট স্পিড: বর্তমান ইস্যু নোট

পরবর্তী আপডেট: কোডের সংশোধন ও পরীক্ষার ফল [স্পিড সংশোধন রিপোর্টে](WEBSITE_SPEED_FIXES_2026-10-13.md) আছে। নিচের লেখা পরিবর্তনের আগের অডিট।

তারিখ: ১৩ অক্টোবর ২০২৬। শুধু লোকাল সোর্স, কনফিগ ও বিদ্যমান build পরীক্ষা করা হয়েছে। কোনো application code/config পরিবর্তন করা হয়নি। লাইভ URL, browser waterfall, Lighthouse বা production database পরীক্ষা করা হয়নি; তাই বর্তমান speed score, TTFB/LCP বা নিশ্চিত সময় সাশ্রয় দাবি করা হচ্ছে না। অগ্রাধিকারগুলো কোডে দেখা কাজের পরিমাণ অনুযায়ী, live profiling অনুযায়ী নয়।

## আগে দেখার ইস্যু

### 1. হোমপেজে একসঙ্গে সর্বোচ্চ ৩০টি ভিডিও autoplay — উচ্চ

- প্রমাণ: `app/Http/Controllers/Frontend/Home/HomeController.php` → `productVideos()` সর্বোচ্চ ৩০টি ভিডিও পাঠায়। `resources/js/Components/Frontend/Product/ProductVideoReel.vue:133` সবগুলোর `<video src>` একসঙ্গে তৈরি করে, সঙ্গে `autoplay`, `loop`; viewport অনুযায়ী চালু/বন্ধ করার ব্যবস্থা নেই।
- প্রভাব: দৃশ্যের বাইরে থাকা ভিডিওও network ও decoding resources নিতে পারে; মোবাইলে hero image এবং interaction-এর সঙ্গে প্রতিযোগিতা করে। প্রকৃত download browser waterfall দিয়ে মাপতে হবে।
- করণীয়: প্রথমে poster দেখানো, visible ভিডিওতে source/play চালু করা, বাইরে গেলে pause করা; প্রাথমিক ভিডিও সংখ্যা কমানো। শুধু `preload="none"` দিলেই autoplay-এর খরচ বন্ধ হবে না।

### 2. একই কার্ট বারবার database থেকে লোড — উচ্চ

- প্রমাণ: `app/Http/Middleware/HandleInertiaRequests.php:94` → `cart`, `cartCount`, `cartTotal` প্রতিটিতে `getCart()` condition ও result-এর জন্য আলাদাভাবে ডাকা হয়। কার্ট থাকলে তিনটি prop resolve করতে মোট ছয়বার; না থাকলে তিনবার। `app/Services/Cart/CartService.php:151` প্রতি call-এ repository query ও সম্পর্ক load করে।
- প্রভাব: full Inertia response-এ একই ডেটার জন্য পুনরাবৃত্ত query; TTFB বাড়ার ঝুঁকি। Closure ব্যবহার মানেই initial full response থেকে ডেটা বাদ যাওয়া নয়।
- করণীয়: request-এর মধ্যে একবার cart load করে resource/count/total reuse করা। Query count দিয়ে নিশ্চিত করা।

### 3. Homepage payload-এ অব্যবহৃত campaign data — উচ্চ

- প্রমাণ: `HomeController.php:33` active campaigns ও তাদের product relations fetch করে পাঠায়। `resources/js/Pages/Frontend/Home/Home.vue`-তে offers section commented out; activeCampaigns-এর দৃশ্যমান ব্যবহার নেই। `ProductService::getActiveCampaigns()` campaign products সীমা ছাড়া আনে।
- প্রভাব: cache hit হলেও serialization, HTML/JSON size, transfer এবং client parsing-এর অপ্রয়োজনীয় খরচ থাকে।
- করণীয়: homepage-এর প্রয়োজন অনুযায়ী prop বাদ দেওয়া বা ছোট summary পাঠানো; দরকার হলে পরে fetch করা।

### 4. Homepage product groups uncached; group count সীমাহীন — উচ্চ

- প্রমাণ: `app/Repository/ProductGroup/ProductGroupRepository.php:78` → `getAllWithProducts()` সব active group আনে এবং nested product/variation/category/campaign/brand load করে। Product query-তে `take(10)` আছে, কিন্তু মোট group limit নেই। Service-এ cache নেই। HomeController সব data synchronously নেয়।
- প্রভাব: group বাড়লে DB, response size এবং initial rendering বাড়বে। Category products-ও প্রতিটি root-এর জন্য সর্বোচ্চ আটটি করে একই initial response-এ যায়।
- করণীয়: homepage group limit, প্রয়োজনীয় field নির্বাচন, cache ও invalidation; নিচের section-এর data/render প্রয়োজনমতো পরে আনা।

### 5. Category cache miss-এ recursive query — মাঝারি

- প্রমাণ: `app/Services/Product/ProductService.php:134` প্রতিটি root category-তে descendant traversal ও product relation queries করে। `getAllDescendantCategoryIds():532` প্রতি category node-এ children query করে। Result ১৮০০ সেকেন্ড cache করা আছে।
- প্রভাব: cache expiry/invalidation-এর পর বড় category tree-তে query count বেড়ে response ধীর হতে পারে; warm cache-এ একই খরচ হয় না।
- করণীয়: tree একবার fetch করে memory-তে traversal, category/product query batching; cold/warm query count আলাদাভাবে মাপা।

### 6. Wishlist IDs-এর জন্য পুরো product relations লোড — মাঝারি

- প্রমাণ: `HandleInertiaRequests.php:110` wishlistIds তৈরি করতে `getWishlist()->pluck('id')` চালায়। `app/Services/Wishlist/WishlistService.php:71` products, category ancestors ও variation attributes load করে। Compare count/IDs-ও `CompareService.php:105,121`-এ একই list দুবার fetch করে।
- প্রভাব: navigation-এ IDs/count দেখাতে প্রয়োজনের চেয়ে বেশি DB কাজ।
- করণীয়: IDs-only query; একই request-এ compare result reuse।

## Frontend delivery ও rendering

### 7. ব্যবহৃত slider-এর priority ও mobile preload mismatch — উচ্চ

- প্রমাণ: Home template আসলে `Sliders.vue` render করে, `GsapSlider.vue` নয়। `resources/js/Components/Frontend/Slider/Sliders.vue`-তে প্রথম image-এর `fetchpriority` নেই এবং পরের slide-এ lazy loading নেই। Home head সব screen-এ `sliders[0].image` preload করে; slider-এর `<picture>` mobile_image থাকলে সেটি বেছে নেয়।
- প্রভাব: মোবাইলে desktop preload ও mobile image দুটোই download হতে পারে; পরের slide প্রথম image-এর bandwidth নিতে পারে। CSR হলে Vue boot হওয়ার পর preload discover হবে।
- করণীয়: actual slider-এ প্রথম image high priority, অন্য slide lazy; media-aware responsive preload। Image-এর নির্দিষ্ট dimensions/aspect ratio-ও যাচাই করা।

### 8. নিচের section-এর ছবিতে lazy loading নেই — মাঝারি

- প্রমাণ: `Home/ProductsGroup.vue:30`, `Home/InstagramFeed.vue:37`, `Home/FeaturedCategories.vue:92`-এর image tags-এ lazy loading নেই। ProductCard image-এ ইতিমধ্যে `loading="lazy"` আছে। ProductCard image-এ responsive `srcset` নেই।
- প্রভাব: নিচের ছবিগুলো initial network contention তৈরি করতে পারে; ছোট card-এ বড় source image আসার সম্ভাবনা আছে। প্রকৃত image size/format যাচাই প্রয়োজন।
- করণীয়: viewport অনুযায়ী loading, resized image variants ও responsive srcset; প্রয়োজন অনুযায়ী WebP/AVIF।

### 9. একাধিক font family ও CSS import chain — মাঝারি

- প্রমাণ: `resources/views/app.blade.php:15` Heebo; `resources/css/app.css:1` Montserrat Google Fonts `@import`; `FrontendLayout.vue` head-এ Jost। Frontend CSS মূলত Heebo ব্যবহার করে।
- প্রভাব: অতিরিক্ত font stylesheet/request; CSS `@import` discover করতে মূল CSS আগে আসতে হয়। Font swap layout shift-ও মাপতে হবে।
- করণীয়: ব্যবহার হওয়া family/style/weight-এ সীমাবদ্ধ রাখা; stylesheet delivery সরল করা।

### 10. SSR enabled কিন্তু bundle/entry পাওয়া যায়নি — উচ্চ, deployment যাচাই প্রয়োজন

- প্রমাণ: `config/inertia.php` SSR enabled এবং bundle path `resources/js/ssr.js`; ফাইলটি নেই। প্রচলিত bootstrap/public SSR bundle-ও লোকাল checkout-এ নেই। Vite config ও package scripts-এ SSR build নেই। Installed Inertia gateway bundle presence check করে।
- প্রভাব: এই checkout-এ SSR কার্যকর হওয়ার প্রমাণ নেই; browser JS boot হওয়ার আগে homepage content/hero discover না হওয়ার ঝুঁকি। Production-এ আলাদা bundle থাকতে পারে। Missing bundle থেকেই SSR timeout হচ্ছে বলা যাবে না।
- করণীয়: production HTML ও SSR service/bundle পরীক্ষা; তারপর SSR ঠিক করা বা critical content/image server HTML-এ দেওয়ার সিদ্ধান্ত।

### 11. Initial assets ও quick-view rendering খরচ — মাঝারি

- প্রমাণ: বিদ্যমান `public/build/manifest.json`-এর app+Home static dependency graph-এ ২৭টি JS/CSS file, মোট ৮৯০,৩০৪ bytes (~৮৬৯ KiB); পৃথক file gzip estimate মোট ২৪৩,৬০২ bytes (~২৩৮ KiB)। মূল app JS ~৩৩৬ KiB ও CSS ~২৭৬ KiB (disk size)। এগুলো পুরনো build হতে পারে; বর্তমান source-এর fresh measurement নয়।
- `ProductCard.vue:308` প্রতিটি card-এ ProductQuickView component render করে, show=false হলেও component instance তৈরি হয়; import synchronous। Home-এর below-fold sections-ও synchronous imports।
- প্রভাব: মোবাইলে parsing, component creation এবং style processing-এর বাড়তি খরচ। সংখ্যাগুলো browser-এর request count নয়।
- করণীয়: coverage/bundle analysis করে CSS ও critical imports ছোট করা; interaction-এর সময় quick-view component/data আনা। ApexCharts/CKEditor শুধু বড় বলে homepage-এ লোড হচ্ছে বলা যাবে না—এই static graph-এ সেগুলো নেই।

## Live environment-এ আরও যাচাই দরকার

- `public/.htaccess`-এ compression বা static cache rules নেই; hosting/CDN অন্য জায়গায় এগুলো দিতে পারে। JS/CSS/image response-এর Cache-Control, Brotli/gzip, CDN hit/miss পরীক্ষা করা দরকার।
- FrontendLayout mount-এ configured marketing scripts inject করে। Meta Pixel enabled হলে app startup-এ শুরু হয়। আসল enabled scripts ও তাদের main-thread/network খরচ waterfall দিয়ে মাপতে হবে।
- Mobile homepage/product/category/checkout-এ TTFB, LCP, INP, CLS এবং cold/warm cache মাপা; query count, response bytes, DB indexes ও production cache/session driver পরীক্ষা করা। লোকাল `.env` দিয়ে production setting নিশ্চিত করা যায় না।

## পুরনো নোটের সংশোধন

`frontend_speed_audit.md`-এর 51 score, 19.8s FCP, 32.8s LCP নতুন করে যাচাই হয়নি; বর্তমান ফল হিসেবে ব্যবহার করা যাবে না। Categories, settings/pages বর্তমানে cached; ApexCharts app.js-এ globally registered নয়; Meta Pixel config initial props-এ আসে। GsapSlider priority fix আছে, কিন্তু homepage ব্যবহৃত Sliders-এ একই fix নেই। FrontendLayout-এর 800ms isLoading timer template-এ loader render করে না, তাই এটিকে দৃশ্যমান delay issue হিসেবে ধরা হয়নি।

প্রস্তাবিত প্রথম ক্রম: autoplay videos → repeated cart queries → unused campaign payload / product groups → actual hero loading → SSR যাচাই → images/fonts/bundle। প্রত্যেক পরিবর্তনের আগে baseline ও পরে একই পরিবেশে measurement প্রয়োজন।
