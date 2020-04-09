var SV = window.SV || {};
SV.WhoReplied = SV.WhoReplied || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Filter = XF.extend(XF.Filter, {
        __backup: {
            'init': 'svWhoReplied__init',
            '_filterAjax': 'svWhoReplied__filterAjax',
            'filter': 'svWhoReplied_filter',
            '_filterAjaxResponse': 'svWhoReplied__filterAjaxResponse',
            '_getStoredValue': 'svWhoReplied__getStoredValue',
            '_updateStoredValue': 'svWhoReplied__updateStoredValue'
        },

        options: $.extend({}, XF.Filter.prototype.options, {
            svLoadInOverlay: true,
            svWhorepliedExistingFilterText: '',
            svWhorepliedExistingFilterPrefix: false,
            svWhorepliedPagenavWrapper: null,
            svWhorepliedPagenavButtonSelector: '.pageNav a[href]',
            svWhorepliedPagenavCurrentButtonSelector: '.pageNav-page--current > a'
        }),

        inOverlay: false,
        xhrFilterOriginal: null,
        svWhoRepliedPageChanged: false,
        svWhoRepliedLastPageSelected: null,

        init: function ()
        {
            this.inOverlay = this.$target.parents('.overlay-container').length  !== 0;

            this.svWhoReplied__init();

            this.svWhoRepliedOverlayShim();

            var storedValue = this._getStoredValue();
            if (!storedValue)
            {
                return;
            }

            if (!this.inOverlay && storedValue.filter === '' && storedValue.page === 1)
            {
                var currentUrl = new Url(window.location.href);
                if ("_xfFilter[text]" in currentUrl.query)
                {
                    var text = currentUrl.query["_xfFilter[text]"],
                        prefix = false;
                    if ("_xfFilter[prefix]" in currentUrl.query)
                    {
                        prefix = currentUrl.query["_xfFilter[prefix]"];
                    }

                    var data = this._readFromStorage(),
                        storageKey = this.storageKey;
                    if (!storedValue)
                    {
                        storedValue = {
                            filter: '',
                            prefix: false,
                            page: 1
                        };
                    }

                    if (data[storageKey])
                    {
                        var record = data[storageKey];
                        if ('page' in record)
                        {
                            storedValue.page = parseInt(record.page) || 1;
                        }
                    }

                    if (this.svWhoRepliedLastPageSelected !== null)
                    {
                        storedValue.page = parseInt(this.svWhoRepliedLastPageSelected) || 1;
                    }

                    data[storageKey] = storedValue;
                    this._writeToStorage(data);

                    if (text.length)
                    {
                        var $rows = this.$search
                            .find(this.options.searchRow)
                            .filter(':not(.is-hidden)');
                        if (!$rows.length)
                        {
                            return;
                        }

                        this._applyFilter($rows, text, prefix);
                    }
                }
            }
        },

        /**
         *
         *
         * @param {String} text
         * @param {Boolean} prefix
         *
         * @private
         */
        _filterAjax: function(text, prefix)
        {
            // this will be null if not used with who replied
            var currentPage = this.svWhoRepliedGetCurrentPage();
            if (!currentPage)
            {
                this.svWhoReplied__filterAjax(text, prefix);
                return;
            }

            var data = {
                _xfFilter: {
                    text: text,
                    prefix: prefix ? 1 : 0,
                    page: currentPage
                }
            };

            this.xhrFilter = { text: text, prefix: prefix, page: currentPage };
            XF.ajax('GET', this.options.ajax, data, XF.proxy(this, '_filterAjaxResponse'));
        },

        _filterAjaxResponse: function(result)
        {
            this.svWhoReplied__filterAjaxResponse(result);

            var oldPageNavWrapper = this.svWhoRepliedGetPagenavWrapper();
            if (!oldPageNavWrapper)
            {
                return;
            }

            var $result = $($.parseHTML(result.html.content)),
                newPageNavWrapper = $result.find(this.options.svWhorepliedPagenavWrapper);
            if (!newPageNavWrapper.length)
            {
                oldPageNavWrapper.empty();
                return;
            }

            oldPageNavWrapper.html(newPageNavWrapper.html());
            this.svWhoRepliedOverlayShim();

            if (!this.inOverlay)
            {
                return;
            }

            var $finalUrlInput = $result.find('input[type="hidden"][name="final_url"]');
            if (!$finalUrlInput.length)
            {
                console.error('No final URL input was provided.');
                return;
            }

            var finalUrl = $finalUrlInput.val();
            if (!finalUrl)
            {
                console.error('No final URL available.');
                return;
            }

            if ('pushState' in window.history)
            {
                window.history.pushState({
                    state: 1,
                    rand: Math.random()
                }, '', finalUrl);
            }
            else
            {
                window.location = finalUrl; // force
            }
        },

        /**
         * @returns {Object}
         *
         * @private
         */
        _getStoredValue: function()
        {
            var storedValue = this.svWhoReplied__getStoredValue(),
                storageKey = this.storageKey;

            if (!storageKey)
            {
                return storedValue;
            }

            var data = this._readFromStorage();
            if (!storedValue)
            {
                storedValue = {
                    filter: '',
                    prefix: false,
                    page: 1
                };
            }

            if (data[storageKey])
            {
                var record = data[storageKey];
                if ('page' in record)
                {
                    storedValue.page = parseInt(record.page) || 1;
                }
            }

            if (this.svWhoRepliedLastPageSelected !== null)
            {
                storedValue.page = parseInt(this.svWhoRepliedLastPageSelected) || 1;
            }

            if (storedValue.filter === '')
            {
                var existingFilterText = this.options.svWhorepliedExistingFilterText;
                if (typeof existingFilterText === 'string' && existingFilterText !== '')
                {
                    storedValue.filter = existingFilterText;

                    var existingFilterPrefix = this.options.svWhorepliedExistingFilterPrefix;
                    if (typeof existingFilterPrefix === 'boolean')
                    {
                        storedValue.prefix = existingFilterPrefix;
                    }
                }
            }

            data[storageKey] = storedValue;
            this._writeToStorage(data);

            return storedValue;
        },

        _updateStoredValue: function(text, prefix)
        {
            var storedValue = this._getStoredValue();
            if (storedValue && typeof storedValue === 'object')
            {
                var storageKey = this.storageKey;
                if (storageKey)
                {
                    var data = this._readFromStorage();
                    if (data[storageKey])
                    {
                        var record = data[storageKey];
                        record.page = 1;
                        data[storageKey] = record;
                        this._writeToStorage(data);
                    }
                }
            }

            this.svWhoReplied__updateStoredValue(text, prefix);
        },

        filter: function(text, prefix)
        {
            var page = this.svWhoRepliedGetCurrentPage();
            if (page === null)
            {
                this.svWhoReplied_filter(text, prefix);

                return;
            }

            this._updateStoredValue(text, prefix);
            this._toggleFilterHide(text.length > 0);

            if (this.options.ajax)
            {
                this._filterAjax(text, prefix);
            }
            else
            {
                var matched = this._applyFilter(this._getSearchRows(), text, prefix);
                this._toggleNoResults(matched === 0);
            }
        },

        svWhoRepliedOverlayShim: function()
        {
            var $pageNavWrapper = this.svWhoRepliedGetPagenavWrapper();
            if (!$pageNavWrapper)
            {
                return;
            }

            $pageNavWrapper.find('.pageNav a[href]').on('click', XF.proxy(this, 'svWhoRepliedUpdateCurrentPage'));
            XF.activate($pageNavWrapper);
        },

        /**
         * @param {Event} e
         */
        svWhoRepliedUpdateCurrentPage: function(e)
        {
            var $target = $(e.target);
            if ($target.hasClass('pageNav-jump--prev'))
            {
                $target = $target.parent()
                    .find('ul.pageNav-main > .pageNav-page--current')
                    .prev();
            }
            else if ($target.hasClass('pageNav-jump--next'))
            {
                $target = $target.parent()
                    .find('ul.pageNav-main > .pageNav-page--current')
                    .next()
                    .find('a');
            }

            if ($target.length)
            {
                var storedValue = this._getStoredValue();
                if (!storedValue || !(typeof storedValue === 'object'))
                {
                    storedValue = {
                        filter: '',
                        prefix: false,
                        page: 1
                    };
                }

                e.preventDefault();

                storedValue.page = parseInt($target.text()) || 1;
                storedValue.saved = Math.floor(new Date().getTime() / 1000);

                this.svWhoRepliedPageChanged = storedValue.page !== this.svWhoRepliedGetCurrentPage();
                this.svWhoRepliedLastPageSelected = storedValue.page;

                var data = this._readFromStorage();
                data[this.storageKey] = storedValue;
                this._writeToStorage(data);

                this.update();

                this.svWhoRepliedPageChanged = false;
                this.svWhoRepliedLastPageSelected = null;
            }
            else
            {
                console.error('No valid page link found.'); // for debugging purposes when preserve log is checked for edge/chrome settings
            }
        },

        /**
         *
         * @param {Boolean} logNotFound
         *
         * @returns {null|{length}|*|jQuery|HTMLElement}
         */
        svWhoRepliedGetPagenavWrapper: function(logNotFound)
        {
            logNotFound = logNotFound === 'undefined' ? true : logNotFound;
            if (!this.options.svWhorepliedPagenavWrapper)
            {
                return;
            }

            var oldPageNavWrapper = $(this.options.svWhorepliedPagenavWrapper);
            if (!oldPageNavWrapper.length)
            {
                if (logNotFound)
                {
                    console.error('No old pagination wrapper available');
                }

                return null;
            }

            return oldPageNavWrapper;
        },

        /**
         *
         * @returns {null|number}
         */
        svWhoRepliedGetCurrentPage: function ()
        {
            if (!this.options.svWhorepliedPagenavWrapper)
            {
                return null;
            }

            var pageNavWrapper = this.svWhoRepliedGetPagenavWrapper(false);
            if (!pageNavWrapper)
            {
                return null;
            }

            var storedValue = this._getStoredValue();
            if (!storedValue)
            {
                var lastPageSelected = parseInt(this.svWhoRepliedLastPageSelected) || null;
                if (lastPageSelected)
                {
                    return lastPageSelected;
                }

                return null;
            }

            return parseInt(storedValue.page) || 1;
        }
    });
}
(jQuery, window, document));