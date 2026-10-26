const CACHE='conexionweb-retiros-v3';
const STATIC=['/home-service-offline.html','/home-service-manifest.webmanifest','/images/home-service-icon-192.png','/images/home-service-icon-512.png','/images/home-service-icon-maskable-192.png','/images/home-service-icon-maskable-512.png'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(STATIC)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(Promise.all([self.clients.claim(),caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key))))])));
self.addEventListener('fetch',event=>{if(event.request.method!=='GET')return;if(event.request.mode==='navigate'){event.respondWith(fetch(event.request).catch(()=>caches.match('/home-service-offline.html')));return}event.respondWith(caches.match(event.request).then(hit=>hit||fetch(event.request))) });
