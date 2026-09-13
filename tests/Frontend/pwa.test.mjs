import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const registrationSource = await readFile(new URL('../../resources/js/pwa.js', import.meta.url), 'utf8');
const workerSource = await readFile(new URL('../../public/sw.js', import.meta.url), 'utf8');

test('the PWA entrypoint registers the root worker after the page loads', async () => {
    const events = new Map();
    const registrations = [];

    vm.runInNewContext(registrationSource, {
        window: { addEventListener: (name, callback) => events.set(name, callback) },
        navigator: {
            serviceWorker: {
                register: async (url, options) => {
                    registrations.push({ url, scope: options.scope });
                    return { scope: options.scope };
                },
            },
        },
        console: { info() {}, warn() {} },
    });

    assert.equal(registrations.length, 0);
    events.get('load')();
    await Promise.resolve();
    assert.deepEqual(registrations, [{ url: '/sw.js', scope: '/' }]);
});

test('the PWA entrypoint works when service workers are unavailable', () => {
    vm.runInNewContext(registrationSource, { navigator: {} });
});

function createWorker({ offline = false } = {}) {
    const events = new Map();
    const storedCaches = new Map([
        ['offline', new Map()],
        ['offline-v1', new Map()],
        ['other-application', new Map()],
    ]);
    const networkRequests = [];
    let claimed = false;

    vm.runInNewContext(workerSource, {
        Response,
        self: {
            addEventListener: (name, callback) => events.set(name, callback),
            skipWaiting() {},
            clients: { claim: async () => { claimed = true; } },
        },
        caches: {
            open: async (name) => {
                if (!storedCaches.has(name)) storedCaches.set(name, new Map());
                const cache = storedCaches.get(name);
                return {
                    addAll: async (urls) => {
                        for (const url of urls) cache.set(url, new Response(`Cached ${url}`));
                    },
                    match: async (request) => cache.get(typeof request === 'string' ? request : request.url)?.clone(),
                };
            },
            keys: async () => [...storedCaches.keys()],
            delete: async (name) => storedCaches.delete(name),
        },
        fetch: async (request) => {
            networkRequests.push(request);
            if (offline) throw new TypeError('Network unavailable');
            return new Response('Fresh response');
        },
    });

    return {
        networkRequests,
        storedCaches,
        isClaimed: () => claimed,
        async lifecycle(name) {
            let completion;
            events.get(name)({ waitUntil: (promise) => { completion = promise; } });
            await completion;
        },
        async request(url, { method = 'GET', mode = 'navigate' } = {}) {
            let response;
            events.get('fetch')({
                request: { url, method, mode },
                respondWith: (promise) => { response = promise; },
            });
            return response;
        },
    };
}

test('worker installation preserves the home and offline pages', async () => {
    const worker = createWorker({ offline: true });
    await worker.lifecycle('install');

    assert.equal(await (await worker.request('/')).text(), 'Cached /');
    assert.equal(await (await worker.request('/request/create')).text(), 'Cached /offline.html');
});

test('activation removes only old offline caches and controls existing pages', async () => {
    const worker = createWorker();
    await worker.lifecycle('install');
    await worker.lifecycle('activate');

    assert.deepEqual([...worker.storedCaches.keys()].sort(), ['offline-v2', 'other-application']);
    assert.equal(worker.isClaimed(), true);
});

test('online pages use the network and POST submissions are not intercepted', async () => {
    const worker = createWorker();
    await worker.lifecycle('install');

    assert.equal(await (await worker.request('/')).text(), 'Fresh response');
    assert.equal(await worker.request('/postlogin', { method: 'POST' }), undefined);
    assert.equal(worker.networkRequests.length, 1);
});

test('offline subresource requests do not receive an HTML fallback', async () => {
    const worker = createWorker({ offline: true });
    await worker.lifecycle('install');

    const response = await worker.request('/missing.js', { mode: 'cors' });
    assert.equal(response.type, 'error');
});
