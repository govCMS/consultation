/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

(function ($, window, Drupal) {

  'use strict';

  Drupal.behaviors.formalSubmissionToggle = {
    attach: function() {
      $('.submission-show-form').click(function() {
        $('.submission-form-wrapper').show();
        $(this).hide();
      });
    }
  }

})(jQuery, window, Drupal);

