/**
 * utils/socialIcons.js — Social Link → Iconify Icon Resolver
 * ───────────────────────────────────────────────────────────
 * Returns an Iconify icon name (e.g. "simple-icons:github") for a given
 * social link object. The components render these with <Icon> from @iconify/vue.
 *
 * Resolution order:
 *   1. link.icon   — admin's explicit Iconify name (if set)
 *   2. URL scheme   — mailto: → mail icon, tel: → phone icon
 *   3. URL hostname — match domain in HOSTNAME_ICON_MAP (ignores "www.")
 *   4. Link name    — match name in NAME_ICON_MAP (case-insensitive, trimmed)
 *   5. Fallback     — "lucide:globe"
 *
 * Used by: components/ui/SocialLinks.vue
 */

/* ── Hostname → Iconify icon ─────────────────────────────────── */

/**
 * Maps domain names (without "www.") to Iconify icon identifiers.
 * Subdomains of these domains are also matched (e.g. "gist.github.com").
 * @type {Record<string, string>}
 */
const HOSTNAME_ICON_MAP = {
  // Code platforms
  'github.com':        'simple-icons:github',
  'gitlab.com':        'simple-icons:gitlab',
  'codepen.io':        'simple-icons:codepen',
  'dev.to':            'simple-icons:devdotto',
  'stackoverflow.com': 'simple-icons:stackoverflow',

  // Social / professional
  'linkedin.com':      'simple-icons:linkedin',
  'x.com':             'simple-icons:x',
  'twitter.com':       'simple-icons:x',
  'facebook.com':      'simple-icons:facebook',
  'fb.com':            'simple-icons:facebook',
  'instagram.com':     'simple-icons:instagram',
  'youtube.com':       'simple-icons:youtube',
  'youtu.be':          'simple-icons:youtube',
  'tiktok.com':        'simple-icons:tiktok',
  'discord.gg':        'simple-icons:discord',
  'discord.com':       'simple-icons:discord',
  'reddit.com':        'simple-icons:reddit',

  // Messaging
  't.me':              'simple-icons:telegram',
  'telegram.me':       'simple-icons:telegram',
  'telegram.org':      'simple-icons:telegram',
  'wa.me':             'simple-icons:whatsapp',
  'whatsapp.com':      'simple-icons:whatsapp',

  // Design
  'behance.net':       'simple-icons:behance',
  'dribbble.com':      'simple-icons:dribbble',
  'figma.com':         'simple-icons:figma',

  // Writing / content
  'medium.com':        'simple-icons:medium',
  'hashnode.com':      'simple-icons:hashnode',
  'substack.com':      'simple-icons:substack',

  // Freelance
  'fiverr.com':        'simple-icons:fiverr',
  'upwork.com':        'simple-icons:upwork',
}

/* ── Name → Iconify icon (fallback when URL doesn't match) ───── */

/**
 * Maps common link names (lowercase) to Iconify icons.
 * Used when the URL hostname doesn't match any known platform.
 * @type {Record<string, string>}
 */
const NAME_ICON_MAP = {
  'github':       'simple-icons:github',
  'gitlab':       'simple-icons:gitlab',
  'linkedin':     'simple-icons:linkedin',
  'twitter':      'simple-icons:x',
  'x':            'simple-icons:x',
  'telegram':     'simple-icons:telegram',
  'whatsapp':     'simple-icons:whatsapp',
  'facebook':     'simple-icons:facebook',
  'instagram':    'simple-icons:instagram',
  'youtube':      'simple-icons:youtube',
  'tiktok':       'simple-icons:tiktok',
  'discord':      'simple-icons:discord',
  'reddit':       'simple-icons:reddit',
  'behance':      'simple-icons:behance',
  'dribbble':     'simple-icons:dribbble',
  'figma':        'simple-icons:figma',
  'medium':       'simple-icons:medium',
  'dev.to':       'simple-icons:devdotto',
  'stackoverflow':'simple-icons:stackoverflow',
  'stack overflow':'simple-icons:stackoverflow',
  'codepen':      'simple-icons:codepen',
  'fiverr':       'simple-icons:fiverr',
  'upwork':       'simple-icons:upwork',
  'hashnode':     'simple-icons:hashnode',
  'substack':     'simple-icons:substack',
  'email':        'lucide:mail',
  'mail':         'lucide:mail',
  'phone':        'lucide:phone',
}

/** Default fallback icon when nothing matches */
const FALLBACK_ICON = 'lucide:globe'

/* ── Helpers ─────────────────────────────────────────────────── */

/**
 * Extract the hostname from a URL string, stripping the "www." prefix.
 * Returns null if the URL is invalid or has no hostname.
 *
 * @param {string} url
 * @returns {string|null}
 */
function extractHostname(url) {
  try {
    const hostname = new URL(url).hostname.toLowerCase()
    return hostname.replace(/^www\./, '')
  } catch {
    return null
  }
}

/**
 * Match a hostname against HOSTNAME_ICON_MAP, also checking parent domains
 * so that "gist.github.com" matches "github.com".
 *
 * @param {string} hostname — normalised, no "www."
 * @returns {string|null} — Iconify icon name, or null
 */
function matchHostname(hostname) {
  // Direct match first (fastest path)
  if (HOSTNAME_ICON_MAP[hostname]) return HOSTNAME_ICON_MAP[hostname]

  // Check if hostname is a subdomain of a known domain
  // e.g. "gist.github.com" ends with ".github.com"
  for (const [domain, icon] of Object.entries(HOSTNAME_ICON_MAP)) {
    if (hostname.endsWith(`.${domain}`)) return icon
  }

  return null
}

/* ── Main export ─────────────────────────────────────────────── */

/**
 * Resolve the Iconify icon name for a social link object.
 *
 * @param {{ icon?: string|null, url?: string, name?: string }} link
 *   A social link object from the API. Fields:
 *   - icon: admin-chosen Iconify name (e.g. "simple-icons:telegram"), may be null
 *   - url:  link URL (e.g. "https://github.com/user"), may be missing
 *   - name: display name (e.g. "GitHub"), may be missing
 *
 * @returns {string} — An Iconify icon identifier, never empty
 *
 * @example
 *   getSocialIcon({ url: 'https://github.com/user' })
 *   // → "simple-icons:github"
 *
 *   getSocialIcon({ icon: 'simple-icons:telegram' })
 *   // → "simple-icons:telegram"
 *
 *   getSocialIcon({ name: 'LinkedIn' })
 *   // → "simple-icons:linkedin"
 */
export function getSocialIcon(link) {
  if (!link) return FALLBACK_ICON

  // 1. Admin's explicit icon choice takes priority
  if (link.icon) return link.icon

  const url = (link.url || '').trim()

  // 2. URL scheme detection (mailto: / tel: / plain email address)
  if (url.startsWith('mailto:')) return 'lucide:mail'
  if (url.startsWith('tel:'))    return 'lucide:phone'
  // A bare email address (no scheme) — e.g. "user@example.com"
  if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(url)) return 'lucide:mail'

  // 3. Hostname-based lookup
  const hostname = extractHostname(url)
  if (hostname) {
    const hostIcon = matchHostname(hostname)
    if (hostIcon) return hostIcon
  }

  // 4. Name-based lookup (trimmed, case-insensitive)
  const name = (link.name || '').trim().toLowerCase()
  if (name && NAME_ICON_MAP[name]) return NAME_ICON_MAP[name]

  // 5. Fallback
  return FALLBACK_ICON
}

/**
 * Check if a social link is an email entry (mailto: URL, name "email"/"mail",
 * or a plain email address as the URL).
 *
 * @param {{ url?: string, name?: string }} link
 * @returns {boolean}
 */
export function isEmailLink(link) {
  if (!link) return false
  const url = (link.url || '').trim().toLowerCase()
  const name = (link.name || '').trim().toLowerCase()
  return (
    url.startsWith('mailto:') ||
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(url) ||
    name === 'email' ||
    name === 'mail'
  )
}
