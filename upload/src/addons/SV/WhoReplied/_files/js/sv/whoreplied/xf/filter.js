var SV = window.SV || {};
SV.WhoReplied = SV.WhoReplied || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Filter = XF.extend(XF.Filter, {
        __backup: {
            '_filterAjax': 'svWhoReplied__filterAjax',
            '_filterAjaxResponse': 'svWhoReplied__filterAjaxResponse', // __ is intentional
            'update': 'svWhoReplied_update',
            '_updateStoredValue': 'svWhoReplied__updateStoredValue'
        },

        options: $.extend({}, XF.Filter.prototype.options, {
            svWhorepliedExistingFilterText: '',
            svWhorepliedExistingFilterPrefix: false,
            svWhorepliedPagenavWrapper: null
        }),

        xhrFilterOriginal: null,

        update: function()
        {
            if (this.svWhoRepliedGetPagenavWrapper())
            {
                var existingFilterText = this.options.svWhorepliedExistingFilterText,
                    existingFilterPrefix = this.options.svWhorepliedExistingFilterPrefix,
                    currentFilterText = this.$input.val(),
                    currentFilterPrefix = this.$prefix.is(':checked');

                this._updateStoredValue(currentFilterText, currentFilterPrefix);

                if (currentFilterText !== '' &&
                    currentFilterText === existingFilterText &&
                    currentFilterPrefix === existingFilterPrefix
                )
                {
                    this._applyFilter(this._getSearchRows(), existingFilterText, existingFilterPrefix);
                    this._toggleFilterHide(true);

                    return;
                }
            }

            this.svWhoReplied_update();
        },

        _updateStoredValue: function(val, prefix)
        {
            this.svWhoReplied__updateStoredValue(val, prefix);

            var finalUrl = new Url(window.location.href);

            if (val === '')
            {
                if ("_xfFilter[text]" in finalUrl.query)
                {
                    delete finalUrl.query["_xfFilter[text]"];
                }
                if ("_xfFilter[prefix]" in finalUrl.query)
                {
                    delete finalUrl.query["_xfFilter[prefix]"];
                }
            }
            else
            {
                finalUrl.query["_xfFilter[text]"] = val;
                finalUrl.query["_xfFilter[prefix]"] = prefix === true ? 1 : 0;
            }

            finalUrl = decodeURIComponent(finalUrl.toString());

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

        _filterAjax: function(text, prefix)
        {
            this.svWhoReplied__filterAjax(text, prefix);

            if (this.svWhoRepliedGetPagenavWrapper())
            {
                if (!text.length)
                {
                    XF.ajax('GET', this.options.ajax, {
                        _xfFilter: {
                            text: text,
                            prefix: prefix ? 1 : 0
                        }
                    }, XF.proxy(this, 'svWhoRepliedMasked_filterAjaxResponse'));
                }
            }
        },

        svWhoRepliedMasked_filterAjaxResponse: function(result)
        {
            if (!this.svWhoRepliedGetPagenavWrapper())
            {
                return;
            }

            this.xhrFilterOriginal = this.xhrFilter;
            this.xhrFilter = {
                text: '',
                prefix: 0
            };

            this._filterAjaxResponse(result);

            this.xhrFilter = this.xhrFilterOriginal;
        },

        _filterAjaxResponse: function(result)
        {
            this.svWhoReplied__filterAjaxResponse(result);

            this.svWhoRepliedUpdatePagination(result);
        },

        svWhoRepliedUpdatePagination: function (result)
        {
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
        },

        svWhoRepliedGetPagenavWrapper()
        {
            if (!this.options.svWhorepliedPagenavWrapper)
            {
                return;
            }

            var oldPageNavWrapper = $(this.options.svWhorepliedPagenavWrapper);
            if (!oldPageNavWrapper.length)
            {
                console.error('No old pagination wrapper available');
                return null;
            }

            return oldPageNavWrapper;
        }
    });
} (jQuery, window, document));