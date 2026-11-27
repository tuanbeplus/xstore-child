/* Blog index: load category filters and pagination without a full reload.
   Falls back to normal navigation if anything goes wrong. */
(function () {
    'use strict';

    var root = document.querySelector('.vf-archive');
    if (!root) {
        return;
    }

    var content = root.querySelector('.vf-archive__content');
    if (!content) {
        return;
    }

    var busy = false;
    var LINK_SELECTOR = '.vf-chip, .vf-pagination a.page-numbers';
    var SCROLL_OFFSET = 100;

    function setBusy(state) {
        busy = state;
        content.classList.toggle('is-loading', state);
        content.setAttribute('aria-busy', state ? 'true' : 'false');
    }

    function scrollToResults() {
        // Land on the results area, not the top of the page: the static
        // top-stories block above should stay out of the way after filtering.
        var top = content.getBoundingClientRect().top + window.pageYOffset - SCROLL_OFFSET;
        window.scrollTo({
            top: top > 0 ? top : 0,
            behavior: 'smooth'
        });
    }

    function load(url, push) {
        if (busy) {
            return;
        }

        setBusy(true);

        window.fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.querySelector('.vf-archive__content');

                if (!next) {
                    window.location.href = url;
                    return;
                }

                content.innerHTML = next.innerHTML;

                var nextTitle = doc.querySelector('title');
                if (nextTitle) {
                    document.title = nextTitle.textContent;
                }

                if (push && window.history && window.history.pushState) {
                    window.history.pushState({ vfBlog: true }, '', url);
                }

                setBusy(false);
                scrollToResults();
            })
            .catch(function () {
                window.location.href = url;
            });
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var link = event.target.closest ? event.target.closest(LINK_SELECTOR) : null;
        if (!link) {
            return;
        }
        if ((link.target && link.target !== '_self') || link.hasAttribute('download')) {
            return;
        }

        var url;
        try {
            url = new URL(link.href, window.location.href);
        } catch (error) {
            return;
        }

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();

        if (url.href === window.location.href) {
            return;
        }

        load(url.href, true);
    });

    window.addEventListener('popstate', function () {
        load(window.location.href, false);
    });
})();
