'use strict';

/**
 * Line for view selector.
 * This view is used to display a datagrid view line in the view-selector select2 module.
 *
 * @author    Adrien Petremann <adrien.petremann@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'jquery',
        'underscore',
        'pim/form',
        'text!pim/template/grid/view-selector-line-before'
    ],
    function (
        $,
        _,
        BaseForm,
        template
    ) {
        return BaseForm.extend({
            template: _.template(template),

            /**
             * {@inheritdoc}
             */
            render: function () {
                this.$el.html(this.template({
                    view: this.getRoot().datagridView
                }));
            }
        });
    }
);
