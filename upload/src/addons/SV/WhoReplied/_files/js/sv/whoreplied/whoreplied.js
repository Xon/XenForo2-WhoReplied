!function($, window, document, _undefined)
{
    "use strict";

    XF.WhoReplied = XF.Element.newHandler({
        options: {
        },

        init: function () {
            console.log(this.$target);
            if (this.$target.parents('.overlay-container').length) {
                this.$target.find('.pageNav a').each(
                    function() {
                        $(this).addClass('js-overlayClose');
                        $(this).attr('data-xf-click', 'overlay');
                    }
                );

                XF.activate(this.$target.children('nav'));
            }
        }
    });

    XF.Element.register('who-replied-init', 'XF.WhoReplied');
}
(jQuery, window, document);