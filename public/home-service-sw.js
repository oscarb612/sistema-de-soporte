const CACHE='conexionweb-retiros-v2';
const STATIC=['/home-service-manifest.webmanifest','/images/home-service-icon.svg'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(STATIC))));
self.addEventListener('activate',event=>event.waitUntil(Promise.all([self.clients.claim(),caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key))))])));
self.addEventListener('fetch',event=>{if(event.request.method!=='GET'||event.request.mode==='navigate')return;event.respondWith(caches.match(event.request).then(hit=>hit||fetch(event.request))) });
