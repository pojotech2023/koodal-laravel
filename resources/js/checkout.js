/*
 * Razorpay Checkout — the one place this website makes an in-browser request
 * rather than a plain form post (docs/05-website-guide.md §8). Progressively
 * enhances any button carrying `data-checkout-plan-id`: without this script
 * the button still renders, it just does nothing, because there is no
 * server-only equivalent of an in-browser payment sheet — Payments §2's
 * order → sheet → verify sequence needs one script holding state across all
 * three steps.
 *
 * `window.Razorpay` comes from Razorpay's own CDN script
 * (https://checkout.razorpay.com/v1/checkout.js), loaded by whichever view
 * includes this file — it is not something to self-host or bundle.
 *
 * The `/payments/order` and `/payments/verify` responses are never treated
 * as success on their own; `/payments/verify`'s JSON is the only thing that
 * redirects the browser anywhere, same "the client never decides a payment
 * succeeded" rule as every other client against this API.
 */

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function appName() {
  return document.querySelector('meta[name="app-name"]')?.content || 'Matrimony';
}

async function postJson(url, body) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify(body),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.message || 'Something went wrong. Please try again.');
  }

  return data;
}

function showSuccess(message) {
  const errorBanner = document.getElementById('checkout-error');
  if (errorBanner) {
    errorBanner.classList.add('hidden');
  }

  const successBanner = document.getElementById('checkout-success');
  if (successBanner) {
    successBanner.textContent = message;
    successBanner.classList.remove('hidden');
  } else {
    window.alert(message);
  }
}

function showError(button, message) {
  const successBanner = document.getElementById('checkout-success');
  if (successBanner) {
    successBanner.classList.add('hidden');
  }

  const banner = document.getElementById('checkout-error');
  if (banner) {
    banner.textContent = message;
    banner.classList.remove('hidden');
  } else {
    window.alert(message);
  }

  if (button) {
    button.disabled = false;
    button.textContent = button.dataset.originalText || 'Subscribe';
  }
}

async function startCheckout(button) {
  const planId = button.dataset.checkoutPlanId;
  const planName = button.dataset.checkoutPlanName || 'Membership';
  const planDuration = button.dataset.checkoutPlanDuration;

  if (!button.dataset.originalText) {
    button.dataset.originalText = button.textContent.trim();
  }

  button.disabled = true;
  button.textContent = 'Processing...';

  const successBanner = document.getElementById('checkout-success');
  if (successBanner) successBanner.classList.add('hidden');
  const errorBanner = document.getElementById('checkout-error');
  if (errorBanner) errorBanner.classList.add('hidden');

  let order;
  try {
    order = await postJson('/payments/order', { plan_id: planId });
  } catch (e) {
    showError(button, e.message);
    return;
  }

  if (typeof window.Razorpay === 'undefined') {
    showError(button, 'Razorpay payment SDK failed to load. Please check your internet connection and refresh.');
    return;
  }

  const rzpOptions = {
    key: order.razorpay_key_id,
    amount: order.amount,
    currency: order.currency || 'INR',
    name: appName(),
    description: planDuration ? `${planName} membership · ${planDuration} days` : `${planName} membership`,
    prefill: {
      name: order.prefill?.name,
      email: order.prefill?.email || undefined,
      contact: order.prefill?.contact || undefined,
    },
    theme: { color: '#e11d48' },
    modal: {
      // The sheet closing without a payment is the user changing their mind
      ondismiss: () => {
        button.disabled = false;
        button.textContent = button.dataset.originalText || 'Subscribe';
      },
    },
    handler: async (response) => {
      showSuccess('Payment successful! Activating membership...');
      button.textContent = 'Activating...';
      try {
        const result = await postJson('/payments/verify', {
          plan_id: planId,
          payment_id: order.payment_id,
          razorpay_order_id: response.razorpay_order_id || null,
          razorpay_payment_id: response.razorpay_payment_id,
          razorpay_signature: response.razorpay_signature || null,
        });

        showSuccess(result.message || 'Payment successful! Your membership has been activated.');
        setTimeout(() => {
          window.location.href = result.redirect || '/membership';
        }, 1500);
      } catch (e) {
        showError(button, e.message);
      }
    },
  };

  if (order.razorpay_order_id) {
    rzpOptions.order_id = order.razorpay_order_id;
  }

  const rzp = new window.Razorpay(rzpOptions);

  // Razorpay calls this inside the sheet before our code sees anything —
  // without it a declined card just silently reopens the sheet.
  rzp.on('payment.failed', (failure) => {
    showError(button, failure.error?.description || 'The payment failed. No charge was made.');
  });

  rzp.open();
}

document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-checkout-plan-id]');

  if (!button) {
    return;
  }

  event.preventDefault();
  startCheckout(button);
});
