# ymaps3-resizer package

---

Yandex JS API package

[![npm version](https://badge.fury.io/js/@yandex%2Fymaps3-resizer.svg)](https://badge.fury.io/js/@yandex%2Fymaps3-resizer)
[![npm](https://img.shields.io/npm/dm/%40yandex%2Fymaps3-resizer)](https://www.npmjs.com/package/%40yandex%2Fymaps3-resizer)

## How use

The package is located in the `dist` folder:

- `dist/types` TypeScript types
- `dist/esm` es6 modules for direct connection in your project
- `dist/index.js` Yandex JS Module

Recommended use `ymaps3-resizer` as usual npm package:

```sh
npm install @yandex/ymaps3-resizer
```

You also need to import css styles into your project:

```css
/* index.css */
@import '@yandex/ymaps3-resizer/dist/esm/index.css';
```

and dynamic import

```js
main();
async function main() {
  await ymaps3.ready;
  const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer} = ymaps3;

  const {YMapResizer} = await import('@yandex/ymaps3-resizer');

  map = new YMap(document.getElementById('app'), {location: LOCATION});

  map.addChild(new YMapDefaultSchemeLayer({}));
  map.addChild(new YMapDefaultFeaturesLayer({}));

  map.addChild(new YMapResizer({minWidth: 400, maxWidth: 1000, minHeight: 400, maxHeight: 600}));
}
```

### YMapResizerProps

| Property          | Type                                   | Default  | Description                          |
| :---------------- | :------------------------------------- | :------- | :----------------------------------- |
| `direction`       | `'both' \| 'horizontal' \| 'vertical'` | `'both'` | Resize direction.                    |
| `minWidth`        | `number`                               | -        | Resize minimum width limit in 'px'.  |
| `maxWidth`        | `number`                               | -        | Resize maximum width limit in 'px'.  |
| `minHeight`       | `number`                               | -        | Resize minimum height limit in 'px'. |
| `maxHeight`       | `number`                               | -        | Resize maximum height limit in 'px'. |
| `showOnlyOnHover` | `boolean`                              | false    | Shows resize controls only on hover. |

### Usage without npm

You can use CDN with module loading handler in JS API on your page.

By default `ymaps3.import` can load self modules.
If you want also load your package, should register cdn:

```js
ymaps3.import.registerCdn('https://cdn.jsdelivr.net/npm/{package}', '@yandex/ymaps3-resizer@latest');
```

Just use `ymaps3.import`:

```js
const {YMapResizer} = await ymaps3.import('@yandex/ymaps3-resizer');
```
