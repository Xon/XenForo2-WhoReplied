var SV = window.SV || {};
SV.WhoReplied = SV.WhoReplied || {};

(function($, window, document, _undefined)
{
    "use strict";

    XF.Filter = XF.extend(XF.Filter, {
        __backup: {
            '_filterAjax': 'svWhoReplied__filterAjax',
            '_filterAjaxResponse': 'svWhoReplied__filterAjaxResponse' // __ is intentional
        },

        options: $.extend({}, XF.Filter.prototype.options, {
            svPagenavWrapper: null
        }),

        _filterAjax: function(text, prefix)
        {
            this.svWhoReplied__filterAjax(text, prefix);

            if (!text.length)
            {
                XF.ajax('GET', this.options.ajax, {
                    _xfFilter: {
                        text: '',
                        prefix: 0
                    }
                }, XF.proxy(this, 'svUpdatePagination'));
            }
        },

        _filterAjaxResponse: function(result)
        {
            this.svWhoReplied__filterAjaxResponse(result);

            this.svUpdatePagination(result);
        },

        svUpdatePagination: function (result)
        {
            if (!this.options.svPagenavWrapper)
            {
                return;
            }

            var oldPageNavWrapper = $(this.options.svPagenavWrapper);
            if (!oldPageNavWrapper.length)
            {
                console.error('No old pagination wrapper available');
                return;
            }

            var $result = $($.parseHTML(result.html.content)),
                newPageNavWrapper = $result.find(this.options.svPagenavWrapper);
            if (!newPageNavWrapper.length)
            {
                oldPageNavWrapper.empty();
                return;
            }

            oldPageNavWrapper.html(newPageNavWrapper.html());
        }
    });
} (jQuery, window, document));