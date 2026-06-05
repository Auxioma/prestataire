import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['viewport', 'track', 'item']
    static values = {
        step: { type: Number, default: 1 },
        interval: { type: Number, default: 2000 } // Défilement toutes les 2s
    }

    connect() {
        console.log('categories-carousel connected (auto-only)')
        this.currentIndex = 0
        this.visibleItems = 1
        this.autoplayTimer = null

        this.updateMetrics = this.updateMetrics.bind(this)
        this.onResize = this.onResize.bind(this)

        this.updateMetrics()
        window.addEventListener('resize', this.onResize)

        this.startAutoplay()

        // Gestion du survol pour le confort de lecture/clic
        this.element.addEventListener('mouseenter', () => this.stopAutoplay())
        this.element.addEventListener('mouseleave', () => this.startAutoplay())
    }

    disconnect() {
        window.removeEventListener('resize', this.onResize)
        this.stopAutoplay()
    }

    onResize() {
        this.updateMetrics()
        this.goTo(this.currentIndex, false)
    }

    next() {
        const maxIndex = this.maxIndex()
        if (this.currentIndex >= maxIndex) {
            this.goTo(0) // Retour fluide au début
        } else {
            this.goTo(this.currentIndex + this.stepValue)
        }
    }

    startAutoplay() {
        this.stopAutoplay()
        this.autoplayTimer = setInterval(() => {
            this.next()
        }, this.intervalValue)
    }

    stopAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer)
        }
    }

    goTo(index, animated = true) {
        const maxIndex = this.maxIndex()
        this.currentIndex = Math.max(0, Math.min(index, maxIndex))

        const item = this.itemTargets[0]
        if (!item) return

        const trackStyle = window.getComputedStyle(this.trackTarget)
        const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || 0)
        const offset = this.currentIndex * (item.offsetWidth + gap)

        this.trackTarget.style.transition = animated ? 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)' : 'none'
        this.trackTarget.style.transform = `translateX(-${offset}px)`

        if (!animated) {
            requestAnimationFrame(() => {
                this.trackTarget.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)'
            })
        }
    }

    updateMetrics() {
        const item = this.itemTargets[0]
        const viewport = this.viewportTarget

        if (!item || !viewport) return

        const trackStyle = window.getComputedStyle(this.trackTarget)
        const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || 0)
        const viewportWidth = viewport.offsetWidth
        const itemWidth = item.offsetWidth

        this.visibleItems = Math.max(1, Math.round((viewportWidth + gap) / (itemWidth + gap)))
    }

    maxIndex() {
        return Math.max(0, this.itemTargets.length - this.visibleItems)
    }
}