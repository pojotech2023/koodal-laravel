import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/*
 * Layered on top of the plain-form send/reload path — docs/05-website-guide.md
 * §7. Without this script the messages page still works end to end: sending
 * posts and reloads, opening a thread is a page load. This only makes new
 * messages/receipts appear without the other participant refreshing.
 *
 * Deliberately does not hand-render an incoming bubble from the broadcast
 * payload — that would be a second implementation of the Blade thread markup
 * in JS. Instead every live update (socket or poll) re-fetches the same
 * `/messages/{conversation}` page the browser already knows how to render and
 * swaps in its `#chat-thread` — one markup source, same as every other page
 * on this site.
 *
 * `window.__chatConfig`, set by messages/index.blade.php, is built from
 * config('reverb.*') server-side — no `GET /chat/config` round trip, since
 * this page is already a server response.
 */

const config = window.__chatConfig;

if (config) {
  window.Pusher = Pusher;

  const echo = new Echo({
    broadcaster: 'reverb',
    key: config.key,
    wsHost: config.host,
    wsPort: config.port,
    wssPort: config.port,
    forceTLS: config.scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: config.authEndpoint,
    auth: {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
    },
  });

  let pollTimer = null;

  async function refreshThread() {
    let html;
    try {
      html = await (await fetch(config.pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).text();
    } catch {
      // Offline or the request otherwise failed — the next poll or socket
      // event tries again. docs/10-chat-realtime.md §12: chat never depends
      // on this for correctness, only for speed.
      return;
    }

    const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('chat-thread');
    const current = document.getElementById('chat-thread');

    if (!fresh || !current) {
      return;
    }

    const wasAtBottom = current.scrollTop + current.clientHeight >= current.scrollHeight - 40;
    current.innerHTML = fresh.innerHTML;

    if (wasAtBottom) {
      current.scrollTop = current.scrollHeight;
    }
  }

  function startPolling() {
    if (!pollTimer) {
      pollTimer = window.setInterval(refreshThread, 10000);
    }
  }

  function stopPolling() {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }

  echo.private(`conversation.${config.conversationId}`)
    .listen('.message.sent', refreshThread)
    .listen('.message.read', refreshThread);

  const connection = echo.connector.pusher.connection;
  let everConnected = false;

  connection.bind('connected', () => {
    everConnected = true;
    stopPolling();
  });

  connection.bind('unavailable', startPolling);
  connection.bind('failed', startPolling);

  // Socket never came up at all — same 10s fallback as a socket that dropped.
  window.setTimeout(() => {
    if (!everConnected) {
      startPolling();
    }
  }, 5000);

  const thread = document.getElementById('chat-thread');
  if (thread) {
    thread.scrollTop = thread.scrollHeight;
  }
}
