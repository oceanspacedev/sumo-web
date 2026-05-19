const cacheName = "offline-v2";
const offlineUrl = "/offline.html";

const preLoad = function () {
    return caches.open(cacheName).then(function (cache) {
        // caching index and important routes
        return cache.addAll(filesToCache);
    });
};

self.addEventListener("install", function (event) {
    event.waitUntil(preLoad());
    self.skipWaiting();
});

const filesToCache = [
    '/',
    offlineUrl
];

const returnFromCache = function (request) {
    return caches.open(cacheName).then(function (cache) {
        return cache.match(request).then(function (matching) {
            if (matching) {
                return matching;
            }

            if (request.mode === "navigate") {
                return cache.match(offlineUrl);
            }

            return Response.error();
        });
    });
};

self.addEventListener("activate", function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (key) {
                if (key === "offline" || (key.startsWith("offline-") && key !== cacheName)) {
                    return caches.delete(key);
                }
            }));
        }).then(function () {
            return self.clients.claim();
        })
    );
});

self.addEventListener("fetch", function (event) {
    if (event.request.method !== "GET") {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(function () {
            return returnFromCache(event.request);
        })
    );
});
