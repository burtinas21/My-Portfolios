/**
 * utils/skillIcons.js — Skill → Iconify icon name suggestions
 * ─────────────────────────────────────────────────────────────
 * Exports a map of common skill names to Iconify icon identifiers
 * and a suggestIcon() helper used by the admin skill form.
 *
 * Icon names can be browsed at: https://icon-sets.iconify.design/logos/
 *
 * Used by: views/admin/SkillsView.vue
 */

/** @type {Record<string, string>} */
export const SKILL_ICON_MAP = {
  // Languages
  'php': 'logos:php',
  'javascript': 'logos:javascript',
  'typescript': 'logos:typescript-icon',
  'python': 'logos:python',
  'go': 'logos:go',
  'rust': 'logos:rust',
  'java': 'logos:java',
  'c#': 'logos:c-sharp',
  'c++': 'logos:c-plusplus',
  'ruby': 'logos:ruby',

  // Frontend frameworks / libs
  'vue.js': 'logos:vue',
  'vue': 'logos:vue',
  'react': 'logos:react',
  'react.js': 'logos:react',
  'angular': 'logos:angular-icon',
  'svelte': 'logos:svelte-icon',
  'nuxt': 'logos:nuxt-icon',
  'nuxt.js': 'logos:nuxt-icon',
  'next.js': 'logos:nextjs-icon',
  'nextjs': 'logos:nextjs-icon',
  'astro': 'logos:astro-icon',

  // CSS / styling
  'tailwind css': 'logos:tailwindcss-icon',
  'tailwindcss': 'logos:tailwindcss-icon',
  'tailwind': 'logos:tailwindcss-icon',
  'sass': 'logos:sass',
  'scss': 'logos:sass',
  'bootstrap': 'logos:bootstrap',

  // Backend frameworks
  'laravel': 'logos:laravel',
  'symfony': 'logos:symfony',
  'django': 'logos:django-icon',
  'fastapi': 'logos:fastapi-icon',
  'express': 'logos:express',
  'express.js': 'logos:express',
  'nestjs': 'logos:nestjs',
  'rails': 'logos:rails',
  'ruby on rails': 'logos:rails',

  // Databases
  'postgresql': 'logos:postgresql',
  'postgres': 'logos:postgresql',
  'mysql': 'logos:mysql',
  'mariadb': 'logos:mariadb',
  'mongodb': 'logos:mongodb-icon',
  'redis': 'logos:redis',
  'sqlite': 'logos:sqlite',
  'supabase': 'logos:supabase-icon',
  'firebase': 'logos:firebase',

  // Runtime / platform
  'node.js': 'logos:nodejs-icon',
  'nodejs': 'logos:nodejs-icon',
  'node': 'logos:nodejs-icon',
  'deno': 'logos:deno',
  'bun': 'logos:bun',

  // DevOps / infra
  'docker': 'logos:docker-icon',
  'kubernetes': 'logos:kubernetes',
  'nginx': 'logos:nginx',
  'apache': 'logos:apache',
  'aws': 'logos:aws',
  'gcp': 'logos:google-cloud',
  'google cloud': 'logos:google-cloud',
  'azure': 'logos:microsoft-azure',
  'vercel': 'logos:vercel-icon',
  'netlify': 'logos:netlify',
  'linux': 'logos:linux-tux',

  // Tooling / VCS
  'git': 'logos:git-icon',
  'github': 'logos:github-icon',
  'gitlab': 'logos:gitlab',
  'vite': 'logos:vitejs',
  'webpack': 'logos:webpack',
  'eslint': 'logos:eslint',
  'jest': 'logos:jest',
  'vitest': 'logos:vitest',
  'graphql': 'logos:graphql',
  'prisma': 'logos:prisma',
}

/**
 * Suggest an Iconify icon name for a given skill name.
 * Matches case-insensitively. Returns null if no match found.
 *
 * @param {string} name — e.g. "Laravel", "Vue.js"
 * @returns {string|null} — e.g. "logos:laravel" or null
 */
export function suggestIcon(name) {
  if (!name) return null
  const key = name.trim().toLowerCase()
  return SKILL_ICON_MAP[key] ?? null
}
