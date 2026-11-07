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

const observerPool = new Map()

function getObserver(threshold, rootMargin) {
  const key = `${threshold}:${rootMargin}`
  let state = observerPool.get(key)

  if (!state && 'IntersectionObserver' in window) {
    state = {
      targets: new Set(),
      observer: new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return

          entry.target.classList.add('revealed')
          state.observer.unobserve(entry.target)
          state.targets.delete(entry.target)
        })
      }, { threshold, rootMargin }),
    }
    observerPool.set(key, state)
  }

  return state
}

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

  let state = null
  let elements = []

  onMounted(() => {
    if (!revealRef.value) return

    elements = [...revealRef.value.querySelectorAll('.reveal')]
    state = getObserver(threshold, rootMargin)

    if (!state) {
      elements.forEach((element) => element.classList.add('revealed'))
      return
    }

    elements.forEach((element) => {
      if (state.targets.has(element)) return

      state.targets.add(element)
      state.observer.observe(element)
    })
  })

  onUnmounted(() => {
    if (!state) return

    elements.forEach((element) => {
      if (!state.targets.delete(element)) return
      state.observer.unobserve(element)
    })
  })

  return { revealRef }
}
