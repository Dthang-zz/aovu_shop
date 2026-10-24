(function () {
    'use strict';

    if (window.AdminNavigation) return;

    var progress = document.getElementById('spa-progress');
    var isNavigating = false;
    var pendingNavigation = null;
    var persistentScripts = new Set(
        Array.from(document.querySelectorAll('script[src]'), function (script) {
            return new URL(script.src, window.location.href).href;
        })
    );

    window.history.scrollRestoration = 'manual';
    window.history.replaceState(
        Object.assign({}, window.history.state, { spaScrollY: window.scrollY }),
        '',
        window.location.href
    );

    function isAdminUrl(value) {
        var url;

        try {
            url = new URL(value, window.location.href);
        } catch (error) {
            return false;
        }

        return url.origin === window.location.origin
            && (url.pathname === '/' || url.pathname === '/admin' || url.pathname.indexOf('/admin/') === 0);
    }

    function setLoading(loading) {
        if (!progress) return;

        progress.classList.toggle('is-loading', loading);
        progress.classList.remove('is-complete');
        document.querySelector('.content-wrapper')?.setAttribute('aria-busy', loading ? 'true' : 'false');

        if (!loading) {
            progress.classList.add('is-complete');
            window.setTimeout(function () {
                progress.classList.remove('is-complete');
            }, 250);
        }
    }

    function showError(message) {
        var container = document.querySelector('.content-wrapper .content .container-fluid');
        if (!container) return;

        var alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show spa-navigation-error';
        alert.setAttribute('role', 'alert');

        var icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-circle mr-1';
        alert.appendChild(icon);
        alert.appendChild(document.createTextNode(message));

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'close';
        close.setAttribute('data-dismiss', 'alert');
        close.setAttribute('aria-label', 'Đóng');
        close.innerHTML = '<span aria-hidden="true">&times;</span>';
        alert.appendChild(close);

        container.querySelectorAll('.spa-navigation-error').forEach(function (existing) {
            existing.remove();
        });
        container.prepend(alert);
    }

    function responsePage(html) {
        var parsed = new DOMParser().parseFromString(html, 'text/html');
        var selectors = ['.main-header', '.main-sidebar', '.content-wrapper', '.main-footer'];

        if (selectors.some(function (selector) {
            return !parsed.querySelector(selector) || !document.querySelector(selector);
        })) {
            return null;
        }

        return parsed;
    }

    async function runPageScripts(parsed) {
        var scripts = Array.from(parsed.querySelectorAll('body script:not([data-spa-persistent])'));

        for (var index = 0; index < scripts.length; index++) {
            var original = scripts[index];
            var source = original.src ? new URL(original.src, parsed.baseURI).href : null;

            if (source && persistentScripts.has(source)) continue;

            await new Promise(function (resolve, reject) {
                var script = document.createElement('script');

                Array.from(original.attributes).forEach(function (attribute) {
                    if (attribute.name !== 'src') {
                        script.setAttribute(attribute.name, attribute.value);
                    }
                });

                if (source) {
                    script.src = source;
                    script.async = false;
                    script.onload = function () {
                        persistentScripts.add(source);
                        resolve();
                    };
                    script.onerror = function () {
                        reject(new Error('Không thể tải tập lệnh giao diện: ' + source));
                    };
                    document.body.appendChild(script);
                    return;
                }

                script.textContent = original.textContent;
                document.body.appendChild(script);
                script.remove();
                resolve();
            });
        }
    }

    async function navigate(value, options) {
        options = options || {};
        if (isNavigating) {
            pendingNavigation = { value: value, options: options };
            return;
        }

        var url = new URL(value, window.location.href);
        if (!isAdminUrl(url.href)) return;

        isNavigating = true;
        setLoading(true);

        try {
            var response = await fetch(url.href, {
                method: options.method || 'GET',
                body: options.body,
                credentials: 'same-origin',
                redirect: 'follow',
                headers: Object.assign({
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }, options.headers || {})
            });

            var contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('text/html')) {
                throw new Error('Máy chủ không trả về nội dung trang HTML.');
            }

            var html = await response.text();
            var parsed = responsePage(html);
            if (!parsed) {
                throw new Error('Không thể cập nhật trang. Máy chủ phản hồi HTTP ' + response.status + '.');
            }

            if (!response.ok) {
                throw new Error('Không thể cập nhật trang. Máy chủ phản hồi HTTP ' + response.status + '.');
            }

            var finalUrl = new URL(response.url);
            var currentState = Object.assign({}, window.history.state, { spaScrollY: window.scrollY });
            window.history.replaceState(currentState, '', window.location.href);

            ['.main-header', '.main-sidebar', '.content-wrapper', '.main-footer'].forEach(function (selector) {
                document.querySelector(selector).replaceWith(parsed.querySelector(selector));
            });

            document.title = parsed.title;
            var incomingToken = parsed.querySelector('meta[name="csrf-token"]')?.content;
            var currentToken = document.querySelector('meta[name="csrf-token"]');
            if (incomingToken && currentToken) currentToken.content = incomingToken;

            if (options.history !== false) {
                if (finalUrl.href === window.location.href) {
                    window.history.replaceState({}, '', finalUrl.href);
                } else {
                    window.history.pushState({}, '', finalUrl.href);
                }
            }

            await runPageScripts(parsed);
            document.querySelector('.content-wrapper')?.classList.add('spa-content-enter');
            document.dispatchEvent(new CustomEvent('admin:page-updated', {
                detail: { url: finalUrl.href }
            }));

            if (options.history === false) {
                window.scrollTo(0, Number(options.scrollY || 0));
            } else {
                window.scrollTo(0, 0);
                document.querySelector('.content-header h1')?.focus({ preventScroll: true });
            }
        } catch (error) {
            console.error('Admin navigation failed:', error);
            showError(error.message || 'Không thể cập nhật nội dung. Vui lòng thử lại.');
        } finally {
            isNavigating = false;
            setLoading(false);
            if (pendingNavigation) {
                var nextNavigation = pendingNavigation;
                pendingNavigation = null;
                navigate(nextNavigation.value, nextNavigation.options);
            }
        }
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        var link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || link.target || link.hasAttribute('data-no-spa')) return;
        if (link.closest('[data-widget], [data-toggle]')) return;

        var url = new URL(link.href, window.location.href);
        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return;
        if (!isAdminUrl(url.href)) return;

        event.preventDefault();
        navigate(url.href);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.hasAttribute('data-no-spa')) return;

        var method = (form.method || 'GET').toUpperCase();
        var methodOverride = form.querySelector('input[name="_method"]')?.value.toUpperCase();
        if (!['GET', 'POST'].includes(method) || (methodOverride && !['PUT', 'PATCH', 'DELETE'].includes(methodOverride))) return;

        var action = new URL(form.action || window.location.href, window.location.href);
        if (!isAdminUrl(action.href) || form.target) return;

        event.preventDefault();

        var formData;
        try {
            formData = event.submitter
                ? new FormData(form, event.submitter)
                : new FormData(form);
        } catch (error) {
            formData = new FormData(form);
        }

        if (method === 'GET') {
            action.search = new URLSearchParams(formData).toString();
            navigate(action.href);
            return;
        }

        var token = document.querySelector('meta[name="csrf-token"]')?.content;
        navigate(action.href, {
            method: method === 'POST' ? 'POST' : method,
            body: formData,
            headers: token ? { 'X-CSRF-TOKEN': token } : {}
        });
    });

    window.addEventListener('popstate', function (event) {
        navigate(window.location.href, {
            history: false,
            scrollY: event.state?.spaScrollY || 0
        });
    });

    window.AdminNavigation = {
        navigate: navigate
    };
})();
