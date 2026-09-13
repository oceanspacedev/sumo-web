if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then((registration) => {
                console.info('Service worker registered for scope: ' + registration.scope);
            })
            .catch((error) => {
                console.warn('Service worker registration failed.', error);
            });
    });
}
