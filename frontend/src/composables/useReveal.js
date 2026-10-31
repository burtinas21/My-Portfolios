/**
 * composables/useReveal.js — Scroll-Reveal Animation
 * ───────────────────────────────────────────────────
 * Uses IntersectionObserver to detect when elements enter the viewport
 * and adds a 'revealed' class to trigger CSS animations.
 *
 * How to use:
 * 1. In your component's <script setup>, call useReveal()
 * 2. Add ref="revealRef" to the container you want to observe
 * 3. Add class="reveal" to elements inside that should animate
 *
 * The animation fires ONCE per element (it doesn't hide again on scroll up).
 * Respects prefers-reduced-motion (the CSS handles disabling animations).
 *
 * @example
 *   <script setup>
 *     import { useReveal } from '@/composables/useReveal.js'
 *     const { revealRef } = useReveal()
 *   </script>
 *
 *   <template>
 *     <section ref="revealRef">
 *       <div class="reveal">I fade in when scrolled into view</div>
 *     </section>
 *   </template>
 *
 * Used by: every section component on the public site
 */

import { ref, onMounted, onUnmounted } from 'vue'

/**
 * @param {object} [options]
 * @param {number} [options.threshold=0.1] — How much of the element must be visible (0-1)
 * @param {string} [options.rootMargin='0px 0px -50px 0px'] — Trigger slightly before full visibility
 * @returns {{ revealRef: import('vue').Ref<HTMLElement|null> }}
 */
export function useReveal(options = {}) {
  const {
    threshold = 0.1,
    rootMargin = '0px 0px -50px 0px',
  } = options

  /** The DOM element to observe (set via ref="revealRef" in the template) */
  const revealRef = ref(null)

  /** The IntersectionObserver instance (stored so we can clean it up) */
  let observer = null

  onMounted(() => {
    if (!revealRef.value) return

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            // Add the class that triggers the CSS transition
            entry.target.classList.add('revealed')
            // Stop observing — the animation should only play once
            observer.unobserve(entry.target)
          }
        })
      },
      { threshold, rootMargin },
    )

    // Find all child elements with the "reveal" class and observe them
    const elements = revealRef.value.querySelectorAll('.reveal')
    elements.forEach((el) => observer.observe(el))
  })

  /* Clean up when the component is destroyed to prevent memory leaks */
  onUnmounted(() => {
    if (observer) {
      observer.disconnect()
    }
  })

  return { revealRef }
}
