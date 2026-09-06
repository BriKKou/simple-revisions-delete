/**
 * Simple Revisions Delete - block editor.
 *
 * Registers an editor plugin rendering a "Purge" control in the post status
 * panel. It relies on the plugin REST routes, the editor data store and the
 * editor notices, so no DOM manipulation is involved.
 *
 * @package Simple_Revisions_Delete
 */

( function ( wp, settings ) {
  'use strict';

  if ( ! wp || ! wp.plugins || ! wp.element || ! wp.data || ! wp.apiFetch || ! settings ) {
    return;
  }

  var editorModule = wp.editor || {};
  var editPostModule = wp.editPost || {};
  var PluginPostStatusInfo = editorModule.PluginPostStatusInfo || editPostModule.PluginPostStatusInfo;

  if ( ! PluginPostStatusInfo ) {
    return;
  }

  var el = wp.element.createElement;
  var useState = wp.element.useState;
  var useEffect = wp.element.useEffect;
  var useRef = wp.element.useRef;
  var useSelect = wp.data.useSelect;
  var useDispatch = wp.data.useDispatch;
  var Button = wp.components.Button;
  var apiFetch = wp.apiFetch;
  var i18n = settings.i18n;

  /**
   * Replaces the %s placeholder of a translated string.
   *
   * @param {string} text  Translated string.
   * @param {number} value Value to inject.
   * @return {string} Formatted string.
   */
  function format( text, value ) {
    return String( text ).replace( '%s', String( value ) );
  }

  /**
   * Announces a message to assistive technologies.
   *
   * @param {string} message Message to announce.
   * @return {void}
   */
  function announce( message ) {
    if ( wp.a11y && wp.a11y.speak ) {
      wp.a11y.speak( message );
    }
  }

  /**
   * Renders the revisions purge control in the post status panel.
   *
   * @return {Object|null} Element to render, or null when there is nothing to purge.
   */
  function PurgeRevisionsControl() {
    var postId = useSelect( function ( select ) {
      return select( 'core/editor' ).getCurrentPostId();
    }, [] );

    var isSaving = useSelect( function ( select ) {
      var editor = select( 'core/editor' );

      return editor.isSavingPost() && ! editor.isAutosavingPost();
    }, [] );

    var lastRevisionId = useSelect( function ( select ) {
      return select( 'core/editor' ).getCurrentPostLastRevisionId();
    }, [] );

    var notices = useDispatch( 'core/notices' );

    var countState = useState( 0 );
    var count = countState[ 0 ];
    var setCount = countState[ 1 ];

    var busyState = useState( false );
    var isBusy = busyState[ 0 ];
    var setBusy = busyState[ 1 ];

    var wasSaving = useRef( false );

    /**
     * Reads the current revision count from the REST API.
     *
     * @return {void}
     */
    function refresh() {
      if ( ! postId ) {
        return;
      }

      apiFetch( { path: '/wpsrd/v1/posts/' + postId + '/revisions' } )
        .then( function ( response ) {
          setCount( response.count );
        } )
        .catch( function () {
          setCount( 0 );
        } );
    }

    useEffect( refresh, [ postId ] );

    useEffect( function () {
      if ( wasSaving.current && ! isSaving ) {
        refresh();
      }

      wasSaving.current = isSaving;
    }, [ isSaving ] );

    /**
     * Deletes every revision of the edited post.
     *
     * @return {void}
     */
    function onPurge() {
      setBusy( true );

      apiFetch( { path: '/wpsrd/v1/posts/' + postId + '/revisions', method: 'DELETE' } )
        .then( function ( response ) {
          var message = response.deleted
            ? format( 1 === response.deleted ? i18n.purgedOne : i18n.purgedMany, response.deleted )
            : i18n.none;

          setCount( response.count );
          announce( message );
          notices.createSuccessNotice( message, { type: 'snackbar' } );
        } )
        .catch( function ( error ) {
          var message = ( error && error.message ) || i18n.error;

          announce( message );
          notices.createErrorNotice( message, { type: 'snackbar' } );
        } )
        .then( function () {
          setBusy( false );
        } );
    }

    if ( ! postId || ! count ) {
      return null;
    }

    var countLabel = format( 1 === count ? i18n.countOne : i18n.countMany, count );

    return el(
      PluginPostStatusInfo,
      { className: 'wpsrd-post-status-info' },
      el( 'span', { className: 'wpsrd-post-status-info__label' }, i18n.label ),
      el(
        'span',
        { className: 'wpsrd-post-status-info__actions' },
        lastRevisionId
          ? el(
              Button,
              {
                variant: 'link',
                href: 'revision.php?revision=' + lastRevisionId,
                className: 'wpsrd-post-status-info__count'
              },
              countLabel
            )
          : el( 'span', { className: 'wpsrd-post-status-info__count' }, countLabel ),
        el(
          Button,
          {
            variant: 'link',
            isDestructive: true,
            isBusy: isBusy,
            disabled: isBusy,
            'aria-disabled': isBusy,
            className: 'wpsrd-post-status-info__purge',
            onClick: onPurge
          },
          isBusy ? i18n.purging : i18n.purge
        )
      )
    );
  }

  wp.plugins.registerPlugin( 'wpsrd-purge-revisions', {
    render: PurgeRevisionsControl
  } );
} )( window.wp, window.wpsrdBlockEditor );
