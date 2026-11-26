// Runs inside each page before its own scripts. While ZaxProxy Connect's GPS is on, the page's
// location requests are answered with the place chosen on the map. When it is off (or the app
// is closed) every request goes to the browser's real location as usual.
(function () {
  if (!navigator.geolocation || window.__zaxgps) return;
  window.__zaxgps = 1;
  var proto = Object.getPrototypeOf(navigator.geolocation);
  var real = { get: proto.getCurrentPosition, watch: proto.watchPosition, clear: proto.clearWatch };
  var cfg = null, at = 0, asking = false, waiting = [], watches = {}, nextId = 900000;

  function done(c) { cfg = c || { on: false }; at = Date.now(); asking = false; var w = waiting; waiting = []; w.forEach(function (f) { f(cfg); }); }
  function config(cb) {
    if (cfg && Date.now() - at < 3000) { cb(cfg); return; }
    waiting.push(cb);
    if (asking) return;
    asking = true;
    window.postMessage({ zaxgps: 'ask' }, '*');
    setTimeout(function () { if (asking) done({ on: false }); }, 1800); // no answer: behave normally
  }
  window.addEventListener('message', function (e) { if (e.source === window && e.data && e.data.zaxgps === 'state' && asking) done(e.data.state); });

  function position(c) {
    var coords = { latitude: c.lat, longitude: c.lng, accuracy: 12, altitude: null, altitudeAccuracy: null, heading: null, speed: null };
    coords.toJSON = function () { return { latitude: c.lat, longitude: c.lng, accuracy: 12, altitude: null, altitudeAccuracy: null, heading: null, speed: null }; };
    var pos = { coords: coords, timestamp: Date.now() };
    pos.toJSON = function () { return { coords: coords.toJSON(), timestamp: pos.timestamp }; };
    return pos;
  }

  proto.getCurrentPosition = function (ok, fail, opts) {
    var self = this;
    config(function (c) {
      if (c.on && isFinite(c.lat) && isFinite(c.lng)) { if (typeof ok === 'function') setTimeout(function () { ok(position(c)); }, 60); }
      else real.get.call(self, ok, fail, opts);
    });
  };
  proto.watchPosition = function (ok, fail, opts) {
    var self = this, id = nextId++, w = watches[id] = { realId: null, timer: 0 };
    function tick() {
      if (!watches[id]) return;
      config(function (c) {
        if (!watches[id]) return;
        if (c.on && isFinite(c.lat) && isFinite(c.lng)) {
          if (w.realId !== null) { real.clear.call(self, w.realId); w.realId = null; }
          if (typeof ok === 'function') ok(position(c));
        } else if (w.realId === null) { w.realId = real.watch.call(self, ok, fail, opts); }
      });
    }
    tick(); w.timer = setInterval(tick, 5000);
    return id;
  };
  proto.clearWatch = function (id) {
    var w = watches[id];
    if (!w) { real.clear.call(this, id); return; }
    clearInterval(w.timer); if (w.realId !== null) real.clear.call(this, w.realId); delete watches[id];
  };

  // Pages that first ask "may I use location?" are told yes while the GPS is on.
  if (navigator.permissions && navigator.permissions.query) {
    var pproto = Object.getPrototypeOf(navigator.permissions), q = pproto.query;
    pproto.query = function (d) {
      var self = this, args = arguments;
      if (!d || d.name !== 'geolocation') return q.apply(self, args);
      return new Promise(function (resolve, reject) {
        config(function (c) {
          if (!c.on) { q.apply(self, args).then(resolve, reject); return; }
          resolve({ name: 'geolocation', state: 'granted', onchange: null, addEventListener: function () {}, removeEventListener: function () {}, dispatchEvent: function () { return false; } });
        });
      });
    };
  }
})();
