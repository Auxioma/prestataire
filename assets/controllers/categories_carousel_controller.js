import { Controller } from '@hotwired/stimulus'


export default class extends Controller {
    static targets = ['viewport', 'track', 'item', 'prevButton', 'nextButton']
    static values = {
        step: { type: Number, default: 1 }
    }

    connect() {
        console.log('categories-carousel connected')
        this.currentIndex = 0
        this.visibleItems = 1

        this.updateMetrics = this.updateMetrics.bind(this)
        this.onResize = this.onResize.bind(this)

        this.updateMetrics()
        window.addEventListener('resize', this.onResize)
    }

    disconnect() {
        window.removeEventListener('resize', this.onResize)
    }

    onResize() {
        this.updateMetrics()
        this.goTo(this.currentIndex, false)
    }

    prev() {
        this.goTo(this.currentIndex - this.stepValue)
    }

    next() {
        this.goTo(this.currentIndex + this.stepValue)
    }

    goTo(index, animated = true) {
        const maxIndex = this.maxIndex()

        this.currentIndex = Math.max(0, Math.min(index, maxIndex))

        const item = this.itemTargets[0]
        if (!item) {
            return
        }

        const trackStyle = window.getComputedStyle(this.trackTarget)
        const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || 0)
        const offset = this.currentIndex * (item.offsetWidth + gap)

        this.trackTarget.style.transition = animated ? 'transform 0.35s ease' : 'none'
        this.trackTarget.style.transform = `translateX(-${offset}px)`

        if (!animated) {
            requestAnimationFrame(() => {
                this.trackTarget.style.transition = 'transform 0.35s ease'
            })
        }

        this.updateButtons()
    }

    updateMetrics() {
        const item = this.itemTargets[0]
        const viewport = this.viewportTarget

        if (!item || !viewport) {
            return
        }

        const trackStyle = window.getComputedStyle(this.trackTarget)
        const gap = parseFloat(trackStyle.columnGap || trackStyle.gap || 0)
        const viewportWidth = viewport.offsetWidth
        const itemWidth = item.offsetWidth

        this.visibleItems = Math.max(1, Math.round((viewportWidth + gap) / (itemWidth + gap)))
        this.updateButtons()
    }

    updateButtons() {
        const maxIndex = this.maxIndex()

        if (this.hasPrevButtonTarget) {
            this.prevButtonTarget.disabled = this.currentIndex <= 0
        }

        if (this.hasNextButtonTarget) {
            this.nextButtonTarget.disabled = this.currentIndex >= maxIndex
        }
    }

    maxIndex() {
        return Math.max(0, this.itemTargets.length - this.visibleItems)
    }
}