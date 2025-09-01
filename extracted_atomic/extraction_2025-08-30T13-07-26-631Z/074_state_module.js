/**
 * STATE MODULE
 * Framework: react
 * Matches: 49
 * Priority: 74
 */

// ========== MATCH 1 ==========
// Pattern: redux
// Context:
production";t["default"]=function (e) {var var1=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},var4=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{};if(O) {if(!e)throw new Error("[redux-first-router] invalid `history` agument. Using the 'history' package on NPM,\n        please provide a `history` object as a second parameter. The object will be the\n        return of createBrowserH

// ----------------------------------------

// ========== MATCH 2 ==========
// Pattern: redux
// Context:
y/redux-first-router-restore-scroll\n        please call `restoreScroll` and assign it the option key\n      

// ----------------------------------------

// ========== MATCH 3 ==========
// Pattern: redux
// Context:
"[redux-first-router] invalid `restoreScroll` option. Using\n        https://github.com/faceyspacey/redux-first-router-restore-scroll\n        please call `restoreScroll` and assign it the option key\n        of the same name.")}var var12=(0,d.default)(e.location),A={pathname:"",type:"",payload:{}},var16

// ----------------------------------------

// ========== MATCH 4 ==========
// Pattern: redux
// Context:
(w,V,q,K,t,e),J=Y,X={},Q=1,Z=(0,v.default)(Y,t),ee=(0,p.default)(t,H),te=(0,l.getDocument)(),re=void 0,ne=void 0,oe=void 0,ie=void 0;if(r.navigators) {if(O&&!r.navigators.navigators)throw new Error("[redux-first-router] invalid `navigators` option. Pass your map\n        of navigators to the default import from 'redux-first-router-navigation'.\n        Don't forget: the keys are your redux state keys."

// ----------------------------------------

// ========== MATCH 5 ==========
// Pattern: redux
// Context:
e=void 0,ie=void 0;if(r.navigators) {if(O&&!r.navigators.navigators)throw new Error("[redux-first-router] invalid `navigators` option. Pass your map\n        of navigators to the default import from 'redux-first-router-navigation'.\n        Don't forget: the keys are your redux state keys.");
re=r.navigators.navigators,ne=r.navigators.patchNavigators,oe=r.navigators.actionToNavigation,ie=r.navigators.n

// ----------------------------------------

// ========== MATCH 6 ==========
// Pattern: redux
// Context:
 new Error("[redux-first-router] invalid `navigators` option. Pass your map\n        of navigators to the default import from 'redux-first-router-navigation'.\n        Don't forget: the keys are your redux state keys.");
re=r.navigators.navigators,ne=r.navigators.patchNavigators,oe=r.navigators.actionToNavigation,ie=r.navigators.navigationToAction,ne(re)
}
var ae=function (e,t,r) {
var var2=r.meta.loca

// ----------------------------------------

// ========== MATCH 7 ==========
// Pattern: redux
// Context:

}
;
S=e,E=W,P=H,var24=r;
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
var var5=void

// ----------------------------------------

// ========== MATCH 8 ==========
// Pattern: redux
// Context:
ction (o) {
var var5=void 0;
if(re&&0===o.type.indexOf("Navigation/")) {
var var7=ie(re,r,t,o);
var5=u.navigationAction,var3=u.action
}
var var17=t[o.type];
o.error&&(0,a.default)(o)?O&&console.warn("redux-first-router: location update did not dispatch as your action has an error."):o.type!==g.NOT_FOUND||(0,a.default)(o)?c&&!(0,a.default)(o)&&(var3=(0,m.default)(o,t,A,e,T,F)):var3=(0,b.default)(o,r.get

// ----------------------------------------

// ========== MATCH 9 ==========
// Pattern: redux
// Context:
ncer:function (r) {
return function (n,o,i) {
"undefined"!=typeof window&&o&&H(o)&&(H(o).routesMap=t);
var var6=r(n,o,i),var17=a.getState(),var18=c&&H(c);
if(var25=a,!l||!l.pathname)throw new Error("[redux-first-router] you must provide the key of the location\n        reducer state and properly assigned the location reducer to that key.");
return e.listen(le.bind(null,a)),!l.hasSSR||(0,u.default)()?(s

// ----------------------------------------

// ========== MATCH 10 ==========
// Pattern: redux
// Context:

}
);
var var17=r(51394);
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
var n,var3="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function

// ----------------------------------------

// ========== MATCH 11 ==========
// Pattern: Redux
// Context:
roperties(e,Object.getOwnPropertyDescriptors(r)):a(Object(r)).forEach((function (t) {
Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))
}
))
}
return e
}
function c(e) {
return"Minified Redux error #"+e+"; visit https://redux.js.org/Errors?code="+e+" for the full message or "+"use the non-minified dev environment for full errors. "
}
r.d(t, {
Tw:function () {
return v
}
,zH:function () {


// ----------------------------------------

// ========== MATCH 12 ==========
// Pattern: redux
// Context:
escriptors(r)):a(Object(r)).forEach((function (t) {
Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))
}
))
}
return e
}
function c(e) {
return"Minified Redux error #"+e+"; visit https://redux.js.org/Errors?code="+e+" for the full message or "+"use the non-minified dev environment for full errors. "
}
r.d(t, {
Tw:function () {
return v
}
,zH:function () {
return b
}
,HY:function () {
retur

// ----------------------------------------

// ========== MATCH 13 ==========
// Pattern: redux
// Context:
() {
return d
}

}
);
var var18="function"==typeof Symbol&&Symbol.observable||"@@observable",var22=function () {
return Math.random().toString(36).substring(7).split("").join(".")
}
,var23= {
INIT:"@@redux/INIT"+s(),REPLACE:"@@redux/REPLACE"+s(),PROBE_UNKNOWN_ACTION:function () {
return"@@redux/PROBE_UNKNOWN_ACTION"+s()
}

}
;
function p(e) {
if("object"!=typeof e||null===e)return!1;
for(var var1=e;
nu

// ----------------------------------------

// ========== MATCH 14 ==========
// Pattern: redux
// Context:
ar18="function"==typeof Symbol&&Symbol.observable||"@@observable",var22=function () {
return Math.random().toString(36).substring(7).split("").join(".")
}
,var23= {
INIT:"@@redux/INIT"+s(),REPLACE:"@@redux/REPLACE"+s(),PROBE_UNKNOWN_ACTION:function () {
return"@@redux/PROBE_UNKNOWN_ACTION"+s()
}

}
;
function p(e) {
if("object"!=typeof e||null===e)return!1;
for(var var1=e;
null!==Object.getPrototypeOf(

// ----------------------------------------

// ========== MATCH 15 ==========
// Pattern: redux
// Context:
e",var22=function () {
return Math.random().toString(36).substring(7).split("").join(".")
}
,var23= {
INIT:"@@redux/INIT"+s(),REPLACE:"@@redux/REPLACE"+s(),PROBE_UNKNOWN_ACTION:function () {
return"@@redux/PROBE_UNKNOWN_ACTION"+s()
}

}
;
function p(e) {
if("object"!=typeof e||null===e)return!1;
for(var var1=e;
null!==Object.getPrototypeOf(t);
)var1=Object.getPrototypeOf(t);
return Object.getPrototypeO

