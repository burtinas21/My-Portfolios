/**
 * utils/socialIcons.js — Social Link → Iconify Icon Resolver
 * ───────────────────────────────────────────────────────────
 * Resolves explicit icons first, then URL schemes, hostnames, and link names.
 */

const PLATFORM_ICONS = [
  { icon: 'simple-icons:gmail', hosts: ['gmail.com', 'googlemail.com', 'mail.google.com'], names: ['gmail', 'email', 'google mail'] },
  { icon: 'simple-icons:github', hosts: ['github.com'], names: ['github'] },
  { icon: 'simple-icons:gitlab', hosts: ['gitlab.com'], names: ['gitlab'] },
  { icon: 'simple-icons:linkedin', hosts: ['linkedin.com'], names: ['linkedin'] },
  { icon: 'simple-icons:x', hosts: ['x.com', 'twitter.com'], names: ['x', 'twitter'] },
  { icon: 'simple-icons:telegram', hosts: ['t.me', 'telegram.me', 'telegram.org'], names: ['telegram'] },
  { icon: 'simple-icons:whatsapp', hosts: ['wa.me', 'whatsapp.com'], names: ['whatsapp'] },
  { icon: 'simple-icons:facebook', hosts: ['facebook.com', 'fb.com'], names: ['facebook'] },
  { icon: 'simple-icons:instagram', hosts: ['instagram.com'], names: ['instagram'] },
  { icon: 'simple-icons:youtube', hosts: ['youtube.com', 'youtu.be'], names: ['youtube'] },
  { icon: 'simple-icons:tiktok', hosts: ['tiktok.com'], names: ['tiktok'] },
  { icon: 'simple-icons:discord', hosts: ['discord.com', 'discord.gg'], names: ['discord'] },
  { icon: 'simple-icons:behance', hosts: ['behance.net'], names: ['behance'] },
  { icon: 'simple-icons:dribbble', hosts: ['dribbble.com'], names: ['dribbble'] },
  { icon: 'simple-icons:medium', hosts: ['medium.com'], names: ['medium'] },
  { icon: 'simple-icons:devdotto', hosts: ['dev.to'], names: ['dev.to'] },
  { icon: 'simple-icons:stackoverflow', hosts: ['stackoverflow.com'], names: ['stackoverflow', 'stack overflow'] },
  { icon: 'simple-icons:codepen', hosts: ['codepen.io'], names: ['codepen'] },
  { icon: 'simple-icons:fiverr', hosts: ['fiverr.com'], names: ['fiverr'] },
  { icon: 'simple-icons:upwork', hosts: ['upwork.com'], names: ['upwork'] },
]

const FALLBACK_ICON = 'lucide:globe'

function getHostname(url) {
  try {
    return new URL(url).hostname.toLowerCase().replace(/^www\./, '')
  } catch {
    return null
  }
}

function findPlatform(match, key) {
  if (!match) return null

  const platform = PLATFORM_ICONS.find(({ [key]: values }) =>
    values.some((value) => match === value || (key === 'hosts' && match.endsWith(`.${value}`))),
  )

  return platform?.icon || null
}

export function getSocialIcon(link) {
  const url = (link?.url || '').trim().toLowerCase()
  if (url.startsWith('mailto:')) return 'simple-icons:gmail'
  if (url.startsWith('tel:')) return 'lucide:phone'
  if (link?.icon) return link.icon

  return suggestSocialIcon(link) || FALLBACK_ICON
}

export function suggestSocialIcon(link) {
  const url = (link?.url || '').trim().toLowerCase()
  if (url.startsWith('mailto:')) return 'simple-icons:gmail'
  if (url.startsWith('tel:')) return 'lucide:phone'

  const hostname = getHostname(url)
  const hostnameIcon = findPlatform(hostname, 'hosts')
  if (hostnameIcon) return hostnameIcon

  const name = (link?.name || '').trim().toLowerCase()
  return findPlatform(name, 'names')
}

export function isEmailLink(link) {
  return (link?.url || '').trim().toLowerCase().startsWith('mailto:')
}
