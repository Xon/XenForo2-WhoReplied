var SV = window.SV || {};
SV.WhoReplied = SV.WhoReplied || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Filter = XF.extend(XF.Filter, {
        __backup: {
            'init': 'svWhoReplied__init',
            '_filterAjax': 'svWhoReplied__filterAjax',
            '_filter': 'svWhoReplied_filter',
            '_filterAjaxResponse': 'svWhoReplied__filterAjaxResponse',
            '_getStoredValue': 'svWhoReplied__getStoredValue'
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
        },

        /**
         * @returns {Object}
         *
         * @private
         */
        _getStoredValue: function()
        {
            var storedValue = this.svWhoReplied__getStoredValue();

            if (storedValue && typeof storedValue === 'object')
            {
                var data = this._readFromStorage();
                if (data[this.storageKey])
                {
                    var record = data[this.storageKey],
                        tsSaved = record.saved || 0,
                        tsNow = Math.floor(new Date().getTime() / 1000);

                    if (tsSaved + this.storageCutOff >= tsNow)
                    {
                        storedValue.page = parseInt(record.page) || 1;
                    }
                }
            }

            return storedValue;
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
            this._toggleFilterHide(text.length > 0 && !this.svWhoRepliedPageChanged);

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
            if (!this.inOverlay)
            {
                return;
            }

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
            e.preventDefault();

            var storedValue = this._getStoredValue();
            if (!storedValue || !(typeof storedValue === 'object'))
            {
                storedValue = {
                    filter: '',
                    prefix: false
                };
            }

            storedValue.page = parseInt($(e.target).text()) || 1;
            storedValue.saved = Math.floor(new Date().getTime() / 1000);

            this.svWhoRepliedPageChanged = storedValue.page !== this.svWhoRepliedGetCurrentPage();
            this.svWhoRepliedLastPageSelected = storedValue.page;

            var data = this._readFromStorage();
            data[this.storageKey] = storedValue;
            this._writeToStorage(data);

            this.update();

            this.svWhoRepliedPageChanged = false;
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

            var storedValue = this._getStoredValue()
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