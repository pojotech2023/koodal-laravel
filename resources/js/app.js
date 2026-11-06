import Alpine from 'alpinejs';

/*
 * Alpine only — applied sparingly per docs/05-website-guide.md §1: a mobile
 * nav toggle, a modal, a flash message that dismisses itself. Every page
 * must work with this script absent; Alpine progressively enhances a plain
 * form/link, it never is the only way to reach something.
 */

window.Alpine = Alpine;
Alpine.start();
