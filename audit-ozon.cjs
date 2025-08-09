const fs = require('fs');
const { chromium } = require('playwright');

async function run(url) {
  const browser = await chromium.launch({
    headless: true,
    args: [
      '--no-sandbox',
      '--disable-blink-features=AutomationControlled',
      '--disable-dev-shm-usage'
    ]
  });
  const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    locale: 'ru-RU',
    viewport: { width: 1440, height: 900 },
    timezoneId: 'Europe/Moscow'
  });
  await context.setExtraHTTPHeaders({
    'Accept-Language': 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    'Upgrade-Insecure-Requests': '1',
    'Sec-Ch-Ua-Platform': '"Windows"',
    'Sec-Ch-Ua': '"Chromium";v="124", "Not.A/Brand";v="24", "Google Chrome";v="124"',
    'Sec-Ch-Ua-Mobile': '?0'
  });
  await context.addInitScript(() => {
    // Webdriver flag off
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    // Languages
    Object.defineProperty(navigator, 'languages', { get: () => ['ru-RU', 'ru', 'en-US', 'en'] });
    // Platform
    Object.defineProperty(navigator, 'platform', { get: () => 'Win32' });
    // Plugins length mock
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3] });
    // Chrome object
    window.chrome = { runtime: {} };
    // WebGL vendor/renderer spoof
    const getParameter = WebGLRenderingContext.prototype.getParameter;
    WebGLRenderingContext.prototype.getParameter = function(parameter) {
      if (parameter === 37445) return 'Intel Inc.'; // UNMASKED_VENDOR_WEBGL
      if (parameter === 37446) return 'Intel Iris OpenGL Engine'; // UNMASKED_RENDERER_WEBGL
      return getParameter.apply(this, [parameter]);
    };
  });
  const page = await context.newPage();
  const requests = [];
  page.on('requestfinished', req => {
    const type = req.resourceType();
    if (['xhr', 'fetch', 'document', 'script'].includes(type)) {
      requests.push({ url: req.url(), method: req.method(), type });
    }
  });

  try {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });
    // Human-like interactions to trigger hydration
    await page.waitForTimeout(2000);
    await page.mouse.move(200 + Math.random()*200, 300 + Math.random()*200);
    await page.evaluate(() => window.scrollBy(0, 400));
    await page.waitForTimeout(1500);
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.6));
    await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
  } catch (e) {
    // continue to attempt extraction even if networkidle not reached
  }

  // Try to dismiss common overlays (cookies/region)
  try {
    const buttons = await page.$$('button');
    for (const btn of buttons) {
      const txt = (await btn.innerText()).toLowerCase();
      if (txt.includes('принять') || txt.includes('согласен') || txt.includes('ок') || txt.includes('хорошо') || txt.includes('continue') || txt.includes('agree')) {
        await btn.click({ timeout: 1000 }).catch(() => {});
      }
    }
  } catch {}

  // Save small HTML snippet for debug
  try {
    const htmlSnippet = await page.evaluate(() => document.documentElement.outerHTML.slice(0, 50000));
    fs.writeFileSync('/workspace/ozon_snippet.html', htmlSnippet);
  } catch {}

  const data = await page.evaluate(() => {
    const getComputed = (el) => {
      const s = window.getComputedStyle(el);
      return {
        display: s.display,
        position: s.position,
        flexDirection: s.flexDirection,
        justifyContent: s.justifyContent,
        alignItems: s.alignItems,
        gridTemplateColumns: s.gridTemplateColumns,
        gridTemplateRows: s.gridTemplateRows,
        gridAutoFlow: s.gridAutoFlow,
        width: s.width,
        height: s.height
      };
    };

    const topChildren = Array.from(document.body.children).slice(0, 20).map(el => ({
      tag: el.tagName.toLowerCase(),
      id: el.id || null,
      class: (el.className && typeof el.className === 'string') ? el.className : null,
      styles: getComputed(el)
    }));

    const cssLinks = Array.from(document.querySelectorAll('link[rel="stylesheet"], link[as="style"]'))
      .map(l => l.href).filter(Boolean);
    const scripts = Array.from(document.scripts).map(s => s.src).filter(Boolean);

    const markers = {
      reactRoot: !!document.querySelector('[data-reactroot], [data-reactid]'),
      nextDataTag: !!document.querySelector('#__NEXT_DATA__'),
      vueRoot: !!document.querySelector('[data-v-app], [data-vue-meta]'),
      nuxt: !!(window.__NUXT__),
      redux: !!(window.__REDUX_DEVTOOLS_EXTENSION__ || window.__REDUX_STORE__ || window.__INITIAL_STATE__),
      nextDataGlobal: !!(window.__NEXT_DATA__),
      appInitial: !!(window.__APP_INITIAL_STATE__ || window.__INITIAL_STATE__),
      webpackChunk: !!(window.webpackChunk || window.webpackJsonp),
      appRootId: !!(document.getElementById('root') || document.getElementById('app') || document.getElementById('__next')),
      title: document.title,
      doctype: document.doctype ? document.doctype.name : null
    };

    // Build a shallow DOM outline for the first major container
    const outline = [];
    const container = document.querySelector('main, #root, #__next, body');
    if (container) {
      const level1 = Array.from(container.children).slice(0, 15);
      for (const l1 of level1) {
        const entry = { tag: l1.tagName.toLowerCase(), id: l1.id || null, class: (l1.className || '').toString().slice(0, 200) };
        const l2 = Array.from(l1.children).slice(0, 10).map(ch => ({ tag: ch.tagName.toLowerCase(), class: (ch.className || '').toString().slice(0, 120) }));
        entry.children = l2;
        outline.push(entry);
      }
    }

    return { markers, cssLinks, scripts, topChildren, outline, url: location.href, htmlLang: document.documentElement.lang || null };
  });

  const endpoints = Array.from(new Set(
    requests
      .filter(r => ['xhr', 'fetch'].includes(r.type))
      .map(r => r.url)
  ));

  const result = {
    visitedUrl: data.url,
    markers: data.markers,
    htmlLang: data.htmlLang,
    cssLinks: data.cssLinks.slice(0, 100),
    scriptLinks: data.scripts.slice(0, 100),
    layoutTopChildren: data.topChildren,
    domOutline: data.outline,
    apiEndpointsSample: endpoints.slice(0, 200)
  };

  fs.writeFileSync('/workspace/ozon_audit.json', JSON.stringify(result, null, 2));
  try { await page.screenshot({ path: '/workspace/ozon_screenshot.png', fullPage: true }); } catch {}
  await browser.close();
  return result;
}

if (require.main === module) {
  const url = process.argv[2] || 'https://www.ozon.ru/';
  run(url).then(r => {
    console.log('Saved to /workspace/ozon_audit.json');
    console.log(JSON.stringify({ markers: r.markers, endpoints: r.apiEndpointsSample.slice(0, 10) }, null, 2));
  }).catch(err => {
    console.error(err);
    process.exit(1);
  });
}

module.exports = { run };