/**
 * utils/navigationSections.js — Shared Public Section Navigation
 * ───────────────────────────────────────────────────────────────
 * Keeps navbar, mobile menu, footer, and scroll-spy section order aligned.
 */

export const PUBLIC_NAVIGATION_SECTIONS = [
  { id: 'hero', label: 'About' },
  { id: 'work', label: 'Work' },
  { id: 'skills', label: 'Skills' },
  { id: 'experience', label: 'Experience' },
  { id: 'certifications', label: 'Achievements', requiresCertifications: true },
  { id: 'contact', label: 'Contact' },
]

export function getVisibleNavigationSections(certifications = []) {
  return PUBLIC_NAVIGATION_SECTIONS.filter(
    (section) => !section.requiresCertifications || certifications.length > 0,
  )
}