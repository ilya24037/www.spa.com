# ymaps3-signpost package

---

Yandex JS API package

[![npm version](https://badge.fury.io/js/@yandex%2Fymaps3-signpost.svg)](https://badge.fury.io/js/ymaps3-signpost)
[![npm](https://img.shields.io/npm/dm/%40yandex%2Fymaps3-signpost)](https://www.npmjs.com/package/ymaps3-signpost)

## How use

The package is located in the `dist` folder:

- `dist/types` TypeScript types
- `dist/esm` es6 modules for direct connection in your project
- `dist/index.js` Yandex JS Module

Recommended use `ymaps3-signpost` as usual npm package:

```sh
npm install @yandex/ymaps3-signpost
```

You also need to import css styles into your project:

```css
/* index.css */
@import '@yandex/ymaps3-signpost/dist/esm/index.css';
```

and dynamic import

```js
main();
async function main() {
  await ymaps3.ready;
  const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer} = ymaps3;
  const {YMapDefaultMarker} = await import('@yandex/ymaps3-default-ui-theme');

  const {YMapSignpost} = await import('@yandex/ymaps3-signpost');

  map = new YMap(document.getElementById('app'), {location: LOCATION}, [
    new YMapDefaultSchemeLayer({}),
    new YMapDefaultFeaturesLayer({})
  ]);

  map.addChild(new YMapSignpost({points: MARKERS_COORDINATES}));
  MARKERS_COORDINATES.forEach((point) => {
    map.addChild(new YMapDefaultMarker({coordinates: point, iconName: 'fallback', size: 'normal'}));
  });
}
```

### Usage without npm

You can use CDN with module loading handler in JS API on your page.

By default `ymaps3.import` can load self modules.
If you want also load your package, should register cdn:

```js
ymaps3.import.registerCdn('https://cdn.jsdelivr.net/npm/{package}', '@yandex/ymaps3-signpost@latest');
```

Just use `ymaps3.import`:

```js
const {YMapSignpost} = await ymaps3.import('@yandex/ymaps3-signpost');
```
