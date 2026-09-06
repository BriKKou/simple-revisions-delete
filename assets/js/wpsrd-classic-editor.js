/**
 * Simple Revisions Delete - classic editor.
 *
 * Moves the purge control next to the core revisions counter, turns its form
 * submission into a REST call, and adds a delete button to each revision of
 * the revisions metabox.
 *
 * @package Simple_Revisions_Delete
 */

( function ( wp, settings ) {
  'use strict';

  if ( ! wp || ! wp.apiFetch || ! settings ) {
    return;
  }

  var apiFetch = wp.apiFetch;
  var i18n = settings.i18n;
  var basePath = '/wpsrd/v1/posts/' + parseInt( settings.postId, 10 ) + '/revisions';

  var control = document.getElementById( 'wpsrd-purge' );
  var form = document.getElementById( 'wpsrd-purge-form' );
  var revisionsRow = document.querySelector( '.misc-pub-revisions' );
  var revisionsBox = document.getElementById( 'revisionsdiv' );
  var status = control ? control.querySelector( '.wpsrd-purge__status' ) : null;
  var spinner = control ? control.querySelector( '.wpsrd-purge__spinner' ) : null;
  var button = control ? control.querySelector( '.wpsrd-purge__button' ) : null;

  /**
   * Announces a message to assistive technologies only.
   *
   * @param {string}  message Message to announce.
   * @param {boolean} isError Whether the message reports a failure.
   * @return {void}
   */
  function speak( message, isError ) {
    if ( wp.a11y && wp.a11y.speak ) {
      wp.a11y.speak( message, isError ? 'assertive' : 'polite' );
    }
  }

  /**
   * Announces a message and displays it next to the revisions counter.
   *
   * Only the purge outcome and the errors are shown: deleting a single
   * revision speaks for itself through the updated counter.
   *
   * @param {string}  message Message to display.
   * @param {boolean} isError Whether the message reports a failure.
   * @return {void}
   */
  function announce( message, isError ) {
    speak( message, isError );

    if ( ! status ) {
      return;
    }

    status.textContent = message;
    status.classList.toggle( 'is-error', !! isError );
    status.classList.toggle( 'is-success', ! isError );
  }

  /**
   * Removes the purge control, keeping the status message in place.
   *
   * @return {void}
   */
  function removePurgeButton() {
    if ( button ) {
      button.parentNode.removeChild( button );
      button = null;
    }

    if ( spinner ) {
      spinner.parentNode.removeChild( spinner );
      spinner = null;
    }
  }

  /**
   * Reflects the current revision count in the publish metabox.
   *
   * @param {number} count Remaining revisions.
   * @return {void}
   */
  function updateCount( count ) {
    var counter = revisionsRow ? revisionsRow.querySelector( 'b' ) : null;

    if ( counter ) {
      counter.textContent = String( count );
    }

    if ( count > 0 ) {
      return;
    }

    removePurgeButton();

    if ( revisionsBox ) {
      revisionsBox.hidden = true;
    }

    var browseLink = revisionsRow ? revisionsRow.querySelector( 'a' ) : null;

    if ( browseLink ) {
      browseLink.hidden = true;
    }
  }

  /**
   * Extracts the revision ID carried by a revisions metabox list item.
   *
   * @param {HTMLElement} item List item to inspect.
   * @return {number} Revision ID, or 0 when the item is not a revision.
   */
  function getRevisionId( item ) {
    var link = item.querySelector( 'a[href*="revision="]' );

    if ( ! link ) {
      return 0;
    }

    var matches = link.href.match( /[?&]revision=(\d+)/ );

    return matches ? parseInt( matches[ 1 ], 10 ) : 0;
  }

  /**
   * Moves the focus to the closest remaining control after a deletion.
   *
   * @param {HTMLElement} item Removed list item.
   * @return {void}
   */
  function moveFocusAfter( item ) {
    var next = item.nextElementSibling || item.previousElementSibling;
    var target = next ? next.querySelector( '.wpsrd-single-delete' ) : null;

    if ( ! target ) {
      target = button;
    }

    if ( target ) {
      target.focus();
    }
  }

  /**
   * Purges every revision of the post.
   *
   * @param {Event} event Submit event of the fallback form.
   * @return {void}
   */
  function onPurgeSubmit( event ) {
    event.preventDefault();

    if ( ! button || button.disabled ) {
      return;
    }

    button.disabled = true;
    button.textContent = i18n.purging;

    if ( spinner ) {
      spinner.classList.add( 'is-active' );
    }

    apiFetch( { path: basePath, method: 'DELETE' } )
      .then( function ( response ) {
        removePurgeButton();
        updateCount( response.count );
        announce( i18n.purged, false );
      } )
      .catch( function ( error ) {
        if ( spinner ) {
          spinner.classList.remove( 'is-active' );
        }

        button.disabled = false;
        button.textContent = i18n.purge;
        announce( ( error && error.message ) || i18n.error, true );
      } );
  }

  /**
   * Deletes a single revision.
   *
   * @param {HTMLElement} item       List item holding the revision.
   * @param {number}      revisionId Revision to delete.
   * @param {HTMLElement} trigger    Clicked button.
   * @return {void}
   */
  function deleteRevision( item, revisionId, trigger ) {
    trigger.disabled = true;
    trigger.textContent = i18n.deleting;

    apiFetch( { path: basePath + '/' + revisionId, method: 'DELETE' } )
      .then( function ( response ) {
        speak( i18n.deleted, false );
        moveFocusAfter( item );
        item.parentNode.removeChild( item );
        updateCount( response.count );
      } )
      .catch( function ( error ) {
        trigger.disabled = false;
        trigger.textContent = i18n.deleteButton;
        announce( ( error && error.message ) || i18n.error, true );
      } );
  }

  /**
   * Adds a delete button to every revision of the revisions metabox.
   *
   * @return {void}
   */
  function setupSingleDeleteButtons() {
    if ( ! revisionsBox ) {
      return;
    }

    var items = revisionsBox.querySelectorAll( '.post-revisions > li' );

    Array.prototype.forEach.call( items, function ( item ) {
      var revisionId = getRevisionId( item );

      if ( ! revisionId ) {
        return;
      }

      var label = item.textContent.replace( /\s+/g, ' ' ).trim();
      var trigger = document.createElement( 'button' );

      trigger.type = 'button';
      trigger.className = 'button-link wpsrd-single-delete';
      trigger.textContent = i18n.deleteButton;
      trigger.setAttribute( 'aria-label', i18n.deleteLabel.replace( '%s', label ) );

      trigger.addEventListener( 'click', function () {
        deleteRevision( item, revisionId, trigger );
      } );

      item.appendChild( document.createTextNode( ' ' ) );
      item.appendChild( trigger );
    } );
  }

  if ( control && button ) {
    i18n.purge = button.textContent.trim();

    if ( revisionsRow ) {
      revisionsRow.appendChild( control );
    }

    control.hidden = false;
  }

  if ( form ) {
    form.addEventListener( 'submit', onPurgeSubmit );
  }

  setupSingleDeleteButtons();
} )( window.wp, window.wpsrdClassic );
