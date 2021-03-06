/**
* PLEASE DO NOT MODIFY THIS FILE. WORK ON THE ES6 VERSION.
* OTHERWISE YOUR CHANGES WILL BE REPLACED ON THE NEXT BUILD.
**/

/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

(function (tinyMCE, Joomla, window, document) {
  'use strict';

  Joomla.JoomlaTinyMCE = {
    /**
     * Find all TinyMCE elements and initialize TinyMCE instance for each
     *
     * @param {HTMLElement}  target  Target Element where to search for the editor element
     *
     * @since 3.7.0
     */
    setupEditors: function setupEditors(target) {
      var container = target || document;
      var pluginOptions = Joomla.getOptions ? Joomla.getOptions('plg_editor_tinymce', {}) : Joomla.optionsStorage.plg_editor_tinymce || {};
      var editors = [].slice.call(container.querySelectorAll('.js-editor-tinymce'));

      editors.forEach(function (editor) {
        var currentEditor = editor.querySelector('textarea');
        Joomla.JoomlaTinyMCE.setupEditor(currentEditor, pluginOptions);
      });
    },

    /**
     * Initialize TinyMCE editor instance
     *
     * @param {HTMLElement}  element
     * @param {Object}       pluginOptions
     *
     * @since 3.7.0
     */
    setupEditor: function setupEditor(element, pluginOptions) {
      // Check whether the editor already has ben set
      if (Joomla.editors.instances[element.id]) {
        return;
      }

      var name = element ? element.getAttribute('name').replace(/\[\]|\]/g, '').split('[').pop() : 'default'; // Get Editor name
      var tinyMCEOptions = pluginOptions ? pluginOptions.tinyMCE || {} : {};
      var defaultOptions = tinyMCEOptions.default || {};
      // Check specific options by the name
      var options = tinyMCEOptions[name] ? tinyMCEOptions[name] : defaultOptions;

      // Avoid an unexpected changes, and copy the options object
      if (options.joomlaMergeDefaults) {
        options = Joomla.extend(Joomla.extend({}, defaultOptions), options);
      } else {
        options = Joomla.extend({}, options);
      }

      if (element) {
        // We already have the Target, so reset the selector and assign given element as target
        options.selector = null;
        options.target = element;
      }

      var buttonValues = [];
      var arr = Object.keys(options.joomlaExtButtons.names).map(function (key) {
        return options.joomlaExtButtons.names[key];
      });

      arr.forEach(function (xtdButton) {
        var tmp = {};
        tmp.text = xtdButton.name;
        tmp.icon = xtdButton.icon;

        if (xtdButton.href) {
          tmp.onclick = function () {
            document.getElementById(xtdButton.id + 'Modal').open();
          };
        } else {
          tmp.onclick = function () {
            // eslint-disable-next-line no-new-func
            new Function(xtdButton.click)();
          };
        }

        buttonValues.push(tmp);
      });

      options.setup = function (editor) {
        editor.addButton('jxtdbuttons', {
          type: 'menubutton',
          text: Joomla.JText._('PLG_TINY_CORE_BUTTONS'),
          icon: 'none icon-joomla',
          menu: buttonValues
        });
      };

      // Create a new instance
      // eslint-disable-next-line no-undef
      var ed = new tinyMCE.Editor(element.id, options, tinymce.EditorManager);
      ed.render();

      /** Register the editor's instance to Joomla Object */
      Joomla.editors.instances[element.id] = {
        // Required by Joomla's API for the XTD-Buttons
        getValue: function getValue() {
          return Joomla.editors.instances[element.id].instance.getContent();
        },
        setValue: function setValue(text) {
          return Joomla.editors.instances[element.id].instance.setContent(text);
        },
        replaceSelection: function replaceSelection(text) {
          return Joomla.editors.instances[element.id].instance.execCommand('mceInsertContent', false, text);
        },
        // Some extra instance dependent
        id: element.id,
        instance: ed,
        onSave: function onSave() {
          if (Joomla.editors.instances[element.id].instance.isHidden()) {
            Joomla.editors.instances[element.id].instance.show();
          }return '';
        }
      };

      /** On save * */
      document.getElementById(ed.id).form.addEventListener('submit', function () {
        return Joomla.editors.instances[ed.targetElm.id].onSave();
      });
    }

  };

  /**
   * Initialize at an initial page load
   */
  document.addEventListener('DOMContentLoaded', function () {
    Joomla.JoomlaTinyMCE.setupEditors(document);
  });

  /**
   * Initialize when a part of the page was updated
   */
  document.addEventListener('joomla:updated', function (event) {
    return Joomla.JoomlaTinyMCE.setupEditors(event.target);
  });
})(window.tinyMCE, Joomla, window, document);
