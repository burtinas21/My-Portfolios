/**
 * composables/useSeo.js — SEO Meta Tags Management
 * ─────────────────────────────────────────────────
 * Sets <title>, meta description, Open Graph tags, and JSON-LD
 * structured data on the current page.
 *
 * How it works:
 * - Call useSeo({ title, description, ... }) in any page/view
 * - It creates or updates <meta> tags in the document <head>
 * - Tags are cleaned up when the component is unmounted
 *
 * Used by: HomeView (for the main page SEO), admin views (for titles)
 */

import { onMounted, onUnmounted, watch } from 'vue'

/**
 * @param {object} options — Reactive or static SEO config
 * @param {import('vue').Ref<string>|string} [options.title] — Page title
 * @param {import('vue').Ref<string>|string} [options.description] — Meta description
 * @param {import('vue').Ref<string>|string} [options.ogImage] — Open Graph image URL
 * @param {import('vue').Ref<object>|object} [options.jsonLd] — JSON-LD structured data object
 */
export function useSeo(options = {}) {
  /** Keep track of meta tags we create so we can remove them on unmount */
  const createdElements = []

  /**
   * Set or update a <meta> tag in the document head.
   * If the tag already exists, update its content. Otherwise create a new one.
   */
  function setMeta(attribute, value, content) {
    let element = document.querySelector(`meta[${attribute}="${value}"]`)
    if (!element) {
      element = document.createElement('meta')
      element.setAttribute(attribute, value)
      document.head.appendChild(element)
      createdElements.push(element)
    }
    element.setAttribute('content', content)
  }

  /**
   * Apply all SEO tags based on the current options.
   */
  function apply() {
    const appTitle = import.meta.env.VITE_APP_TITLE || 'Portfolio'

    // Unwrap refs if needed (support both reactive and static values)
    const title = typeof options.title === 'object' ? options.title?.value : options.title
    const description =
      typeof options.description === 'object' ? options.description?.value : options.description
    const ogImage =
      typeof options.ogImage === 'object' ? options.ogImage?.value : options.ogImage
    const jsonLd = typeof options.jsonLd === 'object' && options.jsonLd?.value !== undefined
      ? options.jsonLd.value
      : options.jsonLd

    // Page title
    if (title) {
      document.title = `${title} | ${appTitle}`
    }

    // Meta description
    if (description) {
      setMeta('name', 'description', description)
    }

    // Open Graph tags (for social media sharing)
    if (title) {
      setMeta('property', 'og:title', title)
    }
    if (description) {
      setMeta('property', 'og:description', description)
    }
    if (ogImage) {
      setMeta('property', 'og:image', ogImage)
    }
    setMeta('property', 'og:type', 'website')

    // JSON-LD structured data (for search engines)
    if (jsonLd) {
      let script = document.querySelector('#json-ld-seo')
      if (!script) {
        script = document.createElement('script')
        script.id = 'json-ld-seo'
        script.type = 'application/ld+json'
        document.head.appendChild(script)
        createdElements.push(script)
      }
      script.textContent = JSON.stringify(jsonLd)
    }
  }

  onMounted(() => {
    apply()
  })

  /* Clean up meta tags when the component/page is destroyed */
  onUnmounted(() => {
    createdElements.forEach((el) => {
      if (el.parentNode) {
        el.parentNode.removeChild(el)
      }
    })
  })

  return { apply }
}
