# ymaps3-drawer-control package

---

Yandex JS API package

[![npm version](https://badge.fury.io/js/@yandex%2Fymaps3-drawer-control.svg)](https://badge.fury.io/js/@yandex%2Fymaps3-drawer-control)
[![npm](https://img.shields.io/npm/dm/%40yandex%2Fymaps3-drawer-control)](https://www.npmjs.com/package/%40yandex%2Fymaps3-drawer-control)

## How use

The package is located in the `dist` folder:

- `dist/types` TypeScript types
- `dist/esm` es6 modules for direct connection in your project
- `dist/index.js` Yandex JS Module

Recommended use `ymaps3-drawer-control` as usual npm package:

```sh
npm install @yandex/ymaps3-drawer-control
```

You also need to import css styles into your project:

```css
/* index.css */
@import '@yandex/ymaps3-drawer-control/dist/esm/index.css';
```

and dynamic import

```ts
main();
async function main() {
  await ymaps3.ready;

  const {YMapDrawerControl} = await import('@yandex/ymaps3-drawer-control');

  map = new ymaps3.YMap(document.getElementById('app'), {location: LOCATION}, [
    /* any map child */
  ]);

  let open = false;
  const drawerControl = new YMapDrawerControl({
    position: 'left',
    width: 300,
    open,
    onOpenChange: (newOpenValue) => {
      open = newOpenValue;
      drawerControl.update({open: newOpenValue});
    },
    content: () => {
      const container = document.createElement('div');
      const title = document.createElement('h1');
      title.textContent = 'Hello world';
      container.appendChild(title);
      const closeButton = document.createElement('button');
      closeButton.textContent = 'Close';
      closeButton.addEventListener('click', () => {
        open = false;
        drawerControl.update({open: false});
      });
      container.appendChild(closeButton);
      return container;
    }
  });
  map.addChild(drawerControl);
}
```

```tsx
main();
async function main() {
  const [ymaps3React] = await Promise.all([ymaps3.import('@yandex/ymaps3-reactify'), ymaps3.ready]);
  const reactify = ymaps3React.reactify.bindTo(React, ReactDOM);

  const {useState, useCallback} = React;
  const {YMap} = reactify.module(ymaps3);
  const {YMapDrawerControl} = reactify.module(await import('@yandex/ymaps3-drawer-control'));

  const App = () => {
    const [open, setOpen] = useState(false);
    const content = useCallback(
      () => (
        <div>
          <h1>Hello world!</h1>
          <button onClick={() => setOpen(false)}>Close</button>
        </div>
      ),
      []
    );
    const onOpenChange = (value: boolean) => {
      setOpen(!value);
    };

    return (
      <YMap location={LOCATION}>
        {/* any map child */}
        <YMapDrawerControl position="left" width={300} content={content} open={open} onOpenChange={onOpenChange} />
      </YMap>
    );
  };
}
```

### Usage without npm

You can use CDN with module loading handler in JS API on your page.

By default `ymaps3.import` can load self modules.
If you want also load your package, should register cdn:

```js
ymaps3.import.registerCdn('https://cdn.jsdelivr.net/npm/{package}', '@yandex/ymaps3-drawer-control@latest');
```

Just use `ymaps3.import`:

```js
const {YMapDrawerControl} = await ymaps3.import('@yandex/ymaps3-drawer-control');
```

### YMapDrawerControl props description

| Name                      | Type                                      | Default    | Description                                                                               |
| :------------------------ | :---------------------------------------- | :--------- | :---------------------------------------------------------------------------------------- |
| position                  | `"left"`, `"right"`, `"top"`, `"bottom"`  | `"left"`   | Specifies which side of the map the drawer opens from.                                    |
| transitionDuration        | `number`, `{open: number; close: number}` | 500        | Duration of the open/close animation in milliseconds.                                     |
| open                      | `boolean`                                 |            | Flag indicating whether the drawer is open.                                               |
| width                     | `string \| number`                        |            | Fixed width of the drawer. If not specified, the drawer adjusts to the content's width.   |
| maxWidth                  | `string \| number`                        |            | Maximum width of the drawer.                                                              |
| minWidth                  | `string \| number`                        |            | Minimum width of the drawer.                                                              |
| height                    | `string \| number`                        |            | Fixed height of the drawer. If not specified, the drawer adjusts to the content's height. |
| maxHeight                 | `string \| number`                        |            | Maximum height of the drawer.                                                             |
| minHeight                 | `string \| number`                        |            | Minimum height of the drawer.                                                             |
| onOpenChange              | `(open: boolean) => void`                 |            | Drawer open status change handler.                                                        |
| verticalTriggerPosition   | `number`                                  | `"center"` | Vertical position of the button that opens the drawer.                                    |
| horizontalTriggerPosition | `number`                                  | `"center"` | Horizontal position of the button that opens the drawer.                                  |
| content                   | `() => HTMLElement \| YMapEntity`         |            | Function that returns the drawer's content as an HTMLElement or an YMapEntity.            |
