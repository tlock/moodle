YUI.add('moodle-form-showadvanced', function(Y) {
    /**
     * Provides the form showadvanced class.
     *
     * @module moodle-form-showadvanced
     */

    /**
     * A class for a showadvanced.
     *
     * @param {Object} config Object literal specifying showadvanced configuration properties.
     * @class M.form.showadvanced
     * @constructor
     * @extends Y.Base
     */
    function SHOWADVANCED(config) {
        SHOWADVANCED.superclass.constructor.apply(this, [config]);
    }

    var SELECTORS = {
            FIELDSETCONTAINSADVANCED : 'fieldset.containsadvancedelements',
            DIVFITEMADVANCED : 'div.fitem.advanced',
            DIVFCONTAINER : 'div.fcontainer'
        },
        CSS = {
            HIDE : 'hide',
            MORELESSTOGGLER : 'morelesstoggler'
        },
        ATTRS = {};

    /**
     * Static property provides a string to identify the JavaScript class.
     *
     * @property NAME
     * @type String
     * @static
     */
    SHOWADVANCED.NAME = 'moodle-form-showadvanced';

    /**
     * Static property used to define the default attribute configuration for the Showadvanced.
     *
     * @property ATTRS
     * @type String
     * @static
     */
    SHOWADVANCED.ATTRS = ATTRS;

    /**
     * The form ID attribute definition.
     *
     * @attribute formid
     * @type String
     * @default ''
     * @writeOnce
     */
    ATTRS.formid = {
        value : null
    };

    Y.extend(SHOWADVANCED, Y.Base, {
        initializer : function() {
            var fieldlist = Y.Node.all('#'+this.get('formid')+' '+SELECTORS.FIELDSETCONTAINSADVANCED);
            // Look through fieldset divs that contain advanced elements.
            fieldlist.each(this.process_fieldset, this);
            // Subscribe more/less links to click event.
            Y.one('#'+this.get('formid')).delegate('click', this.switch_state, SELECTORS.FIELDSETCONTAINSADVANCED+' .'+CSS.MORELESSTOGGLER);
        },
        process_fieldset : function(fieldset) {
            var statuselement = new Y.one('input[name=mform_showmore_'+fieldset.get('id')+']');
            var morelesslink = Y.Node.create('<a href="#"></a>');
            morelesslink.addClass(CSS.MORELESSTOGGLER);
            if (statuselement.get('value') === '0') {
                morelesslink.setHTML(M.str.form.showmore);
                // Hide advanced stuff initially.
                fieldset.all(SELECTORS.DIVFITEMADVANCED).addClass(CSS.HIDE);
            } else {
                morelesslink.setHTML(M.str.form.showless);
            }
            fieldset.one(SELECTORS.DIVFCONTAINER).append(morelesslink);
        },
        switch_state : function(e) {
            e.preventDefault();
            var fieldset = this.ancestor(SELECTORS.FIELDSETCONTAINSADVANCED);
            // Toggle collapsed class.
            fieldset.all(SELECTORS.DIVFITEMADVANCED).toggleClass(CSS.HIDE);
            // Get corresponding hidden variable.
            var statuselement = new Y.one('input[name=mform_showmore_'+fieldset.get('id')+']');
            // Invert it and change the link text.
            if (statuselement.get('value') === '0') {
                statuselement.set('value', 1);
                this.setHTML(M.util.get_string('showless', 'form'));
            } else {
                statuselement.set('value', 0);
                this.setHTML(M.util.get_string('showmore', 'form'));
            }
        }
    });

    M.form = M.form || {};
    M.form.showadvanced = M.form.showadvanced || function(params) {
        return new SHOWADVANCED(params);
    };
}, '@VERSION@', {requires:['base', 'node', 'selector-css3']});
