/**
 * Required for Tailwind to run at all — Vite reads this, not tailwind.config.js.
 * Without it the build succeeds and every utility class silently does nothing,
 * which looks like a CSS bug rather than a missing config file. Same trap
 * documented in web/website's copy of this file.
 */
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
};
