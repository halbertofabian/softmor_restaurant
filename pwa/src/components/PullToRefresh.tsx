import { useQueryClient } from '@tanstack/react-query'
import { useEffect, useRef, useState, type ReactNode } from 'react'
import { RefreshIcon } from './ui/icons'

const THRESHOLD = 70
const MAX_PULL = 120
const RESISTANCE = 0.5
const REFRESH_HEIGHT = 52
const SETTLE_MS = 220

export function PullToRefresh({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()
  const [pull, setPull] = useState(0)
  const [dragging, setDragging] = useState(false)
  const [refreshing, setRefreshing] = useState(false)

  const pullRef = useRef(0)
  const draggingRef = useRef(false)
  const refreshingRef = useRef(false)
  const trackingRef = useRef(false)
  const startYRef = useRef(0)
  const startXRef = useRef(0)

  useEffect(() => {
    function updatePull(value: number) {
      pullRef.current = value
      setPull(value)
    }

    async function triggerRefresh() {
      if (refreshingRef.current) {
        return
      }

      refreshingRef.current = true
      draggingRef.current = false
      setDragging(false)
      setRefreshing(true)
      updatePull(REFRESH_HEIGHT)

      try {
        await queryClient.refetchQueries({ type: 'active' })
      } finally {
        window.setTimeout(() => {
          refreshingRef.current = false
          setRefreshing(false)
          updatePull(0)
        }, SETTLE_MS)
      }
    }

    function onTouchStart(event: TouchEvent) {
      if (refreshingRef.current || window.scrollY > 0 || event.touches.length !== 1) {
        return
      }

      const target = event.target as Element | null
      if (target?.closest('[data-pull-to-refresh-ignore]')) {
        return
      }

      trackingRef.current = true
      draggingRef.current = false
      startYRef.current = event.touches[0].clientY
      startXRef.current = event.touches[0].clientX
    }

    function onTouchMove(event: TouchEvent) {
      if (!trackingRef.current || refreshingRef.current) {
        return
      }

      const touch = event.touches[0]
      if (!touch) {
        return
      }

      const deltaY = touch.clientY - startYRef.current
      const deltaX = Math.abs(touch.clientX - startXRef.current)

      if (!draggingRef.current) {
        if (deltaY <= 4 || deltaX > deltaY) {
          return
        }

        draggingRef.current = true
        setDragging(true)
      }

      if (window.scrollY > 0) {
        trackingRef.current = false
        draggingRef.current = false
        setDragging(false)
        updatePull(0)
        return
      }

      event.preventDefault()
      updatePull(Math.min(MAX_PULL, deltaY * RESISTANCE))
    }

    function onTouchEnd() {
      if (!trackingRef.current) {
        return
      }

      trackingRef.current = false
      draggingRef.current = false
      setDragging(false)

      if (pullRef.current >= THRESHOLD) {
        void triggerRefresh()
      } else {
        updatePull(0)
      }
    }

    window.addEventListener('touchstart', onTouchStart, { passive: true })
    window.addEventListener('touchmove', onTouchMove, { passive: false })
    window.addEventListener('touchend', onTouchEnd, { passive: true })
    window.addEventListener('touchcancel', onTouchEnd, { passive: true })

    return () => {
      window.removeEventListener('touchstart', onTouchStart)
      window.removeEventListener('touchmove', onTouchMove)
      window.removeEventListener('touchend', onTouchEnd)
      window.removeEventListener('touchcancel', onTouchEnd)
    }
  }, [queryClient])

  const progress = Math.min(1, pull / THRESHOLD)
  const transition = dragging ? 'none' : `transform ${SETTLE_MS}ms ease, opacity ${SETTLE_MS}ms ease`

  return (
    <div className="relative">
      <div
        className="pointer-events-none absolute inset-x-0 top-0 z-20 flex justify-center"
        style={{ transform: `translateY(${pull}px)`, transition, opacity: pull > 0 ? 1 : 0 }}
        aria-hidden="true"
      >
        <span className="-mt-2 flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-card text-primary shadow-lg shadow-black/30">
          <RefreshIcon
            className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`}
            style={refreshing ? undefined : { transform: `rotate(${progress * 180}deg)` }}
          />
        </span>
      </div>

      {children}
    </div>
  )
}
