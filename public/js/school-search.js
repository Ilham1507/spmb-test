(function () {
    'use strict';

    window.createSchoolSearchClient = function () {
        let controller = null;
        let version = 0;
        return {
            cancel() {
                version += 1;
                controller?.abort();
                controller = null;
            },
            async search(url, query) {
                this.cancel();
                query = query.trim();
                if (!query) return [];
                const currentVersion = version;
                const currentController = new AbortController();
                controller = currentController;
                const timer = setTimeout(() => currentController.abort(), 5000);
                try {
                    const response = await fetch(`${url}?q=${encodeURIComponent(query)}`, {
                        headers: { Accept: 'application/json' },
                        signal: currentController.signal,
                    });
                    const results = response.ok ? await response.json() : [];
                    return currentVersion === version ? results : null;
                } catch (_) {
                    return currentVersion === version ? [] : null;
                } finally {
                    clearTimeout(timer);
                    if (currentVersion === version) controller = null;
                }
            },
        };
    };
}());
