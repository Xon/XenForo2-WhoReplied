var SV = window.SV || {};
SV.WhoReplied = SV.WhoReplied || {};

!function($, window, document, _undefined)
{
	"use strict";

	// ################################## TICKET MANAGE HANDLER ###########################################

    SV.WhoReplied.DropdownSubmit = XF.Element.newHandler({
		options: {
			submitUrl: '',
            contentWrapper: null,
		},

		$form: null,
		href: null,

		changeTimer: null,
		xhr: null,

		init: function()
		{
			var $form = this.$target.closest('form'),
                href = $form.data('submit-url') || this.options.submitUrl || this.$target.attr('href');

			if (!href)
			{
				console.error('Form manage must have a href');
				return;
			}

            if (!this.options.contentWrapper)
            {
                console.error('Target must have a wrapper');
                return;
            }

			this.href = href;
			this.$form = $form;

			this.$target.on('change', XF.proxy(this, 'change'));
		},

		change: function()
		{
			if (this.changeTimer)
			{
				clearTimeout(this.changeTimer);
			}

			if (this.xhr)
			{
				this.xhr.abort();
				this.xhr = null;
			}

			this.changeTimer = setTimeout(XF.proxy(this, 'onTimer'), 200);
		},

		onTimer: function()
		{
			var value = this.$target.val();

			if (!value)
			{
				return;
			}

			var formData = XF.getDefaultFormData(this.$form),
				currentUrl = new Url(this.href);
            currentUrl.query['per_page'] = formData.get('per_page');

            var finalUrl = currentUrl.toString();

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

			this.xhr = XF.ajax('post', this.href, formData, XF.proxy(this, 'onLoad'));
		},

		onLoad: function(result)
		{
			this.xhr = null;

            var $result = $($.parseHTML(result.html.content)),
                oldContentWrapper = $(this.options.contentWrapper),
                self = this
            ;
            $result.each(function ()
            {
                var newContentWrapper = $(this)
                if (newContentWrapper.is(self.options.contentWrapper))
                {
                    oldContentWrapper.html(newContentWrapper.html());
                    XF.activate(oldContentWrapper)
                }
            })
		}
	});

	XF.Element.register('sv-whoreplied-dropdown-submit', 'SV.WhoReplied.DropdownSubmit');
}
(jQuery, window, document);