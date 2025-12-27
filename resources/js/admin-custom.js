$(document).ready(function () {
    console.log('SPA Script Loaded');

    // Replace initial history state so back button works properly
    if (!history.state) {
        history.replaceState({
            path: window.location.href,
            spa: true
        }, '', window.location.href);
    }

    // Helper to update content from HTML response
    function handleSpaResponse(data, urlToPush) {
        // Use DOMParser for reliable script extraction (jQuery strips scripts)
        var parser = new DOMParser();
        var doc = parser.parseFromString(data, 'text/html');

        // Extract new content using native DOM
        var mainContentEl = doc.querySelector('#main-content');
        var sidebarEl = doc.querySelector('#hs-application-sidebar');

        var newContent = mainContentEl ? mainContentEl.innerHTML : null;
        var newSidebar = sidebarEl ? sidebarEl.innerHTML : null;

        if (newContent) {
            $('#main-content').html(newContent);
            console.log('Content updated');

            // Extract page-specific scripts from the response body
            // Exclude layout scripts (jQuery, NProgress, Vite bundles, SPA handler)
            var layoutScriptPatterns = [
                'jquery',
                'nprogress',
                'vite',
                'SPA Script Loaded', // Our SPA handler
                'preline/index' // Preline UI bundle (not helper scripts like hs-apexcharts-helpers.js)
            ];

            var allBodyScripts = doc.body.querySelectorAll('script');
            var externalScripts = [];
            var inlineScripts = [];

            allBodyScripts.forEach(function (s) {
                var src = s.src || '';
                var content = s.textContent || '';

                // Skip layout scripts
                var isLayoutScript = layoutScriptPatterns.some(function (pattern) {
                    return src.toLowerCase().includes(pattern.toLowerCase()) ||
                        content.includes(pattern);
                });

                if (isLayoutScript) return;

                if (s.src) {
                    externalScripts.push(s.src);
                } else if (s.textContent.trim()) {
                    inlineScripts.push(s.textContent);
                }
            });

            console.log('Found page scripts:', externalScripts.length, 'external,', inlineScripts.length,
                'inline');

            // Function to load external scripts sequentially
            function loadScriptsSequentially(urls, callback) {
                if (urls.length === 0) {
                    callback();
                    return;
                }
                var url = urls.shift();
                // Check if script is already loaded
                if (document.querySelector('script[src="' + url + '"]')) {
                    loadScriptsSequentially(urls, callback);
                    return;
                }
                var script = document.createElement('script');
                script.src = url;
                script.onload = function () {
                    loadScriptsSequentially(urls, callback);
                };
                script.onerror = function () {
                    console.error('Failed to load script:', url);
                    loadScriptsSequentially(urls, callback);
                };
                document.body.appendChild(script);
            }

            loadScriptsSequentially(externalScripts.slice(), function () {
                // Execute inline scripts after external ones have loaded
                inlineScripts.forEach(function (code) {
                    try {
                        eval(code);
                    } catch (e) {
                        console.error('Error executing inline script:', e);
                    }
                });

                // Dispatch load event for scripts waiting on window.load
                window.dispatchEvent(new Event('load'));
            });

        } else {
            console.error('#main-content not found in response');
            return false;
        }

        if (newSidebar) {
            $('#hs-application-sidebar').html(newSidebar);
            console.log('Sidebar updated');
        }

        // Update URL
        if (urlToPush && window.location.href !== urlToPush) {
            window.history.pushState({
                path: urlToPush,
                spa: true
            }, '', urlToPush);
        }

        // Re-initialize plugins
        if (window.HSStaticMethods) {
            window.HSStaticMethods.autoInit();
        }

        // Re-initialize Flatpickr
        if (window.flatpickr) {
            window.flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        }

        return true;
    }

    // Intercept clicks on elements with 'navigate' attribute
    $('body').on('click', 'a[navigate]', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        console.log('SPA Navigation clicked:', url);

        if (!url || url.startsWith('#') || url.startsWith('javascript:')) {
            return;
        }

        // Close Sidebar on Mobile if it's open
        try {
            if (window.HSOverlay) {
                HSOverlay.close(document.querySelector('#hs-application-sidebar'));
            }
        } catch (error) {
            // Ignore errors if overlay library isn't fully loaded or element invalid
            console.log('Sidebar Close Debug:', error);
        }

        loadPage(url);
    });

    function loadPage(url) {
        // Start Loading
        NProgress.start();

        $.ajax({
            url: url,
            success: function (data) {
                if (!handleSpaResponse(data, url)) {
                    window.location.href = url;
                }
                NProgress.done();
            },
            error: function (xhr, status, error) {
                console.error('SPA Load Error:', error);
                window.location.href = url; // Fallback to normal navigation on error
            }
        });
    }

    // Track if we're in the middle of SPA navigation
    var spaNavigating = false;

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function (event) {
        // If no state or not our SPA state, let browser handle it
        if (!event.state || !event.state.spa) {
            console.log('Non-SPA state, letting browser handle');
            return;
        }

        var url = window.location.href;
        console.log('Browser back/forward to:', url);

        // Prevent any default behavior by immediately updating content
        spaNavigating = true;

        // Load the page without pushing to history (already in history)
        NProgress.start();
        $.ajax({
            url: url,
            success: function (data) {
                // Use DOMParser for reliable parsing
                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');

                var mainContentEl = doc.querySelector('#main-content');
                var sidebarEl = doc.querySelector('#hs-application-sidebar');

                if (mainContentEl) {
                    $('#main-content').html(mainContentEl.innerHTML);

                    // Re-load page scripts (same logic as handleSpaResponse)
                    var layoutScriptPatterns = ['jquery', 'nprogress', 'vite',
                        'SPA Script Loaded', 'preline/index'
                    ];
                    var allBodyScripts = doc.body.querySelectorAll('script');
                    var externalScripts = [];
                    var inlineScripts = [];

                    allBodyScripts.forEach(function (s) {
                        var src = s.src || '';
                        var content = s.textContent || '';
                        var isLayoutScript = layoutScriptPatterns.some(function (
                            pattern) {
                            return src.toLowerCase().includes(pattern
                                .toLowerCase()) || content.includes(
                                    pattern);
                        });
                        if (isLayoutScript) return;
                        if (s.src) externalScripts.push(s.src);
                        else if (s.textContent.trim()) inlineScripts.push(s
                            .textContent);
                    });

                    // Load scripts sequentially
                    (function loadNext(urls, cb) {
                        if (urls.length === 0) {
                            cb();
                            return;
                        }
                        var u = urls.shift();
                        if (document.querySelector('script[src="' + u + '"]')) {
                            loadNext(urls, cb);
                            return;
                        }
                        var script = document.createElement('script');
                        script.src = u;
                        script.onload = script.onerror = function () {
                            loadNext(urls, cb);
                        };
                        document.body.appendChild(script);
                    })(externalScripts.slice(), function () {
                        inlineScripts.forEach(function (code) {
                            try {
                                eval(code);
                            } catch (e) { }
                        });
                        window.dispatchEvent(new Event('load'));
                    });
                }

                if (sidebarEl) {
                    $('#hs-application-sidebar').html(sidebarEl.innerHTML);
                }

                if (window.HSStaticMethods) {
                    window.HSStaticMethods.autoInit();
                }

                NProgress.done();
                spaNavigating = false;
            },
            error: function () {
                spaNavigating = false;
                window.location.reload();
            }
        });
    });

    // Handle page restored from bfcache (browser back-forward cache)
    window.addEventListener('pageshow', function (event) {
        if (event.persisted && spaNavigating) {
            // Page was restored from bfcache while we were SPA navigating
            console.log('Page restored from bfcache during SPA navigation');
            event.preventDefault();
        }
    });

    // Helper to handle JSON validation errors
    function handleValidationErrors($form, errors) {
        // Clear previous errors
        $form.find('.border-red-500').removeClass('border-red-500');
        $form.find('.validation-error').remove();

        // Loop through errors
        $.each(errors, function (field, messages) {
            var $input = $form.find('[name="' + field + '"]');
            if ($input.length) {
                $input.addClass('border-red-500');
                // Use first error message
                var message = messages[0];
                $input.after('<p class="text-sm text-red-600 mt-1 validation-error">' + message +
                    '</p>');
            }
        });
    }

    // Global function for custom toast close button
    window.tostifyCustomClose = function (el) {
        $(el).closest('.toastify').remove();
    };

    function getToastNode(message) {
        var html = `
        <div class="animate-toast-pop bg-white border border-gray-200 rounded-xl shadow-lg dark:bg-neutral-800 dark:border-neutral-700" role="alert">
            <div class="flex p-4">
              <p class="text-sm text-gray-700 dark:text-neutral-400">${message}</p>
              <div class="ms-auto">
                <button onclick="tostifyCustomClose(this)" type="button" class="inline-flex shrink-0 justify-center items-center size-5 rounded-lg text-gray-800 opacity-50 hover:opacity-100 focus:outline-hidden focus:opacity-100 dark:text-white" aria-label="Close">
                  <span class="sr-only">Close</span>
                  <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                </button>
              </div>
            </div>
        </div>`;
        var div = document.createElement('div');
        div.innerHTML = html.trim();
        return div.firstChild;
    }

    // Intercept form submissions with 'navigate-form' attribute
    $('body').on('submit', 'form[navigate-form]', function (e) {
        e.preventDefault();
        var $form = $(this);
        console.log('SPA Form Submit:', $form.attr('action'));

        // Find submit button & Set Loading State
        var $btn = $form.find('button[type="submit"]');
        var originalHtml = $btn.html();
        if ($btn.length) {
            $btn.prop('disabled', true).html(
                '<span class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent rounded-full" role="status" aria-label="loading"></span> Loading...'
            );
        }

        NProgress.start();
        var action = $form.attr('action');
        var method = $form.attr('method') || 'POST'; // Default to POST if not specified
        var nativeXhr;
        var ajaxData;
        var ajaxProcessData;
        var ajaxContentType;

        if (method.toUpperCase() === 'GET') {
            ajaxData = $form.serialize();
            ajaxProcessData = true;
            ajaxContentType = 'application/x-www-form-urlencoded; charset=UTF-8';
        } else {
            ajaxData = new FormData(this);
            ajaxProcessData = false;
            ajaxContentType = false;
        }

        $.ajax({
            url: action,
            type: method,
            data: ajaxData,
            processData: ajaxProcessData,
            contentType: ajaxContentType,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                // Capture the native XHR object to access responseURL later
                nativeXhr = xhr;
                return xhr;
            },
            success: function (data, textStatus, xhr) {
                // Attempt to get final URL from native XHR (handles redirects)
                var finalUrl = (nativeXhr ? nativeXhr.responseURL : null) || action;
                console.log('Form Success, Final URL:', finalUrl);

                if (handleSpaResponse(data, finalUrl)) {
                    console.log('Form SPA update success');

                    // Extract dynamic message from parsed response
                    // handleSpaResponse doesn't return the parsed DOM, so we parse again or rely on the inserted DOM.
                    // Since handleSpaResponse just updated the DOM, we can just look for the element in document!
                    // Wait, handleSpaResponse replaces #main-content. So #spa-flash-success should be in document now if it was in the response.
                    var apiMessage = $('#spa-flash-success').text();
                    var toastMessage = apiMessage ? apiMessage.trim() :
                        (method.toUpperCase() === 'GET' ? '' :
                            "Form submitted successfully");

                    // Show Toast Notification
                    if (window.Toastify && toastMessage) {
                        Toastify({
                            node: getToastNode(toastMessage),
                            duration: 3000,
                            className: "p-0 bg-transparent shadow-none max-w-xs",
                            gravity: "top",
                            position: "right",
                            stopOnFocus: true,
                            style: {
                                background: "transparent",
                                boxShadow: "none",
                            }
                        }).showToast();
                    }
                } else {
                    window.location.reload();
                }
                NProgress.done();
            },
            error: function (xhr) {
                console.error('Form Error', xhr);
                NProgress.done();

                // Restore Button State
                if ($btn.length) {
                    $btn.prop('disabled', false).html(originalHtml);
                }

                if (xhr.status === 422) {
                    var response = xhr.responseJSON;
                    if (response && response.errors) {
                        handleValidationErrors($form, response.errors);
                    } else {
                        alert('Validation failed but no errors returned.');
                    }
                } else {
                    // Try to render response if it is HTML (e.g. 500 error page)
                    // Warning: replacing body with error page might break SPA context but provides feedback.
                    var $temp = $('<div>').html(xhr.responseText);
                    if ($temp.find('#main-content').length) {
                        handleSpaResponse(xhr.responseText, action);
                    } else {
                        // Full replacement if critical error
                        if (xhr.responseText) {
                            document.open();
                            document.write(xhr.responseText);
                            document.close();
                        } else {
                            alert('An error occurred: ' + xhr.status + ' ' + xhr
                                .statusText);
                        }
                    }
                }
            }
        });
    });

    // Handle Back Button
    window.onpopstate = function (e) {
        window.location.reload();
    };
});
