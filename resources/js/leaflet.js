// leaflet.markercluster is a plain script that extends a global L. Imports are
// hoisted, so assigning window.L inside map.js would run after the plugin had
// already looked for it: exposing it from its own module, imported first, keeps
// the order right.
import L from 'leaflet'

window.L = L

export default L
