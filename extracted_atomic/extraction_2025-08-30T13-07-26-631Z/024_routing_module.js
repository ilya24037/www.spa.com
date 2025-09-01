/**
 * ROUTING MODULE
 * Framework: react
 * Matches: 24
 * Priority: 24
 */

// ========== MATCH 1 ==========
// Pattern: Router
// Context:
ray(e)?e[0]:e)(this.state.value);var e},r}(i().Component);return l.contextTypes=((var3={})[a]=u().object,o),{Provider:c,Consumer:l}},var9=function (e) {var var1=b();return t.displayName=e,t},var10=h("Router-History"),var11=h("Router"),var12=function (e) {function t(t) {var r;return(var4=e.call(this,t)||this).state={location:t.history.location},r._isMounted=!1,r._pendingLocation=null,t.staticContext||(r.

// ----------------------------------------

// ========== MATCH 2 ==========
// Pattern: Router
// Context:
value);var e},r}(i().Component);return l.contextTypes=((var3={})[a]=u().object,o),{Provider:c,Consumer:l}},var9=function (e) {var var1=b();return t.displayName=e,t},var10=h("Router-History"),var11=h("Router"),var12=function (e) {function t(t) {var r;return(var4=e.call(this,t)||this).state={location:t.history.location},r._isMounted=!1,r._pendingLocation=null,t.staticContext||(r.unlisten=t.history.listen(

// ----------------------------------------

// ========== MATCH 3 ==========
// Pattern: router
// Context:
t["default"]=function (e) {var var1=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},var4=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{};if(O) {if(!e)throw new Error("[redux-first-router] invalid `history` agument. Using the 'history' package on NPM,\n        please provide a `history` object as a second parameter. The object will be the\n        return of createBrowserHistory() (or 

// ----------------------------------------

// ========== MATCH 4 ==========
// Pattern: router
// Context:
m/faceyspacey/redux-first-router-restore-scroll\n        please call `restoreScroll` and assign it the option key\n        of the same

// ----------------------------------------

// ========== MATCH 5 ==========
// Pattern: router
// Context:
w new Error("[redux-first-router] invalid `restoreScroll` option. Using\n        https://github.com/faceyspacey/redux-first-router-restore-scroll\n        please call `restoreScroll` and assign it the option key\n        of the same name.")}var var12=(0,d.default)(e.location),A={pathname:"",type:"",payload:{}},var16=r.notFoundPa

// ----------------------------------------

// ========== MATCH 6 ==========
// Pattern: router
// Context:
),J=Y,X={},Q=1,Z=(0,v.default)(Y,t),ee=(0,p.default)(t,H),te=(0,l.getDocument)(),re=void 0,ne=void 0,oe=void 0,ie=void 0;if(r.navigators) {if(O&&!r.navigators.navigators)throw new Error("[redux-first-router] invalid `navigators` option. Pass your map\n        of navigators to the default import from 'redux-first-router-navigation'.\n        Don't forget: the keys are your redux state keys.");
re=r.navig

// ----------------------------------------

// ========== MATCH 7 ==========
// Pattern: router
// Context:
void 0;if(r.navigators) {if(O&&!r.navigators.navigators)throw new Error("[redux-first-router] invalid `navigators` option. Pass your map\n        of navigators to the default import from 'redux-first-router-navigation'.\n        Don't forget: the keys are your redux state keys.");
re=r.navigators.navigators,ne=r.navigators.patchNavigators,oe=r.navigators.actionToNavigation,ie=r.navigators.navigationToAc

// ----------------------------------------

// ========== MATCH 8 ==========
// Pattern: router
// Context:
,P=H,var24=r;
var se=void 0;
return _=function () {
var var0=!(arguments.length>0&&void 0!==arguments[0])||arguments[0];
if(W)W.manual||W.updateScroll(J,X);
else if(O&&e)throw new Error("[redux-first-router] you must set the `restoreScroll` option before\n        you can call `updateScroll`")
}
, {
reducer:Z,middleware:function (r) {
return function (n) {
return function (o) {
var var5=void 0;
if(re&&0=

// ----------------------------------------

// ========== MATCH 9 ==========
// Pattern: router
// Context:
var var5=void 0;
if(re&&0===o.type.indexOf("Navigation/")) {
var var7=ie(re,r,t,o);
var5=u.navigationAction,var3=u.action
}
var var17=t[o.type];
o.error&&(0,a.default)(o)?O&&console.warn("redux-first-router: location update did not dispatch as your action has an error."):o.type!==g.NOT_FOUND||(0,a.default)(o)?c&&!(0,a.default)(o)&&(var3=(0,m.default)(o,t,A,e,T,F)):var3=(0,b.default)(o,r.getState().locat

// ----------------------------------------

// ========== MATCH 10 ==========
// Pattern: router
// Context:
n (r) {
return function (n,o,i) {
"undefined"!=typeof window&&o&&H(o)&&(H(o).routesMap=t);
var var6=r(n,o,i),var17=a.getState(),var18=c&&H(c);
if(var25=a,!l||!l.pathname)throw new Error("[redux-first-router] you must provide the key of the location\n        reducer state and properly assigned the location reducer to that key.");
return e.listen(le.bind(null,a)),!l.hasSSR||(0,u.default)()?(se=function ()

// ----------------------------------------

// ========== MATCH 11 ==========
// Pattern: router
// Context:
r17=r(51394);
function l(e) {
return e&&e.__esModule?var0: {
default:e
}

}
Object.defineProperty(t,"setKind", {
enumerable:!0,get:function () {
return l(c).default
}

}
);
t.NOT_FOUND="@@redux-first-router/NOT_FOUND"
}
,38042:function (e,t,r) {
"use strict";
Object.defineProperty(t,"__esModule", {
value:!0
}
);
var n,var3="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function (e) {
return

// ----------------------------------------

// ========== MATCH 12 ==========
// Pattern: router
// Context:
0","classnames":"^2.3.1","express":"4.18.2","history":"4.10.1","prop-types":"^15.7.2","query-string":"6.0.0","react":"18.2.0","react-dom":"18.2.0","react-redux":"^7.1.0","redux":"^4.1.1","redux-first-router":"1.9.19","redux-thunk":"^2.3.0","url-polyfill":"1.0.13"},"devDependencies":{"@avito/ct":"^8.15.7","@avito/eslint-config-react":"^2.14.1","@avito/stylelint-config":"^2.6.0","@avito/webpack-service-re

// ----------------------------------------

// ========== MATCH 13 ==========
// Pattern: router
// Context:
@testing-library/react":"16.2.0","@types/express":"4.17.17","@types/history":"^4.7.11","@types/node":"20.11.30","@types/qs":"^6.9.7","@types/react":"18.2.14","@types/react-dom":"18.2.6","@types/react-router-dom":"^5.3.3","@types/redux-first-router":"1.10.4","@typescript-eslint/eslint-plugin":"^7.16.1","body-parser":"1.20.2","eslint":"^8.57.0","eslint-plugin-jest":"^28.2.0","express":"4.16.4","husky":"^7

// ----------------------------------------

// ========== MATCH 14 ==========
// Pattern: router
// Context:
express":"4.17.17","@types/history":"^4.7.11","@types/node":"20.11.30","@types/qs":"^6.9.7","@types/react":"18.2.14","@types/react-dom":"18.2.6","@types/react-router-dom":"^5.3.3","@types/redux-first-router":"1.10.4","@typescript-eslint/eslint-plugin":"^7.16.1","body-parser":"1.20.2","eslint":"^8.57.0","eslint-plugin-jest":"^28.2.0","express":"4.16.4","husky":"^7.0.0","multer":"1.3.1","nodemon":"3.0.1",

// ----------------------------------------

// ========== MATCH 15 ==========
// Pattern: history.
// Context:
:l}},var9=function (e) {var var1=b();return t.displayName=e,t},var10=h("Router-History"),var11=h("Router"),var12=function (e) {function t(t) {var r;return(var4=e.call(this,t)||this).state={location:t.history.location},r._isMounted=!1,r._pendingLocation=null,t.staticContext||(r.unlisten=t.history.listen((function (e) {r._pendingLocation=e}))),r}(0,n.A)(t,e),t.computeRootMatch=function (e) {return{path:"/",

