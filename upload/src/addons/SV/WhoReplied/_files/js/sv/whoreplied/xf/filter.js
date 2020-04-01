var SV = window.SV || {};
SV.WhoReplied = SV.WhoReplied || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Filter = XF.extend(XF.Filter, {
        __backup: {
            '_filterAjax': 'svWhoReplied__filterAjax',
            '_filterAjaxResponse': 'svWhoReplied__filterAjaxResponse', // __ is intentional
            'update': 'svWhoReplied_update'
        },

        options: $.extend({}, XF.Filter.prototype.options, {
            svWhorepliedExistingFilterText: '',
            svWhorepliedExistingFilterPrefix: false,
            svWhorepliedPagenavWrapper: null
        }),

        update: function()
        {
            if (this.svWhoRepliedGetPagenavWrapper())
            {
                var existingFilterText = this.options.svWhorepliedExistingFilterText,
                    existingFilterPrefix = this.options.svWhorepliedExistingFilterPrefix;

                if (this.$input.val() === existingFilterText
                    && (this.$prefix.is(':checked') ? true : false) === existingFilterPrefix
                )
                {
                    // we need set the ajax url to nothing
                    var originalAjax = this.options.ajax;
                    this.options.ajax = null;

                    this.filter(existingFilterText, existingFilterPrefix);

                    // and then restore it back
                    this.options.ajax = originalAjax;

                    return;
                }
            }

            this.svWhoReplied_update();
        },

        _filterAjax: function(text, prefix)
        {
            this.svWhoReplied__filterAjax(text, prefix);

            if (this.svWhoRepliedGetPagenavWrapper() && !text.length)
            {
                XF.ajax('GET', this.options.ajax, {
                    _xfFilter: {
                        text: '',
                        prefix: 0
                    }
                }, XF.proxy(this, 'svWhoRepliedUpdatePagination'));
            }
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